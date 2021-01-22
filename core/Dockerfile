# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

ARG NODE_VERSION=12
ARG PHP_VERSION=7.4
ARG CADDY_VERSION=2
#=======================================================================
FROM node:${NODE_VERSION}-alpine AS salt_core_js_build

WORKDIR /srv/core

COPY package.json yarn.lock ./
RUN yarn install --non-interactive

COPY webpack.config.js ./
COPY assets ./assets/

RUN mkdir -p public/build \
  && ./node_modules/.bin/encore production

# https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
#=======================================================================
# "php" stage
FROM php:${PHP_VERSION}-fpm-alpine AS salt_core_php

# persistent / runtime deps
RUN apk add --no-cache \
		acl \
		bash \
		fcgi \
		file \
		gettext \
		git \
	;

ARG DOCKERIZE_VERSION=v0.6.1
RUN curl -LsS https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
	  | tar -C /usr/local/bin -xzvf -

ARG APCU_VERSION=5.1.19

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		bzip2-dev \
		icu-dev \
		libpng-dev \
		libzip-dev \
		mysql-dev \
		zlib-dev \
	; \
	\
	docker-php-ext-configure zip; \
	docker-php-ext-install -j$(nproc) \
		bcmath \
		bz2 \
		gd \
		intl \
		mysqli \
		opcache \
		pcntl \
		pdo_mysql \
		zip \
	; \
	pecl install \
		apcu-${APCU_VERSION} \
	; \
	pecl clear-cache; \
	docker-php-ext-enable \
		apcu \
		opcache \
	; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

COPY docker/php/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
RUN chmod +x /usr/local/bin/docker-healthcheck
HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

#COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
#RUN chmod +x /usr/local/bin/docker-entrypoint
#ENTRYPOINT ["docker-entrypoint"]

# Install symfony-cli
#ARG SYMFONY_CLI_VERSION=4.21.6
#RUN curl -LsS https://github.com/symfony/cli/releases/download/v$SYMFONY_CLI_VERSION/symfony_linux_amd64  -o /usr/local/bin/symfony \
#    && chmod a+x /usr/local/bin/symfony \
#	&& symfony self-update -y

# Install and configure composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin" \
    COMPOSER_MEMORY_LIMIT=-1
#    PATH=/composer/vendor/bin:$PATH
#    COMPOSER_HOME=/composer

RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY \
    docker/php/conf.d/*.ini \
        $PHP_INI_DIR/conf.d/

COPY docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

#RUN composer global require \
#        maglnet/composer-require-checker \
#        insolita/unused-scanner \
#            --prefer-dist --no-progress --no-suggest --classmap-authoritative \
#        && composer clear-cache \
#	&& chmod -R a+w ${COMPOSER_HOME}

WORKDIR /srv/core

#=======================================================================
# "php" stage
FROM salt_core_php AS salt_core_vendors

# prevent the reinstallation of vendors at every changes in the source code
COPY composer.json composer.lock symfony.lock ./
RUN set -eux; \
	composer install --no-interaction --prefer-dist --no-autoloader --no-scripts --no-progress; \
	composer clear-cache

#=======================================================================
FROM salt_core_vendors AS salt_core_app

ARG VERSION=3.0.0-dev
ARG BUILD_NUMBER=x
ARG BUILD_DATE=0
ARG COMMIT=unknown

LABEL org.label-schema.build-date=$BUILD_DATE \
      org.label-schema.name="OpenSALT PHP Application" \
      org.label-schema.url="https://github.com/opensalt/opensalt" \
      org.label-schema.version=${VERSION}.${BUILD_NUMBER}+${BUILD_DATE}.${COMMIT} \
      org.label-schema.schema-version="1.0"

# build for production
ARG APP_ENV=prod

# copy only specifically what we need
COPY .env \
	composer.json composer.lock symfony.lock \
	package.json webpack.config.js yarn.lock \
	./
COPY bin bin/
COPY config config/
COPY migrations migrations/
COPY public public/
COPY src src/
COPY templates templates/
COPY translations translations/

COPY codeception.dist.yml codeception.dist.yml
COPY tests tests/

RUN echo ${VERSION}.${BUILD_NUMBER}+${BUILD_DATE}.${COMMIT} > public/version.txt

COPY --from=salt_core_vendors /srv/core/vendor/ ./vendor/
COPY --from=salt_core_js_build /srv/core/public/ ./public/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	chmod 777 var/cache var/log; \
	mkdir -p public/attachments; \
	chmod 777 public/attachments; \
	composer dump-autoload --no-interaction --classmap-authoritative; \
	composer dump-env prod; \
	composer run-script post-install-cmd; \
	chmod +x bin/console-real; sync
VOLUME /srv/core/var
VOLUME /srv/core/public/attachments

ENV SYMFONY_PHPUNIT_VERSION=9

#=======================================================================
FROM caddy:${CADDY_VERSION}-builder-alpine AS salt_core_caddy_builder

ARG XCADDY_SKIP_CLEANUP=0
RUN xcaddy build \
    --with github.com/dunglas/mercure/caddy \
    --with github.com/dunglas/vulcain/caddy

#=======================================================================
FROM caddy:${CADDY_VERSION} AS salt_core_caddy

ARG VERSION=3.0.0-dev
ARG BUILD_NUMBER=x
ARG BUILD_DATE=0
ARG COMMIT=unknown

LABEL org.label-schema.build-date=$BUILD_DATE \
      org.label-schema.name="OpenSALT Web Server" \
      org.label-schema.url="https://github.com/opensalt/opensalt" \
      org.label-schema.version=${VERSION}.${BUILD_NUMBER}+${BUILD_DATE}.${COMMIT} \
      org.label-schema.schema-version="1.0"

WORKDIR /srv/core

COPY --from=dunglas/mercure:v0.11 /srv/public /srv/mercure-assets/
COPY --from=salt_core_caddy_builder /usr/bin/caddy /usr/bin/caddy
COPY --from=salt_core_app /srv/core/public public/
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile

