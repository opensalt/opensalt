#!/bin/bash

echo "--------------------------------------------------------------------"
echo "Logs from phpfpm"
echo
docker-compose -f docker/docker-compose.yml logs phpfpm > tests/_output/phpfpm.txt
cat tests/_output/phpfpm.txt
echo "--------------------------------------------------------------------"

if [ "$DOCKER_USER" = "" -o "$DOCKER_PASS" = "" ]; then
    exit
fi

cp docker/errors/Dockerfile tests/_output/
cd tests/_output
docker build . -t opensalt/app:errors-${TRAVIS_BUILD_NUMBER}
cd ../..

docker tag opensalt/app:$COMMIT opensalt/app:fail-${TRAVIS_BUILD_NUMBER}

docker login -u $DOCKER_USER -p $DOCKER_PASS
docker push opensalt/app:errors-${TRAVIS_BUILD_NUMBER}
docker push opensalt/app:fail-${TRAVIS_BUILD_NUMBER}

echo
echo "Pushed:"
echo "  opensalt/app:errors-${TRAVIS_BUILD_NUMBER}"
echo "  opensalt/app:fail-${TRAVIS_BUILD_NUMBER}"
echo "--------------------------------------------------------------------"
