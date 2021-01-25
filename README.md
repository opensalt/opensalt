Standards Alignment Tool (SALT)
===============================

[![Latest Stable Version](https://poser.pugx.org/opensalt/opensalt/v/stable)](https://github.com/opensalt/opensalt) [![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=opensalt_opensalt&metric=alert_status)](https://sonarcloud.io/dashboard?id=opensalt_opensalt) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/opensalt/opensalt/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/opensalt/opensalt/?branch=develop) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/e4aee568-15d9-4d97-944f-fb742bb9e885/mini.png)](https://insight.sensiolabs.com/projects/e4aee568-15d9-4d97-944f-fb742bb9e885)


Overview
--------

This is a prototype for testing the IMS Global Learning Consortium® [CASE™ Specification](https://www.imsglobal.org/case) and proving its use
in real-world scenarios based on various proof of concept and pilot projects.

The code is intended to run using a set of docker containers using
docker-compose so that it can be easily deployed in any Linux environment
with docker installed.


Installation
------------

1. Install Docker from [here](https://www.docker.com/products/docker)
   and Docker Compose from [here](https://docs.docker.com/compose/install/)
  * Note that a new user group, `docker`, has been created. The user that will interact with the Docker service will need to be in this group.
  * Ensure to set the MySQL folder permissions: `chmod -R 777 core/docker/data/mysql`
  * Also set the cache directory permssions: `chmod 777 core/var/cache`

2. Create .env file
  ```
  cp .env.dist .env
  ```

3. Edit .env and set desired values (optional)

4. Start the application
  ```
  make up
  ```
    * To stop the application

    ```
    make down
    ```

5. Install libraries with composer/yarn and build application
  ```
  make force-build
  ```

6. Run database migrations
  ```
  make migrate
  ```

7. http://_ip-address of web container_/ should show the initial screen with debug turned on
  - 

8. One will also need to create the administrative account and password for the system with the **super-user** role:
  - To create an organization use `./core/bin/console salt:org:add [organization name]`
  - To create a user use `./core/bin/console salt:user:add [username] [--password="secret"] [--role="rolename"]`


Other Docs
----------

- [User Management Commands](./core/docs/Commands.md)
