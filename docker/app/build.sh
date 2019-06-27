#!/bin/bash
set -e

if [ "x$COMMIT" == "x" ]; then
  export COMMIT=$(date "+%Y%m%d%H%M")
fi

cd $(dirname $0)/

mkdir -p ../../var/{cache,logs,sessions}
chmod 777 ../../var/{cache,logs,sessions}
tar cf app.tar \
	--exclude-vcs --exclude='var/*/*' \
	-C ../.. \
	assets bin config public src templates translations var vendor web \
	composer.json composer.lock symfony.lock \
	package.json webpack.config.js yarn.lock \
	LICENSE README.md
docker build \
	--build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
	--build-arg VERSION=$(cat ../../VERSION) \
	--build-arg VCS_URL=$(git config --get remote.origin.url) \
	--build-arg VCS_REF=$COMMIT \
	-t opensalt/app:$COMMIT .
rm -rf app.tar
