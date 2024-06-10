from azure.identity import DefaultAzureCredential
from azure.mgmt.resourcegraph import ResourceGraphClient
from azure.mgmt.resourcegraph.models import QueryRequest
from azure.storage.queue import QueueClient, TextBase64EncodePolicy
import json
import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables


def run_resource_graph_query(resource_group_name, image_digest, date):
    try:
        credential = DefaultAzureCredential()
        client = ResourceGraphClient(credential)
        query = set_resource_graph_query(resource_group_name, image_digest)
        result = client.resources(QueryRequest(query=query)).as_dict()
        send_to_queue(
            config.config_variables.connection_string,
            config.config_variables.queue_name,
            result,
            date,
        )
    except Exception as ex:
        return str(ex)


def set_resource_graph_query(resource_group_name, image_digest):
    query = f"""
        securityresources
        | where type =~ 'microsoft.security/assessments/subassessments'
        | where resourceGroup == '{resource_group_name}'
        | where properties.resourceDetails.ResourceProvider == 'acr'
        | where properties contains '{image_digest}'
        | summarize data = make_list(pack(
            'CVE_ID', properties.id,
            'Severity', properties.additionalData.vulnerabilityDetails.severity
        )) by tostring(properties.resourceDetails.ResourceName)
        | project ImageName=properties_resourceDetails_ResourceName , Data=data
    """
    return query


def send_to_queue(connection_string, queue_name, json_message, date):
    try:
        queue_client = QueueClient.from_connection_string(
            connection_string,
            queue_name,
            message_encode_policy=TextBase64EncodePolicy(),
        )
        json_message["dateOfPush"] = date
        queue_client.send_message(json.dumps(json_message))
    except Exception as ex:
        raise Exception(ex)
