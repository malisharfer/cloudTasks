import os
from dotenv import load_dotenv

load_dotenv()

email_secret = os.getenv("EMAILS_SECRET")
keyvault_name = os.getenv("KEYVAULT_NAME")
keyvault_uri = os.getenv("KEYVAULT_URI")
client_id = os.getenv("CLIENT_ID")  
client_secret = os.getenv("CLIENT_SECRET")
tenant_id = os.getenv("TENANT_ID")
user_upn = os.getenv("USER_UPN")
graph_url = os.getenv("GRAPH_URL")

