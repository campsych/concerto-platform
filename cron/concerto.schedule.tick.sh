#!/bin/bash

. /root/env.sh
/usr/bin/php /app/concerto/bin/console concerto:schedule:tick --env=prod