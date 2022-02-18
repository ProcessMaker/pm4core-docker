#!/bin/bash

docker commit $(./container.sh) processmaker/pm4app:"$IMAGE_TAG"
echo "$DOCKERHUB_TOKEN" | docker login -u processmaker --password-stdin
docker push processmaker/pm4app:"$IMAGE_TAG"
