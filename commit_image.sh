set -ex
docker commit $(./container.sh) pm4app
docker container prune -f
