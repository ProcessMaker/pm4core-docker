#!/bin/bash

if [ -n "${CIRCLE_TAG}" ]; then
  TAG="latest"
else
  TAG=$(echo "${CIRCLE_BRANCH}" | sed 's;/;-;g')
fi

docker build -t processmaker/"${CIRCLE_PROJECT_REPONAME}":"$TAG" .
echo "$DOCKERHUB_TOKEN" | docker login -u processmaker --password-stdin
docker push processmaker/"${CIRCLE_PROJECT_REPONAME}":"$TAG"
