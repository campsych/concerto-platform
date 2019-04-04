#!/usr/bin/env bash

echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin
docker push campsych/concerto-platform:$TRAVIS_BRANCH
if [ "$TRAVIS_BRANCH" = "master" ]; then docker push campsych/concerto-platform:latest; fi