# RAMICS 2026 Registration System

## 📁 Organized Structure

All registration system files are contained in this single `registration/` folder for easy management and removal if needed.

## 🚀 Quick Start

### For First Time Setup:
1. Visit: `registration/setup_admin.php` to set admin password
2. Visit: `registration/register.php` to test registration
3. Visit: `registration/view_registrations.php` to view admin panel

### Direct Links:
- **Public Registration Form**: `registration/register.php` (or just `registration/`)
- **Admin Login**: `registration/view_registrations.php`
- **Configuration**: `registration/admin_config.php`
- **Password Setup**: `registration/setup_admin.php`

## 📂 Folder Contents

```
registration/
├── index.php                    - Redirects to register.php
├── db.php                       - Database handler (12KB)
├── register.php                 - Public registration form (13KB)
├── registration_success.php     - Confirmation page (6KB)
├── view_registrations.php       - Admin panel (18KB)
├── admin_config.php            - Configuration panel (10KB)
├── setup_admin.php             - Password setup (7KB)
├── data/                       - Protected database directory
│   ├── .htaccess              - Denies web access
│   └── registrations.db       - SQLite database (chmod 600)
├── README.md                   - This file
├── QUICK_START.md             - Quick start guide
├── REGISTRATION_SYSTEM_README.md - Complete documentation
└── IMPLEMENTATION_SUMMARY.txt  - Implementation details
```

## 🗑️ Easy Removal

If your colleague doesn't like the system, simply delete this entire folder:

```bash
rm -rf registration/
```

Then remove the registration links from `index.php`:
- Line 67: `<a href="#registration">Registration</a>`
- Lines 224-226: Registration section content

## 🔐 Security Features

✅ SQL injection prevention (PDO prepared statements)
✅ XSS protection (htmlspecialchars)
✅ CSRF protection (tokens)
✅ Rate limiting (3 per hour per IP)
✅ Password hashing (bcrypt)
✅ Session timeout (30 minutes)
✅ Database protection (.htaccess + chmod 600)

## 📊 Features

### Public Registration
- Complete registration form
- Email validation & uniqueness check
- Deadline and capacity enforcement
- Confirmation page with details

### Admin Panel
- Password-protected access
- View all registrations
- Statistics dashboard
- Advanced filtering
- CSV export

### Configuration
- Update deadline
- Change capacity
- Enable/disable registration
- Real-time stats

## 📚 Documentation

- **QUICK_START.md** - Get started in 3 steps
- **REGISTRATION_SYSTEM_README.md** - Complete system documentation
- **IMPLEMENTATION_SUMMARY.txt** - Technical implementation details

## 🌐 Integration with Main Site

The main site (`index.php`) has been updated with:
- Registration link in navigation: `registration/register.php`
- Registration section in content (line 224-226)

## ⚙️ Configuration

Default settings (can be changed via admin panel):
- Deadline: March 15, 2026 23:59:59
- Capacity: 50 participants
- Status: Enabled
- Admin password: Not set (use setup_admin.php)

## 🔧 Database

- **Type**: SQLite 3
- **Location**: `data/registrations.db`
- **Permissions**: 600 (secure)
- **Web Access**: Blocked by .htaccess

Tables:
1. `registrations` - All registration data
2. `config` - System configuration
3. `rate_limit` - IP-based rate limiting

## 📞 Support

For questions or issues:
- Email: ramics2026@easychair.org
- Check `REGISTRATION_SYSTEM_README.md` for detailed docs
- Review PHP error logs for debugging

## ✨ System Status

✅ **Ready for Production**

- All files organized in single folder
- Database configured and tested
- Security measures active
- Documentation complete
- Zero registrations (clean slate)

---

**Last Updated**: February 2, 2026
**Version**: 1.0 (Organized Structure)
