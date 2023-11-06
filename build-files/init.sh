set -ex

# This init file is run everytime the container instance starts

if [ ! -f ".env" ]; then

    while ! mysqladmin ping -u pm -ppass -h ${PM_DB_HOST} -P ${PM_DB_PORT} --silent; do
        echo "Waiting for mysql"
        sleep 1
    done

    if [ "${PM_APP_PORT}" = "80" ]; then
        PORT_WITH_PREFIX=""
    else
        PORT_WITH_PREFIX=":${PM_APP_PORT}"
    fi

    php artisan processmaker:install --no-interaction \
    --url=${PM_APP_URL}${PORT_WITH_PREFIX} \
    --broadcast-host=${PM_APP_URL}:${PM_BROADCASTER_PORT} \
    --username=admin \
    --password=admin123 \
    --email=admin@processmaker.com \
    --first-name=Admin \
    --last-name=User \
    --db-host=${PM_DB_HOST} \
    --db-port=${PM_DB_PORT} \
    --db-name=${PM_DB_NAME} \
    --db-username=${PM_DB_USERNAME} \
    --db-password=${PM_DB_PASSWORD} \
    --data-driver=mysql \
    --data-host=${PM_DB_HOST} \
    --data-port=${PM_DB_PORT} \
    --data-name=${PM_DB_NAME} \
    --data-username=${PM_DB_USERNAME} \
    --data-password=${PM_DB_PASSWORD} \
    --redis-host=${PM_REDIS_HOST}


    echo "PROCESSMAKER_SCRIPTS_DOCKER=/usr/local/bin/docker" >> .env
    echo "PROCESSMAKER_SCRIPTS_DOCKER_MODE=copying" >> .env
    echo "LARAVEL_ECHO_SERVER_AUTH_HOST=http://localhost" >> .env
    echo "SESSION_SECURE_COOKIE=false" >> .env
fi
