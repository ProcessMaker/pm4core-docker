set -ex

echo "alias db=\"mysql -h $DB_HOSTNAME -P $DB_PORT -p${DB_PASSWORD} ${DB_DATABASE}\"" >> ~/.bashrc
echo "alias art=\"php artisan\"" >> ~/.bashrc
echo "alias t=\"php artisan tinker\"" >> ~/.bashrc
echo "alias phpunit=\"vendor/bin/phpunit\"" >> ~/.bashrc