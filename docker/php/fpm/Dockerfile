FROM opensalt/php-base:7.1-fpm

ENV DEBIAN_FRONTEND noninteractive

# add extensions
RUN	apt-get -o 'Acquire::CompressionTypes::Order::="gz"' update \
	&& apt-get install -y --no-install-recommends \
	 	libmcrypt-dev \
		libbz2-dev \
	    libfreetype6-dev \
		libpng12-dev \
		libjpeg-dev \
		libicu-dev \
	&& docker-php-ext-configure gd --with-freetype-dir=/usr/lib/x86_64-linux-gnu --with-jpeg-dir \
	&& docker-php-ext-install mcrypt bz2 gd mbstring zip pdo pdo_mysql mysqli pcntl bcmath opcache intl \
	&& pecl install apcu-5.1.8 apcu_bc-1.0.3 \
	&& docker-php-ext-enable --ini-name 0-apc.ini apcu apc \
	&& apt-get purge --auto-remove -y libmcrypt-dev libbz2-dev libpng12-dev libfreetype6-dev libicu-dev \
	&& apt-get install -y --no-install-recommends libmcrypt4 libbz2-1.0 libpng12-0 libfreetype6 libicu52 \
	&& rm -r /var/lib/apt/lists/*

COPY \
    memory_limit.ini \
    upload_size.ini \
    short_tag.ini \
    opcache.ini \
    log.ini \
        /usr/local/etc/php/conf.d/
