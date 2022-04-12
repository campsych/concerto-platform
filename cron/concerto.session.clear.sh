#!/bin/bash

(
  flock -n 9 || exit 0

  . /root/env.sh
  /usr/bin/php /app/concerto/bin/console concerto:sessions:clear --env=prod
) 9>/data/lock/concerto.session.clear.lock
