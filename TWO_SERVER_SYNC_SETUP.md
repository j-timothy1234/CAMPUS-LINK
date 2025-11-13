# Two-Way Database Sync Setup Guide

This guide explains how to set up two CAMPUS-LINK systems to communicate and share database information across different networks.

## Overview

The system works by:

1. **Local Database Changes** → Queued for sync
2. **Automatic Sync** → Periodically syncs pending changes to the other server
3. **Offline-First** → If the other server is unavailable, changes are queued and retried
4. **Bidirectional** → Both servers act as equals; changes sync in both directions

## Prerequisites

- Two laptops/servers with XAMPP or similar PHP/MariaDB setup
- Both running CampusLink application
- Network connectivity (can be on different networks, internet-based)
- Public IP addresses or domain names for both servers

## Step 1: Update Network Configuration

Edit `config.php` on **BOTH** servers and update the IP addresses:

```php
// On LAPTOP-A (Server 1):
define('MASTER_SERVER', 'http://YOUR_LAPTOP_A_IP_OR_DOMAIN');
define('SLAVE_SERVER', 'http://YOUR_LAPTOP_B_IP_OR_DOMAIN');

// On LAPTOP-B (Server 2):
define('MASTER_SERVER', 'http://YOUR_LAPTOP_A_IP_OR_DOMAIN');
define('SLAVE_SERVER', 'http://YOUR_LAPTOP_B_IP_OR_DOMAIN');
// Same config on both servers!
```

### Finding Your IP Address

**Windows:**

```powershell
ipconfig
```

Look for "IPv4 Address" under your network adapter (e.g., 192.168.1.100 or 172.19.25.101)

**For External Networks:**
If your laptops are on different networks (different WiFi, mobile hotspot, etc.):

- Use public IP: `http://YOUR_PUBLIC_IP:8080` (if port forwarded)
- Or use ngrok/Cloudflare Tunnel for secure tunneling
- Or use a domain name with dynamic DNS

### Testing Network Connectivity

```bash
# Test if other server is reachable
ping 172.19.25.102
```

## Step 2: Verify Database Structure

Both databases must have identical table structures. Run the sync initialization:

```bash
# Visit on both servers
http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
```

This creates the `sync_queue` table if it doesn't exist.

## Step 3: Use DatabaseWithSync in Your Code

Instead of using the regular `Database` class, use `DatabaseWithSync`:

### Example: Rider Registration

**Before:**

```php
require_once __DIR__ . '/../db_connect.php';
$db = new Database();
$conn = $db->getConnection();
// Manual insert...
```

**After:**

```php
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();
// Insert - automatically queues for sync
$rider_id = $db->insert('riders', [
    'username' => 'john',
    'email' => 'john@example.com',
    'phone' => '256123456'
]);
// Change is now queued to sync to the other server
```

## Step 4: Set Up Automatic Sync (Periodic Trigger)

### Option A: Windows Task Scheduler (Easiest for Windows)

1. Open **Task Scheduler**
2. Create Basic Task
3. Name: "CampusLink Sync"
4. Trigger: Repeat every 5 minutes
5. Action: Start a program
   - Program: `C:\Windows\System32\curl.exe`
   - Arguments: `http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M`

### Option B: PHP Script (Call from anywhere)

Create a file `sync_admin.php` at the root:

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sync/SyncManager.php';

