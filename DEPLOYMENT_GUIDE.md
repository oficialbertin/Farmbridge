# FarmBridge AI Production Deployment Guide

## Files to Upload via FTP

### Core Application Files
- All PHP files (except test files)
- All CSS/JS files
- All image assets
- All configuration files

### Files to EXCLUDE from Upload
- `test_email.php` (remove or rename)
- `email_secret.php` (use production version)
- `db.php` (use production version)
- `.git/` folder
- `database/` folder (unless needed)
- `*.log` files
- `pending_verifications.json` (if exists)

### Directory Structure on Server
```
/public_html/
├── index.php
├── login.php
├── register.php
├── admin.php
├── crops.php
├── product.php
├── checkout.php
├── header.php
├── footer.php
├── assets/
│   ├── logo.png
│   └── styles.css
├── uploads/
└── config/
    ├── db_production.php
    ├── email_production.php
    └── config_production.php
```

## Database Setup Commands (SSH)

```sql
-- Create database
CREATE DATABASE farmbridge_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace with your credentials)
CREATE USER 'farmbridge_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON farmbridge_production.* TO 'farmbridge_user'@'localhost';
FLUSH PRIVILEGES;

-- Import database schema
USE farmbridge_production;
SOURCE /path/to/schema.sql;
```

## File Permissions (SSH Commands)

```bash
# Set proper permissions
chmod 755 /public_html/
chmod 644 /public_html/*.php
chmod 755 /public_html/uploads/
chmod 644 /public_html/assets/*
chmod 600 /public_html/config/*.php
```

## Environment Variables (if supported)

```bash
# Set production environment
export DB_HOST=localhost
export DB_USER=farmbridge_user
export DB_PASS=your_secure_password
export DB_NAME=farmbridge_production
export SITE_URL=https://www.farmbridge.rw
```

## Post-Deployment Checklist

1. ✅ Upload all files via FTP
2. ✅ Set up database via SSH
3. ✅ Configure email settings
4. ✅ Test website functionality
5. ✅ Test email sending
6. ✅ Test user registration/login
7. ✅ Test admin functions
8. ✅ Test marketplace functionality
9. ✅ Set up SSL certificate (if not already)
10. ✅ Configure backups

