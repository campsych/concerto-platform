#!/usr/bin/env bash

if [[ -n $TRAVIS_TAG ]]
then
  docker run \
      --rm \
      -e SONAR_PROJECT_KEY="concerto-platform" \
      -e SONAR_HOST_URL="${SONAR_HOST_URL}" \
      -e SONAR_LOGIN="${SONAR_LOGIN}" \
      -v "${TRAVIS_BUILD_DIR}:/usr/src" \
      sonarsource/sonar-scanner-cli
fi
