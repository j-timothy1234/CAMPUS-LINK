# Laptop B Setup Guide - Complete Server Configuration

This guide explains how to set up LAPTOP-B as a second CAMPUS-LINK server and configure it to sync with LAPTOP-A over any network.

## Overview: Laptop A vs Laptop B

Both laptops run the **exact same code and database structure**. The only difference is their IP addresses in the configuration.

```
LAPTOP-A (Server 1)                    LAPTOP-B (Server 2)
├─ MariaDB: campuslink DB              ├─ MariaDB: campuslink DB
├─ Apache: http://172.19.25.101        ├─ Apache: http://172.19.25.102
├─ config.php: Master/Slave IPs        ├─ config.php: SAME Master/Slave IPs
└─ Can create/update data              └─ Can create/update data
                                            (syncs back to A)
```

## Step 1: Copy CAMPUS-LINK Folder to Laptop B

### Option A: Via USB Drive (Fastest)

```powershell
# On LAPTOP-A
# Copy entire folder: D:\xampp\htdocs\CAMPUS-LINK
# Paste to USB drive

# On LAPTOP-B
# Copy from USB to: D:\xampp\htdocs\CAMPUS-LINK
```

### Option B: Via Network Share

```powershell
# On LAPTOP-A, enable file sharing
# Right-click CAMPUS-LINK folder > Share > Share with Everyone

# On LAPTOP-B, access via network
# \\LAPTOP-A-IP\CAMPUS-LINK
# Copy entire folder to D:\xampp\htdocs\CAMPUS-LINK
```

### Option C: Via GitHub (If using version control)

```powershell
# On LAPTOP-B
git clone https://github.com/j-timothy1234/CAMPUS-LINK.git
cd D:\xampp\htdocs\CAMPUS-LINK
```

### Option D: Zip and Transfer

```powershell
# On LAPTOP-A
# Right-click CAMPUS-LINK > Send to > Compressed folder
# Transfer zip to LAPTOP-B via USB/Email/Cloud
# Extract to D:\xampp\htdocs\CAMPUS-LINK
```

**Result**: LAPTOP-B now has all the same files as LAPTOP-A ✅

## Step 2: Set Up XAMPP on Laptop B

### Install XAMPP (if not already installed)

```powershell
# Download from: https://www.apachefriends.org/
# Run installer
# Choose same installation path: D:\xampp

# Start Apache and MariaDB from XAMPP Control Panel
```

### Verify XAMPP is Working

```powershell
# Visit on LAPTOP-B:
# http://localhost/CAMPUS-LINK/

# Should see: CampusLink homepage ✅
```

## Step 3: Create/Restore Database on Laptop B

### Option A: Import the SQL file (Easiest)

```sql
-- On LAPTOP-B, open phpMyAdmin
-- http://localhost/phpmyadmin

-- Method 1: Import campuslink.sql
-- 1. Click "Import" tab
-- 2. Choose file: CAMPUS-LINK/campuslink.sql
-- 3. Click "Go"
-- Result: campuslink database created with all tables ✅

-- Method 2: Via MySQL command line
cd D:\xampp\mysql\bin
mysql -u root -p < "D:\xampp\htdocs\CAMPUS-LINK\campuslink.sql"
-- Enter password: job1234joy#
```

### Option B: Copy Database from LAPTOP-A

```powershell
# Stop MariaDB on both servers
# XAMPP Control Panel > Apache: Stop, MariaDB: Stop

# On LAPTOP-A, copy database folder
# C:\xampp\data\campuslink folder
# Paste to LAPTOP-B at: C:\xampp\data\campuslink

# Start MariaDB on LAPTOP-B
# Verify in phpMyAdmin: http://localhost/phpmyadmin
```

### Verify Database

```sql
-- Open MariaDB on LAPTOP-B
-- http://localhost/phpmyadmin

-- Check tables exist:
USE campuslink;
SHOW TABLES;

-- Should see: bookings, clients, drivers, riders, sync_queue, etc.
-- If sync_queue doesn't exist, visit sync_trigger.php (it will create it)
```

**Result**: LAPTOP-B has identical database structure ✅

## Step 4: Configure Network Settings

### Find LAPTOP-B's IP Address

```powershell
# On LAPTOP-B, open PowerShell
ipconfig

# Look for "IPv4 Address" under your network adapter
# Example: 172.19.25.102 or 192.168.1.50
```

### Update config.php on LAPTOP-A

