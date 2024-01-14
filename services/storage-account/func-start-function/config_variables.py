from azure.keyvault.secrets import SecretClient
from azure.identity import DefaultAzureCredential
from dotenv import load_dotenv
import os

load_dotenv()

KVUri=os.getenv("KVUri")
credential = DefaultAzureCredential()
client = SecretClient(vault_url=KVUri, credential=credential)
connection_string = client.get_secret("CONNECTION-STRING").value

documentation_table=os.getenv("DOCUMENTATION_TABLE")
