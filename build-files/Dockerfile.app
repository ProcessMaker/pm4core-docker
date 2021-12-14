FROM pm4-base:local AS build

COPY ./pm4 /code/pm4
COPY ./pm4-packages /code/pm4-packages
COPY ./build-files/services.conf /etc/supervisor/conf.d/services.conf
COPY ./build-files/laravel-echo-server.json /code/pm4/laravel-echo-server.json
COPY ./build-files/app-init.sh /code/app-init.sh

WORKDIR /code
CMD bash app-init.sh && supervisord --nodaemon