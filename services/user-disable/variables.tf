variable acr_name {
  type = string
}

variable REGISTRY_URL {
  type = string
}

variable key_vault_name {
  type = string
}

variable key_vault_resource_group_name {
  type = string
}

variable acr_resource_group_name {
  type = string
}

variable rg_name {
  type    = string
  default = "rg-user-disable-dev"
}

variable rg_location {
  type    = string
  default = "West Europe"
}

variable storage_account_name {
  type    = string
  default = "stuserdisabledev"
}

variable app_service_plan_name{
  type    = string
  default = "app-user-disable-dev"
}

variable function_app_name {
  type    = string
  default = "func-user-disable-dev"
}

variable IMAGE_NAME {
  type    = string
  default = "services/users/user_disable_automation"
}

variable IMAGE_TAG {
  type    = string
  default = "1.0.0"
}