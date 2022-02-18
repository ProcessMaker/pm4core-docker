#!/bin/bash

if [ -z "${IMAGE_TAG}" ]; then
  TAG="latest"
else
  TAG=$(echo "${IMAGE_TAG}" | sed 's;/;-;g')
fi

docker commit $(./container.sh) processmaker/pm4app:"$TAG"
echo "$DOCKERHUB_TOKEN" | docker login -u processmaker --password-stdin
docker push processmaker/pm4app:"$TAG"
