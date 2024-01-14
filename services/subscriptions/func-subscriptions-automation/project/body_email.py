import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables

def build_email_body( sub_name, sub_id, sub_activity, cost):
    body = ''
    if not sub_activity or cost:
        body += f"\n subscription {sub_name} :{sub_id}"
    if not sub_activity:
        body += f"\n The subscription has not been used for the past two weeks, if you do not log in to the subscription in the coming week, the subscription will be deleted."
    if cost:
        cost_set = config.config_variables.cost
        body += f"\n The cost of the subscription is lower than {cost_set}"
    return body


def build_email_body_to_excel( sub_activity, cost):
    body = ''
    if not sub_activity:
        body += f"The subscription has not been used recently."
    if cost:
        cost_set = config.config_variables.cost
        body += f"The cost of the subscription is lower than {cost_set}."
    return body
