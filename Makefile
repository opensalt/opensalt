COMMIT="$(shell git describe --always --match='x' --dirty=-x 2>/dev/null || date "+%Y%m%d%H%M")"
TAG="$(shell git describe --always --match='[0-9].[0-9]*' --dirty=-x 2>/dev/null || date "+%Y%m%d%H%M")"
PROJ_DIR ?= $(shell pwd)

default:
	@echo "You need to supply an argument to make"
.PHONY: default


# Docker commands
docker-start:
	cd docker && \
		docker-compose pull && \
		docker-compose up -d
up: docker-start
.PHONY: docker-start up

docker-stop:
	cd docker && \
		docker-compose down -v
down: docker-stop
.PHONY: docker-stop down

docker-restart: docker-stop docker-start
restart: docker-restart
.PHONY: docker-restart restart

docker-build:
	docker build \
		--build-arg BUILD_DATE="$(shell date -u +"%Y-%m-%dT%H:%M:%SZ")" \
		--build-arg VERSION="$(shell cat VERSION)" \
		--build-arg VCS_URL="$(shell git config --get remote.origin.url)" \
		--build-arg VCS_REF=$(COMMIT) \
		-t opensalt/opensalt:$(TAG) .
	docker rmi opensalt/opensalt:$(TAG)
image: docker-build
.PHONY: docker-build image


# Cache commands
cache-clear:
	rm -rf var/cache/{dev,test,prod}/*
cc: cache-clear
.PHONY: cache-clear cc

cache-warmup: cache-clear
	bin/console cache:warmup --env=prod
.PHONY: cache-warmup

# Composer commands
vendor: composer.json composer.lock
	bin/composer install --no-interaction
composer-install: vendor
.PHONY: composer-install


# Encore commands
js: encore cache-clear
.PHONY: js

encore: yarn-install
	bin/node ./node_modules/.bin/encore production
encore-dev: yarn-install
	bin/node ./node_modules/.bin/encore dev
encore-build: encore
.PHONY: encore encore-dev encore-build

node_modules: yarn.lock package.json
	bin/yarn install --non-interactive
	touch node_modules
yarn-install: node_modules
.PHONY: yarn-install

# Install and build commands
assets-install:
	bin/console assets:install public --symlink --relative
.PHONY: assets-install

build: vendor encore-build assets-install cache-clear
.PHONY: build

force-vendor:
	touch -c composer.lock
.PHONY: force-vendor

force-node-modules:
	touch -c yarn.lock
.PHONY: force-node-modules

force-build: force-vendor force-node-modules build
.PHONY: force-build

update: force-build migrate
.PHONY: update

install: cache-clear force-build
.PHONY: install

# DB commands
migrate:
	bin/console doctrine:migrations:migrate --no-interaction
.PHONY: migrate


# Clean
clean: cache-clear
	rm -rf build/* public/build/*
.PHONY: clean
