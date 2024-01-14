from unittest.mock import patch,Mock
from project.current_capacity import *
from datetime import date , timedelta


class mock_storage_client:
    def __init__(self):
        self.storage_accounts = mock_storage_accounts()

class mock_storage_accounts:
    def get_properties(self,resource_group_name,storage_account_name):
        return "storage_account"

class mock_mertrics_data:
    def __init__(self,value):
        self.value = value

class mock_object_with_timeseries:
    def __init__(self):
        self.timeseries ="timeseries"

class mock_time_series:
    def __init__(self,last_total):
        self.data = [mock_object_with_total(5),mock_object_with_total(5),mock_object_with_total(123456789),mock_object_with_total(last_total)]

class mock_object_with_total:
    def __init__(self,total):
        self.total = total

class mock_storage_account:
    def __init__(self):
        self.id = "id"

class mock_monitor_client:
    def __init__(self):
        self.metrics = mock_metrics()

class mock_metrics:
    def list(self,storage_account_id,metricnames,timespan,interval,aggregation):
        return (storage_account_id == "id" and metricnames == "UsedCapacity" and timespan=="2023-11-28T00:00:00Z/2023-11-29T00:00:00Z" and interval == "PT1H" and aggregation=="Total")


@patch('project.current_capacity.create_storage_management_client',Mock(return_value = mock_storage_client()))
@patch('project.current_capacity.create_monitor_management_client',Mock(return_value = "monitor_client"))
@patch('project.current_capacity.get_metrics_list',Mock(return_value = mock_mertrics_data("value")))
@patch('project.current_capacity.return_the_first',Mock(side_effect = [mock_object_with_timeseries(),mock_time_series(456789123)]))
@patch('project.current_capacity.find_total_capacity',Mock(return_value = 123456789))
def test_get_used_capacity_does_not_called_find_total_capacity():
    assert round(get_used_capacity("subscription_id", "resource_group_name", "storage_account_name"),6) == 456.789123
    
@patch('project.current_capacity.create_storage_management_client',Mock(return_value = mock_storage_client()))
@patch('project.current_capacity.create_monitor_management_client',Mock(return_value = "monitor_client"))
@patch('project.current_capacity.get_metrics_list',Mock(return_value = mock_mertrics_data("value")))
@patch('project.current_capacity.return_the_first',Mock(side_effect = [mock_object_with_timeseries(),mock_time_series(None)]))
@patch('project.current_capacity.find_total_capacity')
def test_get_used_capacity_called_find_total_capacity(find_total_capacity):
    get_used_capacity("subscription_id", "resource_group_name", "storage_account_name")
    find_total_capacity.assert_called_once


def test_find_total_capacity():
    assert find_total_capacity([mock_object_with_total(None),mock_object_with_total(5),mock_object_with_total(None),mock_object_with_total(3),mock_object_with_total(None)]) == 3

def test_find_total_capacity_0():
    assert find_total_capacity([mock_object_with_total(None),mock_object_with_total(None),mock_object_with_total(None),mock_object_with_total(None),mock_object_with_total(None)]) == 0


@patch('project.current_capacity.datetime.date',Mock(**{"today.return_value": date(2023,11,29)}))
@patch('project.current_capacity.datetime.timedelta',Mock(return_value = timedelta(days = 1)))
def test_get_metrics_list_assert_the_function_of_metrics_list_get_the_correct_attributes():
    assert get_metrics_list(mock_storage_account(),mock_monitor_client())


@patch('project.current_capacity.datetime.date',Mock(**{"today.return_value": date(2023,11,29)}))
@patch('project.current_capacity.datetime.timedelta',Mock(return_value = timedelta(days = 1)))
def test_get_metrics_list_assert_the_function_of_metrics_list_get_the_correct_attributes():
    assert get_metrics_list(mock_storage_account(),mock_monitor_client())


def test_return_the_first():
    assert return_the_first([{"name":"hello"},{"second_name":"world"}])  == {"name":"hello"}
