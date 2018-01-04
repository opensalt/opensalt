Development Notes
=================

## Using the `app_dev.php` front controller

In order to use `/app_dev.php/...` you will need to ensure that the appropriate environment variable(s) are set when starting the docker containers (in the `docker/docker-compose.yml` or the `docker/.env` files).

* **ALLOW_LOCAL_DEV** - set to allow using the development front controller from a non-routable IP address.

* **ALLOW_EXTERNAL_DEV_IPS** - set to a comma separated list of IP addresses to allow those addresses to have access to the development front controller.

* **DEV_COOKIE** - set to a secret value, then as a super user you can go to `/dev/cookie` on the site and a cookie will be set on the browser.  As long as the cookie is passed with the correct value the browser will be able to access the development front controller.
