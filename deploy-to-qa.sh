#!/bin/sh

git clone https://github.com/ProcessMaker/pm4core-docker.git --branch v2 --depth 1

sed -i "s#\${CI_PROJECT}#$CI_PROJECT#g" pm4core-docker/docker-compose.yml
sed -i "s#\${CI_PACKAGE_BRANCH}#$CI_PACKAGE_BRANCH#g" pm4core-docker/docker-compose.yml
sed -i "s#\${PM_APP_PORT}#$PM_APP_PORT#g" pm4core-docker/docker-compose.yml
sed -i "s#\${PM_BROADCASTER_PORT}#$PM_BROADCASTER_PORT#g" pm4core-docker/docker-compose.yml
sed -i "s#\${PM_DOCKER_SOCK}#/var/run/docker.sock#g" pm4core-docker/docker-compose.yml
sed -i "s#\${IMAGE_TAG}#$IMAGE_TAG#g" pm4core-docker/docker-compose.yml

ssh ssh-qapackages-pm4 " sudo rm -rf /home/DeployQA/$CI_PROJECT-$CI_PACKAGE_BRANCH/*"
ssh ssh-qapackages-pm4 " sudo mkdir -p /home/DeployQA/$CI_PROJECT-$CI_PACKAGE_BRANCH"
ssh ssh-qapackages-pm4 " sudo chmod 777 /home/DeployQA/$CI_PROJECT-$CI_PACKAGE_BRANCH"
scp -r -o StrictHostKeyChecking=no -o ProxyCommand='ssh -o StrictHostKeyChecking=no buildbot@buildbot.processmaker.net -A -W %h:%p' pm4core-docker buildbot@qa-pm4packages:/home/DeployQA/$CI_PROJECT-$CI_PACKAGE_BRANCH/
ssh ssh-qapackages-pm4 " sudo docker login -u=processmaker -p=$DOCKERHUB_TOKEN"
ssh ssh-qapackages-pm4 " sudo docker-compose -p $CI_PROJECT-$CI_PACKAGE_BRANCH -f /home/DeployQA/$CI_PROJECT-$CI_PACKAGE_BRANCH/pm4core-docker/docker-compose.yml up web >/dev/null 2>&1 &"
