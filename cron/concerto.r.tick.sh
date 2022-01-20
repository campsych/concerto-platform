#!/bin/bash

. /root/env.sh
/usr/bin/php /app/concerto/bin/console concerto:test:run _concerto-tick --env=prod