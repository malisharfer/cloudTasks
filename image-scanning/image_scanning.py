import json
from azure.identity import DefaultAzureCredential
import json
from azure.storage.queue import  QueueClient, TextBase64EncodePolicy
from azure.mgmt.resourcegraph import ResourceGraphClient
from azure.mgmt.resourcegraph.models import QueryRequest
import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import config.config_variables

def run_resource_graph_query(resource_group_name,image_digest,date):
    try:
        credential = DefaultAzureCredential()
        client = ResourceGraphClient(credential)
        query=set_resource_graph_query(resource_group_name,image_digest)
        result =client.resources(QueryRequest(query=query)).as_dict()
        send_to_queue(config.config_variables.connection_string, config.config_variables.queue_name, result ,date)
    except:
        return "an error ecourd during the procces"    

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


def send_to_queue(conn_string, queue_name, json_message, date):
    queue_client = QueueClient.from_connection_string(
        conn_string,
        queue_name,
        message_encode_policy=TextBase64EncodePolicy()
    )
    queue_client.send_message(json.dumps(json_message))
