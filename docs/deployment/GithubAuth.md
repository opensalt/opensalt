# GitHub Authentication Config

To setup the GitHub authentication config we need to provide the
appropriate GitHub keys to the application.


#### 1. Obtain access keys

You need have your GitHub Access Keys (`client_id` and `client_secret`).
If you don't have them you can get them [here](https://github.com/settings/applications/new).

You can use `https://domainapp.com/login/check-github`
or just `https://domainapp.com` as the *Authorization callback URL*.


#### 2. Setup OpenSALT with GitHub access

There are two options on how to add the GitHub access keys:

  1. If using _docker_ then add the GitHub keys to the appropriate
     variables in `docker/.env`.

  `.env` will looks like this:

  ```
  ...
  GITHUB_CLIENT_ID=01234567890123456789
  GITHUB_CLIENT_SECRET=0123456789abcdefghijklmnopqrstuv
  ```

  2. If not using _docker_ then add the GitHub keys to the
     appropriate parameters in `config/app/config/parameters.yml`.

  `parameters.yml` will looks like this:

  ```
  ...
  env(GITHUB_CLIENT_ID): 01234567890123456789
  env(GITHUB_CLIENT_SECRET): 0123456789abcdefghijklmnopqrstuv
  ```
