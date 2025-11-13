# Laptop A vs Laptop B - Side-by-Side Comparison

Quick reference showing what goes on each laptop.

## Installation Overview

```
LAPTOP-A (Server 1)                       LAPTOP-B (Server 2)
═══════════════════════════════════════════════════════════════════════

STEP 1: XAMPP Installation
├─ D:\xampp\apache                        ├─ D:\xampp\apache
├─ D:\xampp\mysql (or MariaDB)            ├─ D:\xampp\mysql (or MariaDB)
└─ D:\xampp\php                           └─ D:\xampp\php
   (Start Apache & MariaDB)                  (Start Apache & MariaDB)

STEP 2: Application Files
├─ D:\xampp\htdocs\CAMPUS-LINK\           ├─ D:\xampp\htdocs\CAMPUS-LINK\
│  ├─ sync/                               │  ├─ sync/
│  ├─ api/                                │  ├─ api/
│  ├─ riderDashboard/                     │  ├─ riderDashboard/
│  ├─ driverDashboard/                    │  ├─ driverDashboard/
│  ├─ clientDashboard/                    │  ├─ clientDashboard/
│  ├─ config.php  ⚠️ IMPORTANT             │  ├─ config.php  ⚠️ IMPORTANT
│  └─ [all other files]                   │  └─ [all other files]

STEP 3: Database
├─ MariaDB: campuslink                    ├─ MariaDB: campuslink
├─ Tables: bookings, riders, drivers      ├─ Tables: bookings, riders, drivers
├─ Tables: clients, notifications         ├─ Tables: clients, notifications
├─ Table: sync_queue ✅                    ├─ Table: sync_queue ✅
└─ Same structure as B ⚠️                  └─ Same structure as A ⚠️

STEP 4: Configuration
└─ config.php settings:                   └─ config.php settings:
   define('MASTER_SERVER',                   define('MASTER_SERVER',
      'http://172.19.25.101');               'http://172.19.25.101');
   define('SLAVE_SERVER',                    define('SLAVE_SERVER',
      'http://172.19.25.102');               'http://172.19.25.102');
   (IDENTICAL on both!)                      (IDENTICAL on both!)

STEP 5: Automation
└─ Task Scheduler:                        └─ Task Scheduler:
   Every 5 minutes:                          Every 5 minutes:
   sync_trigger.php                         sync_trigger.php
   (send changes to B)                      (send changes to A)

STEP 6: Code Changes
├─ rider_api/register.php                 ├─ rider_api/register.php
├─ driver_api/register.php                ├─ driver_api/register.php
├─ client_api/register.php                ├─ client_api/register.php
├─ clientDashboard/create_booking.php     ├─ clientDashboard/create_booking.php
└─ Use: DatabaseWithSync                  └─ Use: DatabaseWithSync
```

## What's Different?

| Aspect              | LAPTOP-A                 | LAPTOP-B                        |
| ------------------- | ------------------------ | ------------------------------- |
| **Installation**    | Same XAMPP               | Same XAMPP                      |
| **Files**           | Same CAMPUS-LINK code    | Same CAMPUS-LINK code           |
| **Database**        | campuslink DB            | campuslink DB (identical)       |
| **IP Address**      | 172.19.25.101            | 172.19.25.102                   |
| **config.php**      | Same Master/Slave values | **Same Master/Slave values** ⚠️ |
| **Task Scheduler**  | Runs sync_trigger        | Runs sync_trigger               |
| **Data Operations** | Can create/update        | Can create/update               |
| **Sync Direction**  | Sends to B               | Sends to A                      |
| **Both Receive**    | Changes from B           | Changes from A                  |

## What's the Same?

✅ XAMPP version  
✅ CAMPUS-LINK code  
✅ Database structure  
✅ API keys  
✅ Sync system  
✅ Code changes needed  
✅ Task Scheduler setup  
✅ sync_queue table

## Configuration Files Comparison

### config.php - LAPTOP-A

