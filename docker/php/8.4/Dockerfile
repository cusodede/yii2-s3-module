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