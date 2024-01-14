import azure.functions as func
from managed_storages import *
from config_variables import essential_tag
import logging, json ,io

app = func.FunctionApp()


@app.function_name(name="HttpTrigger1")
@app.route(route="")
def test_function(req: func.HttpRequest) -> func.HttpResponse:
    logging.info('Python HTTP trigger function processed a request.')

    try:
        
        fix_bytes_value = req.get_body().replace(b"'", b'"')
        subscriptions_json = json.load(io.BytesIO(fix_bytes_value))
                
        storage_account_list = get_storage_list(subscriptions_json['subscription_id'])

        storage_accounts=[]
        for storage_account in storage_account_list:
            storage_accounts.append({'name':storage_account.name,'id':storage_account.id,'tags':'true' if storage_account.tags.get(essential_tag) else 'false'})
        
    except Exception as e:
        logging.info(f"-<<->>-{e}")

    return func.HttpResponse(str(storage_accounts), status_code=200)
