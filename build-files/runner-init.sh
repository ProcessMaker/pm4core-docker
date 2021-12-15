set -e

ls -la /code/pm4

while [ ! -f /code/pm4/done.txt ]
do
  sleep 2
done

cd /code/pm4
./vendor/bin/phpunit --testdox