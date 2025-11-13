#!/bin/bash
# sync_cron.sh - Cron job for periodic database sync
# 
# Install on both servers:
# Edit crontab: crontab -e
# Add: */5 * * * * /path/to/CAMPUS-LINK/sync/sync_cron.sh
# 
# This runs sync every 5 minutes

API_KEY="XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M"
LOCAL_SERVER="http://localhost/CAMPUS-LINK"

curl -s "$LOCAL_SERVER/api/sync_trigger.php?api_key=$API_KEY" >> /tmp/campuslink_sync.log 2>&1
