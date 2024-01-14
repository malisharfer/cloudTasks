from azure.data.tables import TableClient
from azure.core.exceptions import ResourceNotFoundError

from config_variables import connection_string, deleted_accounts_table
import pandas as pd, json


def deleted_storages(table_name,  test_number, all_storages):
    parameters = {'name': str(test_number)}
    last_test_storages = retrieve_data_from_table(False,connection_string,table_name, query_filter="PartitionKey eq @name", parameters=parameters,select=["PartitionKey,storage_name"] )
    delete_storages = check_deleted_storage(all_storages, last_test_storages)
    upload_deleted_storages_table(connection_string, table_name, delete_storages)


def retrieve_data_from_table(flag,con_str, table_name, query_filter, parameters="None", select=["*"]):
    try:
        table = TableClient.from_connection_string(con_str, table_name)
        queried_entities = table.query_entities(
            query_filter=query_filter, select=select, parameters=parameters
        )
        return convert_to_json(queried_entities) if flag else queried_entities

    except ResourceNotFoundError:
        raise ResourceNotFoundError("This table does not exist")


def convert_to_json(queried_entities):
    return list((json.loads(pd.Series.to_json(pd.Series(queried_entities)))).values())


def check_deleted_storage(all_storages,last_test_storages):
    return list(filter(
        lambda entity: entity["storage_name"] not in all_storages,
        last_test_storages
    ))


def upload_deleted_storages_table(conn_str, table_name, delete_storages):
    for item in delete_storages:
        parameters = {'storage_name':item["storage_name"], 'PartitionKey': item["PartitionKey"]}
        deleted_storage_rows =retrieve_data_from_table(False,conn_str, table_name,query_filter="storage_name eq @storage_name and PartitionKey eq @PartitionKey",parameters = parameters,select=["*"] )
        upload_to_table(deleted_accounts_table, return_the_first(deleted_storage_rows))


def upload_to_table(my_table_name, my_entity):
    table_client = TableClient.from_connection_string(
        connection_string, table_name=my_table_name
    )
    table_client.create_entity(entity=my_entity)
    return True


def return_the_first(objet):
    for item in objet:
        return item
