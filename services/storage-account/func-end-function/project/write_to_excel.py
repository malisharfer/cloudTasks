from azure.storage.blob import BlobServiceClient
from openpyxl import Workbook
import io

def write_to_excel(connection_string, excel_array):
    try:
        last_cell_written = 1
        blob_name = 'alert_file.xlsx'
        for i in excel_array:
            if last_cell_written == 1:
                workbook = Workbook()
                sheet = workbook.active
                sheet['A1']="Storage Account"
                sheet['B1']="Alert Body"
                sheet['C1']="Subscription Name"
                sheet['D1']="Subscription Manager Email"
                file_stream = io.BytesIO()

            column_storage_account = "A{}".format(last_cell_written + 1)
            sheet[column_storage_account] = i['storage_account']
            column_alert_body = "B{}".format(last_cell_written + 1)
            sheet[column_alert_body] = i['alert_body']
            column_manager = "C{}".format(last_cell_written + 1)
            sheet[column_manager] = i['subscription_name']
            column_recipient_email = "D{}".format(last_cell_written + 1)
            sheet[column_recipient_email] = i['subscription_manager_email']
            workbook.save(file_stream)
            file_stream.seek(0)
            last_cell_written += 1

        container_name = 'excel'
        blob_service_client = BlobServiceClient.from_connection_string(connection_string)
        blob_client = blob_service_client.get_blob_client(container=container_name, blob=blob_name)
        blob_client.upload_blob(file_stream.getvalue(), overwrite=True)

    except Exception as e:
        raise Exception ("Could not succeed write to Excel")
