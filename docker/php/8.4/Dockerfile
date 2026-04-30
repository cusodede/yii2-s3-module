FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    git \
    procps \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
        zip \
        pdo_pgsql \
        sockets \
        bcmath \
        pcntl \
        intl \
        mbstring

# Install PCOV for code coverage
RUN pecl install pcov && docker-php-ext-enable pcov

# Install Xdebug for step debugging.
# Default mode=off — extension loaded but inactive (no overhead, PCOV stays
# the coverage driver). To enable step debugging at runtime:
#   XDEBUG_MODE=debug XDEBUG_TRIGGER=1 docker compose exec php-8.4 php ...
# IDE should listen on port 9003; client_host=host.docker.internal lets the
# container reach the host (works on Docker Desktop for WSL2/Mac/Windows).
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && { \
        echo 'xdebug.mode=off'; \
        echo 'xdebug.start_with_request=trigger'; \
        echo 'xdebug.client_host=host.docker.internal'; \
        echo 'xdebug.client_port=9003'; \
    } >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create non-root user
RUN useradd -ms /bin/bash -u 1000 yii2user

# Set working directory
WORKDIR /var/www

# Change ownership of working directory
RUN chown -R yii2user:yii2user /var/www

# Switch to non-root user
USER yii2user

# Expose port for potential HTTP server
EXPOSE 8000

# Keep container running
CMD ["tail", "-f", "/dev/null"]