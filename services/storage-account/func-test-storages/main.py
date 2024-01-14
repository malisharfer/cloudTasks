from project.config_variables import documentation_storage_name
from project.storage_account_test import *

def test_function():
    print("hello")
    # body=req.get_body()
    # my_json = body.decode('utf8').replace("'", '"')
    # data = json.loads(my_json)
    data={
        "last_fetch_time": "2023-12-24 15:33:26.221635+00:00",
        "partition_key": "329",
        "row_key": 0,
        "storage_account": {
            "id": "/subscriptions/a173eef2-33d7-4d55-b0b5-18b271f8d42b/resourceGroups/a-function-app-to-logic-app_group/providers/Microsoft.Storage/storageAccounts/afunctionapptologic8641",
            "name": "afunctionapptologic8641",
            "tag": "false"
        },
        "subscription_id": "a173eef2-33d7-4d55-b0b5-18b271f8d42b",
        "subscription_name": "Moon- azure camp"
    }
    subscription_id=data['subscription_id']
    subscription_name=data['subscription_name']
    storage_account=data['storage_account']
    partition_key=data['partition_key']
    row_key=data['row_key']
    last_fetch_time=data['last_fetch_time']

    response_for_null_storages={"storage_account":"null"}
    
    try:
        if storage_account['tag'] == "True" :
            response = {"value": [response_for_null_storages],"nextLink": None}
            return json.dumps(response)
        
        if(storage_account['name'] == documentation_storage_name):
            response = {"value": [response_for_null_storages],"nextLink": None}
            return json.dumps(response)

        object_for_alerts_to_excel=storage_account_test(
            storage_account['name'],
            partition_key,
            row_key,
            subscription_id,
            subscription_name,
            storage_account['id'],
            last_fetch_time
        )

    except Exception as e:
        print(f"?!?!?!?!?!?!?{e}")
        response_for_null_storages={"storage_account":storage_account['name'],"alert_body":"null"}
        response = {"value": [response_for_null_storages],"nextLink": None}
        return json.dumps(response)
    
    response = {"value": [object_for_alerts_to_excel],"nextLink": None}

    # return func.HttpResponse(json.dumps(response), mimetype="application/json")
    print(f"response----{response}")
    
test_function()