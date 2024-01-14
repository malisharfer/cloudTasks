import pytest
from unittest.mock import patch, Mock
from managed_subscription import *


@patch("managed_subscription.DefaultAzureCredential",Mock(return_value="azure cli credential"))
@patch("managed_subscription.SubscriptionClient")
def test_create_subscription_client(SubscriptionClient):
    create_subscription_client()
    SubscriptionClient.assert_called_once_with(credential="azure cli credential")


@patch("managed_subscription.create_subscription_client")
def test_get_subscription_list(create_subscription_client):
    list_subscriptions = get_subscription_list()
    create_subscription_client.assert_called_once_with()


def test_get_subscription_list_return_exception():
    with pytest.raises(Exception) as exception:
        get_subscription_list("a17")
    assert (
        "get_subscription_list() takes 0 positional arguments but 1 was given"
        in str(exception.value)
    )



@patch("managed_subscription.connection_string", "123456-789456")
@patch(
    "managed_subscription.convert_to_json",
    Mock(
        return_value={
            "1": {"PartitionKey": "1"},
            "2": {"PartitionKey": "2"},
            "3": {"PartitionKey": "3"},
            "4": {"PartitionKey": "4"},
            "5": {"PartitionKey": "5"},
        }
    ),
)
@patch("managed_subscription.TableClient")
def test_get_a_last_partitionKey_number_called_connection_string(TableClient):
    get_a_last_partitionKey_number("table_name")
    TableClient.from_connection_string.assert_called_once_with(
        "123456-789456", "table_name"
    )


@patch("managed_subscription.connection_string", "123456-789456")
@patch(
    "managed_subscription.convert_to_json",
    Mock(
        return_value={
            "1": {"PartitionKey": "1"},
            "2": {"PartitionKey": "2"},
            "3": {"PartitionKey": "3"},
            "4": {"PartitionKey": "4"},
            "5": {"PartitionKey": "5"},
        }
    ),
)
@patch("managed_subscription.TableClient")
def test_get_a_last_partitionKey_number_called_query_entities(TableClient):
    get_a_last_partitionKey_number("table_name")
    TableClient.from_connection_string().query_entities.assert_called_once_with(
        query_filter="", select=["*"]
    )


@patch("managed_subscription.connection_string", "123456-789456")
@patch(
    "managed_subscription.convert_to_json",
    Mock(
        return_value={
            "1": {"PartitionKey": "1"},
            "2": {"PartitionKey": "2"},
            "3": {"PartitionKey": "3"},
            "4": {"PartitionKey": "4"},
            "5": {"PartitionKey": "5"},
        }
    ),
)
@patch("managed_subscription.TableClient")
def test_get_a_last_partitionKey_number_return_5(TableClient):
    assert get_a_last_partitionKey_number("table_name") == 5

@patch("managed_subscription.connection_string", "123456-789456")
@patch(
    "managed_subscription.convert_to_json",
    Mock(
        return_value={}
    ),
)
@patch("managed_subscription.TableClient")
def test_get_a_last_partitionKey_number_return_negative_one(TableClient):
    assert get_a_last_partitionKey_number("table_name") == -1


@patch('managed_subscription.pd')
@patch('managed_subscription.json.loads',Mock(return_value = "json"))
def test_convert_to_json_convert_entities(pd):
    assert convert_to_json("entities")=="json"