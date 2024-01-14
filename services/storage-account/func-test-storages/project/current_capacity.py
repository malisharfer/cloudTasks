import datetime
from project.connect_to_azure import *


def get_used_capacity(subscription_id, resource_group_name, storage_account_name):
    storage_client = create_storage_management_client(subscription_id)
    storage_account = storage_client.storage_accounts.get_properties(
        resource_group_name, storage_account_name
    )
    monitor_client = create_monitor_management_client(subscription_id)

    metrics_data = get_metrics_list(storage_account, monitor_client)

    metric = return_the_first(metrics_data.value)
    time_series = return_the_first(metric.timeseries)
    if(time_series.data[-1].total==None):
        return find_total_capacity(time_series.data)* (10**-6)
    return time_series.data[-1].total * (10**-6)

def find_total_capacity(data_time_series):
    len_data_time_series=len(data_time_series)
    for i in reversed(range(len_data_time_series)):
        if(data_time_series[i].total != None):
            return data_time_series[i].total
    return 0

def get_metrics_list(storage_account, monitor_client):
    end_time = datetime.date.today()
    start_time = end_time - datetime.timedelta(days=1)
    time_span = f"{start_time}T00:00:00Z/{end_time}T00:00:00Z"

    metrics_data = monitor_client.metrics.list(
        storage_account.id,
        metricnames="UsedCapacity",
        timespan=time_span,
        interval="PT1H",
        aggregation="Total",
    )

    return metrics_data


def return_the_first(obj):
    for metric in obj:
        return metric
