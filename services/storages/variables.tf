variable key_vault_name {
  type = string
}

variable key_vault_resource_group_name {
  type = string
}

variable key_vault_secret_excel_name {
  type = string
}

variable acr_name {
  type = string
}

variable REGISTRY_URL {
  type = string
}

variable acr_resource_group_name {
  type = string
}

variable rg_name{
  type    = string
  default ="rg-storages-dev"
}

variable rg_location {
  type    = string
  default = "West Europe"
}

variable storage_account_name {
  type    = string
  default = "ststoragesdev"
}

variable key_vault_secret_name {
  type    = string
  default = "CONNECTION-STRING-MANAGEMENT-STORAGES-DEV"
}

variable app_service_plan_name{
  type    = list(string)
  default = ["app-get-last-fetch-time-for-each-storage-account-dev","app-get-subscription-list-dev","app-get-storage-list-by-subscription-dev","app-test-storage-dev","app-send-excel-mark-delete-dev"]
}

variable function_app_name {
  type    = list(string)
  default = ["func-get-last-fetch-time-for-each-storage-account-dev","func-get-subscription-list-dev","func-get-storage-list-by-subscription-dev","func-test-storage-dev","func-send-excel-mark-delete-dev"]
}

variable FREQ_AUTOMATION_TEST_TYPE {
  type    = string
  default = "Week"
  validation {
    condition = contains(["Month","Week","Day","Hour","Minute","Second"], var.FREQ_AUTOMATION_TEST_TYPE)
    error_message = "Valid values for var: FREQ_AUTOMATION_TEST_TYPE are (Month,Week,Day,Hour,Minute,Second)."
  }
}

variable FREQ_AUTOMATION_TEST_NUMBER {
  type    = number
  default = 1
}

variable logic_app_workflow_name {
  type    = string
  default = "logic-app-storage-management-dev"
}

variable table_name {
  type    = list(string)
  default = [ "documentationdev","deletedStoragesAcountsdev","alertsDocumentationdev" ]
}

variable IMAGE_NAME {
  type    = list(string)
  default = ["services/storage_account/func_get_last_fetch_time_for_each_storage_account","services/storage_account/func_get_subscription_list","services/storage_account/func_get_storage_list_by_subscription","services/storage_account/func_test_storage","services/storage_account/func_send_excel_mark_delete"]
}

variable IMAGE_TAG {
  type    = string
  default = "1.0.0"
}
