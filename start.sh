#!/bin/bash
/usr/bin/pgrep rtl_433 && /bin/echo "rtl_433 already running"||( /usr/local/bin/rtl_433 -f 433820000 -C customary -F json -q | /usr/bin/php -f /var/www/html/parse_and_save.php & )