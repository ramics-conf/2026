# RAMICS 2026 Registration System Documentation

## Overview

A complete conference registration system with SQLite database, public registration form, and password-protected admin panel.

## System Components

### Database (SQLite)
- **Location**: `/data/registrations.db`
- **Permissions**: 600 (read/write for owner only)
- **Protected**: Apache `.htaccess` denies web access

### Tables
1. **registrations** - Stores all registration data
2. **config** - System configuration (deadline, capacity, etc.)
3. **rate_limit** - IP-based rate limiting (3 attempts per hour)

### Files Created

| File | Purpose |
|------|---------|
| `db.php` | Database handler with static methods |
| `register.php` | Public registration form |
| `registration_success.php` | Confirmation page |
| `view_registrations.php` | Admin panel for viewing registrations |
| `admin_config.php` | Admin configuration panel |
| `setup_admin.php` | One-time admin password setup |
| `data/.htaccess` | Database protection |

## Getting Started

### Step 1: Set Admin Password

1. Visit: `setup_admin.php`
2. Set a strong password (minimum 12 characters)
3. Page automatically locks after setup

**Important**: Store the password securely. Password recovery is not available.

### Step 2: Configure System

Default settings:
- Registration deadline: March 15, 2026 23:59:59
- Capacity: 50 participants
- Registration: Enabled

To change settings:
1. Login to `admin_config.php`
2. Update deadline, capacity, or enable/disable registration
3. Changes take effect immediately

### Step 3: Test Registration

1. Visit `register.php`
2. Complete the form with test data
3. Verify confirmation page appears
4. Check admin panel to see the registration

## Features

### Security Features

✓ **SQL Injection Protection**: PDO prepared statements
✓ **XSS Prevention**: `htmlspecialchars()` on all output
✓ **CSRF Protection**: Token validation on all forms
✓ **Authentication**: Bcrypt password hashing
✓ **Rate Limiting**: 3 registrations per IP per hour
✓ **Session Security**: 30-minute timeout, regeneration
✓ **Database Protection**: `.htaccess` + file permissions

### Registration Form Features

- Required fields: Name, Email, Affiliation
- Optional: Dietary requirements, transport needs, invoice details
- Email uniqueness validation
- Deadline enforcement
- Capacity limit enforcement
- Real-time invoice details toggle
- Form data preservation on error

### Admin Panel Features

**View Registrations** (`view_registrations.php`):
- Password-protected access
- Complete registration table
- Statistics dashboard
- Search/filter by:
  - Name, email, affiliation
  - Transport needs
  - Invoice requests
  - Date range
- CSV export (respects filters)
- Session timeout protection

**Configuration** (`admin_config.php`):
- Update registration deadline
- Change capacity limit
- Enable/disable registration
- View current statistics
- Real-time status display

## Usage Instructions

### For Public Users

1. Go to main site (`index.html`)
2. Click "Registration" in sidebar
3. Complete the form
4. Review confirmation details
5. Contact organizers with questions at: ramics2026@easychair.org

### For Administrators

**Login**:
1. Visit `view_registrations.php`
2. Enter admin password
3. Session valid for 30 minutes

**View Registrations**:
- All registrations displayed in table
- Invoice details and notes shown in highlighted rows
- Use filters to search specific criteria

**Export Data**:
1. Apply filters (optional)
2. Click "Export to CSV"
3. Opens in Excel/Google Sheets
4. Filename: `ramics2026_registrations_YYYY-MM-DD.csv`

**Update Settings**:
1. Go to `admin_config.php`
2. Modify deadline/capacity/status
3. Click "Update Configuration"

**Logout**:
- Click "Logout" in sidebar
- Or close browser (session expires in 30 min)

## Database Schema

### registrations
```sql
id                    INTEGER PRIMARY KEY
full_name            TEXT NOT NULL
email                TEXT NOT NULL UNIQUE
affiliation          TEXT NOT NULL
dietary_requirements TEXT
needs_transport      INTEGER (0/1)
arrival_date         TEXT
arrival_time         TEXT
needs_invoice        INTEGER (0/1)
invoice_details      TEXT
additional_notes     TEXT
registration_date    TIMESTAMP
ip_address          TEXT
```

