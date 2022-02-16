set -ex

MYSQL_CONNECTION="-u $DB_USERNAME -p\"$DB_PASSWORD\" -h $DB_HOSTNAME -P $DB_PORT"

while ! mysqladmin ping $MYSQL_CONNECTION --silent; do
    echo "Waiting for mysql"
    sleep 1
done

mysql $MYSQL_CONNECTION -e "DROP DATABASE IF EXISTS $DB_DATABASE;"
mysql $MYSQL_CONNECTION -e "CREATE DATABASE $DB_DATABASE;"
mysql $MYSQL_CONNECTION $DB_DATABASE < database.sql

supervisord --nodaemon