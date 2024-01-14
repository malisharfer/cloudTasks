import azure.functions as func
import logging

from managed_subscription import *
from config_variables import documentation_table

app = func.FunctionApp()


@app.function_name(name="HttpTrigger1")
@app.route(route="")
def test_function(req: func.HttpRequest) -> func.HttpResponse:
    logging.info('Python HTTP trigger function processed a request.')

    try:
        subscription_list = get_subscription_list()
        
        partition_key = str(get_a_last_partitionKey_number(documentation_table) + 1)

        subscriptions=[]
        for subscription in subscription_list:
            subscriptions.append({'subscription_id':subscription.subscription_id,'subscription_name':subscription.display_name})

    except Exception as e:
        logging.warn(f"---{e}")

    answer={'subscriptions':subscriptions,'partition_key':str(partition_key)}

    logging.info(answer)

    return func.HttpResponse(str(answer), status_code=200)
