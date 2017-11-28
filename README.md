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

  > To create an organization use `./bin/console salt:org:add [organization name]`
  > To create a user use `./bin/console salt:user:add [username] [--password="secret"] [--role="rolename"]`
  > > The *initial_dev_install.sh* command creates an initial super admin "admin" with password "secret"

  > `./bin/build.sh` also does much of the following, for doing a "build" after one has started development

2. Create env file
  ```
  cp docker/.env.dist docker/.env
  ```

3. Install components with composer
  ```
  ./bin/composer install
  ```
  *When asked, leave everything as their defaults except for the secret keys*

4. Run Gulp
  ```
  ./bin/gulp
  ```

5. Run database migrations
  ```
  ./bin/console-docker doctrine:migrations:migrate --no-interaction
  ```

6. Add a port to the nginx config in `docker/docker-compose.yml` change "80" to something like "3000:80" if you want use port :3000

7. Run the app
  ```
  cd docker; docker-compose up -d
  ```
    * Stop app

    ```
    cd docker; docker-compose down -v
    ```

8. [http://127.0.0.1:3000/app_dev.php/](http://127.0.0.1:3000/app_dev.php/) should show the initial screen with debug turned on


Other Docs
----------

- [User Management Commands](./src/Salt/UserBundle/Resources/doc/Commands.md)
- [Github Authentication Config](./src/GithubFilesBundle/Resources/doc/GithubAuth.md)
