import pytest
from unittest.mock import patch, Mock
from project.connect_to_azure import *


class mock_Table_client:
    def from_connection_string(self,con_str,table_name):
        return mock_connection()

class mock_connection:
    def query_entities(self,query_filter, select, parameters):
        return False
    


@patch(
    "project.connect_to_azure.DefaultAzureCredential",
    Mock(return_value="default azure credential"),
)
@patch("project.connect_to_azure.MonitorManagementClient")
def test_create_monitor_management_client(MonitorManagementClient):
    create_monitor_management_client("123456-789")
    MonitorManagementClient.assert_called_once_with(
        credential="default azure credential", subscription_id="123456-789"
    )


@patch(
    "project.connect_to_azure.DefaultAzureCredential",
    Mock(return_value="default azure credential"),
)
@patch("project.connect_to_azure.StorageManagementClient")
def test_create_storage_management_client(StorageManagementClient):
    create_storage_management_client("123456-789")
    StorageManagementClient.assert_called_once_with(
        credential="default azure credential", subscription_id="123456-789"
    )


@patch("project.connect_to_azure.convert_to_json", Mock(return_value=[]))
@patch("project.connect_to_azure.TableClient")
def test_retrieve_data_from_table(TableClient):
    retrieve_data_from_table(
        True,"con_str", "table_name", "query_filter", parameters="None", select=["*"]
    )
    TableClient.from_connection_string.assert_called_once_with("con_str", "table_name")
    TableClient.from_connection_string().query_entities.assert_called_once_with(
        query_filter="query_filter", select=["*"], parameters="None"
    )

@patch("project.connect_to_azure.convert_to_json", Mock(return_value=[]))
@patch("project.connect_to_azure.TableClient",mock_Table_client())
def test_retrieve_data_from_table_without_convert_to_json():
    assert not retrieve_data_from_table(
        False,"con_str", "table_name", "query_filter", parameters="None", select=["*"]
    )

@patch('project.connect_to_azure.convert_to_json',Mock(side_effect = ResourceNotFoundError("ResourceNotFoundError")))
@patch('project.connect_to_azure.TableClient')
def test_retrieve_data_from_table_raise_ResourceNotFoundError(TableClient):
    with pytest.raises(ResourceNotFoundError,match = "This table does not exist") as r:  
        retrieve_data_from_table(
        True,"con_str", "table_name", "query_filter", parameters="None", select=["*"]
        )


def test_convert_to_json():
    assert convert_to_json([{"entity": 1}, {"entity": 2}, {"entity": 3}]) == [
        {"entity": 1},
        {"entity": 2},
        {"entity": 3},
    ]

def test_find_resource_group_name_return_group_name():
    assert find_resource_group_name("subscriptions/a17sdf4ft/resourceGroups/resourceName/")== "resourceName"
    assert find_resource_group_name("accountName/account_name/") == ""

def test_find_resource_group_name_return_empty_name():
    assert find_resource_group_name("accountName/account_name/") == ""


@patch("project.connect_to_azure.connection_string", "123456-789456")
@patch("project.connect_to_azure.TableClient")
def test_upload_to_table_called_create_entity(TableClient):
    upload_to_table("my_table_name", {"entity": 1})
    TableClient.from_connection_string().create_entity.assert_called_once_with(
        entity={"entity": 1}
    )


@patch("project.connect_to_azure.connection_string", "123456-789456")
@patch("project.connect_to_azure.TableClient")
def test_upload_to_table_called_from_connection_string(TableClient):
    upload_to_table("my_table_name", {"entity": 1})
    TableClient.from_connection_string.assert_called_once_with(
        "123456-789456", table_name="my_table_name"
    )