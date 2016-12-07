#!/bin/bash

mkdir -p var/{cache,logs,sessions}
chmod 777 var/{cache,logs,sessions}
tar cf app.tar --exclude=.git -C ../.. app bin src vendor web composer.json composer.lock LICENSE README.md
tar rf app.tar var
rm -rf var app.tar
