# Setup & Configuration Guide

## 1. Environment Configuration

### Create `.env` file from template:

```bash
cp .env.example .env
```

### Edit `.env` with your settings:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=job1234joy#
DB_NAME=campusLink
DB_CHARSET=utf8mb4

# Security Settings
SESSION_LIFETIME=1800          # 30 minutes
MAX_LOGIN_ATTEMPTS=5           # Per LOGIN_ATTEMPT_WINDOW
LOGIN_ATTEMPT_WINDOW=900       # 15 minutes

# CORS Settings (for API calls)
ALLOWED_ORIGINS=http://localhost,http://127.0.0.1

# File Upload Settings
MAX_UPLOAD_SIZE=5242880        # 5MB
ALLOWED_PHOTO_TYPES=image/jpeg,image/png,image/gif,image/jpg

# Environment
APP_ENV=development            # 'production' or 'development'
DEBUG_MODE=false              # true or false
```

## 2. Directory Setup

### Create required directories:

```bash
mkdir -p logs
mkdir -p uploads_driver
mkdir -p upload_rider
mkdir -p /tmp/campuslink_sessions
```

### Set proper permissions:

```bash
# Logs directory
chmod 755 logs

# Session directory
chmod 700 /tmp/campuslink_sessions

# Upload directories
chmod 755 uploads_driver
chmod 755 upload_rider
```

## 3. Database Setup

### Import database schema:

```bash
mysql -u root -p campusLink < campuslink.sql
```

### Add recommended indexes for performance:

```sql
ALTER TABLE riders ADD INDEX idx_email_username (Email, Username);
ALTER TABLE drivers ADD INDEX idx_email_username (Email, Username);
ALTER TABLE clients ADD INDEX idx_email_username (Email, Username);
ALTER TABLE bookings ADD INDEX idx_agent_id (agent_id);
ALTER TABLE bookings ADD INDEX idx_client_id (client_id);
ALTER TABLE bookings ADD INDEX idx_created_at (created_at);
```

## 4. Security Configuration (Production)

### Update `.env` for production:

```env
APP_ENV=production
DEBUG_MODE=false
SESSION_LIFETIME=1800
MAX_LOGIN_ATTEMPTS=3
LOGIN_ATTEMPT_WINDOW=1800
ALLOWED_ORIGINS=https://yourdomain.com
```

### Enable HTTPS:

- Install SSL certificate (Let's Encrypt recommended)
- Configure Apache/Nginx for HTTPS
- Update `ALLOWED_ORIGINS` with HTTPS URLs

### Disable error display:

```php
// In db_connect.php:
ini_set('display_errors', 0);
error_reporting(E_ALL);
```

## 5. File Permissions

### Set proper ownership:

```bash
chown -R www-data:www-data /var/www/html/CAMPUS-LINK
chmod -R 755 /var/www/html/CAMPUS-LINK
chmod 600 /var/www/html/CAMPUS-LINK/.env
```

## 6. Monitoring Setup

### Check security logs:

```bash
tail -f logs/security.log
```

### Monitor login attempts:

```bash
grep "LOGIN" logs/security.log
```

### Check for attacks:

```bash
grep "RATE_LIMITED\|HIJACK" logs/security.log
```

## 7. Testing the System

### Test User Registration:

1. Navigate to: `http://localhost/CAMPUS-LINK/riders/rider.html`
2. Register as a rider with valid credentials
3. Check `/logs/security.log` for LOGIN_SUCCESS event

### Test Rate Limiting:

1. Try logging in with wrong password 5+ times
2. On 5th attempt, should get "Too many login attempts" error
3. Check `/logs/security.log` for RATE_LIMITED events

### Test Session Timeout:

1. Login successfully
2. Wait 31 minutes without activity
3. Refresh page, should redirect to login

### Test Secure Passwords:

1. Try password with only lowercase: "password123" ❌
2. Try strong password: "MyPassword123!" ✅
3. Check validation in registration page

## 8. Backup & Recovery

### Regular backups:

```bash
# Database backup
mysqldump -u root -p campusLink > backup_$(date +%Y%m%d_%H%M%S).sql

# Full system backup
tar -czf campuslink_backup_$(date +%Y%m%d).tar.gz /var/www/html/CAMPUS-LINK
```

### Restore from backup:

```bash
# Database restore
mysql -u root -p campusLink < backup_20231113_120000.sql

# System restore
tar -xzf campuslink_backup_20231113.tar.gz -C /var/www/html/
```

## 9. Performance Tuning

### MySQL optimization:

```sql
-- Check if indexes exist
SHOW INDEXES FROM riders;

-- Analyze table statistics
ANALYZE TABLE riders;
ANALYZE TABLE drivers;
ANALYZE TABLE clients;
ANALYZE TABLE bookings;

-- Check slow queries
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### PHP optimization:

```ini
; In php.ini
max_execution_time = 30
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
```

## 10. Troubleshooting

### Login not working

- Check `.env` database credentials
- Verify database connection: `mysql -u root -p campusLink -e "SELECT 1;"`
- Check `/logs/security.log` for errors
- Verify user exists in database

### Photos not displaying

- Check upload directories exist: `ls uploads_driver/ upload_rider/`
- Verify file permissions: `chmod 755 uploads_driver/`
- Check session photo path: `echo $_SESSION['profile_photo'];`

### Rate limiting too strict

- Increase `MAX_LOGIN_ATTEMPTS` in `.env`
- Increase `LOGIN_ATTEMPT_WINDOW` for longer window
- Check logs for false positives

### Slow login

- Check database indexes are created
- Run `ANALYZE TABLE` on all tables
- Check MySQL performance: `SHOW STATUS;`

---

**Questions?** Check SECURITY_IMPROVEMENTS.md for detailed information.
