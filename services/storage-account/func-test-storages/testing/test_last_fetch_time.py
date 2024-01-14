import pytest
from unittest.mock import patch, Mock
from datetime import date
from project.last_fetch_time import *

class mock_client:
    def __init__(self):
        self.storage_accounts = mock_storage_accounts()


class mock_storage_accounts:
    def get_properties(self, resource_group_name, storage_account_name):
        return mock_get_properties()


class mock_get_properties:
    def __init__(self):
        self.creation_time = date(2023, 11, 28)




def test_check_last_fetch_is_early_return_last_fetch_time():
    assert check_last_fetch_is_early("storage_client",
                                    "resource_group_name",
                                    "storage_account_name",
                                    "2023-11-12")=={'last_fetch_time':"2023-11-12",'alert':False}


@patch('project.last_fetch_time.check_creation_date_is_early',Mock(return_value = True))
def test_check_last_fetch_is_early_return_false_true():
    assert check_last_fetch_is_early("storage_client",
                                    "resource_group_name",
                                    "storage_account_name",
                                    False)=={'last_fetch_time':False,'alert':True}
    
@patch('project.last_fetch_time.check_creation_date_is_early',Mock(return_value = False))
def test_check_last_fetch_is_early_return_false_false():
    assert check_last_fetch_is_early("storage_client",
                                    "resource_group_name",
                                    "storage_account_name",
                                    False)=={'last_fetch_time':False,'alert':False}


@patch('project.last_fetch_time.get_creation_date',Mock(return_value = date(2023,11,28)))
@patch('project.last_fetch_time.should_alert',Mock(return_value = "should_alert"))
def test_check_creation_date_is_early_return_result_of_should_alert():
    assert check_creation_date_is_early("storage_client", "resource_group_name", "storage_account_name", "time_object") == "should_alert"

@patch('project.last_fetch_time.should_alert',Mock(return_value = True))
@patch('project.last_fetch_time.get_creation_date')
def test_check_creation_date_is_early_called_get_creation_date(get_creation_date):
    check_creation_date_is_early("storage_client", "resource_group_name", "storage_account_name", "time_object")
    get_creation_date.assert_called_once_with("storage_client", "resource_group_name", "storage_account_name")

@patch('project.last_fetch_time.get_creation_date',Mock(return_value = date(2023,11,28)))
@patch('project.last_fetch_time.should_alert')
def test_check_creation_date_is_early_called_should_alert(should_alert):
    check_creation_date_is_early("storage_client", "resource_group_name", "storage_account_name", "time_object")
    should_alert.assert_called_once_with("time_object",date(2023,11,28))


@patch('project.last_fetch_time.get_date',Mock(return_value = date(2023,10,29)))
@patch('project.last_fetch_time.convert_datetime_to_date',Mock(side_effect = [date(2023,10,29),date(2023,11,28)]))
@patch('project.last_fetch_time.calculate_the_dif_between_two_dates',Mock(return_value = 30))
def test_should_alert_true():
    assert should_alert({"type_of_time": "days", "number": 30},date(2023,11,28))

@patch('project.last_fetch_time.get_date',Mock(return_value = date(2023,10,29)))
@patch('project.last_fetch_time.convert_datetime_to_date',Mock(side_effect = [date(2023,10,15),date(2023,10,20)]))
@patch('project.last_fetch_time.calculate_the_dif_between_two_dates',Mock(return_value = -5))
def test_should_alert_false():
    assert not should_alert({"type_of_time": "days", "number": 30},date(2023,11,28))


@patch('project.last_fetch_time.convert_to_date_type_mil_seconds')
def test_get_creation_date(convert_to_date_type_mil_seconds):
    get_creation_date(mock_client(), "resource_group_name", "storage_account_name")
    convert_to_date_type_mil_seconds.assert_called_once_with("2023-11-28")

@patch('project.last_fetch_time.convert_to_date_type_mil_seconds',Mock(return_value=False))
def test_get_creation_date_raise_exception():
    with pytest.raises(Exception):
        get_creation_date("mock_client", "resource_group_name", "storage_account_name")