from project.connect_to_azure import *
from project.date_functions import *


def check_last_fetch_is_early(storage_client, resource_group_name, storage_account_name,last_fetch_time):
    
    result = {}
    if last_fetch_time:
        result['last_fetch_time'] = str(last_fetch_time)[0:10]
        result['alert'] =False
    else:
        result['last_fetch_time']= False
        result['alert'] =  check_creation_date_is_early(storage_client, resource_group_name, storage_account_name, last_fetch_time)
    return result

def check_creation_date_is_early(storage_client, resource_group_name, storage_account_name, time_object):
    date_creation_storage_account = get_creation_date(storage_client, resource_group_name, storage_account_name)
    return should_alert(time_object, date_creation_storage_account)

def should_alert(time_object,date):
    the_first_date_in_desired_period = get_date(time_object)
    difference_days = calculate_the_dif_between_two_dates(
        convert_datetime_to_date(the_first_date_in_desired_period),convert_datetime_to_date(date)
    )
    return difference_days >= 0


def get_creation_date(client, resource_group_name, storage_account_name):
    try:
        storage_account_properties = client.storage_accounts.get_properties(
            resource_group_name, storage_account_name
        )
        return convert_to_date_type_mil_seconds(
            str(storage_account_properties.creation_time)
        )
    except Exception as e:
        raise e