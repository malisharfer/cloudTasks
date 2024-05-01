terraform {
  backend "azurerm" {
    resource_group_name      = "rg-dev"
    storage_account_name     = "rgdev9346"
    container_name           = "terraform"
    key                      = "terraform.tfstate"
  }
}

provider "azurerm" {
  features {
    resource_group {
      prevent_deletion_if_contains_resources = false
    }
  }

  subscription_id = var.subscription_id
}


data "azurerm_storage_account" "storage_account"{
  name = "rgdev9346"
  resource_group_name = "rg-dev"
}

resource "azurerm_service_plan" "service_plan" {
  name                = "service-plan-1"
  location            = data.azurerm_storage_account.storage_account.location
  resource_group_name = data.azurerm_storage_account.storage_account.resource_group_name
  os_type             = "Linux"
  sku_name            = "P1v2"
}

resource "azurerm_linux_function_app" "linux_function_app" {
  name                        = "func-try-deploy-acr"
  location                    = data.azurerm_storage_account.storage_account.location
  resource_group_name         = data.azurerm_storage_account.storage_account.resource_group_name
  service_plan_id             = azurerm_service_plan.service_plan.id
  storage_account_name        = data.azurerm_storage_account.storage_account.name
  storage_account_access_key  = data.azurerm_storage_account.storage_account.primary_access_key
  functions_extension_version = "~4"

  app_settings = {
    FUNCTIONS_WORKER_RUNTIME = "python"
    WEBSITES_ENABLE_APP_SERVICE_STORAGE = false
  }

  site_config {
    always_on = true
    application_stack {
      docker {
        registry_url = "containerregistryautomationdev.azurecr.io"
        image_name = "services/emails/func_emails"
        image_tag = "b57fb37c99bd68d1488b979d06bebbe92182c7aa"
      }
    }

  }

  identity {
    type = "SystemAssigned"
  }
}

data "azurerm_client_config" "current_client" {}

data "azurerm_container_registry" "example" {
  name                = "containerRegistryAutomationDev"
  resource_group_name = data.azurerm_storage_account.storage_account.resource_group_name
}

resource "azurerm_role_assignment" "example" {
  # object_id    = azurerm_linux_function_app.linux_function_app.identity.principal_id
  principal_id                     = azurerm_linux_function_app.linux_function_app.identity[0].principal_id
  role_definition_name             = "AcrPull"
  scope                            = data.azurerm_container_registry.example.id
  skip_service_principal_aad_check = true
}

