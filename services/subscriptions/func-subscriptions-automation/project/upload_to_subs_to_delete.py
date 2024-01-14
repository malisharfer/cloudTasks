import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables
from project.storage_table import upload_to_table
from datetime import datetime
from pytz import timezone

table_name = config.config_variables.table_subscriptions_to_delete

def upload_subscriptions_to_delete(subscription_id,subscription_name, is_activity, is_high_cost):
    try:
        sub = build_sub_object(subscription_id,subscription_name, is_activity, is_high_cost)
        upload_to_table(table_name, sub)
    except:
        return "Error occured in the upload process"
  
    
def build_sub_object(subscription_id,subscription_name, is_activity, is_high_cost):
    date = datetime.now(tz = timezone("Asia/Jerusalem"))
    delete_reason = ""
    if subscription_id is None or subscription_name is None or is_activity is None or is_high_cost is None:
        raise ValueError("The values cannot be None")
    if is_activity == False:
        delete_reason = "not activity"
    if is_high_cost == True:
        delete_reason = "too cheap"
    if is_activity == False and is_high_cost == True:
        delete_reason = "not activity and too cheap"

    try:
        return {
            "PartitionKey": date.strftime("%Y-%m-%d"),
            "RowKey": date.strftime("%Y-%m-%d %H:%M:%S"),
            "subscription_id": subscription_id,
            "subscription_name": subscription_name,
            #TODO "owner": sub.owner,
            "reason": delete_reason,
        }
    except Exception as ex:
        return str(ex)
