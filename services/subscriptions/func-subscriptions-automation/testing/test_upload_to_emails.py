from datetime import datetime
from unittest.mock import patch
from project.upload_to_emails import *


def test_build_email_object():
    date = datetime.now(tz = timezone("Asia/Jerusalem"))
    return_email = {
        "PartitionKey": date.strftime("%Y-%m-%d"),
        "RowKey": date.strftime("%Y-%m-%d %H:%M:%S"),
        "recipient_email": "aaa@gmail.com",
        "reason": "not activity",
    }
    assert build_email_object("aaa@gmail.com", False, False) == return_email
    

@patch("project.upload_to_emails.build_email_object",return_value = [{"recipient_email": "aaa@gmail.com"}])
@patch("project.upload_to_emails.upload_to_table")
def test_upload_deleted_subscriptions(build_email_object, upload_to_table):
    assert upload_to_emails("aaa@gmail.com", False, False) == None
