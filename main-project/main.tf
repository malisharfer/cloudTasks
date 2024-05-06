terraform {
  backend "azurerm" {
    resource_group_name      = "rg-dev"
    storage_account_name     = "rgdev9346"
    container_name           = "terraform-dev"
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

module administrator {
  source = "../administrator/"
}

module emails{
    source = "../emails/"
    key_vault_name = module.administrator.key_vault_name
    key_vault_resource_group_name = module.administrator.key_vault_resource_group_name
    acr_name = var.acr_name
    REGISTRY_URL = "https://${var.acr_name}.azurecr.io"
    depends_on = [
      module.administrator
    ]
}

module subscriptions {
    source = "../subscriptions-automation/"
    key_vault_name = module.administrator.key_vault_name
    key_vault_resource_group_name = module.administrator.key_vault_resource_group_name
    acr_name = var.acr_name
    REGISTRY_URL = "https://${var.acr_name}.azurecr.io"
    depends_on = [
      module.emails
    ]
}

module storages{
  source = "../storages/"
  key_vault_name = module.administrator.key_vault_name
  key_vault_resource_group_name = module.administrator.key_vault_resource_group_name
  key_vault_secret_excel_name = module.administrator.secret_administrators_name
  acr_name = var.acr_name
  REGISTRY_URL = "https://${var.acr_name}.azurecr.io"
  depends_on = [
    module.emails
  ]
}

module user-disable{
  source = "../user-disable/"
  key_vault_name = module.administrator.key_vault_name
  key_vault_resource_group_name = module.administrator.key_vault_resource_group_name
  acr_name = var.acr_name
  REGISTRY_URL = "https://${var.acr_name}.azurecr.io"
  depends_on = [
      module.administrator
  ]
}