$sync = new SyncManager();
$result = $sync->processPendingQueue();
echo "Synced $result operations\n";
?>
```

Then call via cron or manually:

```bash
php C:\xampp\htdocs\CAMPUS-LINK\sync_admin.php
```

### Option C: JavaScript Auto-Trigger (Every page load)

Add to your dashboard footer or global script:

```javascript
// Trigger sync on every page load
fetch(
  "/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M",
  {
    method: "GET",
  }
).catch((e) => console.log("Background sync:", e.message));
```

## Step 5: Test the Sync

### Manual Test

1. On LAPTOP-A, go to the riders registration form and create a new rider
2. Manually trigger sync: Visit `http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=...`
3. On LAPTOP-B, check the riders table - the new rider should appear

### Check Sync Queue Status

```bash
curl "http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M"
```

Response example:

```json
{
  "success": true,
  "processed": 5,
  "queue_status": {
    "pending": { "status": "pending", "count": 2 },
    "synced": { "status": "synced", "count": 145 }
  }
}
```

## Step 6: Configure Network Access

### If servers are on same WiFi network:

- Use local IP (e.g., 192.168.1.100)
- No additional setup needed

### If servers are on different networks:

- **Option 1: Port Forwarding** - Forward port 80/443 to XAMPP server
- **Option 2: ngrok Tunnel** - Use free ngrok service
  ```bash
  ngrok http 80
  # Will give you: https://abc123.ngrok.io
  # Update config.php with this URL
  ```
- **Option 3: Cloudflare Tunnel** - Free, more reliable than ngrok
- **Option 4: VPN** - Set up VPN between both laptops

## Step 7: Monitor and Maintain

### View Sync Logs

```bash
# On Windows, logs go to Apache error log
# Typically: C:\xampp\apache\logs\error.log

# Or create a sync monitor page:
# Create file: sync_monitor.php
```

### Clean Old Sync Records

Add to `sync_admin.php`:

```php
$sync->cleanupOldSyncs(30); // Delete synced records older than 30 days
```

## Conflict Resolution

If the same record is updated on both servers simultaneously:

- **Last-write-wins**: The timestamp from sync record determines priority
- Changes from the server with later timestamp take precedence
- Queue has attempt retry logic (max 3 attempts before marking failed)

## Troubleshooting

### "No target server configured"

- Check `config.php` - MASTER_SERVER and SLAVE_SERVER must be set
- Verify IP addresses are reachable: `ping 172.19.25.102`

### "Network timeout"

- Increase timeout in `SyncManager.php` line ~160: `CURLOPT_TIMEOUT => 15`
- Check firewall rules on both servers
- Ensure port 80/443 is accessible

### "API key rejected"

- Verify the API key matches in `config.php` on both servers
- It's case-sensitive

### Sync not happening automatically

- Check Windows Task Scheduler is running the task
- Or manually visit: `http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=...`
- Check `error_log` in PHP error logs for detailed errors

## Architecture Diagram

```
LAPTOP-A (172.19.25.101)          LAPTOP-B (172.19.25.102)
│                                 │
├─ Database (campusLink)          ├─ Database (campusLink)
├─ sync_queue table               ├─ sync_queue table
│                                 │
└─ INSERT/UPDATE/DELETE ──────→ Queued ──────→ sync_trigger.php
   (automatic queue)              (every 5 min) │
                                                ├─ Send to LAPTOP-A
                                                │  /api/sync_receive.php
                                                │
                                                └─ Insert/Update/Delete
                                                   (with skip_sync flag)
```

## Security Considerations

1. **API Key**: Change `SYNC_API_KEY` in `config.php` to a strong unique value
2. **HTTPS**: In production, use HTTPS (SSL/TLS)
3. **Firewall**: Only allow API endpoints from known servers
4. **Database Access**: Ensure database credentials are different on each server

## Migration: From Single Server to Sync System

If you already have data on LAPTOP-A:

1. **Backup existing database** on LAPTOP-A
2. **Export database** from LAPTOP-A
3. **Import into LAPTOP-B** (ensures both start with same data)
4. **Update config.php** on both servers
5. **Test a small change** on one server and verify it syncs to the other

## Next Steps

- Set up automatic sync via Task Scheduler (Step 4)
- Configure firewall/network access for external connections (Step 6)
- Monitor logs and test edge cases (Step 7)
- In production, enable HTTPS and update API key

For support, check Apache error logs:

```
C:\xampp\apache\logs\error.log
```

Or query the sync queue:

```sql
SELECT * FROM sync_queue WHERE status = 'failed' LIMIT 10;
```
