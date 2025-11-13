# Quick Start: Two-Server Sync Implementation

## 5-Minute Setup

### 1. Update config.php

```php
// Find your laptop IP addresses
// Windows: Open PowerShell > ipconfig

define('MASTER_SERVER', 'http://172.19.25.101');  // Your LAPTOP-A IP
define('SLAVE_SERVER', 'http://172.19.25.102');   // Your LAPTOP-B IP
```

### 2. Test Connection

Visit on both servers (to create sync_queue table):

```
http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M
```

Expected response:

```json
{
  "success": true,
  "processed": 0,
  "queue_status": { "pending": { "count": 0 }, "synced": { "count": 0 } }
}
```

### 3. Start Using It

In any registration or data operation file, change:

```php
// OLD:
require_once __DIR__ . '/../db_connect.php';
$db = new Database();

// NEW:
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();
```

That's it! Now every insert/update/delete automatically queues for sync.

### 4. Set Up Auto-Sync (Windows Task Scheduler)

1. Open Task Scheduler
2. Right-click "Task Scheduler Library" → "Create Basic Task"
3. Name: "CampusLink Sync"
4. Trigger: Daily → Repeat every 5 minutes
5. Action: Start program
   - Program: `C:\Windows\System32\curl.exe`
   - Arguments: `http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M`
6. Check: Run with highest privileges
7. Finish

## Testing

### Test 1: Manual Insert & Sync

**On LAPTOP-A:**

```sql
-- Direct insert test
INSERT INTO riders (Username, Email, Phone_Number)
VALUES ('sync_test', 'test@example.com', '256123456');
```

**Trigger sync on LAPTOP-A:**

```
http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=...
```

**Check on LAPTOP-B:**

```sql
SELECT * FROM riders WHERE Username = 'sync_test';
```

Should see the new rider!

### Test 2: Check Sync Queue

```bash
# On LAPTOP-A
curl "http://172.19.25.101/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M"
```

## Verify Both Servers Can Reach Each Other

```powershell
# On both servers:
ping 172.19.25.102  # If on same network
curl http://172.19.25.101/CAMPUS-LINK/config.php  # Should get config
```

If ping fails:

- Check both on same WiFi network
- Check Windows Firewall allows XAMPP
- Use ngrok for external networks (see full guide)

## What Gets Synced

✅ Automatic:

- New riders registering
- New drivers registering
- New bookings
- Status updates
- Profile updates
- Password resets

❌ Not needed to sync:

- Session data
- Temporary cache
- Logs
- User activity tracking

## Files Created

```
sync/
├── SyncManager.php              # Core sync logic
├── DatabaseWithSync.php         # Enhanced DB class
├── sync_cron.sh                 # Cron job for Linux
api/
├── sync_trigger.php             # Manually trigger sync
├── sync_receive.php             # Updated to prevent loops
TWO_SERVER_SYNC_SETUP.md         # Full documentation
TWO_SERVER_SYNC_QUICKSTART.md    # This file
```

## Troubleshooting

| Problem              | Solution                                         |
| -------------------- | ------------------------------------------------ |
| "API key rejected"   | Check key in config.php matches                  |
| "No target server"   | Update MASTER_SERVER, SLAVE_SERVER in config.php |
| "Connection refused" | Verify other laptop IP, check firewall           |
| "Sync not running"   | Manually visit sync_trigger.php URL              |
| "Data not appearing" | Check sync queue status, look at error logs      |

## For Different Networks

If laptops are on different WiFi/networks:

```bash
# Install ngrok: https://ngrok.com
ngrok http 80

# In config.php on LAPTOP-B:
define('MASTER_SERVER', 'https://abc123.ngrok.io');  # From ngrok output
```

## Production Checklist

- [ ] Change SYNC_API_KEY to a strong random value
- [ ] Enable HTTPS (SSL certificates)
- [ ] Set up firewall rules (allow only between your IPs)
- [ ] Configure Task Scheduler to run auto-sync
- [ ] Test sync with sample data
- [ ] Monitor error logs: `C:\xampp\apache\logs\error.log`
- [ ] Set up log rotation for `sync_queue` table

## Commands Reference

```bash
# Trigger sync manually
curl "http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M"

# Check sync queue in MySQL
SELECT status, COUNT(*) as count FROM sync_queue GROUP BY status;

# View pending syncs
SELECT * FROM sync_queue WHERE status = 'pending';

# View failed syncs
SELECT * FROM sync_queue WHERE status = 'failed';

# Clean old syncs (keep 30 days)
DELETE FROM sync_queue WHERE status = 'synced' AND synced_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## Next Steps

1. ✅ Update config.php with your IPs
2. ✅ Create sync_queue table (visit sync_trigger.php)
3. ✅ Migrate your code to use DatabaseWithSync
4. ✅ Set up Task Scheduler for auto-sync
5. ✅ Test with sample data
6. ✅ Monitor logs and sync queue

See `TWO_SERVER_SYNC_SETUP.md` for detailed setup including network configuration, HTTPS, VPN, ngrok, etc.
