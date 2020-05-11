#!/bin/bash
cd /var/www/html/
/usr/bin/pgrep rtl_433 && /bin/echo "rtl_433 already running"||( /usr/bin/rtl_433 -f 433820000 -C customary -F json  | /usr/bin/php -f /var/www/html/parse_and_save.php & )