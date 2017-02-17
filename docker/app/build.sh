#!/bin/bash
set -e

if [ "x$COMMIT" == "x" ]; then
  export COMMIT=$(date "+%Y%m%d%H%M")
fi

cd $(dirname $0)/

mkdir -p ../../var/{cache,logs,sessions}
chmod 777 ../../var/{cache,logs,sessions}
tar cf app.tar --exclude-vcs --exclude='var/*/*' -C ../.. app bin src var vendor web composer.json composer.lock LICENSE README.md
docker build -t opensalt/app:$COMMIT .
rm -rf app.tar
