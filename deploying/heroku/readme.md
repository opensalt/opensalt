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

Package.json
------------

Copy the package.json file located in `/deploying/heroku/package.json` to root directory, in order to make Heroku understand the app as a Nodejs app.

The package.json need to run the gulp command to run the assets:

```
"scripts": {
  "postinstall": "gulp"
}
```

Procfile
------------
You can copy the Procfile located in `/deploying/heroku/Procfile` to the root directory or let heroku make one for you.
