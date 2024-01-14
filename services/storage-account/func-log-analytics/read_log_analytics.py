from azure.identity import DefaultAzureCredential
from azure.monitor.query import LogsQueryClient
from config_variables import workspace_id,time_period_for_check_last_fetch,time_index_for_check_last_fetch as time_index

from datetime import timedelta
from itertools import groupby
from operator import itemgetter


def get_array_of_last_fetch_time():
    
    times=get_times()
    max_time_foreach_storage=get_max_times_log_for_each_storage(times)
    
    return max_time_foreach_storage


def get_times():

    storage_types=['Table','Queue','Blob','File']
    times=[]

    for type in storage_types:
        data_for_type_query = get_workspace_table(type)
        times_array = get_times_logs(data_for_type_query)
        times.extend(times_array)

    return times


def get_workspace_table(type_query):

    type_query=f"""Storage{type_query}Logs"""
    time_object = {"type_of_time": time_index, "number": int(time_period_for_check_last_fetch)}

    LogsQuery_client=create_log_query_client()
    seconds=return_seconds(time_object)
    
    try:
        response = LogsQuery_client.query_workspace(
            workspace_id=workspace_id,
            query=type_query,
            timespan=timedelta(seconds=seconds)
        )
        try:
            return response.tables
    
        except AttributeError as e:
            return response.partial_data
    
    except Exception as e:
        raise Exception('Failed to retrieve the data')


def create_log_query_client():
    log_query_client=LogsQueryClient(credential=DefaultAzureCredential())
    return log_query_client


def return_seconds(obj_time):
    match obj_time["type_of_time"]:
        case "days":
            seconds = obj_time["number"] * 24 * 60 * 60
        case "weeks":
            seconds = obj_time["number"] * 24 * 60 * 60 * 7
        case "months":
            seconds = obj_time["number"] * 24 * 60 * 60 * 30
        case "years":
            seconds = obj_time["number"] * 24 * 60 * 60 * 365
        case _:
            seconds = -1
    return seconds


def get_times_logs(data):
    arr = []
    for table in data:
        arr.extend(get_times_logs_per_table(table))
    return arr


def get_times_logs_per_table(table):
    arr = []
    for i in range(len(table.rows)):
        object_for_array={'storage_name':table.rows[i][2],'time':table.rows[i][1]}
        arr.append(object_for_array)
    return arr


def get_max_times_log_for_each_storage(data):
    sorted_data = sorted(data, key=lambda x: (x['storage_name'], -x['time'].timestamp()))
    answer = [next(group) for key, group in groupby(sorted_data, key=itemgetter('storage_name'))]
    convert_time_to_str=[{'storage_name':item['storage_name'],'time':str(item['time'])} for item in answer]
    return convert_time_to_str
