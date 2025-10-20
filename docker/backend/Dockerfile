# ------- Frontend build stage -------
FROM node:21.6 AS frontend-build
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY . .
RUN npm run build

# ------- Base stage -------
FROM php:8.3-apache AS base
WORKDIR /var/www/html
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Configure Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
 && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && a2enmod rewrite headers

# Setup composer
COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ------- Prod stage -------
FROM base AS prod

# Install PHP extensions
RUN install-php-extensions pdo pdo_mysql zip

# Install composer dependencies (separate layer to leverage Docker cache)
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader --optimize-autoloader

# Application code
COPY . .

# Copy frontend assets
COPY --from=frontend-build /app/node_modules/bootstrap-icons/font/fonts /var/www/html/public/assets/fonts
COPY --from=frontend-build /app/public/build /var/www/html/public/build

# Generate inertia version
RUN echo "<?php return ['$(date +%Y%m%d%H%M%S)'];" \
    > /var/www/html/app/config/packages/inertia/version.php

# Optimized autoloader
RUN composer dump-autoload --optimize

# Framework environment
ENV MAKO_ENV=prod

# Permissions for storage
RUN chown -R www-data:www-data /var/www/html/app/storage \
 && chmod -R 2775 /var/www/html/app/storage

# Clean up unnecessary files
RUN apt clean \
 && rm -rf /usr/local/bin/install-php-extensions /usr/bin/composer ~/.composer /tmp/* /var/tmp/*

# Entrypoint and start
COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

# ------- Dev stage -------
FROM base AS dev

# Install system dependencies manually for zip so they stick around for composer commands
# (install-php-extensions cleans up installs after itself)
RUN apt update && apt install -y zip unzip libzip-dev \
 && rm -rf /var/lib/apt/lists/*

# Enable proxy for dev resources
RUN a2enmod proxy proxy_http

# Install PHP extensions
RUN install-php-extensions pdo pdo_mysql zip xdebug

# Setup entrypoint
COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Framework environment
ENV MAKO_ENV=docker

# Allow configurable UID/GID
ARG UID=1000
ARG GID=1000
RUN if ! getent group ${GID} >/dev/null; then \
      groupadd -g ${GID} devuser; \
    fi
RUN if ! id -u ${UID} >/dev/null 2>&1; then \
      useradd -u ${UID} -g ${GID} -m devuser; \
    fi

# Allow www-data to write to storage
RUN usermod -a -G ${GID} www-data

# Fire in the hole!
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]