FROM opensalt/php:7.2-cli

# Memory Limit
RUN echo "memory_limit=-1" > "${PHP_INI_DIR}/conf.d/memory-limit.ini" \
    && echo "date.timezone=${PHP_TIMEZONE:-UTC}" > "${PHP_INI_DIR}/conf.d/date_timezone.ini" \
    && echo "always_populate_raw_post_data=-1" > "${PHP_INI_DIR}/conf.d/always_populate_raw_post_data.ini"

# Setup the Composer installer
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
  && curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
  && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }"

# Register the COMPOSER_HOME environment variable
# Add global binary directory to PATH and make sure to re-export it
# Allow Composer to be run as root
ENV COMPOSER_HOME=/composer \
    PATH=/composer/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_VERSION=1.7.0

# Install Composer
RUN php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer --version=${COMPOSER_VERSION} && rm -rf /tmp/composer-setup.php \
    && composer --version \
    && composer global require "hirak/prestissimo:^0.3" \
    && chmod -R a+w /composer

# Set up the volumes and working directory
VOLUME ["/app"]
WORKDIR /app

# Set up the command arguments
ENTRYPOINT ["composer", "--ansi"]
CMD ["-"]
