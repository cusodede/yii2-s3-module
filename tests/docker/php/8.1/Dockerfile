FROM php:8.1.4-cli

RUN apt-get update \
    && apt-get install -y curl unzip zlib1g-dev libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-install zip pdo_pgsql sockets bcmath pcntl \
    && rm -rf /var/lib/apt/lists/* \
    && curl -L -o /tmp/composer-setup.php https://getcomposer.org/installer \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php

COPY . /code
WORKDIR /code

ENTRYPOINT ["sh", "tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