```php
// File: D:\xampp\htdocs\CAMPUS-LINK\config.php

// Update if needed (but usually same on both):
define('MASTER_SERVER', 'http://172.19.25.101');  // LAPTOP-A IP (no change)
define('SLAVE_SERVER', 'http://172.19.25.102');   // LAPTOP-B IP (verify correct)

define('SYNC_API_KEY', 'XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M');
```

### Update config.php on LAPTOP-B (IMPORTANT!)

```php
// File: D:\xampp\htdocs\CAMPUS-LINK\config.php

// LAPTOP-B gets the SAME configuration:
define('MASTER_SERVER', 'http://172.19.25.101');  // LAPTOP-A IP
define('SLAVE_SERVER', 'http://172.19.25.102');   // LAPTOP-B IP
define('SYNC_API_KEY', 'XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M');
```

**CRITICAL**: Both servers must have **identical Master/Slave configuration** ✅

## Step 5: Verify Database Connectivity

### Test LAPTOP-B can reach LAPTOP-A

```powershell
# On LAPTOP-B, open PowerShell
ping 172.19.25.101

# Expected: Replies from 172.19.25.101 (success)
# If fails: Check firewall, WiFi connection, IP address
```

### Test LAPTOP-A can reach LAPTOP-B

```powershell
# On LAPTOP-A, open PowerShell
ping 172.19.25.102

# Expected: Replies from 172.19.25.102 (success)
```

### Test Network Config Tool

```
# Visit on LAPTOP-B:
http://localhost/CAMPUS-LINK/network_config.php

# Should show:
# - This Server IP: 172.19.25.102
# - Master Server: http://172.19.25.101
# - Slave Server: http://172.19.25.102
# - Connectivity: SUCCESS (shows both servers can reach each other)
```

**Result**: Both servers can communicate ✅

## Step 6: Initialize Sync Queue on Laptop B

### Create sync_queue Table

```
# Visit on LAPTOP-B:
http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M

# Expected response:
{
  "success": true,
  "processed": 0,
  "queue_status": {
    "pending": { "status": "pending", "count": 0 },
    "synced": { "status": "synced", "count": 0 }
  }
}
```

### Verify Table Created

```sql
-- In phpMyAdmin on LAPTOP-B
USE campuslink;
SHOW TABLES LIKE 'sync_queue';

-- Should show: sync_queue table exists
DESCRIBE sync_queue;
-- Should show: id, table_name, action, record_id, data, status, etc.
```

**Result**: sync_queue table created ✅

## Step 7: Set Up Automatic Sync on Laptop B

### Windows Task Scheduler Setup

```powershell
# On LAPTOP-B, open Task Scheduler
# Windows key > Task Scheduler

# Right-click "Task Scheduler Library" > "Create Basic Task"

# Step 1: General
Name: "CampusLink Sync"
Description: "Sync database changes every 5 minutes"

# Step 2: Triggers
Trigger: Daily (or When the computer starts)
Click "Change" > Repeat every 5 minutes
For a duration of: Indefinitely

# Step 3: Actions
Action: Start a program
Program: C:\Windows\System32\curl.exe
Arguments: http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M

# Step 4: Conditions (uncheck these for reliability)
[ ] Wake the computer to run this task
[ ] Start only if idle

# Step 5: Settings
[X] Run with highest privileges
[X] Run whether user is logged in or not

# Click "Finish"
```

### Repeat on LAPTOP-A (if not already done)

Apply same Task Scheduler setup to LAPTOP-A

**Result**: Both servers sync every 5 minutes ✅

## Step 8: Migrate Application Code on Laptop B

Update registration files on LAPTOP-B to use sync:

### File: rider_api/register.php

```php
// BEFORE:
require_once __DIR__ . '/../db_connect.php';
$db = new Database();
$conn = $db->getConnection();

// AFTER:
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();
```

### File: driver_api/register.php

```php
// BEFORE:
require_once __DIR__ . '/../db_connect.php';
$db = new Database();

// AFTER:
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();
```

### File: client_api/register.php

```php
// BEFORE:
require_once __DIR__ . '/../db_connect.php';
$db = new Database();

// AFTER:
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();
```

### File: clientDashboard/create_booking.php

```php
// BEFORE:
require_once __DIR__ . '/../db_connect.php';
$db = new Database();

// AFTER:
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();
```

### Any other data creation files

Apply same pattern: DatabaseWithSync instead of Database

**Result**: All code changes applied to LAPTOP-B ✅

## Step 9: Test Sync Between Laptops

