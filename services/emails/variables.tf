variable key_vault_name {
  type = string
}

variable key_vault_resource_group_name{
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
  default = "rg-emails-dev"
}

variable rg_location {
  type    = string
  default = "West Europe"
}

variable storage_account_name {
  type    = string
  default = "stemailsdev"
}

variable key_vault_secret_name {
  type    = string
  default = "EMAILS-SECRET-DEV"
}

variable service_plan_name{
  type    = string
  default = "app-emails-dev111"
}

variable function_app_name {
  type    = string
  default = "func-emails-dev111"
}

variable IMAGE_NAME {
  type    = string
  default = "services/emails/func_emails"
}

variable IMAGE_TAG {
  type    = string
  default = "1.0.0"
}
