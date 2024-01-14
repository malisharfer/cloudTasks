import pytest
from unittest.mock import Mock, patch
import project  # Assuming your module is named 'project'

class MockSheet:
    def __init__(self):
        self.max_row = 1

    def __getitem__(self, key):
        return None  

    def __setitem__(self, key, value):
        pass  


class MockWorkbook:
    def __init__(self):
        self.active = MockSheet()

    def save(self, file_stream):
        return 'save'


@pytest.fixture
def mock_get_connection_string():
    with patch("project.get_connection_string.get_connection_string_from_keyvault", return_value="connection_string"):
        yield


@pytest.fixture
def mock_blob_service_client():
    with patch('project.write_to_excel.BlobServiceClient', Mock(return_value=None)):
        yield


@pytest.fixture
def mock_load_workbook():
    with patch('project.write_to_excel.load_workbook', Mock(return_value=MockWorkbook())):
        yield


@pytest.fixture
def mock_download_blob():
    with patch("project.write_to_excel.download_blob", Mock(return_value=b'att_file')):
        yield


def test_write_to_excel(mock_get_connection_string, mock_blob_service_client, mock_load_workbook, mock_download_blob):
    subscription_obj = {"display_name": "display_name", "subscription_id": "subscription_id", "body": "body"}
    result = project.write_to_excel.write_to_excel(subscription_obj)
    assert result is None
