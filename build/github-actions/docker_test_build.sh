#!/usr/bin/env bash

PLATFORM="linux/amd64"

docker buildx build --platform $PLATFORM --load -t campsych/concerto-platform:test .