NAME   := opensalt/php
TAG    := $$(git log -1 --pretty=%h)
IMG    := ${NAME}:${TAG}-cli
LATEST := ${NAME}:latest-cli
 
.PHONY: build push login
build:
	@docker build -t ${IMG} .
	@docker tag ${IMG} ${LATEST}
 
push:
	@docker push ${NAME}
 
login:
	@docker log -u ${DOCKER_USER} -p ${DOCKER_PASS}
