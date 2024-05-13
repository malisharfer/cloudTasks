output key_vault_name {
  value = azurerm_key_vault.key_vault.name
}

output key_vault_resource_group_name {
  value = azurerm_key_vault.key_vault.resource_group_name
}

output secret_administrators_name {
  value = azurerm_key_vault_secret.key_vault_secret.name
}
