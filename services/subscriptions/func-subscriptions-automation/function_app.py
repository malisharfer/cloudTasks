import requests
from project.body_email import *
from project.subscription_activity import *
from project.upload_to_emails import upload_to_emails
from project.upload_to_subs_to_delete import upload_subscriptions_to_delete
from project.sub_manager_email import get_email_manager_by_sub_name
from project.write_to_excel import write_to_excel
import config.config_variables 
import azure.functions as func

app = func.FunctionApp()

@app.function_name(name = "HttpTrigger1")
@app.route(route = "")
def func_subscriptions_automation(req: func.HttpRequest) -> func.HttpResponse:
    subscriptions_to_excel={}
    req_body = req.get_json()
    subscription_name = req_body['subscription_name']
    subscription_id = req_body['subscription_id']
    activity = check_subscription_activity(subscription_id)
    low_price = is_lower_than_the_set_price(subscription_id)
    if activity == False or low_price == True:
        body = build_email_body(
                subscription_name, subscription_id, activity, low_price
            )
        recipient_email = get_email_manager_by_sub_name(subscription_name)
        if not recipient_email.__contains__('@'):
            recipient_email = config.config_variables.recipient_email
        if body != "":
                requests.post(
                    config.config_variables.http_trigger_url,
                    json = {
                        "recipient_email": recipient_email,
                        "subject": "Subscription Activity Alert",
                        "body": body,
                        "excel":None
                    }
                )
        body_to_excel = build_email_body_to_excel(activity,low_price)
        subscriptions_to_excel = {"display_name":subscription_name,"subscription_id":subscription_id,"body":body_to_excel}
        upload_to_emails(recipient_email, activity, low_price)
        upload_subscriptions_to_delete(subscription_id,subscription_name, activity, low_price)
        write_to_excel(subscriptions_to_excel)
   
    return func.HttpResponse(
        "This HTTP triggered function executed successfully.",
        status_code = 200
    )