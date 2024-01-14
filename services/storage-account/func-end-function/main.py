from project.write_to_excel import *
from project.managed_deleted_storages import deleted_storages
from config_variables import excel_connection_string, http_trigger_url, main_manager, documentation_table
import requests ,json


def test_function():
    
    print('Python HTTP trigger function processed a request.')

    try:
        
        # body = req.get_body()
        # my_json = body.decode('utf8').replace("'", '"')
        # data = json.loads(my_json)
        data={
            "alerts_to_excel":[
                {
                    "storage_account": "afunctionapptologic8641",
                    "alert_body": ":storage account afunctionapptologic8641\nThe amount of storage in use has increased in the last 30 days",
                    "subscription_name": "Moon- azure camp",
                    "subscription_manager_email": "yafiv@skyvar.co.il"
                },
                {
                    "storage_account": "afunctionapptologic8743",
                    "alert_body": ":storage account afunctionapptologic8743\nThe amount of storage in use has increased in the last 30 days",
                    "subscription_name": "Moon- azure camp",
                    "subscription_manager_email": "yafiv@skyvar.co.il"
                }
            ],
            "all_storages":[
                " afunctionapptologic8641",
                " afunctionapptologic8743",
                " checkworkfolwstorag86ee",
                " checkworkfolwstoraga55d"
            ],
            "partition_key": "322"
        }

        alerts_to_excel = data['alerts_to_excel']
        partition_key = data['partition_key']
        all_storages = data['all_storages']
        
        write_to_excel(excel_connection_string, alerts_to_excel)
        
        requests.post(
            http_trigger_url,
            json={
                "recipient_email": main_manager,
                "subject": "Summary Alerts For Storage Accounts",
                "body": "summary file",
                "excel":'alert_file.xlsx'
        })
        
        deleted_storages(documentation_table, int(partition_key)-1 , all_storages)

    except Exception as e:
        print(f"-!!!!!!!!!!!-{e}")
        
    print("success")
test_function()