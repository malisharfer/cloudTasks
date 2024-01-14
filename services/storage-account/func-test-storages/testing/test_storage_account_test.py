from unittest.mock import patch, Mock, call
from enum import Enum
from datetime import datetime
import pytest
from project.storage_account_test import *

class alert_reasons_mock(Enum):
    USED_CAPACITY = "The amount of storage in use has increased in the last 30 days"
    LAST_FETCH_TIME = "The Storge has not been used for 30 days"


@patch("project.storage_account_test.documentation_table","documentation")
@patch("project.storage_account_test.create_storage_management_client",Mock(return_value="create storage management client"))
@patch("project.storage_account_test.find_resource_group_name", Mock(return_value="resource_group"))
@patch("project.storage_account_test.used_capacity_comparison_test",
    Mock(
        return_value={
            "alert": True,
            "resource_group": "aaa",
            "current_used_storage_capacity": 30.5,
        }
    ),
)
@patch("project.storage_account_test.check_last_fetch_is_early",Mock(return_value={"alert": False, "last_fetch_time": "12-10-2023"}))
@patch("project.storage_account_test.creating_an_object_for_sending_to_a_documentation_table",Mock(return_value="entity"))
@patch("project.storage_account_test.upload_to_table", Mock(return_value=""))
@patch("project.storage_account_test.check_alert")
def test_storage_account_test_assert_called_check_alert(check_alert):
    storage_account_test(
        "storage_name",
        "5",
        0,
        "subscription_id",
        "subscription_name",
        "storage_account_id",
        "last_fetch_time"
    )
    check_alert.assert_called_once_with(True, False,"storage_name",'5',0,"subscription_name")


@patch("project.storage_account_test.documentation_table","documentation")
@patch("project.storage_account_test.used_capacity_comparison_test",
    Mock(
        return_value={
            "alert": False,
            "resource_group": "aaa",
            "current_used_storage_capacity": 30.5,
        }
    ),
)
@patch("project.storage_account_test.create_monitor_management_client",Mock(return_value="create_monitor_management_client"))
@patch("project.storage_account_test.check_last_fetch_is_early",Mock(return_value={"alert": True, "last_fetch_time": "12-10-2023"}))
@patch("project.storage_account_test.creating_an_object_for_sending_to_a_documentation_table",Mock(return_value="entity"))
@patch("project.storage_account_test.check_alert", Mock(return_value=""))
@patch("project.storage_account_test.upload_to_table")
def test_storage_account_test_assert_called_upload_to_table(upload_to_table):
    storage_account_test(
        "storage_name",
        "5",
        0,
        "subscription_id",
        "subscription_name",
        "storage_account_id",
        "last_fetch_time"
    )
    upload_to_table.assert_called_once_with("documentation", "entity")



@patch("project.storage_account_test.documentation_table","documentation")
@patch('project.storage_account_test.alert_reasons',alert_reasons_mock)
@patch(
    "project.storage_account_test.find_resource_group_name", Mock(return_value="resource_group")
)
@patch(
    "project.storage_account_test.used_capacity_comparison_test",
    Mock(
        return_value={
            "alert": True,
            "resource_group": "resource_group",
            "current_used_storage_capacity": 30.5,
        }
    ),
)
@patch(
    "project.storage_account_test.create_monitor_management_client",
    Mock(return_value="create_monitor_management_client"),
)
@patch(
    "project.storage_account_test.check_last_fetch_is_early",
    Mock(
        return_value={
            "alert": False,
            "last_fetch_time": "2023-09-11 00:00:00.000+00:00",
        }
    ),
)
@patch("project.storage_account_test.check_alert", Mock(return_value=""))
@patch("project.storage_account_test.upload_to_table", Mock(return_value="entity"))
@patch(
    "project.storage_account_test.datetime",
    Mock(**{"today.return_value": datetime(2023, 11, 16, 11, 55, 51, 569122)}),
)
@patch("project.storage_account_test.creating_an_object_for_sending_to_a_documentation_table")
def test_storage_account_test_assert_called_creating_an_object_for_sending_to_a_documentation_table(
    creating_an_object_for_sending_to_a_documentation_table,
):
    storage_account_test(
        "storage_name",
        "5",
        0,
        "subscription_id",
        "subscription_name",
        "storage_account_id",
        "last_fetch_time"
    )
    creating_an_object_for_sending_to_a_documentation_table.assert_called_once_with(
        "5",
        "0",
        datetime(2023, 11, 16, 11, 55, 51, 569122),
        "subscription_id",
        "resource_group",
        "storage_name",
        30.5,
        "2023-09-11 00:00:00.000+00:00",
        True,
        "The amount of storage in use has increased in the last 30 days",
        False,
        "null"
    )


