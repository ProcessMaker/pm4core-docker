#!/bin/bash

if [ -n "${CIRCLE_TAG}" ]; then
  TAG="latest"
else
  TAG=$(echo "${CIRCLE_BRANCH}" | sed 's;/;-;g')
fi

docker build -t processmaker/"${CIRCLE_PROJECT_REPONAME}":"$TAG" .
echo "$DOCKER_PROJECT_OWNER_PASSWD" | docker login -u "$DOCKER_PROJECT_OWNER" --password-stdin
docker push processmaker/"${CIRCLE_PROJECT_REPONAME}":"$TAG"

