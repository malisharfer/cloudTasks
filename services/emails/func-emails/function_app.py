import project
import json
import requests
from project.send_email import build_email_message
import config.config_variables
import azure.functions as func
import logging
import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

app = func.FunctionApp()

@app.function_name(name = "HttpTrigger1")
@app.route(route = "")
def send_email_function(req: func.HttpRequest) -> func.HttpResponse:
    req_body = req.get_json()
    logging.info(req_body.get('excel'))
    message = build_email_message(req_body.get('recipient_email'), req_body.get('subject'), req_body.get('body'), req_body.get('excel'))
    email_data = json.dumps(message)
    client_id = config.config_variables.client_id
    client_secret = config.config_variables.client_secret
    tenant_id = config.config_variables.tenant_id
    graph_url = config.config_variables.graph_url
    access_token = project.get_connection_string.get_access_token(client_id, client_secret, tenant_id)
    requests.post(
        graph_url,
        headers = {
            "Authorization": "Bearer " + access_token,
            "Content-Type": "application/json",
        },
        data = email_data,
    ) 
    logging.info("The email was sent")
    return func.HttpResponse(
        "This HTTP triggered function executed successfully.",
        status_code = 200
    )
