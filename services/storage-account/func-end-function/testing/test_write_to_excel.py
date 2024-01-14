from unittest.mock import patch, Mock
import pytest
from project.write_to_excel import *

class mock_Workbook():
    def __init__(self):
        self.active={}
    def save(self,file_stream):
        return 'save'

@patch('project.write_to_excel.Workbook',mock_Workbook)
@patch('project.write_to_excel.BlobServiceClient')
def test_write_to_excel(BlobServiceClient):
    write_to_excel('con',[{'storage_account': 'afunctionapptologic8743',
                        'alert_body': ':storage account afunctionapptologic8743\nThe amount of storage in use has increased in the last 30 days',
                        'subscription_name': 'null',
                        'subscription_manager_email': 'null'},
                        {'storage_account': 'checkworkfolwstorag86ee',
                        'alert_body': ':storage account checkworkfolwstorag86ee\nThe amount of storage in use has increased in the last 30 days',
                        'subscription_name': 'null',
                        'subscription_manager_email': 'null'}])
    BlobServiceClient.from_connection_string.assert_called_once_with('con')

@patch('project.write_to_excel.Workbook',Mock(side_effect=Exception()))
@patch('project.write_to_excel.BlobServiceClient',Mock(return_value=None))
def test_write_to_excel_failed():
    with pytest.raises(Exception) as exception:
        write_to_excel('con',[{'storage_account': 'afunctionapptologic8743',
                        'alert_body': ':storage account afunctionapptologic8743\nThe amount of storage in use has increased in the last 30 days',
                        'subscription_name': 'null',
                        'subscription_manager_email': 'null'},
                        {'storage_account': 'checkworkfolwstorag86ee',
                        'alert_body': ':storage account checkworkfolwstorag86ee\nThe amount of storage in use has increased in the last 30 days',
                        'subscription_name': 'null',
                        'subscription_manager_email': 'null'}])
    assert "Could not succeed write to Excel" in str(exception.value)
