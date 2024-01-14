import os
from dotenv import load_dotenv

load_dotenv()

tag_name = os.getenv("TAG_NAME")
table_subscriptions_to_delete = os.getenv("TABLE_SUBSCRIPTIONS_TO_DELETE")
subscription_secret = os.getenv("SUBSCRIPTION_SECRET")
email_secret = os.getenv("EMAILS_SECRET")
keyvault_name = os.getenv("KEYVAULT_NAME")
keyvault_uri = os.getenv("KEYVAULT_URI")
cloud_email = os.getenv("CLOUD_EMAIL")
http_trigger_url = os.getenv("HTTP_TRIGGER_URL")
http_trigger_url_subscription_automation = os.getenv("HTTP_TRIGGER_URL_SUBSCRIPTION_AUTOMATION")
