set -ex

# # Wait for mysql to bec available
# while ! mysqladmin ping -u pm -ppass -h mysql --silent; do
#     echo "Waiting for mysql"
#     sleep 1
# done

# # Check if database has been initialized
# NUM_TABLES=$(mysql -u pm -p'pass' -h mysql -N -B -e 'show tables;' processmaker | wc -l)

# if [ $NUM_TABLES -eq 0 ]; then
#     mysql -u pm -p'pass' -h mysql processmaker < mysqldump.sql
# fi

if [ ! -f ".env" ]; then

    while ! mysqladmin ping -u pm -ppass -h mysql --silent; do
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
    --db-host=mysql \
    --db-port=3306 \
    --db-name=processmaker \
    --db-username=pm \
    --db-password=pass \
    --data-driver=mysql \
    --data-host=mysql \
    --data-port=3306 \
    --data-name=processmaker \
    --data-username=pm \
    --data-password=pass \
    --redis-host=redis
    

    echo "PROCESSMAKER_SCRIPTS_DOCKER=/usr/local/bin/docker" >> .env
    echo "PROCESSMAKER_SCRIPTS_DOCKER_MODE=copying" >> .env
    echo "LARAVEL_ECHO_SERVER_AUTH_HOST=http://localhost" >> .env
    echo "SESSION_SECURE_COOKIE=false" >> .env
fi
