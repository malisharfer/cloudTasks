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

variable rg_name{
  type    = string
  default = "rg-manage-subscrioptions-dev"
}

variable rg_location {
  type    = string
  default = "West Europe"
}

variable storage_account_name {
  type    = string
  default = "stsubscritionmanagmentdev"
}

variable service_plan_name{
  type    = list(string)
  default = ["app-subscriptions-automation-dev","app-subscriptions-list-dev"]
}

variable function_app_name {
  type    = list(string)
  default = ["func-subscriptions-automation-dev" , "func-subscriptions-list-dev"]
}

variable logic_app_workflow_name {
  type    = string
  default = "logic-app-subscription-management-dev"
}

variable key_vault_secret_name {
  type    = string
  default = "SUBSCRIPTION-SECRET-DEV"
}

variable table_name {
  type    = list(string)
  default = ["deletedSubscriptionsdev","subscriptionsToDeletedev","emailsdev"]
}

variable IMAGE_NAME {
  type    = list(string)
  default = ["services/subscriptions/func_subscriptions_automation" , "services/subscriptions/func_subscriptions_list"]
}

variable IMAGE_TAG {
  type    = string
  default = "1.0.0"
}
