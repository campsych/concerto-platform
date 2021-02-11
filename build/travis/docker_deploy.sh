#!/usr/bin/env bash

echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [[ -n $TRAVIS_BRANCH ]]
then
  docker push campsych/concerto-platform:$TRAVIS_BRANCH
  if [[ $TRAVIS_BRANCH = master ]]; then docker push campsych/concerto-platform:latest; fi
fi

if [[ -n $TRAVIS_COMMIT ]]
then
  COMMIT_SHORT=${$TRAVIS_COMMIT:0:7}
  docker push campsych/concerto-platform:$COMMIT_SHORT
fi

if [[ -n $TRAVIS_TAG ]]
then
  if [[ $TRAVIS_TAG =~ ^(v)?([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]
  then
    docker push campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}
    docker push campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}.${BASH_REMATCH[3]}
  fi

  docker push campsych/concerto-platform:$TRAVIS_TAG
fi