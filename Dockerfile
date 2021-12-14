FROM pm4-base:local AS build

COPY ./pm4-tools /tmp/pm4-tools
WORKDIR  /tmp/pm4-tools
RUN composer install
RUN composer global config repositories.pm4-tools path /tmp/pm4-tools
RUN composer global require processmaker/pm4-tools
RUN ln -s /root/.config/composer/vendor/bin/pm /bin/pm

WORKDIR  /code

RUN mkdir -p /code/pm4-packages
RUN mkdir -p /code/pm4

RUN echo "variables_order = \"EPCS\"" > /etc/php/7.4/cli/conf.d/30-env.ini

ENV CACHE_PATH=/root/.cache

COPY ./build-files /code/build-files

CMD bash build-files/install-and-run.sh
