from unittest.mock import patch
from project.image_scanning import run_resource_graph_query 
import config

@patch('project.image_scanning.DefaultAzureCredential')
@patch('project.image_scanning.ResourceGraphClient')
@patch('project.image_scanning.set_resource_graph_query')
@patch('project.image_scanning.send_to_queue')
def test_run_resource_graph_query(mock_send_to_queue, mock_set_resource_graph_query, mock_ResourceGraphClient, mock_DefaultAzureCredential):
    mock_credential = mock_DefaultAzureCredential.return_value
    mock_client = mock_ResourceGraphClient.return_value
    mock_client.resources.return_value.as_dict.return_value = {'mocked': 'result'}
    mock_set_resource_graph_query.return_value = 'mocked_query'
    resource_group_name = 'test_rg'
    image_digest = 'test_digest'
    date = '2023-06-13'
    result = run_resource_graph_query(resource_group_name, image_digest, date)
    mock_DefaultAzureCredential.assert_called_once()
    mock_ResourceGraphClient.assert_called_once_with(mock_credential)
    mock_set_resource_graph_query.assert_called_once_with(resource_group_name, image_digest)
    mock_client.resources.assert_called_once()
    mock_send_to_queue.assert_called_once_with(
        config.config_variables.connection_string,
        config.config_variables.queue_name,
        {'mocked': 'result'},
        date,
    )
    assert result is None

@patch('project.image_scanning.DefaultAzureCredential')
@patch('project.image_scanning.ResourceGraphClient')
@patch('project.image_scanning.set_resource_graph_query')
@patch('project.image_scanning.send_to_queue')
def test_run_resource_graph_query_exception(mock_send_to_queue, mock_set_resource_graph_query, mock_ResourceGraphClient, mock_DefaultAzureCredential):
    mock_client = mock_ResourceGraphClient.return_value
    mock_client.resources.side_effect = Exception('Test Exception')
    resource_group_name = 'test_rg'
    image_digest = 'test_digest'
    date = '2023-06-13'
    result = run_resource_graph_query(resource_group_name, image_digest, date) 
    assert result == 'Test Exception'


# class TestRunResourceGraphQuery(unittest.TestCase):

#     @patch('project.image_scanning.DefaultAzureCredential')
#     @patch('project.image_scanning.ResourceGraphClient')
#     @patch('project.image_scanning.set_resource_graph_query')
#     @patch('project.image_scanning.send_to_queue')
#     def test_run_resource_graph_query(self, mock_send_to_queue, mock_set_resource_graph_query, mock_ResourceGraphClient, mock_DefaultAzureCredential):
#         mock_credential = mock_DefaultAzureCredential.return_value
#         mock_client = mock_ResourceGraphClient.return_value
#         mock_client.resources.return_value.as_dict.return_value = {'mocked': 'result'}
#         mock_set_resource_graph_query.return_value = 'mocked_query'
#         resource_group_name = 'test_rg'
#         image_digest = 'test_digest'
#         date = '2023-06-13'
#         result = run_resource_graph_query(resource_group_name, image_digest, date)
#         mock_DefaultAzureCredential.assert_called_once()
#         mock_ResourceGraphClient.assert_called_once_with(mock_credential)
#         mock_set_resource_graph_query.assert_called_once_with(resource_group_name, image_digest)
#         mock_client.resources.assert_called_once()
#         mock_send_to_queue.assert_called_once_with(
#             config.config_variables.connection_string,
#             config.config_variables.queue_name,
#             {'mocked': 'result'},
#             date,
#         )
#         self.assertIsNone(result)  

#     @patch('project.image_scanning.DefaultAzureCredential')
#     @patch('project.image_scanning.ResourceGraphClient')
#     @patch('project.image_scanning.set_resource_graph_query')
#     @patch('project.image_scanning.send_to_queue')
#     def test_run_resource_graph_query_exception(self, mock_send_to_queue, mock_set_resource_graph_query, mock_ResourceGraphClient, mock_DefaultAzureCredential):
#         mock_client = mock_ResourceGraphClient.return_value
#         mock_client.resources.side_effect = Exception('Test Exception')
#         resource_group_name = 'test_rg'
#         image_digest = 'test_digest'
#         date = '2023-06-13'
#         result = run_resource_graph_query(resource_group_name, image_digest, date)
#         self.assertEqual(result, 'Test Exception')