### Test 1: LAPTOP-A → LAPTOP-B

```
1. On LAPTOP-A, create a new rider
   http://localhost/CAMPUS-LINK/riders/rider.html
   Fill form and submit

2. On LAPTOP-A, check database
   SELECT * FROM riders WHERE Username = 'test_user';
   Should see new record

3. On LAPTOP-A, trigger sync manually
   http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=...
   Should show: "processed": 1

4. On LAPTOP-B, check database
   SELECT * FROM riders WHERE Username = 'test_user';
   Should see the SAME rider record ✅
```

### Test 2: LAPTOP-B → LAPTOP-A

```
1. On LAPTOP-B, create a new driver
   http://localhost/CAMPUS-LINK/drivers/driver.html
   Fill form and submit

2. On LAPTOP-B, check database
   SELECT * FROM drivers WHERE Username = 'test_driver';
   Should see new record

3. On LAPTOP-B, trigger sync manually
   http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=...
   Should show: "processed": 1

4. On LAPTOP-A, check database
   SELECT * FROM drivers WHERE Username = 'test_driver';
   Should see the SAME driver record ✅
```

### Test 3: Monitor Dashboard

```
On both laptops, visit:
http://localhost/CAMPUS-LINK/sync_monitor.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M

Should show:
- Pending: number of changes waiting to sync
- Synced: number of successfully synced changes
- Server configuration (IPs match)
- Recent sync activity
```

**If all tests pass**: ✅ **Sync is working!**

## Step 10: Configure for Different Networks

### Scenario 1: Both on Same WiFi

**No additional setup needed!**

```
LAPTOP-A: 192.168.1.10
LAPTOP-B: 192.168.1.20
Both on same WiFi network
↓
Just use local IPs in config.php ✅
```

### Scenario 2: Different WiFi Networks

**Use ngrok for tunneling:**

#### Install ngrok on LAPTOP-A

```powershell
# Download: https://ngrok.com/download
# Extract ngrok.exe to: C:\ngrok\

# Run ngrok:
cd C:\ngrok
.\ngrok.exe http 80

# Terminal shows:
# Forwarding https://abc123def456.ngrok.io -> http://localhost:80
# Copy the URL!
```

#### Update config.php on BOTH LAPTOPS

```php
// Use ngrok URL instead of IP:
define('MASTER_SERVER', 'https://abc123def456.ngrok.io');  // ngrok URL
define('SLAVE_SERVER', 'http://172.19.25.102');   // LAPTOP-B local IP (or another ngrok)
```

#### Keep ngrok Running

- ngrok must stay running in PowerShell
- If closed, syncs will fail (create new tunnel)
- For production: use Cloudflare Tunnel instead (more stable)

**Result**: Laptops sync over internet ✅

### Scenario 3: Corporate Network / VPN

**Steps:**

1. Set up VPN between both laptops
2. Use local network IPs in config.php
3. Same as Scenario 1

### Scenario 4: Mobile Hotspot

**Same as Scenario 2:**

1. One laptop hosts ngrok tunnel
2. Other uses ngrok URL in config.php
3. Both sync over internet

## Quick Checklist for Laptop B

- [ ] XAMPP installed and running (Apache + MariaDB)
- [ ] CAMPUS-LINK folder copied to D:\xampp\htdocs\
- [ ] Database imported (campuslink.sql)
- [ ] config.php updated with correct Master/Slave IPs
- [ ] Network connectivity tested (ping between laptops)
- [ ] sync_trigger.php called (sync_queue table created)
- [ ] Task Scheduler configured (runs every 5 minutes)
- [ ] Application files migrated (DatabaseWithSync)
- [ ] Test sync A→B works ✅
- [ ] Test sync B→A works ✅
- [ ] sync_monitor.php shows activity ✅

## Folder Structure on LAPTOP-B

```
D:\xampp\htdocs\CAMPUS-LINK\
├── sync/
│   ├── SyncManager.php                ← Use as-is
│   └── DatabaseWithSync.php           ← Use as-is
├── api/
│   ├── sync_trigger.php               ← Use as-is
│   ├── sync_receive.php               ← Use as-is
│   └── sync_send.php                  ← Use as-is
├── rider_api/
│   └── register.php                   ← MODIFY (DatabaseWithSync)
├── driver_api/
│   └── register.php                   ← MODIFY (DatabaseWithSync)
├── client_api/
│   └── register.php                   ← MODIFY (DatabaseWithSync)
├── clientDashboard/
│   ├── create_booking.php             ← MODIFY (DatabaseWithSync)
│   └── respond_booking.php            ← MODIFY (DatabaseWithSync)
├── config.php                         ← UPDATE (same Master/Slave IPs)
├── db_connect.php                     ← Use as-is
├── sync_monitor.php                   ← Use as-is
├── network_config.php                 ← Use as-is
└── [all other files unchanged]
```

