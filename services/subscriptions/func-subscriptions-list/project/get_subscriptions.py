from azure.identity import DefaultAzureCredential
from azure.mgmt.resource import SubscriptionClient
import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables

credentials = DefaultAzureCredential()

def get_subscriptions():
    subscription_client = SubscriptionClient(credentials)
    subscriptions = list(subscription_client.subscriptions.list())
    tagged_subscriptions = []
    for subscription in subscriptions:
        tags = subscription.tags
        if tags and config.config_variables.tag_name not in tags.keys():
            tagged_subscriptions.append(subscription)
    return tagged_subscriptions
