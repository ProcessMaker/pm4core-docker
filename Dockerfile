FROM pm4-base:local AS build

# RUN wget https://github.com/runyan-co/pm/archive/refs/heads/master.zip
# RUN unzip master.zip
# TMP HERE
COPY pmrepo/pm /tmp/pm-master
RUN rm -rf /tmp/pm-master/.env

RUN composer global config repositories.pm4-tools path /tmp/pm-master
RUN composer global require runyan-co/pm
RUN ln -s /root/.config/composer/vendor/bin/pm /bin/pm

RUN mkdir -p /code/pm4-packages
RUN mkdir -p /code/pm4

RUN echo "variables_order = \"EPCS\"" > /etc/php/7.4/cli/conf.d/30-env.ini

ENV CACHE_PATH=/root/.cache

WORKDIR  /code
COPY ./build-files /code/build-files

CMD bash build-files/install-and-run.sh
