from azure.data.tables import TableClient
from project.connect_to_azure import *
from project.date_functions import *
from project.config_variables import (connection_string,
                                    documentation_table,
                                    freq_automation_test_type,
                                    freq_automation_test_number,
                                    time_index_for_check_used_capacity,
                                    time_period_for_check_used_capacity)
import datetime

def get_storage_used_capacity_information(storage_name):
    table_client = TableClient.from_connection_string(
        connection_string, documentation_table
    )
    x_time = {
        "type_of_time": freq_automation_test_type,
        "number": int(freq_automation_test_number),
    }
    freq_test = {
        "type_of_time": time_index_for_check_used_capacity,
        "number": int(time_period_for_check_used_capacity),
    }

    required_date = get_date(x_time)

    start_date, end_date = calculate_start_and_end_dates(
        required_date, x_time, freq_test
    )

    queried_entities = query_entities_between_two_dates(
        table_client, storage_name, start_date, end_date
    )
    try:
        required_entity = get_the_nearest_date_entity(queried_entities, required_date)
        return required_entity
    except:
        return False


def calculate_start_and_end_dates(required_date, x_time, freq_test):
    x_time_seconds = return_seconds(x_time)
    freq_test_seconds = return_seconds(freq_test)

    if freq_test_seconds <= (x_time_seconds * 2):
        start_date = required_date - datetime.timedelta(seconds=(x_time_seconds))
        end_date = required_date + datetime.timedelta(seconds=x_time_seconds)
    else:
        start_date = required_date - datetime.timedelta(seconds=freq_test_seconds)
        end_date = required_date + datetime.timedelta(seconds=freq_test_seconds)

    return start_date, end_date


def query_entities_between_two_dates(table_client, storage_name, start_date, end_date):
    parameters = {"storage_name": f"{storage_name}"}
    start_date = f"datetime'{start_date}T00:00:00Z'"
    end_date = f"datetime'{end_date}T00:00:00Z'"
    name_filter = f"storage_name eq @storage_name and test_date gt {start_date} and test_date lt {end_date}"
    
    queried_entities = table_client.query_entities(
        query_filter=name_filter,
        select=["*"],
        parameters=parameters,
    )

    return queried_entities


def get_the_nearest_date_entity(queried_entities, required_date):
    required_entity = get_first_entity(queried_entities)
    nearest_date = convert_to_date_type_mil_seconds(str(required_entity["test_date"]))
    diff_days = calculate_the_dif_between_two_dates(required_date, nearest_date)
    required_entity = find_the_nearest_date_entity(
        queried_entities, required_date, required_entity, nearest_date, diff_days
    )
    return required_entity

#
def get_first_entity(queried_entities):
    for entity_chosen in queried_entities:
        return entity_chosen
    return


def find_the_nearest_date_entity(
    queried_entities, required_date, required_entity, nearest_date, diff_days
):
    for entity_chosen in queried_entities:
        nearest_date = convert_to_date_type_mil_seconds(str(entity_chosen["test_date"]))
        if abs(calculate_the_dif_between_two_dates(required_date, nearest_date)) < abs(
            diff_days
        ):
            diff_days = calculate_the_dif_between_two_dates(required_date, nearest_date)
            required_entity = entity_chosen

    return required_entity
