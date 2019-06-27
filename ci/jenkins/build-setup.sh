#!/bin/bash -x

cd $(dirname $0)/../..

# Create an .env file at the root level
ln -sf docker/.env .env

cd docker

# Setup the .env file
if [ -f .env ]; then
    if [ ! -f .env,orig ]; then
        mv .env .env,orig
    fi
fi

#Setup .env file for test config
cp -f .env.dist .env

PROJ_DIR=${PROJ_DIR:-$(pwd)}
if [ -n "$JENKINS_HOME" -a -n "$WORKSPACE" ]; then
    PROJ_DIR=$WORKSPACE
fi
if grep '^PROJ_DIR' .env ; then
	sed -i "s#^PROJ_DIR.*#PROJ_DIR=${PROJ_DIR}#" .env
else
	echo "PROJ_DIR=${PROJ_DIR}" >> .env
fi

DOCKER_PROJECT=${DOCKER_PROJECT:-salttest}
sed -i "s/^COMPOSE_PROJECT_NAME.*/COMPOSE_PROJECT_NAME=${DOCKER_PROJECT}/" .env


# Use the test-local docker-compose file
if [ ! -f docker-compose.yml,orig ]; then
    cp docker-compose.yml docker-compose.yml,orig
fi
cp -f docker-compose.test-local.yml docker-compose.yml
