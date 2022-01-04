set -ex

while ! mysqladmin ping -u $DB_USERNAME -p"$DB_PASSWORD" -h $DB_HOSTNAME -P $DB_PORT --silent; do
    echo "Waiting for mysql"
    sleep 1
done

echo "memory_limit=10G" >> /usr/local/etc/php/php.ini

/code/pm4-tools/pm install-ci
cd pm4
vendor/bin/paratest -p 6

