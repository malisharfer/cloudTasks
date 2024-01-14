from datetime import datetime
from unittest.mock import patch
from project.upload_to_subs_to_delete import *
import pytest

def test_build_sub_object_activity():
    subscription_id = "123"
    subscription_name = "Example Subscription"
    is_activity = True
    is_high_cost = False
    
    result = build_sub_object(subscription_id, subscription_name, is_activity, is_high_cost)
    expected_result = {
        "PartitionKey": result["PartitionKey"],
        "RowKey": result["RowKey"],
        "subscription_id": subscription_id,
        "subscription_name": subscription_name,
        "reason": "",
    }

    assert result == expected_result


def test_build_sub_object_not_activity():
    subscription_id = "123"
    subscription_name = "Example Subscription"
    is_activity = False
    is_high_cost = False
    result = build_sub_object(subscription_id, subscription_name, is_activity, is_high_cost)

    expected_result = {
        "PartitionKey": result["PartitionKey"],
        "RowKey": result["RowKey"],
        "subscription_id": subscription_id,
        "subscription_name": subscription_name,
        "reason": "not activity",
    }

    assert result == expected_result


def test_build_sub_object_with_none_values():
    subscription_id = None
    subscription_name = "Example Subscription"
    is_activity = True
    is_high_cost = False

    with pytest.raises(ValueError, match="The values cannot be None"):
        build_sub_object(subscription_id, subscription_name, is_activity, is_high_cost)


@patch("project.upload_to_subs_to_delete.build_sub_object",return_value = [{"subscription_id": "id"}])
@patch("project.upload_to_subs_to_delete.upload_to_table")
def test_upload_deleted_subscriptions(build_email_object, upload_to_table):
    assert upload_subscriptions_to_delete("id","name" ,False, False) == None
    