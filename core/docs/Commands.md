User Management Commands
-----------------------

A set of commands can be run from the command line in order to manage
local users in the system.  If arguments are missing then the command
will prompt for the values.

- **salt:group:add**

  This command will create an access group in the database.

  `./core/bin/console salt:group:add [--no-interaction] [<group name>]`

- **salt:user:add**

  This command will create a local user in the database.

  `./core/bin/console salt:user:add [--no-interaction] [--password=<password>] [--role=<role>] [<username>]`

- **salt:user:add-role**

  This command will add a role to a local user in the database.

  `./core/bin/console salt:user:add-role [<username>] [<role>]`

- **salt:user:remove-role**

  This command will remove a role from a local user in the database.

  `./core/bin/console salt:user:remove-role [<username>] [<role>]`

- **salt:user:set-password**

  This command will set the password for a local user in the database.
  If a password is not provided then one will be generated.

  `./core/bin/console salt:user:set-password [<username>] [<password>]`