### config
```sql
key          TEXT PRIMARY KEY
value        TEXT NOT NULL
updated_at   TIMESTAMP
```

Default keys:
- `registration_deadline`
- `capacity_limit`
- `registration_enabled`
- `admin_password_hash`
- `admin_setup_locked`

## API Reference (DB Class)

### Initialization
```php
DB::init()                          // Initialize database
DB::getConnection()                 // Get PDO connection
```

### Registrations
```php
DB::insertRegistration($data)       // Insert new registration
DB::getAllRegistrations()           // Get all registrations
DB::getRegistrationCount()          // Count total registrations
DB::searchRegistrations($filters)   // Search with filters
DB::emailExists($email)             // Check duplicate email
```

### Configuration
```php
DB::getConfig($key)                 // Get config value
DB::setConfig($key, $value)         // Set config value
```

### Authentication
```php
DB::verifyAdminPassword($password)  // Verify password
DB::setAdminPassword($password)     // Set password (hashed)
```

### Export
```php
DB::exportToCSV($filters)           // Generate CSV export
```

### Statistics
```php
DB::getStatistics()                 // Get summary statistics
```

### Rate Limiting
```php
DB::checkRateLimit($ip, $limit, $window)  // Check IP rate limit
```

## Configuration Options

### Change Deadline
```php
DB::setConfig('registration_deadline', '2026-03-31 23:59:59');
```

### Change Capacity
```php
DB::setConfig('capacity_limit', '100');
```

### Disable Registration
```php
DB::setConfig('registration_enabled', '0');
```

## Troubleshooting

### "Registration deadline has passed"
- Check system time
- Update deadline in `admin_config.php`

### "Registration capacity reached"
- Increase capacity in `admin_config.php`
- Or export and remove test registrations

### "Invalid password"
- Verify caps lock is off
- If forgotten, database must be reset (no recovery)

### Database locked
- Check file permissions: `chmod 600 data/registrations.db`
- Ensure web server has write access

### CSV export is empty
- Check filters are not too restrictive
- Verify registrations exist in database

## Backup Recommendations

### Manual Backup
```bash
# Copy database file
cp data/registrations.db data/registrations_backup_$(date +%Y%m%d).db

# Export CSV via admin panel
# Or command line:
php -r "require 'db.php'; echo DB::exportToCSV();" > backup.csv
```

### Automated Backup (cron)
```bash
# Daily backup at 2 AM
0 2 * * * cd /path/to/web && cp data/registrations.db data/backups/registrations_$(date +\%Y\%m\%d).db
```

## Security Notes

1. **Admin password**: Use strong password (12+ chars, mixed case, numbers, symbols)
2. **HTTPS**: Deploy with SSL certificate for production
3. **Database**: Never commit `registrations.db` to version control
4. **Permissions**: Verify `data/` directory is not web-accessible
5. **Updates**: Keep PHP updated for security patches

## Integration with Main Site

Registration link added to:
- Navigation sidebar in `index.php`
- Registration section in body content

To regenerate static HTML:
```bash
php index.php > index.html
```

## Testing Checklist

- [x] Database initializes correctly
- [x] Admin password setup works
- [x] Registration form validation works
- [x] CSRF protection active
- [x] Duplicate email rejected
- [x] Rate limiting enforced
- [x] Admin login works
- [x] Session timeout works
- [x] Search/filter works
- [x] CSV export works
- [x] Configuration updates work
- [x] XSS attempts blocked
- [x] SQL injection blocked

## Support

For questions or issues:
- Email: ramics2026@easychair.org
- Check logs: PHP error log
- Database issues: SQLite documentation

## Version

- Created: February 2026
- PHP: 7.4+ required
- SQLite: 3+
- License: Conference use

---

**Important**: After initial setup, delete or restrict access to `setup_admin.php` for additional security.