@patch("project.storage_account_test.documentation_table","documentation")
@patch(
    "project.storage_account_test.used_capacity_comparison_test",
    Mock(
        return_value={
            "alert": True,
            "resource_group": "resource_group",
            "current_used_storage_capacity": 30.5,
        }
    ),
)
@patch("project.storage_account_test.check_alert", Mock(return_value=""))
@patch("project.storage_account_test.upload_to_table", Mock(return_value="entity"))
@patch(
    "project.storage_account_test.creating_an_object_for_sending_to_a_documentation_table",
    Mock(return_value="entity"),
)
@patch(
    "project.storage_account_test.create_monitor_management_client",
    Mock(return_value="create_monitor_management_client"),
)
@patch(
    "project.storage_account_test.create_storage_management_client",
    Mock(return_value="create_storage_management_client"),
)
@patch("project.storage_account_test.check_last_fetch_is_early")
def test_storage_account_test_assert_called_check_last_fetch_is_early(check_last_fetch_is_early):
    storage_account_test(
        "storage_name",
        "5",
        0,
        "subscription_id",
        "subscription_name",
        "storage_account_id",
        "last_fetch_time"
    )
    check_last_fetch_is_early.assert_called_once_with(
        "create_storage_management_client",
        "resource_group",
        "storage_name",
        "last_fetch_time"
    )


@patch("project.storage_account_test.documentation_table","documentation")
@patch(
    "project.storage_account_test.find_resource_group_name", Mock(return_value="resource_group")
)
@patch("project.storage_account_test.check_alert", Mock(return_value=""))
@patch("project.storage_account_test.upload_to_table", Mock(return_value="entity"))
@patch(
    "project.storage_account_test.creating_an_object_for_sending_to_a_documentation_table",
    Mock(return_value="entity"),
)
@patch(
    "project.storage_account_test.check_last_fetch_is_early",
    Mock(
        return_value={
            "alert": False,
            "last_fetch_time": datetime.today(),
        }
    ),
)
@patch(
    "project.storage_account_test.create_monitor_management_client",
    Mock(return_value="create_monitor_management_client"),
)
@patch("project.storage_account_test.used_capacity_comparison_test")
def test_storage_account_test_assert_called_used_capacity_comparison_test(
    used_capacity_comparison_test,
):
    storage_account_test(
        "storage_name",
        "5",
        0,
        "subscription_id",
        "subscription_name",
        "storage_account_id",
        "last_fetch_time"
    )
    used_capacity_comparison_test.assert_called_once_with(
        "resource_group", "storage_name", "subscription_id"
    )

@patch("project.storage_account_test.documentation_table","documentation")
@patch("project.storage_account_test.find_resource_group_name", Mock(return_value="resource_group"))
@patch("project.storage_account_test.used_capacity_comparison_test",
    Mock(
        return_value={
            "alert": True,
            "resource_group": "aaa",
            "current_used_storage_capacity": 30.5,
        }
    ),
)
@patch("project.storage_account_test.create_monitor_management_client",Mock(return_value="create_monitor_management_client"))
@patch("project.storage_account_test.check_last_fetch_is_early",Mock(return_value={"alert": False, "last_fetch_time": "12-10-2023"}))
@patch("project.storage_account_test.creating_an_object_for_sending_to_a_documentation_table",Mock(return_value="entity"))
@patch("project.storage_account_test.upload_to_table", Mock(return_value=""))
@patch("project.storage_account_test.check_alert",Mock(side_effect = Exception("check alert raise exception")))
def test_storage_account_test_check_alert_raise_exception():
    with pytest.raises(Exception) as exception:
        storage_account_test(
        "storage_name",
        "5",
        0,
        "subscription_id",
        "subscription_name",
        "storage_account_id",
        "last_fetch_time"
    )
    assert "check alert raise exception" in str(exception.value)



