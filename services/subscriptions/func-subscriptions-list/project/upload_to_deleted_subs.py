import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables
import project.get_connection_string
from project.storage_table import upload_to_table
from datetime import datetime
from pytz import timezone
from azure.data.tables import TableServiceClient

def get_subscriptions_to_delete():
    secret = config.config_variables.subscription_secret
    connection_string = project.get_connection_string.get_connection_string_from_keyvault(secret)
    try:
        table_name = config.config_variables.table_subscriptions_to_delete
        table_service_client = TableServiceClient.from_connection_string(
            conn_str = connection_string
        )
        table_client = table_service_client.get_table_client(
            table_name = table_name
        )
        sub_to_delete = table_client.list_entities()
    except Exception as ex:
        return ex
    return list(sub_to_delete)


def upload_deleted_subscriptions(subscriptions):
    subs_to_delete = get_subscriptions_to_delete()
    all_subs = []
    for sub in subscriptions:
        all_subs.append(sub.subscription_id)
    for sub in subs_to_delete:
        if sub["subscription_id"] not in all_subs:
            deleted_sub = build_sub_object(sub)
            try:
                upload_to_table(
                    table_name = config.config_variables.table_subscriptions_to_delete,
                    entity = deleted_sub,
                )
            except Exception as ex:
                return Exception(ex)


def build_sub_object(sub):
    date = datetime.now(tz = timezone("Asia/Jerusalem"))
    try:
        return {
            "PartitionKey": date.strftime("%Y-%m-%d %H:%M:%S"),
            "RowKey": sub["subscription_id"],
            "subscription_id": sub["subscription_id"],
            "subscription_name": sub["subscription_name"],
            "sender_email_date": sub["PartitionKey"],
            "reason": sub["reason"],
        }
    except Exception as ex:
        return "Missing argument:" + str(ex)
