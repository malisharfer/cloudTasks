from datetime import datetime
from unittest.mock import patch, Mock
from project.upload_to_deleted_subs import *
import project.get_connection_string


def test_build_sub_object():
    date = datetime.now(tz = timezone("Asia/Jerusalem"))
    sub = dict(
        {
            "subscription_id": "id",
            "subscription_name": "name",
            "PartitionKey": date.strftime("%Y-%m-%d %H:%M:%S"),
            "reason": "not activity",
        }
    )
    return_sub = dict(
        {
            "PartitionKey": date.strftime("%Y-%m-%d %H:%M:%S"),
            "RowKey": sub["subscription_id"],
            "subscription_id": sub["subscription_id"],
            "subscription_name": sub["subscription_name"],
            "sender_email_date": sub["PartitionKey"],
            "reason": sub["reason"],
        }
    )
    assert build_sub_object(sub) == return_sub
    assert build_sub_object({}) == "Missing argument:'subscription_id'"


class table_client:
    def __init__(self):
        self.table_client = []

    def list_entities(self):
        return self.table_client


class table_service_client:
    def __init__(self):
        self.table_client = table_client()

    def get_table_client(self, table_name = "table_name"):
        return self.table_client


@patch("project.get_connection_string.get_connection_string_from_keyvault",return_value = "connection_string")
@patch("project.upload_to_deleted_subs.TableServiceClient.from_connection_string", Mock(return_value = table_service_client()))
def test_get_subscriptions_to_delete(get_connection_string_from_keyvault):
    assert get_subscriptions_to_delete() == []


class sub:
    def __init__(self):
        self.subscription_id = "id"


subs_to_delete = [sub()]


@patch("project.upload_to_deleted_subs.get_subscriptions_to_delete",return_value = [{"subscription_id": "id"}])
@patch("project.upload_to_deleted_subs.build_sub_object", return_value = {"test": "object"})
@patch("project.upload_to_deleted_subs.upload_to_table")
def test_upload_deleted_subscriptions(get_subscriptions_to_delete, build_sub_object, upload_to_table):
    assert upload_deleted_subscriptions(subs_to_delete) == None
