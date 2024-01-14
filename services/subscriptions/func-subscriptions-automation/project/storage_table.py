from azure.data.tables import TableServiceClient
import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import project.get_connection_string
import config.config_variables

def upload_to_table(table_name, entity):
    secret = config.config_variables.subscription_secret
    connection_string = project.get_connection_string.get_connection_string_from_keyvault(secret)
    try:
        table_service_client = TableServiceClient.from_connection_string(
            conn_str = connection_string
        )
        table_client = table_service_client.get_table_client(table_name = table_name)
        entity = table_client.create_entity(entity = entity)
    except Exception as ex:
        raise Exception(ex)
