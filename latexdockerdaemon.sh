#!/bin/sh
IMAGE=blang/latex:ubuntu
exec docker run -d --rm --name latex_daemon -i --user="$(id -u):$(id -g)" --net=none -t -v /var/www/html/var/tmp:/data "$IMAGE" /bin/sh -c "sleep infinity"