## Database Structure on LAPTOP-B

Must be **identical** to LAPTOP-A:

```
campuslink database
├── bookings         ← Syncs both directions
├── clients          ← Syncs both directions
├── drivers          ← Syncs both directions
├── riders           ← Syncs both directions
├── notifications    ← Syncs both directions
├── password_resets  ← Syncs both directions
├── sync_queue       ← Internal sync tracking
└── [other tables]   ← Use as-is
```

All changes to these tables on LAPTOP-B automatically sync to LAPTOP-A ✅

## Troubleshooting LAPTOP-B

### "Connection refused" error

```
Cause: XAMPP not running on LAPTOP-B
Fix:
1. Start XAMPP Control Panel
2. Click "Start" for Apache and MariaDB
3. Wait 5 seconds
4. Try again
```

### "Database does not exist" error

```
Cause: campuslink database not imported
Fix:
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "Import" tab
3. Choose CAMPUS-LINK/campuslink.sql
4. Click "Go"
5. Wait for import to complete
```

### "API key rejected" error

```
Cause: config.php has wrong API key
Fix:
1. Check SYNC_API_KEY in LAPTOP-A config.php
2. Copy exact same value to LAPTOP-B config.php
3. Ensure both have identical MASTER_SERVER and SLAVE_SERVER
```

### "No target server configured" error

```
Cause: MASTER_SERVER or SLAVE_SERVER IPs are wrong
Fix:
1. On LAPTOP-A, run: ipconfig → note IPv4 address
2. On LAPTOP-B, run: ipconfig → note IPv4 address
3. Update config.php on both with correct IPs
4. Make sure both have IDENTICAL configuration
```

### Sync not working

```
Cause: Task Scheduler not running / network down
Check:
1. Task Scheduler: Open Task Scheduler, find "CampusLink Sync"
2. Check "Last Run Result" (should be 0 = success)
3. If task is disabled, right-click > Enable
4. Test network: ping between laptops
5. Check error logs: C:\xampp\apache\logs\error.log
```

## What Happens on LAPTOP-B

### When you create a rider:

1. Insert into local riders table
2. Automatically queued in sync_queue
3. Every 5 minutes: sync_trigger.php runs
4. Sends to LAPTOP-A via sync_receive.php
5. LAPTOP-A inserts same rider
6. Marked as "synced" in sync_queue ✅

### When LAPTOP-A creates a driver:

1. Insert into LAPTOP-A riders table
2. Queued in LAPTOP-A sync_queue
3. Every 5 minutes: sync_trigger.php runs on LAPTOP-A
4. Sends to LAPTOP-B via sync_receive.php
5. LAPTOP-B inserts same driver
6. Marked as "synced" ✅

### If network is down:

1. Changes queue up locally
2. When network returns, sync resumes
3. Retries up to 3 times
4. No data is lost ✅

## Production Deployment Checklist

- [ ] Both laptops running XAMPP 24/7
- [ ] Both Task Schedulers configured and running
- [ ] Network connectivity verified (ping working)
- [ ] Test data syncs both directions
- [ ] Monitor dashboard accessible
- [ ] Error logs checked weekly
- [ ] Database backups configured
- [ ] SYNC_API_KEY changed to random value
- [ ] HTTPS enabled (for production)
- [ ] Firewall rules configured

## Summary

**LAPTOP-B is now a fully-functional CAMPUS-LINK server that:**

- Runs the same code as LAPTOP-A
- Has identical database structure
- Automatically syncs all changes
- Works on same or different networks
- Can create/modify data independently
- Both servers are equal (no master/slave)

**To summarize the setup:**

1. Copy CAMPUS-LINK folder to LAPTOP-B
2. Import campuslink.sql database
3. Update config.php (same IPs on both)
4. Run sync_trigger.php (creates sync_queue)
5. Configure Task Scheduler (every 5 min)
6. Update registration files (DatabaseWithSync)
7. Test sync both directions
8. Configure for your network type

**Time to complete**: 1-2 hours for first-time setup

**Result**: Two fully-synchronized servers ready for production! 🎉
