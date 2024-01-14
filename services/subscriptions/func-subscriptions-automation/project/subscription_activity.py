from datetime import datetime
from dateutil.relativedelta import relativedelta
from azure.identity import DefaultAzureCredential
from azure.mgmt.monitor import MonitorManagementClient
from azure.mgmt.consumption import ConsumptionManagementClient
import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables

credentials = DefaultAzureCredential()

def check_subscription_activity(subscription_id):
    try:
        monitor_client = MonitorManagementClient(credentials, subscription_id)
        date = get_start_date()
        filter_condition = (
            f"eventTimestamp ge '{date[0]}' and eventTimestamp le '{date[1]}'"
        )
        activity_logs = monitor_client.activity_logs.list(filter = filter_condition)
        if next(activity_logs, None):
            return True
        else:
            return False
    except:
        return "The start time cannot be more than 90 days in the past."


def get_start_date():
    try:
        num_of_months = config.config_variables.num_of_months
        current_date = datetime.now().date()
        start_date = current_date - relativedelta(months = int(num_of_months))
        return start_date, current_date
    except:
        return "An error occurred in the calculation"


def is_lower_than_the_set_price(subscription_id):
    cost = config.config_variables.cost 
    try:
        consumption_client = ConsumptionManagementClient(credentials, subscription_id)
        results = consumption_client.usage_details.list(
            scope = f"/subscriptions/{subscription_id}"
        )
        total_cost = sum(result.cost for result in results)
        return total_cost < float(cost)
    except:
        return "An error occurred in the process"
