from unittest.mock import patch, Mock
from project.send_alert_email import *
from datetime import date

@patch("project.send_alert_email.con_str","")
@patch("project.send_alert_email.add_entity_to_alerts_documentation",Mock(return_value=None))
@patch("project.send_alert_email.requests", Mock(return_value=None))
@patch("project.send_alert_email.retrieve_data_from_table")
def test_main_alerts_called_retrieve_data_from_table(retrieve_data_from_table):
    main_alerts("storage_name", "email_body",1,1,"subscription_name")
    retrieve_data_from_table.assert_called_once_with(
        True,
        '',
        "subscriptionManagersRealy",
        "subName eq @subscription_name",
        {"subscription_name": "subscription_name"},
        ["subName", "subManagerMail"]
    )



@patch("project.send_alert_email.retrieve_data_from_table",Mock(side_effect=Exception()))
@patch('project.send_alert_email.requests',Mock(return_value=True))
@patch("project.send_alert_email.add_entity_to_alerts_documentation")
def test_main_alerts_assert_manager_information_raise_exception(add_entity_to_alerts_documentation):
    main_alerts("storage_name", "email_body",1,1,"subscription_name")
    add_entity_to_alerts_documentation.assert_called_once_with(
        {"subName":'null','subManagerMail':'null'}, "storage_name", "email_body",1,1
    )


@patch("project.send_alert_email.alerts_documentation","alerts_documentation")
@patch("project.send_alert_email.datetime", Mock(return_value=date(2023,11,22)))
@patch("project.send_alert_email.str", Mock(return_value = "2023-11-22"))
@patch(
    "project.send_alert_email.creating_an_object_for_sending_to_documentation_table",
    Mock(
        return_value={
            "PartitionKey": "3",
            "RowKey": "3",
            "sender_date": "sender_date",
            "subName": "sub_name",
            "subManagerMail": "sub_manager_email",
            "storage_account": "storage_account",
            "email_body": "email_body",
        }
    ),
)
@patch("project.send_alert_email.upload_to_table")
def test_add_entity_to_alerts_documentation(upload_to_table):
    add_entity_to_alerts_documentation(
        {"subName": "Sara", "subManagerMail": "sara@gmail.com"},
        "storage_account",
        "body",
        "3",
        "3"
    )
    upload_to_table.assert_called_once_with(
        "alerts_documentation",
        {
            "PartitionKey": "3",
            "RowKey": "3",
            "sender_date": "sender_date",
            "subName": "sub_name",
            "subManagerMail": "sub_manager_email",
            "storage_account": "storage_account",
            "email_body": "email_body",
        },
    )


def test_creating_an_object_send_to_table():
    assert creating_an_object_for_sending_to_documentation_table(
        1,1, "sender_date", "sub_name", "sub_manager_email", "storage_account", "email_body"
    ) == {
        "PartitionKey": "1",
        "RowKey": "1",
        "sender_date": "sender_date",
        "subName": "sub_name",
        "subManagerMail": "sub_manager_email",
        "storage_account": "storage_account",
        "email_body": "email_body",
    }