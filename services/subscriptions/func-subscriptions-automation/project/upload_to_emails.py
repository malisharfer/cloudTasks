import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables
from project.storage_table import upload_to_table
from datetime import datetime
from pytz import timezone

table_name = config.config_variables.table_emails

def upload_to_emails(recipient_email, is_activity, is_high_cost):
    try:
        entity = build_email_object(recipient_email, is_activity, is_high_cost)
        upload_to_table(table_name, entity)
    except:
        return "Error occured in the upload process"


def build_email_object(recipient_email, is_activity, is_high_cost):
    date = datetime.now(tz = timezone("Asia/Jerusalem"))
    delete_reason = ""
    if is_activity == False:
        delete_reason = "not activity"
    if is_high_cost == True:
        delete_reason = "too cheap"
    if is_activity == False and is_high_cost == True:
        delete_reason = "not activity and expensive"
    try:
        return {
            "PartitionKey": date.strftime("%Y-%m-%d"),
            "RowKey": date.strftime("%Y-%m-%d %H:%M:%S"),
            "recipient_email": recipient_email,
            "reason": delete_reason,
        }
    except Exception as ex:
        return str(ex)
