#!/bin/sh

if [ -e /app/node_modules ]; then
  rm -rf /app/node_modules
fi
ln -s /build/node_modules /app/
ln -s /build/package.json /app/
cd /app
./node_modules/gulp/bin/gulp.js "$@"
rm -f node_modules package.json
