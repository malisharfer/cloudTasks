from unittest.mock import patch, MagicMock
import json
from datetime import datetime
from project.image_scanning import send_to_queue


@patch("azure.storage.queue.QueueClient.from_connection_string")
def test_send_to_queue(mock_from_connection_string):
    mock_queue_client_instance = MagicMock()
    mock_from_connection_string.return_value = mock_queue_client_instance

    connection_string = "fake_connection_string"
    queue_name = "test_queue"
    json_message = {"key": "value"}
    date = datetime.now().isoformat()

    send_to_queue(connection_string, queue_name, json_message, date)

    assert json_message["dateOfPush"] == date

    expected_message = json.dumps({"key": "value", "dateOfPush": date})
    mock_queue_client_instance.send_message.assert_called_once_with(expected_message)
