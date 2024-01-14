from azure.keyvault.secrets import SecretClient
from azure.identity import DefaultAzureCredential
from dotenv import load_dotenv
import os

load_dotenv()

KVUri=os.getenv("KVUri")
credential = DefaultAzureCredential()
client = SecretClient(vault_url=KVUri, credential=credential)
connection_string = client.get_secret("CONNECTION-STRING").value

excel_connection_string=os.getenv('EXCEL_CONNECTION_STRING')
http_trigger_url = os.getenv("HTTP_TRIGGER_URL")
deleted_accounts_table=os.getenv("DELETED_ACCOUNTS_TABLE")
documentation_table=os.getenv("DOCUMENTATION_TABLE")
main_manager=os.getenv('MAIN_MANAGER')
