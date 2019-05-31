Standards Alignment Tool (SALT)
===============================

[![Latest Stable Version](https://poser.pugx.org/opensalt/opensalt/v/stable)](https://github.com/opensalt/opensalt) [![Build Status](https://travis-ci.org/opensalt/opensalt.svg?branch=develop)](https://travis-ci.org/opensalt/opensalt) [![StyleCI](https://styleci.io/repos/72233269/shield?style=flat&branch=develop)](https://styleci.io/repos/72233269) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/opensalt/opensalt/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/opensalt/opensalt/?branch=develop) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/e4aee568-15d9-4d97-944f-fb742bb9e885/mini.png)](https://insight.sensiolabs.com/projects/e4aee568-15d9-4d97-944f-fb742bb9e885)


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
  - [Docker for Mac notes](./docs/DOCKER_FOR_MAC.md)

  > **Note: the rest of the following can be automated by running `./local-dev/initial_dev_install.sh`**

  > Once the application is running:
  > To create an organization use `./bin/console salt:org:add [organization name]`
  > To create a user use `./bin/console salt:user:add [username] [--password="secret"] [--role="rolename"]`
  > > The *initial_dev_install.sh* command creates an initial super admin "admin" with password "secret"

  > `./bin/build.sh` also does much of the following, for doing a "build" after one has started development

2. Create env file and docker-compose file
  ```
  cp docker/.env.dist docker/.env
  ln -s docker/.env .env

  ln -s docker-compose.dev.yml docker/docker-compose.yml
  ```

3. Edit docker/.env and set desired values
  - The `PORT` specified is what is used in step 7 below

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
  * Linux users should note that a new user group, `docker`, has been created. The user that will interact with the Docker service will need to be in this group.
  * Linux users also set the MySQL folder permissions: `chmod -R 777 docker/data/mysql`
  * Linux users should set the cache directory permssions: `chmod 777 var/cache`


6. Run database migrations
  ```
  make migrate
  ```

7. [http://127.0.0.1:3000/app_dev.php/](http://127.0.0.1:3000/app_dev.php/) should show the initial screen with debug turned on
  - Note that the port here should be the value of `PORT` in the `.env` file (default being 3000)

8. If you have run these manual tasks, you will also need to create the administrative account and password for the system:
    ```
    ./bin/console salt:user:add admin Unknown --password=secret --role=super-user
    ```


Other Docs
----------

- [User Management Commands](./docs/Commands.md)
- [Github Authentication Config](./docs/deployment/GithubAuth.md)
