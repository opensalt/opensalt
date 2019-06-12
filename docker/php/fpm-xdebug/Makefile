NAME   := opensalt/php
TAG    := $$(git log -1 --pretty=%h)
IMG    := ${NAME}:${TAG}-fpm-xdebug
LATEST := ${NAME}:latest-fpm-xdebug
 
.PHONY: build push login
build:
	@docker build --pull -t ${IMG} .
	@docker tag ${IMG} ${LATEST}
 
push:
	@docker push ${NAME}
 
login:
	@docker log -u ${DOCKER_USER} -p ${DOCKER_PASS}
