Deploying to Heroku
===================

Follow these steps in order to deploy the application to heroku.

Buildpacks
---------

The application need two buildpacks, the buildpack for PHP and another one for the NodeJs.

__Set PHP buildpack__

```
 heroku buildpacks:set heroku/php
```

__Add Node buildpack__

```
heroku buildpacks:add heroku/nodejs
```

Procfile
------------
You can copy the Procfile located in `/deploying/heroku/Procfile` to the root directory or let heroku make one for you.

Package.json
------------

Copy the package.json file located in `/docker/encore/node/package.json` to root directory, in order to make Heroku understand the app as a Nodejs app.

Composer.json
--------------------

Add `ext-apcu` to `composer.json` file: `"ext-apcu": "*"`.

Assets
----------

The assets needs to be compiled before pushing to heroku, to do that you need to follow the next steps: 

- remove `/web/build/*` from `.gitignore` file.
- run the encore command: `./bin/encore production`.
- commit the changes.

TravisCI
------------
In order to config automated deploys for an Heroku app using TravisCI, you need to setup the `OPENSALT_HEROKU_DEPLOY=true` environment variable and appropriate values for the next variables:
```
OPENSALT_GIT_USER_MAIL
OPENSALT_GIT_COMMIT_MSG
OPENSALT_GIT_USER_NAME
OPENSALT_HEROKU_APP
OPENSALT_HEROKU_TOKEN
```
You can check the `OPENSALT_HEROKU_TOKEN` with: `heroku auth:token`.

