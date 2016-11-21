#!/bin/bash

# Get to main project directory
cd $(dirname $0)/..

cp docker/.env.dist docker/.env

# Install php libraries
./bin/composer --no-interaction install

# Replace tokens with random values
TOKEN1=$(openssl rand -base64 32)
TOKEN2=$(openssl rand -base64 32)
sed -i '' "s#ThisTokenIsNotSoSecretSoChangeIt#${TOKEN1}#" app/config/parameters.yml
sed -i '' "s#ThisTokenIsNotSoSecretChangeIt#${TOKEN2}#" app/config/parameters.yml

# Set secure_cookie to false to allow http connections
sed -i '' "s#secure_cookie:.*#secure_cookie: false#" app/config/parameters.yml

# Install node libraries for gulp
./bin/npm install
# Run gulp to create css and js files
./bin/gulp

# Start docker containers
cd docker
docker-compose up -d
cd ..

# Do database migrations
./bin/console-docker doctrine:migrations:migrate --no-interaction

echo 'You should now be able to connect to http://127.0.0.1:3000'
