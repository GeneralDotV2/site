#!/bin/bash
##every hour
cd /home/www-dota2/match_analysis/routine/daily/
echo "Run at: $(date -u)"
~/gd_md_stop
echo "-------------------"
./refresh_q5.php
echo "-------------------"
~/gd_md_start
echo "Run at: $(date -u)"