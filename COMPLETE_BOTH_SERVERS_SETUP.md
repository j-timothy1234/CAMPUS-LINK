# Complete Setup Guide: Both Servers (A & B)

This is the master setup guide for configuring both LAPTOP-A and LAPTOP-B to work together with automatic database sync.

## Prerequisites

Before you start, you need:

- ✅ LAPTOP-A with XAMPP running and CAMPUS-LINK installed (already done)
- ✅ LAPTOP-B with XAMPP installed (may need to install)
- ✅ Both laptops connected to same or accessible network
- ✅ 1-2 hours for complete setup

## Phase 1: Prepare LAPTOP-B (1 hour)

### 1.1 Install XAMPP on LAPTOP-B (if needed)

```powershell
# Download XAMPP from: https://www.apachefriends.org/download.html
# Version: 8.2 or higher (same as LAPTOP-A if possible)

# Run the installer
# Install to: D:\xampp
# Components: Apache, MySQL, MariaDB, PHP
# Select "Start Apache and MariaDB after installation"
```

Verify XAMPP is running:

```
http://localhost/dashboard
# Should show XAMPP dashboard ✅
```

### 1.2 Copy CAMPUS-LINK Folder to LAPTOP-B

**Option A: USB Drive (Fastest)**

```powershell
# On LAPTOP-A:
# Copy folder: D:\xampp\htdocs\CAMPUS-LINK
# Paste to USB drive

# On LAPTOP-B:
# Copy from USB to: D:\xampp\htdocs\CAMPUS-LINK
```

**Option B: Network Share**

```powershell
# On LAPTOP-A:
# Right-click D:\xampp\htdocs\CAMPUS-LINK > Share with > Everyone

# On LAPTOP-B:
# \\LAPTOP-A-IP\xampp\htdocs\CAMPUS-LINK
# Copy entire folder to: D:\xampp\htdocs\CAMPUS-LINK
```

**Option C: File Transfer Tool**
Use: Dropbox, Google Drive, OneDrive, or WeTransfer

**Verify the copy:**

```powershell
# On LAPTOP-B, check folder exists:
dir D:\xampp\htdocs\CAMPUS-LINK

# Should show: sync/, api/, config.php, etc.
```

### 1.3 Import Database on LAPTOP-B

Open phpMyAdmin on LAPTOP-B:

```
http://localhost/phpmyadmin
```

**Method 1: Import SQL File**

1. Click the "Import" tab
2. Click "Choose File" → Select `D:\xampp\htdocs\CAMPUS-LINK\campuslink.sql`
3. Click "Go"
4. Wait for import to complete (2-3 minutes)
5. Should show: "Import successful"

**Verify Database:**

```sql
-- In phpMyAdmin, run:
USE campuslink;
SHOW TABLES;

-- Should show tables:
-- bookings, clients, drivers, riders, notifications, password_resets, etc.
```

## Phase 2: Network Configuration (30 min)

### 2.1 Find Both IP Addresses

**On LAPTOP-A:**

```powershell
ipconfig
# Look for "IPv4 Address" under your network adapter
# Example: 172.19.25.101
# Note this IP
```

**On LAPTOP-B:**

```powershell
ipconfig
# Look for "IPv4 Address"
# Example: 172.19.25.102
# Note this IP
```

### 2.2 Test Network Connectivity

**From LAPTOP-A, ping LAPTOP-B:**

```powershell
ping 172.19.25.102

# Expected output:
# Reply from 172.19.25.102: bytes=32 time=5ms TTL=64
# Success ✅
```

**From LAPTOP-B, ping LAPTOP-A:**

```powershell
ping 172.19.25.101

# Expected output:
# Reply from 172.19.25.101: bytes=32 time=5ms TTL=64
# Success ✅
```

If ping fails:

- Check both laptops are on same WiFi
- Check Windows Firewall isn't blocking
- Try restarting network adapter

### 2.3 Update config.php on BOTH Laptops

**On LAPTOP-A:**

```powershell
# Edit: D:\xampp\htdocs\CAMPUS-LINK\config.php
```

```php
<?php
// LAPTOP-A config.php

define('MASTER_SERVER', 'http://172.19.25.101');  // ← Your LAPTOP-A IP
define('SLAVE_SERVER', 'http://172.19.25.102');   // ← Your LAPTOP-B IP

define('SYNC_API_KEY', 'XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'job1234joy#');
define('DB_NAME', 'campuslink');
?>
```

Save file.

**On LAPTOP-B:**

```powershell
# Edit: D:\xampp\htdocs\CAMPUS-LINK\config.php
```

```php
<?php
// LAPTOP-B config.php

// ⚠️ IMPORTANT: MUST BE IDENTICAL TO LAPTOP-A!
define('MASTER_SERVER', 'http://172.19.25.101');  // ← LAPTOP-A IP (SAME!)
define('SLAVE_SERVER', 'http://172.19.25.102');   // ← LAPTOP-B IP (SAME!)

define('SYNC_API_KEY', 'XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'job1234joy#');
define('DB_NAME', 'campuslink');
?>
```

