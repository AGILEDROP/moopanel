FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        git \
        libzip-dev \
        libpq-dev \
        libicu-dev

# Install extensions
RUN docker-php-ext-install zip pdo_pgsql intl opcache
RUN set -eux; \
    pecl install -o -f redis; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    rm -rf /tmp/pear; \
    docker-php-ext-enable redis

# Remove dependencies with removal of dependencies that got automatically installed
RUN set -eux; \
    apt-get clean; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*

# Install composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Change current user to www-data (should be in php-fpm docker image).
USER www-data

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]