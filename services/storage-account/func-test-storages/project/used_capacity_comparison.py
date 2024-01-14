from project.previous_capacity import get_storage_used_capacity_information
from project.current_capacity import get_used_capacity


def used_capacity_comparison_test(resource_group_name, storage_name, subscription_id):
    current_used_capacity = get_used_capacity(
        subscription_id,
        resource_group_name,
        storage_name,
    )
    used_quantity_test_results = {
        "storage_name": storage_name,
        "resource_group": resource_group_name,
        "current_used_storage_capacity": current_used_capacity,
    }

    storage_information = get_storage_used_capacity_information(
        storage_name
    )
    if storage_information:
        used_quantity_test_results["alert"] = (
            current_used_capacity > storage_information["used_storage_capacity"]
        )
    else:
        used_quantity_test_results["alert"] = False
    return used_quantity_test_results

