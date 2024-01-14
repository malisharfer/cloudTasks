from project.subscription_activity import *
from dateutil.relativedelta import relativedelta
from datetime import datetime
import config.config_variables
import project.subscription_activity
from unittest import mock
from unittest.mock import Mock, patch


def test_get_start_date_by_months():
    mock_config = mock.Mock()
    mock_config.num_of_months = "5"
    with mock.patch('config.config_variables', mock_config):
        assert get_start_date() == (
            datetime.now().date() - relativedelta(months = 5),
            datetime.now().date(),
        )


def test_get_start_date_by_months_with_other_value():
    mock_config = mock.Mock()
    mock_config.num_of_months = "12"
    with mock.patch('config.config_variables', mock_config):
        assert get_start_date() == (
            datetime.now().date() - relativedelta(months = 12),
            datetime.now().date(),
        )

        
def test_get_start_date_in_case_of_error():
    assert get_start_date() == "An error occurred in the calculation"


class mock_monitorManagementClient:
    def __init__(self):
        self.activity_logs = mock_activity_logs()

class mock_activity_logs:
    def __init__(self):
        self.activity_log = [{"aaa":"bbb","ccc":"ddd"},{"aaa":"bbb","ccc":"ddd"}]

    def list(self,filter):
        return self.activity_log


@patch('project.subscription_activity.MonitorManagementClient', Mock(return_value = mock_monitorManagementClient()))
@patch('project.subscription_activity.next', Mock(return_value = True))
def test_mock_check_subscription_activity():
    mock_subscription_id = "a173eef2-33d7-4d55-b0b5-18b271f8d42b"
    mock_config = mock.Mock()
    mock_config.num_of_months = "1"
    with mock.patch('config.config_variables', mock_config):
        result = check_subscription_activity(mock_subscription_id)
        assert result == True


@patch('project.subscription_activity.MonitorManagementClient', Mock(return_value = mock_monitorManagementClient()))
@patch('project.subscription_activity.next', Mock(return_value = False))
def test_mock_check_subscription_activity_with_False():
    mock_subscription_id = "a173eef2-33d7-4d55-b0b5-18b271f8d42b"
    mock_config = mock.Mock()
    mock_config.num_of_months = "10"
    with mock.patch('config.config_variables', mock_config):
        result = check_subscription_activity(mock_subscription_id)
        assert result == False


@patch('project.subscription_activity.MonitorManagementClient', Mock(return_value = mock_monitorManagementClient()))
def test_mock_check_subscription_activity_with_Error():
    mock_subscription_id = "a173eef2-33d7-4d55-b0b5-18b271f8d42b"
    mock_config = mock.Mock()
    mock_config.num_of_months = "10"
    with mock.patch('config.config_variables', mock_config):
        result = check_subscription_activity(mock_subscription_id)
        assert result == "The start time cannot be more than 90 days in the past."


def test_when_the_total_cost_is_higher_than_the_price(mocker):
    subscription_id = "41565288-8108-4a1e-bc76-ef9237d9046e"
    mocker.patch.object(project.subscription_activity, "sum", return_value = 300)
    mock_config = mocker.Mock()
    mock_config.cost = 150
    with mock.patch("config.config_variables", mock_config):
        result = is_lower_than_the_set_price(subscription_id)
        assert result == False


def test_when_the_total_cost_is_lower_than_the_price(mocker):
    subscription_id = "41565288-8108-4a1e-bc76-ef9237d9046e"
    mocker.patch.object(project.subscription_activity, "sum", return_value = 100)
    mock_config = mocker.Mock()
    mock_config.cost = 150
    with mock.patch("config.config_variables", mock_config):
        result = is_lower_than_the_set_price(subscription_id)
        assert result == True
