resource "azurerm_resource_group" "resource_group" {
  name     = var.rg_name
  location = var.rg_location
}

resource "azurerm_storage_account" "storage_account" {
  name                     = var.storage_account_name
  resource_group_name      = azurerm_resource_group.resource_group.name
  location                 = azurerm_resource_group.resource_group.location
  account_tier             = "Standard"
  account_replication_type = "LRS"
}

data "azurerm_key_vault" "key_vault" {
  name                = var.key_vault_name
  resource_group_name = var.key_vault_resource_group_name
}

resource "azurerm_key_vault_secret" "key_vault_secret" {
  name         = var.key_vault_secret_name
  value        = azurerm_storage_account.storage_account.primary_connection_string
  key_vault_id = data.azurerm_key_vault.key_vault.id
}

resource "azurerm_service_plan" "service_plan" {
  name                = var.app_service_plan_name[count.index]
  resource_group_name = azurerm_storage_account.storage_account.resource_group_name
  location            = azurerm_storage_account.storage_account.location
  os_type             = "Linux"
  sku_name            = "P1v2"

  count = length(var.app_service_plan_name)
}

resource "azurerm_linux_function_app" "linux_function_app" {
  name                        = var.function_app_name[count.index]
  resource_group_name         = azurerm_storage_account.storage_account.resource_group_name
  location                    = azurerm_storage_account.storage_account.location
  storage_account_name        = azurerm_storage_account.storage_account.name
  storage_account_access_key  = azurerm_storage_account.storage_account.primary_access_key
  service_plan_id             = azurerm_service_plan.service_plan[count.index].id
  functions_extension_version = "~4"

  app_settings = count.index==0 ? {
    DESIRED_TIME_PERIOD_SINCE_LAST_RETRIEVAL_FOR_CHECK_LAST_FETCH = ""
    TIME_INDEX_FOR_CHECK_LAST_FETCH = ""
    WORKSPACE_ID = ""
    https_only = true
    WEBSITES_ENABLE_APP_SERVICE_STORAGE = false
  } : count.index==1 ? {
    DOCUMENTATION_TABLE = azurerm_storage_table.storage_table[0].name
    SECRET = azurerm_key_vault_secret.key_vault_secret.name
    KEYVAULT_URI = data.azurerm_key_vault.key_vault.vault_uri
    https_only = true
    WEBSITES_ENABLE_APP_SERVICE_STORAGE = false
  } : count.index==2 ? {
    ESSENTIAL_TAG = " "
    https_only = true
    WEBSITES_ENABLE_APP_SERVICE_STORAGE = false
  }: count.index==3 ? {
    DESIRED_TIME_PERIOD_SINCE_LAST_RETRIEVAL_FOR_CHECK_LAST_FETCH = " "
    DESIRED_TIME_PERIOD_SINCE_LAST_RETRIEVAL_FOR_CHECK_USED_CAPACITY = " "
    TIME_INDEX_FOR_CHECK_LAST_FETCH = " "
    TIME_INDEX_FOR_CHECK_USED_CAPACITY = " "
    HTTP_TRIGGER_URL = " "
    FREQ_AUTOMATION_TEST_TYPE=var.FREQ_AUTOMATION_TEST_TYPE
    FREQ_AUTOMATION_TEST_NUMBER=var.FREQ_AUTOMATION_TEST_NUMBER
    DOCUMENTATION_TABLE = azurerm_storage_table.storage_table[0].name
    ALERTS_DOCUMENTATION = azurerm_storage_table.storage_table[2].name
    DOCUMENTATION_STORAGE_NAME= azurerm_storage_account.storage_account.name
    SECRET = azurerm_key_vault_secret.key_vault_secret.name
    KEYVAULT_URI = data.azurerm_key_vault.key_vault.vault_uri
    https_only = true
    WEBSITES_ENABLE_APP_SERVICE_STORAGE = false
  } : count.index==4 ? {
    HTTP_TRIGGER_URL = " "
    MAIN_MANAGER = " "
    DOCUMENTATION_TABLE = azurerm_storage_table.storage_table[0].name
    DELETED_ACCOUNTS_TABLE = azurerm_storage_table.storage_table[1].name
    KEYVAULT_URI = data.azurerm_key_vault.key_vault.vault_uri
    SECRET = azurerm_key_vault_secret.key_vault_secret.name
    SECRET_EXCEL = var.key_vault_secret_excel_name
    https_only = true
    WEBSITES_ENABLE_APP_SERVICE_STORAGE = false
  }: {}

  site_config {
    always_on = true
    application_stack {
      docker {
        registry_url = var.REGISTRY_URL
        image_name = var.IMAGE_NAME[count.index]
        image_tag = var.IMAGE_TAG
      }
    }
  }

  identity {
    type = "SystemAssigned"
  }
  count= length(var.function_app_name)
}

