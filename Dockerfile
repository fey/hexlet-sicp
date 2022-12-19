FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Europe/Moscow
ENV PATH=node_modules/.bin:$PATH

WORKDIR /app

RUN apt-get update && apt-get install -y \
    make \
    curl \
    git \
    libpq-dev \
    libzip-dev \
    racket \
    sqlite3 \
    unzip \
    zip \
    php \
    php-bcmath \
    php-exif \
    php-pdo \
    php-pgsql \
    php-pgsql \
    php-zip \
    php-xdebug \
    php-dom \
    php-xml \
    php-mbstring \
    php-sqlite3 \
    php-curl

RUN apt-get update && apt-get install -y \
    software-properties-common \
    && rm -rf /var/lib/apt/lists/*

RUN add-apt-repository -y ppa:plt/racket
RUN apt update && apt-get install -y racket
RUN raco pkg install sicp

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY ./xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get update && apt-get install -y nodejs

ENV PATH=node_modules/.bin:$PATH
ENV PHP_CLI_SERVER_WORKER=5

EXPOSE 8000

COPY package.json package-lock.json ./
RUN npm ci

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts

COPY . .

RUN composer run post-autoload-dump

CMD make start-app
