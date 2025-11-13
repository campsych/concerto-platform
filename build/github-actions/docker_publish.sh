#!/usr/bin/env bash

PLATFORM="linux/amd64"

echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [[ -n $GITHUB_REF_NAME ]]
then
  docker buildx build --platform $PLATFORM --push -t campsych/concerto-platform:$GITHUB_REF_NAME .
  if [[ $GITHUB_REF_NAME = master ]]; then docker buildx build --platform $PLATFORM --push -t campsych/concerto-platform:latest .; fi
fi

if [[ -n $GITHUB_SHA ]]
then
  COMMIT_SHORT=${GITHUB_SHA:0:7}
  docker buildx build --platform $PLATFORM --push -t campsych/concerto-platform:$COMMIT_SHORT .
fi

if [[ -n $GITHUB_REF_NAME ]]
then
  if [[ $GITHUB_REF_NAME =~ ^(v)?([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]
  then
    docker buildx build --platform $PLATFORM --push -t campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]} .
    docker buildx build --platform $PLATFORM --push -t campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}.${BASH_REMATCH[3]} .
  fi
fi