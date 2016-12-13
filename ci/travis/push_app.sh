#!/bin/bash

if [ "$TRAVIS_PULL_REQUEST" == "false" ]; then
    docker login -u $DOCKER_USER -p $DOCKER_PASS
    export REPO=opensalt/app
    #  export TAG=`if [ "$TRAVIS_BRANCH" == "master" ]; then echo "latest"; else echo $(echo $TRAVIS_BRANCH | sed -e 's#/#-#g') ; fi`
    #  docker tag $REPO:$COMMIT $REPO:$TAG
    if [ "$TRAVIS_BRANCH" == "master" ]; then
        docker tag $REPO:$COMMIT $REPO:latest
    fi
    if [ "$TRAVIS_BRANCH" == "develop" ]; then
        docker tag $REPO:$COMMIT $REPO:develop
    fi
    docker tag $REPO:$COMMIT $REPO:travis-$TRAVIS_BUILD_NUMBER
    docker rmi $REPO:$COMMIT
    docker push $REPO
fi
