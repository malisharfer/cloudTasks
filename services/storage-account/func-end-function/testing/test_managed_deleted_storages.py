from unittest.mock import patch, Mock
import pytest
from project.managed_deleted_storages import *


class mock_Table_client:
    def from_connection_string(self,con_str,table_name):
        return mock_connection()
    
class mock_connection:
    def query_entities(self,query_filter, select, parameters):
        return False


@patch('project.managed_deleted_storages.deleted_accounts_table',"aa")
@patch('project.managed_deleted_storages.upload_deleted_storages_table',Mock(return_value=''))
@patch('project.managed_deleted_storages.retrieve_data_from_table',Mock(return_value=[{'PartitionKey': '1', 'storage_name': 'myfirsttrail'},{'PartitionKey': '1', 'storage_name': 'mysecondtrail'}]))
@patch('project.managed_deleted_storages.return_the_first',  Mock(return_value={'PartitionKey': '1', 'storage_name': 'myfirsttrail'}))
def test_deleted_storages():
    deleted_storages("table_name",  1, ['myfirsttrail'])

@patch('project.managed_deleted_storages.deleted_accounts_table',"aa")
@patch('project.managed_deleted_storages.retrieve_data_from_table',Mock(return_value=[{'PartitionKey': '1', 'storage_name': 'myfirsttrail'},{'PartitionKey': '1', 'storage_name': 'mysecondtrail'}]))
@patch('project.managed_deleted_storages.upload_to_table')
@patch('project.managed_deleted_storages.return_the_first',  Mock(return_value={'PartitionKey': '1', 'storage_name': 'myfirsttrail'}))
def test_upload_deleted_storages_table(upload_to_table):
    upload_deleted_storages_table('con_str',"table", [{'PartitionKey': '1', 'storage_name': 'myfirsttrail'}])
    upload_to_table.assert_called_once_with("aa",{'PartitionKey': '1', 'storage_name': 'myfirsttrail'})


@patch("project.managed_deleted_storages.convert_to_json", Mock(return_value=[]))
@patch("project.managed_deleted_storages.TableClient")
def test_retrieve_data_from_table(TableClient):
    retrieve_data_from_table(
        True,"con_str", "table_name", "query_filter", parameters="None", select=["*"]
    )
    TableClient.from_connection_string.assert_called_once_with("con_str", "table_name")
    TableClient.from_connection_string().query_entities.assert_called_once_with(
        query_filter="query_filter", select=["*"], parameters="None"
    )

@patch("project.managed_deleted_storages.convert_to_json", Mock(return_value=[]))
@patch("project.managed_deleted_storages.TableClient",mock_Table_client())
def test_retrieve_data_from_table_without_convert_to_json():
    assert not retrieve_data_from_table(
        False,"con_str", "table_name", "query_filter", parameters="None", select=["*"]
    )

@patch('project.managed_deleted_storages.convert_to_json',Mock(side_effect = ResourceNotFoundError("ResourceNotFoundError")))
@patch('project.managed_deleted_storages.TableClient')
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


def test_check_deleted_storage_with_no_deleted():
    assert check_deleted_storage(['myfirsttrail','mysecondtrail'],[{'PartitionKey': '1', 'storage_name': 'myfirsttrail'},{'PartitionKey': '1', 'storage_name': 'mysecondtrail'}]) ==[]

def test_check_deleted_storage_with_deleted():
    assert check_deleted_storage(['mytheardtrail'],[{'PartitionKey': '1', 'storage_name': 'myfirsttrail'},{'PartitionKey': '1', 'storage_name': 'mysecondtrail'}]) == [{'PartitionKey': '1', 'storage_name': 'myfirsttrail'},{'PartitionKey': '1', 'storage_name': 'mysecondtrail'}]

def test_check_deleted_storage_with_empty_array():
    assert check_deleted_storage([],[{'PartitionKey': '1', 'storage_name': 'myfirsttrail'},{'PartitionKey': '1', 'storage_name': 'mysecondtrail'}]) == [{'PartitionKey': '1', 'storage_name': 'myfirsttrail'},{'PartitionKey': '1', 'storage_name': 'mysecondtrail'}]    

def test_check_deleted_storage_with_two_empty_arrays():
    assert check_deleted_storage([],[]) == []


@patch('project.managed_deleted_storages.deleted_accounts_table',"aa")
@patch('project.managed_deleted_storages.retrieve_data_from_table',Mock(return_value=[{'PartitionKey': '1', 'storage_name': 'myfirsttrail'},{'PartitionKey': '1', 'storage_name': 'mysecondtrail'}]))
@patch('project.managed_deleted_storages.upload_to_table')
@patch('project.managed_deleted_storages.return_the_first',  Mock(return_value={'PartitionKey': '1', 'storage_name': 'myfirsttrail'}))
def test_upload_deleted_storages_table(upload_to_table):
    upload_deleted_storages_table('con_str',"table", [{'PartitionKey': '1', 'storage_name': 'myfirsttrail'}])
    upload_to_table.assert_called_once_with("aa",{'PartitionKey': '1', 'storage_name': 'myfirsttrail'})


@patch("project.managed_deleted_storages.connection_string", "123456-789456")
@patch("project.managed_deleted_storages.TableClient")
def test_upload_to_table_called_create_entity(TableClient):
    upload_to_table("my_table_name", {"entity": 1})
    TableClient.from_connection_string().create_entity.assert_called_once_with(
        entity={"entity": 1}
    )

@patch("project.managed_deleted_storages.connection_string", "123456-789456")
@patch("project.managed_deleted_storages.TableClient")
def test_upload_to_table_called_from_connection_string(TableClient):
    upload_to_table("my_table_name", {"entity": 1})
    TableClient.from_connection_string.assert_called_once_with(
        "123456-789456", table_name="my_table_name"
    )


def test_return_the_first():
    assert return_the_first([{"name":"hello"},{"second_name":"world"}])  == {"name":"hello"}