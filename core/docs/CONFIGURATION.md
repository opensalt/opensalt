OpenSALT Configuration
======================

OpenSALT is intended to be configured using environment variables that can be passed into the docker container via the `docker/.env` file.

MySQL configuration
-------------------
To set the location of the MySQL database use the environment variables

 - MYSQL_HOST - Hostname to connect to the database
 - MYSQL_PORT - *(optional)* Port number to use to connect to the database
 - MYSQL_DATABASE - Name of the database schema
 - MYSQL_USER - Username used to connect to the database
 - MYSQL_PASSWORD - Password used to connect to the database

Secrets configuration
---------------------

A secret is required for creating secure tokens in the application

 - APP_SECRET - Should be a long random string

Branding configuration
----------------------

OpenSALT can have some branding associated with it

 - BRAND_LOGO_URL - *(optional)* URL to the logo shown to the right of the OpenSALT logo
 - BRAND_LINK_URL - *(optional)* URL that the brand logo will use when clicked
 - BRAND_LOGO_STYLE - *(optional)* An embedded style that will be added to the **img** tag of the logo
 - BRAND_LINK_STYLE - *(optional)* An embedded style that will be added ot the **a** tag wrapping the logo

Optional Features
-----------------------

### Commenting

OpenSALT uses the http://viima.github.io/jquery-comments/ bundle to allow editors to comment on and upvote published frameworks
  - COMMENTS_FEATURE - *(optional)* set to **always-active** to enable, the default is **inactive**

### Mail Service/Self-Service Add a User
------------------------------------
OpenSALT can allow users to self-create an organization and acccount, then email them when that account has been authorized by a super-user of the site on the User management page /admin/user/ where an option to Approve will appear. By default accounts created in this way are suspended upon creation, and cannot Comment until approved.

 - CREATE_ACCOUNT_FEATURE - always-active or leave empty (turns on "create account" text on public /cfdoc
 - USE_MAIL_FEATURE - always-active or leave empty
 - MAILER_DSN - mailer configuration, ex: smtp://user:pass@smtp.example.com:25
OpenSALT instance with this feature active: http://frameworks.act.org 

### Realtime notifications

OpenSALT can use Mercure realtime database to update editors in real-time.

### Additional Fields

For the purpose of increasing presentation appeal, OpenSALT allows for additional fields in release 2.2. To add additional fields to CfItem, navigate to /additionalfield as a logged in SuperUser and add a field with LsItem in the dropdown.

Any additional fields will automatically be added to the spreadsheet export file and be read by the Spreadsheet Update feature. As of OpenSALT 2.2 additional fields for LsDoc and LsAssociation are not supported on the UI or backend. 
