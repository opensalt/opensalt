Standards Alignment Tool (SALT)
===============================
[![Build Status](https://travis-ci.org/opensalt/opensalt.svg?branch=develop)](https://travis-ci.org/opensalt/opensalt)


Overview
--------

This is a prototype for testing the IMS Global Competency Task Force
specification (that is currently under development) and proving its use
in real-world scenarios based on various proof of concept and pilot projects.

The code is intended to run using a set of docker containers using
docker-compose so that it can be easily deployed in any Linux environment
with docker installed.

Installation
------------

1. Install Docker from [here](https://www.docker.com/products/docker)
   and Docker Compose from [here](https://docs.docker.com/compose/install/)
  - [Docker for Mac notes](./DOCKER_FOR_MAC.md)

  > **Note: the rest of the following can be automated by running `./local-dev/initial_dev_install.sh`**

  > To create a user use `./bin/console salt:user:add [username] [--password="secret"] [--role="rolename"]`
  > > The *initial_dev_install.sh* command creates an initial super admin "admin" with password "secret"

  > `./bin/build.sh` also does much of the following, for doing a "build" after one has started development

2. Create env file
  ```
  cp docker/.env.dist to docker/.env
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
