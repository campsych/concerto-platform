#!/bin/bash

. /root/env.sh

PROCESS="service.R";

if ! ps ax | grep -v grep | grep $PROCESS > /dev/null; then
  echo "`date`: service process not running";
  /usr/bin/php /app/concerto/bin/console concerto:service:start --env=prod;
fi