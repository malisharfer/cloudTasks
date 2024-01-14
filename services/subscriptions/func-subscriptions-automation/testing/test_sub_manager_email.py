from unittest.mock import patch, Mock
from project.sub_manager_email import *
import project.get_connection_string

class table_client:
    def __init__(self):
        self.table_client = []

    def list_entities(self):
        return self.table_client


class table_service_client:
    def __init__(self):
        self.table_client = table_client()

    def get_table_client(self, table_name = "table_name"):
        return self.table_client


@patch("project.get_connection_string.get_connection_string_from_keyvault",return_value = "connection_string")
@patch("project.sub_manager_email.TableServiceClient.from_connection_string", Mock(return_value = table_service_client()))
def test_get_subscriptions_managers(get_connection_string_from_keyvault):
    assert get_subscriptions_managers() == []


sub_managers = [
    {'subName': 'name1', 'subManagerMail': 'name1@gmail.com'},
    {'subName': 'name2', 'subManagerMail': 'name2@gmail.com'}
]

@patch("project.sub_manager_email.get_subscriptions_managers", return_value = sub_managers)
def test_get_email_manager_by_sub_name(mock_get_managers):
    assert get_email_manager_by_sub_name('name1') == 'name1@gmail.com'
    assert get_email_manager_by_sub_name('name3') == 'subscription name is not exist'