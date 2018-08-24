#!/bin/bash

# Get to main project directory
cd $(dirname $0)/..

cp docker/.env.dist docker/.env
ln -sf docker/.env ./.env
ln -sf docker-compose.dev.yml docker/docker-compose.yml

# Replace tokens with random values
TOKEN=$(openssl rand -base64 33)
sed -i '' "s#ThisTokenIsNotSoSecretSoChangeIt#${TOKEN}#" docker/.env
TOKEN=$(openssl rand -base64 33)
sed -i '' "s#ThisTokenIsNotSoSecretChangeIt#${TOKEN}#" docker/.env


# Start docker containers
make up

# Install libraries, create css and js files, and setup database
touch -c composer.lock yarn.lock
make update

# Add an initial super user
./bin/console salt:user:add admin Unknown --password=secret --role=super-user

echo 'You should now be able to connect to http://127.0.0.1:3000'
echo 'Log in with initial user "admin" with password "secret"'
