#!/bin/sh

ln -s /build/node_modules /app
cd /app
./node_modules/gulp/bin/gulp.js "$@"
rm -f node_modules
