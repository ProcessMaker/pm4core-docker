FROM processmaker/pm4-base:2.0.0 AS build

WORKDIR  /code

ENV CACHE_PATH=/root/.cache

COPY ./build-files /code/build-files

CMD bash build-files/builder-init.sh