Save file.

**Critical Point:**
Both `config.php` files must have identical MASTER_SERVER and SLAVE_SERVER values!

### 2.4 Verify Network Configuration

**On LAPTOP-B, check network diagnostics:**

```
http://localhost/CAMPUS-LINK/network_config.php
```

Should show:

```json
{
  "this_server": {
    "ip_address": "172.19.25.102",
    "full_url": "http://172.19.25.102"
  },
  "configured_servers": {
    "MASTER_SERVER": "http://172.19.25.101",
    "SLAVE_SERVER": "http://172.19.25.102"
  },
  "connectivity": {
    "target_server": "http://172.19.25.101",
    "status": "SUCCESS",
    "reachable": true
  }
}
```

If connectivity fails:

1. Check firewall on LAPTOP-A
2. Verify both IPs are correct
3. Try ping again

## Phase 3: Initialize Sync System (15 min)

### 3.1 Create sync_queue Table on LAPTOP-A

**Visit on LAPTOP-A:**

```
http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
```

Response should be:

```json
{
  "success": true,
  "processed": 0,
  "queue_status": {
    "pending": { "status": "pending", "count": 0 },
    "synced": { "status": "synced", "count": 0 }
  }
}
```

### 3.2 Create sync_queue Table on LAPTOP-B

**Visit on LAPTOP-B:**

```
http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
```

Same response expected ✅

### 3.3 Verify sync_queue Table

**On LAPTOP-A and LAPTOP-B:**

```sql
-- In phpMyAdmin, run:
USE campuslink;
SHOW TABLES LIKE 'sync%';

-- Should show: sync_queue
DESCRIBE sync_queue;

-- Should show columns: id, table_name, action, record_id, status, etc.
```

## Phase 4: Set Up Automatic Sync (30 min)

### 4.1 Windows Task Scheduler - LAPTOP-A

On LAPTOP-A:

1. Open **Task Scheduler** (Windows key → search "Task Scheduler")
2. Right-click "Task Scheduler Library" → "Create Basic Task"

**Step 1: General**

- Name: `CampusLink Sync`
- Description: `Sync database changes every 5 minutes`

**Step 2: Triggers**

- Trigger: `Daily` (or "When the computer starts")
- Click "Change"
  - Repeat every: `5 minutes`
  - For a duration of: `Indefinitely`
- Click OK

**Step 3: Actions**

- Action: `Start a program`
- Program: `C:\Windows\System32\curl.exe`
- Arguments: `http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M`

**Step 4: Conditions**

- Uncheck: "Wake the computer to run this task"
- Uncheck: "Start only if idle"

**Step 5: Settings**

- Check: "Run with highest privileges"
- Check: "Run whether user is logged in or not"

Click "Finish"

### 4.2 Windows Task Scheduler - LAPTOP-B

Repeat the **exact same steps** on LAPTOP-B.

### 4.3 Verify Tasks are Running

**Check on both laptops:**

Open Task Scheduler → find "CampusLink Sync"

- Status should show: "Running" or "Ready"
- Last Run Result should show: "0" (success)

After 5 minutes:

- Check sync_monitor.php to see if syncs happened

## Phase 5: Code Migration (15 min)

### 5.1 Update LAPTOP-A Code Files

**Files to update:**

- `rider_api/register.php`
- `driver_api/register.php`
- `client_api/register.php`
- `clientDashboard/create_booking.php`
- `clientDashboard/respond_booking.php`
- Any other data-creation files

**For each file, change:**

```php
// OLD:
require_once __DIR__ . '/../db_connect.php';
$db = new Database();

// NEW:
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();
```

See `EXAMPLE_SYNC_USAGE.php` for complete example.

### 5.2 Update LAPTOP-B Code Files

**Copy the same changes to LAPTOP-B files:**
Apply identical changes to the same files on LAPTOP-B.

## Phase 6: Testing (20 min)

### 6.1 Test Sync: LAPTOP-A → LAPTOP-B

**Step 1: Create data on LAPTOP-A**

```
1. Visit: http://localhost/CAMPUS-LINK/riders/rider.html
2. Fill form:
   - Username: test_sync_001
   - Email: test@example.com
   - Phone: 256123456
   - Password: Test123!
3. Submit form
```

**Step 2: Verify on LAPTOP-A database**

```sql
-- On LAPTOP-A phpMyAdmin:
SELECT * FROM riders WHERE Username = 'test_sync_001';
-- Should see: 1 row with your test data
```

**Step 3: Trigger sync on LAPTOP-A**

```
http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
```

Response should show: `"processed": 1`

**Step 4: Check LAPTOP-B database**

```sql
-- On LAPTOP-B phpMyAdmin:
SELECT * FROM riders WHERE Username = 'test_sync_001';
-- Should see: SAME row as on LAPTOP-A ✅
```

**Success!** Data synced from A to B

### 6.2 Test Sync: LAPTOP-B → LAPTOP-A

**Step 1: Create data on LAPTOP-B**