```php
<?php
// LAPTOP-A config.php

define('MASTER_SERVER', 'http://172.19.25.101');  // ← LAPTOP-A
define('SLAVE_SERVER', 'http://172.19.25.102');   // ← LAPTOP-B

define('SYNC_API_KEY', 'XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'job1234joy#');
define('DB_NAME', 'campuslink');
?>
```

### config.php - LAPTOP-B

```php
<?php
// LAPTOP-B config.php

// ⚠️ MUST BE IDENTICAL TO LAPTOP-A:
define('MASTER_SERVER', 'http://172.19.25.101');  // ← LAPTOP-A (SAME!)
define('SLAVE_SERVER', 'http://172.19.25.102');   // ← LAPTOP-B (SAME!)

define('SYNC_API_KEY', 'XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'job1234joy#');
define('DB_NAME', 'campuslink');
?>
```

**CRITICAL**: Both files must have identical MASTER_SERVER and SLAVE_SERVER definitions!

## Database Comparison

### LAPTOP-A Database

```
campuslink/
├── bookings
├── clients
├── drivers
├── riders
├── notifications
├── password_resets
├── sync_queue           ← Created by sync_trigger.php
└── [other tables]
```

### LAPTOP-B Database

```
campuslink/
├── bookings            ← IDENTICAL structure
├── clients             ← IDENTICAL structure
├── drivers             ← IDENTICAL structure
├── riders              ← IDENTICAL structure
├── notifications       ← IDENTICAL structure
├── password_resets     ← IDENTICAL structure
├── sync_queue          ← Created by sync_trigger.php
└── [other tables]      ← IDENTICAL structure
```

## Data Flow Comparison

### When you create a rider on LAPTOP-A:

```
LAPTOP-A                          LAPTOP-B
│                                 │
├─ Create rider ────────────────┐ │
│  (register form)              │ │
│                                │ │
├─ Insert in riders table        │ │
│  (id=123, name='John')         │ │
│                                │ │
├─ Queue in sync_queue           │ │
│  (status='pending')            │ │
│                                │ │
├─ Every 5 minutes:              │ │
│  sync_trigger.php runs         │ │
│                                │ │
├─ POST to sync_receive.php ────►├─ Receive sync
│  (API key, table, data)        │
│                                │ ├─ Insert rider
│                                │ │ (same id, name)
│                                │ │
│                                │ ├─ Skip own sync
│                                │ │ (prevent loop)
│                                │ │
├─ Mark synced ◄────────────────┤├─ Success response
│  (status='synced')             │ │
│                                │ └─ Done ✅
└─ Both databases now identical──┴─
```

### When you create a driver on LAPTOP-B:

```
LAPTOP-B                          LAPTOP-A
│                                 │
├─ Create driver ────────────────┐ │
│  (register form)              │ │
│                                │ │
├─ Insert in drivers table       │ │
│  (id=456, name='Jane')         │ │
│                                │ │
├─ Queue in sync_queue           │ │
│  (status='pending')            │ │
│                                │ │
├─ Every 5 minutes:              │ │
│  sync_trigger.php runs         │ │
│                                │ │
├─ POST to sync_receive.php ────►├─ Receive sync
│  (API key, table, data)        │
│                                │ ├─ Insert driver
│                                │ │ (same id, name)
│                                │ │
│                                │ ├─ Skip own sync
│                                │ │ (prevent loop)
│                                │ │
├─ Mark synced ◄────────────────┤├─ Success response
│  (status='synced')             │ │
│                                │ └─ Done ✅
└─ Both databases now identical──┴─
```

## Setup Steps Comparison

### LAPTOP-A Setup (7 steps)

1. ✅ XAMPP already running
2. ✅ CAMPUS-LINK folder already copied
3. ✅ Database already set up
4. ✅ config.php already configured
5. ✅ sync_queue table created
6. ✅ Task Scheduler configured
7. ✅ Code files migrated

### LAPTOP-B Setup (7 steps, mostly same)

