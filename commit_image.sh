set -ex
docker commit $(docker ps -aqlf "name=^pm4core-docker_cicd_run") debug
docker container prune -f
