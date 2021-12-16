set -e

while [ ! -f /code/pm4/done.txt ]
do
  sleep 2
done

cd /code/pm4
echo "Running './vendor/bin/phpunit${PHPUNIT_ARGS}'"
./vendor/bin/phpunit${PHPUNIT_ARGS}