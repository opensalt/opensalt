Standards Alignment Tool (SALT)
===============================


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

2. Create env file
  ```
  cp docker/.env.dist to docker/.env
  ```
  
3. Install components with composer
  ```
  ./bin/composer install
  ```
  *When asked, leave everything as their defaults except for the two secret keys*
  
4. Install the node packages
  ```
  ./bin/npm install
  ```
  
5. Run Gulp
  ```
  ./bin/gulp
  ```
  
6. Run database migrations
  ```
  ./bin/console-docker doctrine:migrations:migrate --no-interaction
  ```

7. Add a port to the nginx config in `docker/docker-compose.yml` change "80" to something like "3000:80" if you want use port :3000

8. Run the app
  ```
  cd docker; docker-compose up -d
  ```
    * Stop app

    ```
    cd docker; docker-compose down -v
    ```

9. [http://localhost:3000/app_dev.php/](http://localhost:3000/app_dev.php/) should show the initial screen with debug turned on
