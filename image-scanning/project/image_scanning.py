from azure.identity import DefaultAzureCredential
from azure.mgmt.resourcegraph import ResourceGraphClient
from azure.mgmt.resourcegraph.models import QueryRequest
import os
import sys
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

def run_resource_graph_query(resource_group_name,image_digest,date):
    try:
        credential = DefaultAzureCredential()
        client = ResourceGraphClient(credential)
        query=set_resource_graph_query(resource_group_name,image_digest)
        result = client.resources(QueryRequest(query=query)).as_dict()
    except:
        return "An error occurred during the process"    

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
