#!/bin/bash

find /data/tools/billing/rrd/ -name "*.rrd" -exec /data/tools/billing/scripts/removespikes.php -R={} -A=avg -P=8000 -M=variance  \;
rm -f /tmp/*dump*
rm -f /tmp/*rrd*
