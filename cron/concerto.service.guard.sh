#!/bin/bash

(
  flock -n 9 || exit 0

  . /root/env.sh
  PROCESS="service.R";

  if [ "$CONCERTO_R_SERVICES_NUM" -gt "0" ]; then
    PRC_RUNNING=$(! ps ax | grep -v grep | grep -c "$PROCESS")
    PRC_DIFF=$(($CONCERTO_R_SERVICES_NUM-$PRC_RUNNING))
    if [ "$PRC_DIFF" -gt "0" ]; then
      for i in $(seq $PRC_DIFF); do
        echo "$(date): service process not running ($i/$PRC_DIFF)";
        /usr/bin/php /app/concerto/bin/console concerto:service:start --env=prod;
      done
    fi
  fi
) 9>/var/lock/concerto.service.guard.lock