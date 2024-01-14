import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
from unittest.mock import patch
from project.storage_table import *
import project.get_connection_string


@patch("project.get_connection_string.get_connection_string_from_keyvault",return_value = "connection_string")
@patch("project.storage_table.TableServiceClient", return_value = "connection_string")
@patch("project.storage_table.TableServiceClient.get_table_client", return_value = "table_client")
def test_upload_to_table(get_connection_string_from_keyvault,TableServiceClient, get_table_client):
    entity = {}
    assert upload_to_table("table_name", entity) == None
