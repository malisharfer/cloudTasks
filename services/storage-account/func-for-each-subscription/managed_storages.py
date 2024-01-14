from azure.mgmt.storage import StorageManagementClient
from azure.identity import DefaultAzureCredential


def get_storage_list(sub_id):
    storage_client = create_storage_management_client(sub_id)
    storage_accounts = storage_client.storage_accounts.list()
    return storage_accounts


def create_storage_management_client(sub_id):
    storage_client = StorageManagementClient(
        credential=DefaultAzureCredential(), subscription_id=sub_id
    )
    return storage_client
