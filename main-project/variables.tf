
variable subscription_id {
  type = string
}

variable acr_name {
  type = string
  default = "containerRegistryAutomationDev"
}

variable registry_url {
  type = string
  default = "https://containerregistryautomationdev.azurecr.io"
}
