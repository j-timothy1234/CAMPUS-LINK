# Two-Laptop Sync Implementation Checklist

Use this checklist to ensure proper setup of your two-server sync system.

## Phase 1: Planning & Network Setup (15 min)

### Network Configuration

- [ ] Identify IP address of LAPTOP-A
  - Command: `ipconfig` → Look for "IPv4 Address"
  - Example: 172.19.25.101
- [ ] Identify IP address of LAPTOP-B
  - Command: `ipconfig` on LAPTOP-B
  - Example: 172.19.25.102
- [ ] Test connectivity between laptops
  - From LAPTOP-A: `ping 172.19.25.102`
  - From LAPTOP-B: `ping 172.19.25.101`
  - Both should respond successfully
- [ ] If on different networks, decide connectivity method
  - [ ] Same WiFi? Skip to Phase 2
  - [ ] Different networks? Choose:
    - [ ] ngrok (free, easy): https://ngrok.com/download
    - [ ] Cloudflare Tunnel (recommended)
    - [ ] VPN between laptops
    - [ ] Port forwarding with public IP

### Network Connectivity

If using ngrok (recommended for different networks):

- [ ] Download and install ngrok
- [ ] Run on LAPTOP-A: `ngrok http 80`
- [ ] Note the URL: `https://abc123.ngrok.io`
- [ ] Use this URL as MASTER_SERVER in config.php

## Phase 2: Configuration (10 min)
172.20.10.2
### LAPTOP-A Configuration

- [ ] Open `d:\xampp\htdocs\CAMPUS-LINK\config.php`
- [ ] Find lines with MASTER_SERVER and SLAVE_SERVER
- [ ] Update to your IPs:
  ```php
  define('MASTER_SERVER', 'http://172.19.25.101');  // Your LAPTOP-A IP
  define('SLAVE_SERVER', 'http://172.19.25.102');   // Your LAPTOP-B IP
  ```
- [ ] Save file
- [ ] Verify SYNC_API_KEY matches (default is fine for testing)

### LAPTOP-B Configuration

- [ ] Copy the SAME config.php from LAPTOP-A
- [ ] Or manually update with the SAME IP values:
  ```php
  define('MASTER_SERVER', 'http://172.19.25.101');
  define('SLAVE_SERVER', 'http://172.19.25.102');
  ```
- [ ] Save file
- [ ] **Important: Both servers must have identical MASTER/SLAVE configuration**

## Phase 3: Database Initialization (5 min)

### Create Sync Queue Table

- [ ] On LAPTOP-A, visit:

  ```
  http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
  ```

  - Expected response: `{"success": true, "processed": 0}`

- [ ] On LAPTOP-B, visit same URL:
  ```
  http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
  ```
  - Expected response: `{"success": true, "processed": 0}`

### Verify Database Structure

- [ ] On LAPTOP-A, check MariaDB:

  ```sql
  SHOW TABLES FROM campuslink LIKE 'sync%';
  -- Should show: sync_queue
  ```

- [ ] On LAPTOP-B, verify same table exists:
  ```sql
  SHOW TABLES FROM campuslink LIKE 'sync%';
  -- Should show: sync_queue
  ```

## Phase 4: Code Integration (30 min)

### Identify Files to Update

Files that create new data should use DatabaseWithSync:

- [ ] `rider_api/register.php`
- [ ] `driver_api/register.php`
- [ ] `client_api/register.php`
- [ ] `clientDashboard/create_booking.php`
- [ ] Any other registration/creation endpoints

### Update Each File

For each file identified above:

- [ ] Open file
- [ ] Find line: `require_once __DIR__ . '/../db_connect.php';`
- [ ] Replace with: `require_once __DIR__ . '/../sync/DatabaseWithSync.php';`
- [ ] Find line: `$db = new Database();`
- [ ] Replace with: `$db = new DatabaseWithSync();`
- [ ] If using `$db->getConnection()`, use the `$db->insert()`, `$db->update()`, `$db->delete()` methods instead
- [ ] Test the file locally to ensure no errors

**Example (rider_api/register.php):**

```php
// BEFORE:
require_once __DIR__ . '/../db_connect.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("INSERT INTO riders ...");

// AFTER:
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();
$result = $db->insert('riders', [
    'Username' => $username,
    'Email' => $email,
    'Phone_Number' => $phone
]);
```

## Phase 5: Testing (20 min)

### Manual Test: Create a New Record

- [ ] On LAPTOP-A, create a new rider via web form

  - Go to: `http://localhost/CAMPUS-LINK/riders/rider.html`
  - Fill form and submit
  - Note the new username

- [ ] Check LAPTOP-A database:

  ```sql
  SELECT * FROM riders WHERE Username = 'your_test_username';
  ```

  - Should see the new record

- [ ] Check LAPTOP-A sync queue:
  ```sql
  SELECT * FROM sync_queue WHERE status = 'pending' LIMIT 5;
  ```
  - Should see pending sync for the new rider

### Manual Test: Trigger Sync

- [ ] On LAPTOP-A, trigger sync:

  ```
  http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
  ```

  - Should show: `{"success": true, "processed": 1}`

- [ ] Check LAPTOP-A sync queue status:
  ```sql
  SELECT status, COUNT(*) FROM sync_queue GROUP BY status;
  ```
  - Should show: pending=0, synced=1

### Manual Test: Verify on LAPTOP-B

