# Github Authentication Config

To get a clear github authentication config we need a set of steps to complete it efficiently.

#### 1. Access keys
You need have your Github Access Keys (`client_id` and `client_secret`). If you don't have them you can get them [here](https://github.com/settings/applications/new).

#### 2. Setup OpenSalt
Add github keys on `config/app/config/parameters.yml`.

Replace values of keys `github_client_id`, `github_client_secret`. `parameters.yml` will looks like this:


```
...
github_client_id: 01234567890123456789
github_client_secret: 0123456789abcdefghijklmnopqrstuv
```
