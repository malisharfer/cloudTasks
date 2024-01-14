from unittest.mock import patch, Mock
from project.used_capacity_comparison import used_capacity_comparison_test


@patch(
    "project.used_capacity_comparison.get_storage_used_capacity_information",
    Mock(
        return_value={
            "subscription_id": "a17",
            "resource_group": "resource_group",
            "storage_name": "storage_name",
            "used_storage_capacity": 30.5,
        }
    ),
)
@patch(
    "project.used_capacity_comparison.get_used_capacity", Mock(return_value=20.5)
)
def test_used_capacity_comparison_test_return_object():
    used_capacity_comparison_test_object = used_capacity_comparison_test(
        "resource_group_name", "storage_name", "subscription_id"
    )
    assert used_capacity_comparison_test_object == {
        "storage_name": "storage_name",
        "resource_group": "resource_group_name",
        "current_used_storage_capacity": 20.5,
        "alert": False,
    }

@patch(
    "project.used_capacity_comparison.get_storage_used_capacity_information",
    Mock(
        return_value={}
    ),
)
@patch(
    "project.used_capacity_comparison.get_used_capacity", Mock(return_value=20.5)
)
def test_used_capacity_comparison_test_return_object_with_alert_False():
    used_capacity_comparison_test_object = used_capacity_comparison_test(
        "resource_group_name", "storage_name", "subscription_id"
    )
    assert used_capacity_comparison_test_object == {
        "storage_name": "storage_name",
        "resource_group": "resource_group_name",
        "current_used_storage_capacity": 20.5,
        "alert": False,
    }