```
1. Visit: http://localhost/CAMPUS-LINK/drivers/driver.html
2. Fill form:
   - Username: test_driver_001
   - Email: driver@example.com
   - Phone: 256789012
   - Car Plate: TEST123
   - Password: Test123!
3. Submit form
```

**Step 2: Verify on LAPTOP-B database**

```sql
-- On LAPTOP-B phpMyAdmin:
SELECT * FROM drivers WHERE Username = 'test_driver_001';
-- Should see: 1 row
```

**Step 3: Trigger sync on LAPTOP-B**

```
http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
```

Response should show: `"processed": 1`

**Step 4: Check LAPTOP-A database**

```sql
-- On LAPTOP-A phpMyAdmin:
SELECT * FROM drivers WHERE Username = 'test_driver_001';
-- Should see: SAME row as on LAPTOP-B ✅
```

**Success!** Data synced from B to A

### 6.3 Monitor Sync Activity

**Visit sync_monitor.php on both laptops:**

LAPTOP-A:

```
http://localhost/CAMPUS-LINK/sync_monitor.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
```

LAPTOP-B:

```
http://localhost/CAMPUS-LINK/sync_monitor.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
```

Both should show:

- Pending: 0 (all synced)
- Synced: 2+ (from your tests)
- Recent activity log

## Phase 7: Network Scenarios (Optional)

### For Same WiFi

No additional setup needed!

- Both laptops automatically discover each other
- Syncing works immediately

### For Different WiFi Networks

**Install ngrok on LAPTOP-A:**

```powershell
# Download: https://ngrok.com/download
# Extract to: C:\ngrok\ngrok.exe

# Run ngrok:
cd C:\ngrok
.\ngrok.exe http 80

# Output will show:
# Forwarding https://abc123def456.ngrok.io -> http://localhost:80
```

**Update config.php on BOTH laptops:**

```php
<?php
// Use ngrok URL for MASTER_SERVER:
define('MASTER_SERVER', 'https://abc123def456.ngrok.io');
define('SLAVE_SERVER', 'http://172.19.25.102');  // or another ngrok URL
?>
```

**Keep ngrok running:**

- Don't close the terminal window
- If closed, create new tunnel and update config again

## Final Verification Checklist

### LAPTOP-A

- [ ] XAMPP running (Apache + MariaDB)
- [ ] CAMPUS-LINK folder at D:\xampp\htdocs\CAMPUS-LINK
- [ ] config.php has correct IPs
- [ ] Database campuslink imported
- [ ] sync_queue table exists
- [ ] Task Scheduler configured and running
- [ ] Application code migrated (DatabaseWithSync)
- [ ] Can ping LAPTOP-B
- [ ] sync_monitor.php accessible
- [ ] network_config.php shows SUCCESS

### LAPTOP-B

- [ ] XAMPP running (Apache + MariaDB)
- [ ] CAMPUS-LINK folder at D:\xampp\htdocs\CAMPUS-LINK
- [ ] config.php has SAME IPs as A
- [ ] Database campuslink imported
- [ ] sync_queue table exists
- [ ] Task Scheduler configured and running
- [ ] Application code migrated (DatabaseWithSync)
- [ ] Can ping LAPTOP-A
- [ ] sync_monitor.php accessible
- [ ] network_config.php shows SUCCESS

### Sync Testing

- [ ] Created test data on A, synced to B ✅
- [ ] Created test data on B, synced to A ✅
- [ ] sync_monitor.php shows activity on both
- [ ] sync_queue status shows synced items
- [ ] Both databases have identical data

## Troubleshooting

| Issue                        | Fix                                               |
| ---------------------------- | ------------------------------------------------- |
| "Cannot ping other laptop"   | Check WiFi connection, firewall settings          |
| "API key rejected"           | Ensure SYNC_API_KEY is identical on both          |
| "No target server"           | Check MASTER/SLAVE IPs in config.php              |
| "Sync not working"           | Check Task Scheduler, verify network connectivity |
| "Database does not exist"    | Import campuslink.sql to LAPTOP-B                 |
| "sync_queue table not found" | Visit sync_trigger.php to create table            |

## Next Steps

1. ✅ Complete all steps above
2. ✅ Run tests and verify both ways sync works
3. ✅ Monitor sync_monitor.php for 24 hours
4. ✅ Check error logs: `C:\xampp\apache\logs\error.log`
5. ✅ Deploy to production

## Documentation

For more details, see:

- `LAPTOP_B_SETUP.md` - Detailed LAPTOP-B setup
- `LAPTOP_A_VS_B_COMPARISON.md` - What's the same/different
- `SYNC_IMPLEMENTATION_SUMMARY.md` - How sync works
- `EXAMPLE_SYNC_USAGE.php` - Code examples

## Summary

You now have:
✅ Two identical CAMPUS-LINK servers  
✅ Automatic bidirectional sync every 5 minutes  
✅ Shared database that stays in sync  
✅ Monitoring dashboard  
✅ Network diagnostics  
✅ Works on same or different networks

**Estimated setup time: 1-2 hours**

**Result: Enterprise-grade database replication system** 🎉
