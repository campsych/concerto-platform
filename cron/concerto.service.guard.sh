#!/bin/bash

. /root/env.sh

PROCESS="service.R";

if [ "$CONCERTO_R_SERVICE" = "true" ]; then
  if ! ps ax | grep -v grep | grep $PROCESS > /dev/null; then
    echo "`date`: service process not running";
    /usr/bin/php /app/concerto/bin/console concerto:service:start --env=prod;
  fi
fi