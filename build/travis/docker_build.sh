#!/usr/bin/env bash

if [[ -n $TRAVIS_BRANCH ]]
then
  docker build -t campsych/concerto-platform:$TRAVIS_BRANCH .
  docker tag campsych/concerto-platform:$TRAVIS_BRANCH campsych/concerto-platform:test
  if [[ $TRAVIS_BRANCH = master ]]; then docker tag campsych/concerto-platform:$TRAVIS_BRANCH campsych/concerto-platform:latest; fi
fi

if [[ -n $TRAVIS_TAG ]]
then
  if [[ $TRAVIS_TAG =~ ^(v)?([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]
  then
    docker build -t campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]} .
    docker tag campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}

    docker build -t campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}.${BASH_REMATCH[3]} .
    docker tag campsych/concerto-platform:${BASH_REMATCH[1]}${BASH_REMATCH[2]}.${BASH_REMATCH[3]}
  fi

  docker build -t campsych/concerto-platform:$TRAVIS_TAG .
  docker tag campsych/concerto-platform:$TRAVIS_TAG campsych/concerto-platform:test
fi