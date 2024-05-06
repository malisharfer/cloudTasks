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

data "azurerm_client_config" "current_client" {}

resource "azurerm_key_vault" "key_vault" {
  name                       = var.key_vault_name
  location                   = var.rg_location
  resource_group_name        = azurerm_storage_account.storage_account.resource_group_name
  soft_delete_retention_days = 7
  tenant_id                  = data.azurerm_client_config.current_client.tenant_id
  sku_name                   = var.key_vault_sku_name

  access_policy {
    tenant_id = data.azurerm_client_config.current_client.tenant_id
    object_id = data.azurerm_client_config.current_client.object_id
    certificate_permissions = var.key_vault_certificate_permissions
    key_permissions = var.key_vault_key_permissions
    secret_permissions = var.key_vault_secret_permissions
    storage_permissions = var.key_vault_storage_permissions
  }
}

resource "azurerm_key_vault_secret" "key_vault_secret" {
  name         = var.key_vault_secret_name
  value        = azurerm_storage_account.storage_account.primary_connection_string
  key_vault_id = azurerm_key_vault.key_vault.id
}

resource "azurerm_storage_table" "storage_table" {
  name                 = var.table_name
  storage_account_name = azurerm_storage_account.storage_account.name

  depends_on = [
    azurerm_storage_account.storage_account
 ]
}
