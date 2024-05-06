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

resource "azurerm_logic_app_workflow" "logic_app_workflow" {
  name                =  var.logic_app_workflow_name
  location            = azurerm_storage_account.storage_account.location
  resource_group_name = azurerm_storage_account.storage_account.resource_group_name
}

resource "azurerm_service_plan" "service_plan" {
  name                = var.service_plan_name[count.index]
  location            = azurerm_storage_account.storage_account.location
  resource_group_name = azurerm_storage_account.storage_account.resource_group_name
  os_type             = "Linux"
  sku_name            = "P1v2"

  count = length(var.service_plan_name)
}

resource "azurerm_linux_function_app" "function_app" {
  name                        = var.function_app_name[count.index]
  location                    = azurerm_storage_account.storage_account.location
  resource_group_name         = azurerm_storage_account.storage_account.resource_group_name
  service_plan_id             = azurerm_service_plan.service_plan[count.index].id
  storage_account_name        = azurerm_storage_account.storage_account.name
  storage_account_access_key  = azurerm_storage_account.storage_account.primary_access_key
  functions_extension_version = "~4"

  app_settings = count.index==0 ? {
    COST = " "
    EMAILS_SECRET = " "
    ADMINISTRATORS_SECRET = " "
    HTTP_TRIGGER_URL = " "
    NUM_OF_MONTHS = " "
    RECIPIENT_EMAIL	 = " "
    TABLE_DELETED_SUBSCRIPTIONS = " "
    TABLE_EMAILS = " "
    TABLE_SUBSCRIPTIONS_MANAGERS = " "
    TABLE_SUBSCRIPTIONS_TO_DELETE = " "
    SUBSCRIPTION_SECRET = azurerm_key_vault_secret.key_vault_secret.name
    KEYVAULT_URI = data.azurerm_key_vault.key_vault.vault_uri
    https_only = true
    WEBSITES_ENABLE_APP_SERVICE_STORAGE = false
  } : count.index==1 ? {
    CLOUD_EMAIL = " "
    EMAILS_SECRET = " "
    HTTP_TRIGGER_URL = " "
    HTTP_TRIGGER_URL_SUBSCRIPTION_AUTOMATION = " "
    SUBSCRIPTION_SECRET = azurerm_key_vault_secret.key_vault_secret.name
    KEYVAULT_URI = data.azurerm_key_vault.key_vault.vault_uri
    TABLE_SUBSCRIPTIONS_TO_DELETE = " "
    TAG_NAME = " "
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
  count = length(var.function_app_name)
}

resource "azurerm_storage_table" "example" {
  name                 = var.table_name[count.index]
  storage_account_name = azurerm_storage_account.storage_account.name

  count = length(var.table_name)
}

data "azurerm_client_config" "current_client" {}

resource "azurerm_key_vault_access_policy" "principal" {
  key_vault_id = data.azurerm_key_vault.key_vault.id
  tenant_id    = data.azurerm_client_config.current_client.tenant_id
  object_id    = azurerm_linux_function_app.function_app[count.index].identity[0].principal_id

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
  principal_id                     = azurerm_linux_function_app.function_app[count.index].identity[0].principal_id
  role_definition_name             = "AcrPull"
  scope                            = data.azurerm_container_registry.container_registry.id
  skip_service_principal_aad_check = true
  count = length(var.function_app_name)
}
