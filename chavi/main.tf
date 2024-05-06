terraform {
  backend "azurerm" {
    resource_group_name      = "rg-dev"
    storage_account_name     = "rgdev9346"
    container_name           = "terraform-chavi"
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

data "azurerm_subscription" "primary" {
    subscription_id= "a273b4fb-6a3d-4804-a047-5d293da8811d"
}

data "azurerm_user_assigned_identity" "example" {
  name                = "example"
  resource_group_name = "DefaultResourceGroup-EUS"
}

resource "azurerm_role_assignment" "example" {
  principal_id                     = data.azurerm_subscription.primary.id
  role_definition_name             = "Owner"
  scope                            = data.azurerm_user_assigned_identity.example.id
  skip_service_principal_aad_check = true
}
