from unittest.mock import patch, Mock, call
from managed_storages import *
import pytest

@patch("managed_storages.create_storage_management_client")
def test_get_storage_list(create_storage_management_client):
    get_storage_list("a171324323")
    create_storage_management_client.assert_called_once_with(
        "a171324323"
    ), f"The function get_subscription_list doesn't send to create_subscription_client"


def test_get_storage_list_return_exception():
    with pytest.raises(Exception) as exception:
        get_storage_list()
    assert "get_storage_list() missing 1 required positional argument: 'sub_id'" in str(
        exception.value
    )


@patch(
    "managed_storages.DefaultAzureCredential",
    Mock(return_value="default azure credential"),
)
@patch("managed_storages.StorageManagementClient")
def test_create_storage_management_client(StorageManagementClient):
    create_storage_management_client("123456-789")
    StorageManagementClient.assert_called_once_with(
        credential="default azure credential", subscription_id="123456-789"
    )