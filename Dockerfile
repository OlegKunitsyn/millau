FROM php:8.1-cli
WORKDIR /app
RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libonig-dev libxml2-dev libzip-dev zip wget \
    && docker-php-ext-install intl xml zip mbstring opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony
RUN curl -sS https://getcomposer.org/download/1.10.26/composer.phar -o /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer
COPY . .
RUN cp .env.example .env
RUN chown -R www-data:www-data /app
ENV HOME=/app
USER www-data
RUN composer install --no-interaction --prefer-dist
EXPOSE 9000
CMD ["symfony", "server:start", "--no-tls", "--allow-http", "--allow-all-ip", "--port=9000", "--dir=public"]
