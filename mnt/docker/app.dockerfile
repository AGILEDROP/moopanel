# FROM wodby/drupal-php:8.2 as app
# USER root
# RUN mkdir -p /app
# WORKDIR /app
# COPY ./web /app
# RUN composer install --optimize-autoloader --no-dev \
#     && echo "App was build on `date +%Y-%m-%d.%H-%M`" > deploy.info

# We don't need to build assets as of now.
# FROM node:18.17.1-alpine3.17 as theme
# USER root
# RUN mkdir -p /theme
# WORKDIR /theme
# COPY ./web /theme
# COPY --from=app /app/vendor /theme/vendor
# RUN npm install; \
#     npm run build; \
#     echo "Theme was build on `date +%Y-%m-%d.%H-%M`" > deploy.info; \
#     rm -rf node_modules

# from https://www.drupal.org/docs/system-requirements/php-requirements
FROM php:8.3-apache-bullseye

# Required for mbstring and intl php extensions
RUN apt-get update -y && apt-get install libonig-dev libicu-dev -y

# install the PHP extensions we need
RUN set -eux; \
	\
	if command -v a2enmod; then \
		a2enmod rewrite; \
	fi; \
	\
	savedAptMark="$(apt-mark showmanual)"; \
	\
	apt-get clean; \
	apt-get update; \
	apt-get install -y --no-install-recommends \
		libfreetype6-dev \
		libjpeg-dev \
		libpng-dev \
		libpq-dev \
		libwebp-dev \
		libzip-dev \
	; \
	\
    pecl install -o -f redis; \
    rm -rf /tmp/pear; \
    docker-php-ext-enable redis \
	; \
	\
	docker-php-ext-configure gd \
		--with-freetype \
		--with-jpeg=/usr \
		--with-webp \
	; \
	\
	docker-php-ext-install -j "$(nproc)" \
		gd \
		opcache \
		pdo_mysql \
		pdo_pgsql \
		zip \
        mbstring \
        bcmath \
        intl \
	; \
	\
    # reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { print $3 }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
    \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    TZ=Europe/Ljubljana; \
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone; \
    apt-get clean; \
    apt-get update -qq; \
    apt-get install -y ntpdate nano bash-completion curl default-mysql-client; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=60'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Add custom php.ini settings
RUN { \
		echo 'upload_max_filesize = 1M'; \
		echo 'post_max_size = 1M'; \
	} > /usr/local/etc/php/conf.d/docker-php-upload-limit.ini

# Copy build ..
# COPY --from=app /app /opt/app
# COPY --from=theme /theme/public /opt/app/public
# COPY ../../web/storage/app/public /opt/app/storage/app/public
COPY ./web /opt/app
WORKDIR /opt/app
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --optimize-autoloader --no-dev

# Set the project root
RUN set -eux; \
	export COMPOSER_HOME="$(mktemp -d)"; \
	chown -R www-data:www-data storage public bootstrap/cache; \
	rmdir /var/www/html; \
	ln -sf /opt/app/public /var/www/html; \
	# delete composer cache
	rm -rf "$COMPOSER_HOME"

ENV PATH=${PATH}:/opt/app/vendor/bin