#!/usr/bin/env bash

PLATFORM="linux/arm64"

if [[ -n $GITHUB_REF_NAME ]]
then
  docker buildx build --platform $PLATFORM -t campsych/concerto-platform:$GITHUB_REF_NAME .
  docker tag campsych/concerto-platform:$GITHUB_REF_NAME campsych/concerto-platform:test
  if [[ $GITHUB_REF_NAME = master ]]; then docker tag campsych/concerto-platform:$GITHUB_REF_NAME campsych/concerto-platform:latest; fi
fi

if [[ -n $GITHUB_SHA ]]
then
  COMMIT_SHORT=${GITHUB_SHA:0:7}
  docker buildx build --platform $PLATFORM -t campsych/concerto-platform:$COMMIT_SHORT .
  docker tag campsych/concerto-platform:$COMMIT_SHORT campsych/concerto-platform:test
fi

if [[ -n $GITHUB_REF_NAME ]]
then
  if [[ $GITHUB_REF_NAME =~ ^(v)?([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]
  then
    docker buildx build --platform $PLATFORM -t campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]} .
    docker tag campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}

    docker buildx build --platform $PLATFORM -t campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}.${BASH_REMATCH[3]} .
    docker tag campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}.${BASH_REMATCH[3]}
  fi
fi