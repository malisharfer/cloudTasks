import azure.functions as func
import requests
from project.get_subscriptions import *
from project.upload_to_deleted_subs import upload_deleted_subscriptions
from project.create_excel_blob import create_excel_blob

app = func.FunctionApp()

@app.function_name(name = "HttpTrigger1")
@app.route(route = "")
def func_subscriptions_list(req: func.HttpRequest) -> func.HttpResponse:
    subscriptions = get_subscriptions()
    create_excel_blob()
    for sub in subscriptions:
        requests.post(   
            config.config_variables.http_trigger_url_subscription_automation,
            json = {
                "subscription_name":sub.display_name,
                "subscription_id":sub.subscription_id
            }
        )
    upload_deleted_subscriptions(subscriptions) 

    requests.post(
            config.config_variables.http_trigger_url,
            json = {
                "recipient_email": config.config_variables.shelis_email,
                "subject": "Summary Subscription",
                "body": "Summary file",
                "excel":'file_subscription.xlsx'
            })  
    
    return func.HttpResponse(
            "This HTTP triggered function executed successfully. ",
            status_code = 200
        )