resource "azurerm_logic_app_workflow" "logic_app_workflow" {
  name                = var.logic_app_workflow_name
  location            = var.rg_location
  resource_group_name = var.rg_name

  workflow_parameters = {
    "workflows_logic_app_name" : "{ \"defaultValue\":\"${var.logic_app_workflow_name}\", \"type\" : \"string\"}"
    "sites_func_get_last_fetch_time_for_each_storage_account_externalid": "{\"defaultValue\": \"${azurerm_linux_function_app.linux_function_app[0].id}\",\"type\": \"string\"}"
    "sites_func_get_subscription_list_externalid": "{\"defaultValue\": \"${azurerm_linux_function_app.linux_function_app[1].id}\", \"type\": \"string\"}"
    "sites_func_get_storage_list_by_subscription_externalid": "{\"defaultValue\": \"${azurerm_linux_function_app.linux_function_app[2].id}\",\"type\": \"string\" }"
    "sites_func_test_storage_externalid": "{ \"defaultValue\":\"${azurerm_linux_function_app.linux_function_app[3].id}\", \"type\": \"string\"}"
    "sites_func_sending_excel_by_email_and_mark_storages_for_deletion_externalid": "{\"defaultValue\": \"${azurerm_linux_function_app.linux_function_app[4].id}\",\"type\": \"string\" }"
    "location":"{\"defaultValue\": \"${var.rg_location}\",\"type\": \"string\" }"
    "frequency":"{\"defaultValue\": \"${var.FREQ_AUTOMATION_TEST_TYPE}\",\"type\": \"string\",\"allowedValues\": [\"Month\",\"Week\",\"Day\",\"Hour\",\"Minute\",\"Second\"]}"
    "interval": "{ \"defaultValue\": ${var.FREQ_AUTOMATION_TEST_NUMBER}, \"type\": \"int\" }"
  }
}

data "azurerm_client_config" "current_client" {}

resource "azurerm_key_vault_access_policy" "principal" {
  key_vault_id = data.azurerm_key_vault.key_vault.id
  tenant_id    = data.azurerm_client_config.current_client.tenant_id
  object_id    = azurerm_linux_function_app.linux_function_app[count.index].identity[0].principal_id

  key_permissions = [
    "Get", "List", "Encrypt", "Decrypt"
  ]

  secret_permissions = [
    "Get",
  ]

  count = length(var.function_app_name)
}

data "azurerm_container_registry" "container_registry" {
  name                = var.acr_name
  resource_group_name = azurerm_storage_account.storage_account.resource_group_name
}

resource "azurerm_role_assignment" "role_assignment" {
  principal_id                     = azurerm_linux_function_app.linux_function_app[count.index].identity[0].principal_id
  role_definition_name             = "AcrPull"
  scope                            = data.azurerm_container_registry.container_registry.id
  skip_service_principal_aad_check = true
}

resource "azurerm_storage_table" "storage_table" {
  name                 = var.table_name[count.index]
  storage_account_name = azurerm_storage_account.storage_account.name

  count = length(var.table_name)
}
