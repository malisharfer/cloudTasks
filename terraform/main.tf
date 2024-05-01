terraform {
  backend "azurerm" {
    resource_group_name      = "rg-dev"
    storage_account_name     = "rgdev9346"
    container_name           = "terraformstate"
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
    # application_stack {
    #   docker {
    #     registry_url = var.DOCKER_REGISTRY_SERVER_URL
    #     image_name = var.IMAGE_NAME
    #     image_tag = var.IMAGE_TAG
    #     registry_username = var.DOCKER_REGISTRY_SERVER_USERNAME
    #     registry_password = var.DOCKER_REGISTRY_SERVER_PASSWORD
    #   }
    # }
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
  principal_id                     = azurerm_linux_function_app.linux_function_app.identity.object_id
  role_definition_name             = "AcrPull"
  scope                            = data.azurerm_container_registry.example.id
  skip_service_principal_aad_check = true
}