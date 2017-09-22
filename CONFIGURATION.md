OpenSALT Configuration
======================

OpenSALT is intended to be configured using environment variables that can be passed into the docker container.

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

A couple secrets are required for creating secure tokens

 - SECRET - Should be a long random string
 - COOKIE_SECRET - Should be a different long random string

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
