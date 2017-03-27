#!/usr/bin/env bash

if [ "${OPENSALT_HEROKU_DEPLOY}" == "true" ]; then
    git config --global user.email "${OPENSALT_GIT_USER_MAIL}"
    git config --global user.name "${OPENSALT_GIT_USER_NAME}"
    cp -v ./deploying/heroku/package.json .
    cp -v ./deploying/heroku/Procfile .
    sed -i 's/^\/package.json$/\#\/package.json/g' .gitignore
    git add package.json Procfile .gitignore
    git commit -m "${OPENSALT_GIT_COMMIT_MSG}"
fi
