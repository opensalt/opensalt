Upgrade requirements
--------------------

* For 3.2 the MySQL version has been upgraded from 5.7 to 8.0.
  * This change requires the data directory to have its ownership changed
    from uid/gid 999 to uid/gid 1001 based on the upstream docker images.
  * The following command can be used before starting the new version
    * `docker-compose run --rm -u root db chown -R mysql:mysql /var/lib/mysql`
* Upgrading to Mercure 0.14 requires the MERCURE_JWT_TOKEN to have _publish: ["*"]_ instead of just _publish: []_ in order to publish to all topics
