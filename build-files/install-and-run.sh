export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"$GITHUB_TOKEN\"}}"

while ! mysqladmin ping -u $DB_USERNAME -p"$DB_PASSWORD" -h $DB_HOSTNAME -P $DB_PORT --silent; do
    echo "Waiting for mysql"
    sleep 1
done

/bin/pm install-packages-ci

docker build -t pm4-app:$PM_VERSION -f build-files/Dockerfile.app .