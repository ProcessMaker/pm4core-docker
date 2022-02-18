set -ex

while ! mysqladmin ping -u $DB_USERNAME -p"$DB_PASSWORD" -h $DB_HOSTNAME -P $DB_PORT --silent; do
    echo "Waiting for mysql"
    sleep 1
done

mysql -u $DB_USERNAME -p"$DB_PASSWORD" -h $DB_HOSTNAME -P $DB_PORT -e "DROP DATABASE IF EXISTS $DB_DATABASE;"
mysql -u $DB_USERNAME -p"$DB_PASSWORD" -h $DB_HOSTNAME -P $DB_PORT -e "CREATE DATABASE $DB_DATABASE;"
mysql -u $DB_USERNAME -p"$DB_PASSWORD" -h $DB_HOSTNAME -P $DB_PORT $DB_DATABASE < database.sql

cd /code/pm4
PM_CI=false php artisan docker-executor-php:install

supervisord --nodaemon