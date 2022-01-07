FROM php:7.4-alpine

RUN apk update
RUN apk add bash curl git vim rsync mysql-client

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
RUN install-php-extensions imagick exif pcntl zip imap gd pdo_mysql

WORKDIR /code
RUN curl -s https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
ENV COMPOSER_HOME=/cache/composer

COPY --from=node:14.4-alpine /usr/local /nodejs
ENV PATH="/nodejs/bin:${PATH}"
RUN npm config set cache /cache/npm

COPY ./pm4-tools /code/pm4-tools
WORKDIR /code/pm4-tools

WORKDIR /code

COPY build-files/builder.sh .