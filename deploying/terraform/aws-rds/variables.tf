variable "identifier" {
  default     = "opensalt-dev"
  description = "Identifier for your DB"
}

variable "storage" {
  default     = "30"
  description = "Storage size in GB"
}

variable "storage_type" {
  default     = "gp2"
  description = "Storage type of the instance"
}

variable "engine" {
  default     = "mysql"
  description = "Engine type, example values mysql, postgres"
}

variable "engine_version" {
  description = "Engine version"

  default = {
    mysql = "5.7.16"
  }
}

variable "instance_class" {
  default     = "db.t2.micro"
  description = "Instance class"
}

variable "db_name" {
  default     = "opensalt"
  description = "db name"
}

variable "username" {
  default     = "opensalt"
  description = "User name"
}

variable "password" {
  description = "password, provide through your ENV variables"
}

variable "publicly_accessible" {
  default     = true
  description = "Whether or not instance is publicly accessible"
}
