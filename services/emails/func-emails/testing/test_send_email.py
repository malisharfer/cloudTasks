from project.send_email import build_email_message
import os
import sys
import base64
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import project.download_excel
from unittest import mock
from unittest.mock import Mock, patch

def test_build_email_message():
    email_recipient = "porina3345@ustorp.com"
    subject = "Test build email message"
    body = "Test build email message"
    result = build_email_message(email_recipient, subject, body,None)
    assert result == {'message': {'subject': 'Test build email message', 'body': {'contentType': 'Text', 'content': 'Test build email message'}, 'toRecipients': [{'emailAddress': {'address': 'porina3345@ustorp.com'}}]}}

@patch("project.send_email.base64.b64encode",return_value = b"att_file_after_convert_to_base64")
@patch("project.download_excel.download_blob_excel",return_value = b'att_file')
@patch("project.download_excel.delete_blob_excel",return_value = None)
def test_build_email_message_with_excel(b64encode,download_blob_excel,delete_blob_excel):
    email_recipient = "porina3345@ustorp.com"
    subject = "Test build email message"
    body = "Test build email message"
    result = build_email_message(email_recipient, subject, body,"file_excel.xlsx")
    assert result == {'message': {'subject': 'Test build email message', 'body': {'contentType': 'Text', 'content': 'Test build email message'}, 'toRecipients': [{'emailAddress': {'address': 'porina3345@ustorp.com'}}], 'attachments': [{'@odata.type': '#microsoft.graph.fileAttachment', 'name': 'file_excel.xlsx', 'contentBytes': 'att_file_after_convert_to_base64'}]}}
        