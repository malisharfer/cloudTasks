from project.image_scanning import set_resource_graph_query  
def test_set_resource_graph_query():
    resource_group_name = 'test_resource_group'
    image_digest = 'test_image_digest'
    actual_query = set_resource_graph_query(resource_group_name, image_digest) 
    assert isinstance(actual_query, str)