@patch('project.storage_account_test.alert_reasons',alert_reasons_mock)
@patch('project.storage_account_test.main_alerts')
def test_check_alert_with_alert_in_used_capacity(main_alerts):
    check_alert(True,False,'myfirsttrail',1,1,"subscription_name")
    main_alerts.assert_called_once_with('myfirsttrail',':storage account myfirsttrail\nThe amount of storage in use has increased in the last 30 days',1,1,"subscription_name")


@patch('project.storage_account_test.alert_reasons',alert_reasons_mock)
@patch('project.storage_account_test.main_alerts')
def test_check_alert_with_alert_in_fetch_time(main_alerts):
    check_alert(False,True,'myfirsttrail',1,1,"subscription_name")
    main_alerts.assert_called_once_with('myfirsttrail',':storage account myfirsttrail\nThe Storge has not been used for 30 days',1,1,"subscription_name")

@patch('project.storage_account_test.alert_reasons',alert_reasons_mock)
@patch('project.storage_account_test.main_alerts')
def test_check_alert_with_alert_in_used_capacity_and_in_fetch_time(main_alerts):
    check_alert(True,True,'myfirsttrail',1,1,"subscription_name")
    main_alerts.assert_called_once_with('myfirsttrail',':storage account myfirsttrail\nThe amount of storage in use has increased in the last 30 days and The Storge has not been used for 30 days',1,1,"subscription_name")



def test_creating_an_object_for_sending_to_a_documentation_table_return_type_dict():
    assert (
        type(
            creating_an_object_for_sending_to_a_documentation_table(
                "1",
                "0",
                datetime.today(),
                "a17df",
                "NetworkWatcherRG",
                "myfirsttrail",
                30.5,
                "2023-11-06",
                True,
                "alert_reason_for_check_used_capacity",
                False,
                "alert_reason_for_check_last_fetch",
            )
        )
        == dict
    )


def test_creating_an_object_for_sending_to_a_documentation_table_return_str_value_for_partitionkey():
    assert (
        type(
            creating_an_object_for_sending_to_a_documentation_table(
                "1",
                "0",
                datetime.today(),
                "a17df",
                "NetworkWatcherRG",
                "myfirsttrail",
                30.5,
                "2023-11-06",
                True,
                "alert_reason_for_check_used_capacity",
                False,
                "alert_reason_for_check_last_fetch",
            )["PartitionKey"]
        )
        == str
    )


def test_creating_an_object_for_sending_to_a_documentation_table_return_str_value_for_rowkey():
    assert (
        type(
            creating_an_object_for_sending_to_a_documentation_table(
                "1",
                "0",
                datetime.today(),
                "a17df",
                "NetworkWatcherRG",
                "myfirsttrail",
                30.5,
                "2023-11-06",
                True,
                "alert_reason_for_check_used_capacity",
                False,
                "alert_reason_for_check_last_fetch",
            )["RowKey"]
        )
        == str
    )


def test_creating_an_object_for_sending_to_a_documentation_table_return_dict_with_len_12():
    assert (
        len(
            creating_an_object_for_sending_to_a_documentation_table(
                "1",
                "0",
                datetime.today(),
                "a17df",
                "NetworkWatcherRG",
                "myfirsttrail",
                30.5,
                "2023-11-06",
                True,
                "alert_reason_for_check_used_capacity",
                False,
                "alert_reason_for_check_last_fetch",
            )
        )
        == 12
    )

def test_creating_an_object_for_sending_to_a_documentation_table_when_last_storage_fetch_time_do_not_exist():
    assert (
        len(
            creating_an_object_for_sending_to_a_documentation_table(
                "1",
                "0",
                datetime.today(),
                "a17df",
                "NetworkWatcherRG",
                "myfirsttrail",
                30.5,
                None,
                True,
                "alert_reason_for_check_used_capacity",
                False,
                "alert_reason_for_check_last_fetch",
            )
        )
        == 11
    )

