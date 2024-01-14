from azure.identity import DefaultAzureCredential
from azure.keyvault.secrets import SecretClient
from config import config_variables
import msal

credentials = DefaultAzureCredential()

def get_connection_string_from_keyvault(secret_name):
    client = SecretClient(config_variables.keyvault_uri, credential = credentials)
    keyVaultNameValue = client.get_secret(secret_name)
    return keyVaultNameValue.value


def get_access_token(client_id, client_secret, tenant_id):
    authority = f"https://login.microsoftonline.com/{tenant_id}"
    scope = ["https://graph.microsoft.com/.default"]

    app = msal.ConfidentialClientApplication(client_id, authority = authority, client_credential = client_secret)
    result = app.acquire_token_for_client(scopes = scope)

    if "access_token" in result:
        return result["access_token"]
  

