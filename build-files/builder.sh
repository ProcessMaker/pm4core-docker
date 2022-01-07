set -ex

while ! mysqladmin ping -u $DB_USERNAME -p"$DB_PASSWORD" -h $DB_HOSTNAME -P $DB_PORT --silent; do
    echo "Waiting for mysql"
    sleep 1
done

echo "memory_limit=10G" >> /usr/local/etc/php/php.ini

composer config --global github-oauth.github.com $GITHUB_TOKEN

/code/pm4-tools/pm build-ci
/code/pm4-tools/pm build-javascript-ci
/code/pm4-tools/pm install-ci

cd /code/pm4
vendor/bin/paratest -p 6
