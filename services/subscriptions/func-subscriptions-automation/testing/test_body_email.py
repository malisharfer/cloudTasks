import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
from project.body_email import *
import config.config_variables
from unittest import mock


def test_build_email_body_with_cost_true_and_activity_true():
    mock_config = mock.Mock()
    mock_config.cost = "100"
    with mock.patch("config.config_variables", mock_config):
        result = build_email_body("sub_name", "sub_id", True, True)
        assert result == "\n subscription sub_name :sub_id\n The cost of the subscription is lower than 100"


def test_build_email_body_with_cost_false_and_activity_true():
    mock_config = mock.Mock()
    mock_config.cost = "100"
    with mock.patch("config.config_variables", mock_config):
        result = build_email_body("sub_name", "sub_id", True, False)
        assert result == ""


def test_build_email_body_with_cost_true_and_activity_false():
    mock_config = mock.Mock()
    mock_config.cost = "100"
    with mock.patch("config.config_variables", mock_config):
        result = build_email_body("sub_name", "sub_id", False, True)
        assert result == "\n subscription sub_name :sub_id\n The subscription has not been used for the past two weeks, if you do not log in to the subscription in the coming week, the subscription will be deleted.\n The cost of the subscription is lower than 100"


def test_build_email_body_with_cost_false_and_activity_false():
    mock_config = mock.Mock()
    mock_config.cost = "100"
    with mock.patch("config.config_variables", mock_config):
        result = build_email_body("sub_name", "sub_id", False, False)
        assert result == "\n subscription sub_name :sub_id\n The subscription has not been used for the past two weeks, if you do not log in to the subscription in the coming week, the subscription will be deleted."
