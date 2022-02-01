FROM processmaker/pm4-base:2.0.0

RUN npm config set cacphe /cache/npm
ENV COMPOSER_HOME=/cache/composer

COPY ./pm4-tools /code/pm4-tools

WORKDIR /code
COPY build-files/builder.sh .