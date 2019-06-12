FROM opensalt/php:7.3-fpm

# add xdebug
RUN pecl install xdebug-2.7.2 \
        && docker-php-ext-enable xdebug \
        && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.profiler_enable=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.profiler_enable_trigger=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.profiler_output_dir=/var/www/html/var/logs" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
