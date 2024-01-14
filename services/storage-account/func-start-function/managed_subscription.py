from azure.identity import DefaultAzureCredential
from azure.mgmt.subscription import SubscriptionClient
from azure.data.tables import TableClient

import json, pandas as pd
from config_variables import connection_string


def create_subscription_client():
    subscription_client = SubscriptionClient(credential=DefaultAzureCredential())
    return subscription_client


def get_subscription_list():
    subscription_client = create_subscription_client()
    sub_list = subscription_client.subscriptions.list()
    return sub_list


def get_a_last_partitionKey_number(table_name):
    
    table_client = TableClient.from_connection_string(connection_string, table_name)
    partitionKeys_table = convert_to_json(
        table_client.query_entities(
            query_filter="",
            select=["*"],
        )
    )
    if partitionKeys_table == {}:
        return -1
    table = [
        int(partition["PartitionKey"]) for partition in partitionKeys_table.values()
    ]
    return max(table)


def convert_to_json(entities):
    return json.loads(pd.Series.to_json(pd.Series(entities)))