- [ ] On LAPTOP-B, check database:

  ```sql
  SELECT * FROM riders WHERE Username = 'your_test_username';
  ```

  - **IMPORTANT**: Should see the same rider record!

- [ ] Check LAPTOP-B sync queue:
  ```sql
  SELECT * FROM sync_queue;
  ```
  - Should be empty or show synced status

### Test Reverse Sync (LAPTOP-B → LAPTOP-A)

- [ ] On LAPTOP-B, create a new rider
- [ ] On LAPTOP-B, trigger sync
- [ ] On LAPTOP-A, verify the new rider appears

**If all tests pass: ✅ Sync system is working!**

## Phase 6: Automation Setup (15 min)

### Windows Task Scheduler (Recommended)

- [ ] Open "Task Scheduler" on LAPTOP-A
- [ ] Right-click "Task Scheduler Library" → "Create Basic Task"
- [ ] **Name**: "CampusLink Sync"
- [ ] **Trigger**: Daily at system startup (or hourly)
  - [ ] Click "Change" → Set to repeat every 5 minutes
- [ ] **Action**: "Start a program"
  - [ ] Program: `C:\Windows\System32\curl.exe`
  - [ ] Arguments: `http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M`
- [ ] **Settings**: Check "Run with highest privileges"
- [ ] Click "Finish"

- [ ] Repeat same process on LAPTOP-B

### Verify Task is Running

- [ ] After 5 minutes, check sync queue:
  ```sql
  SELECT status, COUNT(*) FROM sync_queue GROUP BY status;
  ```
  - Should show new synced records every 5 minutes

## Phase 7: Monitoring & Validation (10 min)

### Access Monitoring Dashboard

- [ ] On LAPTOP-A, visit:

  ```
  http://localhost/CAMPUS-LINK/sync_monitor.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
  ```

  - Should show sync queue status
  - Should show server configuration
  - Should be able to trigger sync manually

- [ ] Repeat on LAPTOP-B

### Network Configuration Check

- [ ] Visit:
  ```
  http://localhost/CAMPUS-LINK/network_config.php
  ```
  - Should show:
    - This server IP address
    - Master/Slave server IPs
    - Connectivity status to other server

### Test Edge Cases

- [ ] Stop LAPTOP-B (simulate offline)
- [ ] Create new rider on LAPTOP-A
- [ ] Trigger sync (should fail gracefully)
- [ ] Check sync queue shows "pending" status
- [ ] Start LAPTOP-B
- [ ] Trigger sync again
- [ ] Verify rider appears on LAPTOP-B ✅

## Phase 8: Production Hardening (Optional)

- [ ] Change `SYNC_API_KEY` in config.php to random value
  - Generate: Use random key generator or `echo md5(microtime()) | head -c 40`
  - Update on BOTH servers
- [ ] Enable HTTPS/SSL on both servers
  - [ ] Install SSL certificates (Let's Encrypt: free)
  - [ ] Update MASTER_SERVER and SLAVE_SERVER to use `https://`
- [ ] Set up firewall rules
  - [ ] Allow LAPTOP-A IP in LAPTOP-B firewall
  - [ ] Allow LAPTOP-B IP in LAPTOP-A firewall
- [ ] Enable database backups
  - [ ] Set up daily backup of campuslink database
  - [ ] Store backups securely
- [ ] Monitor performance
  - [ ] Check sync_queue table growth
  - [ ] Clean old synced records monthly
- [ ] Enable error logging
  - [ ] Check Apache error log regularly
  - [ ] Set up log rotation

## Final Verification Checklist

### On Both LAPTOP-A and LAPTOP-B:

- [ ] `config.php` has correct IPs
- [ ] `sync_queue` table exists
- [ ] Task Scheduler running every 5 minutes
- [ ] Can visit `sync_monitor.php` dashboard
- [ ] Can visit `network_config.php` and see connectivity success

### Data Sync:

- [ ] Create data on LAPTOP-A
- [ ] Auto-syncs to LAPTOP-B within 5 minutes
- [ ] Create data on LAPTOP-B
- [ ] Auto-syncs to LAPTOP-A within 5 minutes
- [ ] Both databases stay in sync

## Troubleshooting Quick Reference

| Issue                | Fix                                                             |
| -------------------- | --------------------------------------------------------------- |
| "API key rejected"   | Verify SYNC_API_KEY in config.php matches on both servers       |
| "No target server"   | Check MASTER_SERVER and SLAVE_SERVER IPs are correct            |
| "Connection refused" | Check firewall, test ping to other laptop                       |
| Data not syncing     | Run sync_trigger.php manually, check Task Scheduler             |
| Sync is too slow     | Increase frequency in Task Scheduler (every 1 min instead of 5) |
| High latency         | Check network speed, use ngrok if on different networks         |

## Support Resources

- **Full Setup Guide**: `TWO_SERVER_SYNC_SETUP.md`
- **Quick Start**: `TWO_SERVER_SYNC_QUICKSTART.md`
- **Code Examples**: `EXAMPLE_SYNC_USAGE.php`
- **Implementation Summary**: `SYNC_IMPLEMENTATION_SUMMARY.md`
- **Monitoring**: `sync_monitor.php?api_key=...`
- **Network Diagnostics**: `network_config.php`

---

**Status**: ✅ Ready to deploy when all checkboxes are complete

**Estimated Time**: 1-2 hours for first-time setup
**Ongoing Maintenance**: 5 minutes per week to monitor logs

Start with Phase 1 and work through each phase sequentially. Don't skip testing!
