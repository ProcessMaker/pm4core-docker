export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"$GITHUB_TOKEN\"}}"

while ! mysqladmin ping -u $DB_USERNAME -p"$DB_PASSWORD" -h $DB_HOSTNAME -P $DB_PORT --silent; do
    echo "Waiting for mysql"
    sleep 1
done

composer global config repositories.pm4-tools vcs https://${GITHUB_TOKEN}@github.com/ProcessMaker/pm4-tools.git
composer global require processmaker/pm4-tools:dev-master

/root/.config/composer/vendor/bin install-packages-ci

touch /code/pm4/done.txt

tail -f /dev/null