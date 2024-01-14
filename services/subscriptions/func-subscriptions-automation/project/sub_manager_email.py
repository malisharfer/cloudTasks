import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables
import project.get_connection_string
from azure.data.tables import TableServiceClient

def get_subscriptions_managers():
    secret = config.config_variables.administrator_secret
    connection_string = project.get_connection_string.get_connection_string_from_keyvault(secret)
    try:
        table_service_client = TableServiceClient.from_connection_string(
            conn_str = connection_string
        )
        table_client = table_service_client.get_table_client(
            table_name = config.config_variables.table_subscription_managers
        )
        subscriptions_managers = table_client.list_entities()
    except Exception as ex:
        return str(ex)
    return subscriptions_managers


def get_email_manager_by_sub_name(subName):
    try:
        sub = None
        managers = get_subscriptions_managers()
        for manager in managers:
            if manager['subName'] == subName:
                sub = manager
        if sub != None:
            return sub['subManagerMail']
        return 'subscription name is not exist'
    except Exception as ex:
        return str(ex)