1. ⏳ Install XAMPP (if needed)
2. ⏳ Copy CAMPUS-LINK folder
3. ⏳ Import campuslink.sql
4. ⏳ Update config.php (same as A)
5. ⏳ Run sync_trigger.php
6. ⏳ Configure Task Scheduler
7. ⏳ Migrate code files

**Time for LAPTOP-B**: ~1-2 hours

## Troubleshooting: What's Different?

| Issue                    | LAPTOP-A             | LAPTOP-B             | Solution                        |
| ------------------------ | -------------------- | -------------------- | ------------------------------- |
| Apache won't start       | Check port 80        | Check port 80        | Change Apache port              |
| Database won't start     | Check MariaDB        | Check MariaDB        | Reinstall XAMPP                 |
| config.php has wrong IPs | Edit config.php      | Edit config.php      | Use 172.19.25.101 and .102      |
| Can't reach LAPTOP-B     | Check WiFi           | Check WiFi           | Verify both on same network     |
| Sync not working         | Check Task Scheduler | Check Task Scheduler | Enable task, set interval 5 min |
| API key rejected         | Check SYNC_API_KEY   | Check SYNC_API_KEY   | Both must be identical          |
| No target server         | Check Master/Slave   | Check Master/Slave   | Both must have same IPs         |

## Quick Checklist

### LAPTOP-A (Already done ✅)

- [x] XAMPP running
- [x] CAMPUS-LINK copied
- [x] Database imported
- [x] config.php configured
- [x] sync_queue created
- [x] Task Scheduler set up
- [x] Code migrated

### LAPTOP-B (Do this now)

- [ ] XAMPP running
- [ ] CAMPUS-LINK copied
- [ ] Database imported
- [ ] config.php configured **⚠️ SAME VALUES AS A**
- [ ] sync_queue created
- [ ] Task Scheduler set up
- [ ] Code migrated

## Network Scenarios

### Same WiFi (Simplest)

```
Both laptops on same WiFi (e.g., home or office)
│
├─ LAPTOP-A: 192.168.1.10
├─ LAPTOP-B: 192.168.1.20
│
├─ config.php:
│  MASTER_SERVER = 192.168.1.10
│  SLAVE_SERVER = 192.168.1.20
│
└─ No additional setup needed ✅
```

### Different WiFi (Use ngrok)

```
LAPTOP-A: Home WiFi (192.168.1.10)
LAPTOP-B: Mobile Hotspot (10.0.0.50)
│
├─ Install ngrok on LAPTOP-A
├─ Run: ngrok http 80
├─ Get URL: https://abc123.ngrok.io
│
├─ config.php on BOTH:
│  MASTER_SERVER = https://abc123.ngrok.io
│  SLAVE_SERVER = 10.0.0.50 (or another ngrok)
│
└─ Both sync over internet ✅
```

## Summary

| Component           | LAPTOP-A         | LAPTOP-B                  |
| ------------------- | ---------------- | ------------------------- |
| **Role**            | Server 1         | Server 2                  |
| **Code**            | Same             | Same                      |
| **Database**        | campuslink       | campuslink (identical)    |
| **IP**              | 172.19.25.101    | 172.19.25.102             |
| **config.php**      | Master/Slave IPs | **Same Master/Slave IPs** |
| **Syncs to**        | LAPTOP-B         | LAPTOP-A                  |
| **Receives from**   | LAPTOP-B         | LAPTOP-A                  |
| **Task Scheduler**  | Every 5 min      | Every 5 min               |
| **Auto-sync**       | Yes              | Yes                       |
| **Can create data** | Yes              | Yes                       |
| **Can update data** | Yes              | Yes                       |
| **Can delete data** | Yes              | Yes                       |
| **Bidirectional**   | ✅ Yes           | ✅ Yes                    |

**Both laptops are equal servers that automatically keep each other in sync!** 🎉

See `LAPTOP_B_SETUP.md` for complete step-by-step instructions.
