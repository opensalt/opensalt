Docker for Windows notes
------------------------

[Docker for Windows](https://docs.docker.com/docker-for-windows/) is one of the tools that can be used on a Windows computer to run docker containers in order to do development.

The biggest issue with running OpenSALT with Docker for Windows is that MySQL has trouble accessing un-named volumes. If you use the automatic installation script, you will see an error like this during the migrate step:
```
SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: No address associated with hostname
```

If you follow the manual installation steps, the "cftf_db_1" container will not start and running `docker logs cftf_db_1` will show:
```
[ERROR] InnoDB: Write to file /var/lib/mysql/xb_doublewritefailed at offset 0
```

The solution is to add a global volume to docker/docker-compose.yml and replace the un-named data volume with a named volume like this:
```
services:
  db:
    image: percona:${MYSQL_VERSION}
    volumes:
#     - "${PROJ_DIR:-..}/docker/data/mysql:/var/lib/mysql"
      - mysql-data:/var/lib/mysql
      - "${PROJ_DIR:-..}/docker/mysql/max_allowed_packet.cnf:/etc/mysql/conf.d/max_allowed_packet.cnf:ro"
      - "${PROJ_DIR:-..}/docker/mysql/charset.cnf:/etc/mysql/conf.d/charset.cnf:ro"
    environment:
      MYSQL_ROOT_PASSWORD:
      MYSQL_DATABASE:
      MYSQL_USER:
      MYSQL_PASSWORD:

volumes:
  mysql-data:
```
