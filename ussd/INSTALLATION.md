# FarmBridge AI USSD Application Installation Guide

This guide will help you install and configure the FarmBridge AI USSD application on your server.

## 📋 Prerequisites

### System Requirements
- **Operating System**: Ubuntu 20.04 LTS or CentOS 8+
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: Minimum 2GB RAM
- **Storage**: Minimum 10GB free space
- **Network**: Stable internet connection

### Software Dependencies
- Composer (PHP package manager)
- Git (version control)
- SSL certificate (for production)
- Africa's Talking account

## 🚀 Installation Steps

### Step 1: System Preparation

#### Update System Packages
```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

#### Install Required Packages
```bash
# Ubuntu/Debian
sudo apt install -y php7.4 php7.4-cli php7.4-fpm php7.4-mysql php7.4-json php7.4-curl php7.4-mbstring php7.4-xml php7.4-zip composer git curl wget unzip

# CentOS/RHEL
sudo yum install -y php74 php74-cli php74-fpm php74-mysql php74-json php74-curl php74-mbstring php74-xml php74-zip composer git curl wget unzip
```

### Step 2: Database Setup

#### Install MySQL
```bash
# Ubuntu/Debian
sudo apt install -y mysql-server mysql-client

# CentOS/RHEL
sudo yum install -y mysql-server mysql
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

#### Secure MySQL Installation
```bash
sudo mysql_secure_installation
```

#### Create Database and User
```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE farmbridge_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ussd_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON farmbridge_ai.* TO 'ussd_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Import Database Schema
```bash
mysql -u ussd_user -p farmbridge_ai < database_schema.sql
```

### Step 3: Application Installation

#### Create Application Directory
```bash
sudo mkdir -p /var/www/html/ussd
sudo chown -R www-data:www-data /var/www/html/ussd
```

#### Clone or Upload Application Files
```bash
# If using Git
cd /var/www/html/ussd
git clone <repository-url> .

# Or upload files manually
# Upload all files to /var/www/html/ussd/
```

#### Install PHP Dependencies
```bash
cd /var/www/html/ussd
composer install --no-dev --optimize-autoloader
```

### Step 4: Configuration

#### Copy Configuration File
```bash
cp config.example.php config.php
```

#### Edit Configuration
```bash
sudo nano config.php
```

Update the following settings:
```php
'database' => [
    'host' => 'localhost',
    'name' => 'farmbridge_ai',
    'user' => 'ussd_user',
    'pass' => 'secure_password_here',
],

'africas_talking' => [
    'username' => 'your_at_username',
    'api_key' => 'your_at_api_key',
    'company' => 'FarmBridge AI',
    'short_code' => 4627,
],
```

#### Set File Permissions
```bash
sudo chown -R www-data:www-data /var/www/html/ussd
sudo chmod -R 755 /var/www/html/ussd
sudo chmod -R 644 /var/www/html/ussd/*.php
sudo chmod +x /var/www/html/ussd/test_ussd.php
```

### Step 5: Web Server Configuration

#### Apache Configuration
```bash
sudo nano /etc/apache2/sites-available/ussd.conf
```

```apache
<VirtualHost *:80>
    ServerName ussd.farmbridgeai.com
    DocumentRoot /var/www/html/ussd
    
    <Directory /var/www/html/ussd>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ussd_error.log
    CustomLog ${APACHE_LOG_DIR}/ussd_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite ussd.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/ussd
```

```nginx
server {
    listen 80;
    server_name ussd.farmbridgeai.com;
    root /var/www/html/ussd;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/ussd /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 6: SSL Certificate Setup

#### Install Certbot
```bash
# Ubuntu/Debian
sudo apt install -y certbot python3-certbot-apache

# CentOS/RHEL
sudo yum install -y certbot python3-certbot-nginx
```

#### Obtain SSL Certificate
```bash
# For Apache
sudo certbot --apache -d ussd.farmbridgeai.com

# For Nginx
sudo certbot --nginx -d ussd.farmbridgeai.com
```

### Step 7: Africa's Talking Configuration

#### Create Africa's Talking Account
1. Visit [Africa's Talking](https://africastalking.com)
2. Create an account
3. Navigate to USSD section
4. Create a new USSD service

#### Configure USSD Service
- **Service Code**: `*384*123#` (or your preferred code)
- **Callback URL**: `https://ussd.farmbridgeai.com/index.php`
- **HTTP Method**: POST

#### Update API Credentials
```bash
sudo nano /var/www/html/ussd/config.php
```

Update Africa's Talking credentials:
```php
'africas_talking' => [
    'username' => 'your_sandbox_username',
    'api_key' => 'your_api_key_here',
    'company' => 'FarmBridge AI',
    'short_code' => 4627,
],
```

### Step 8: Testing

#### Test Database Connection
```bash
cd /var/www/html/ussd
php test_ussd.php
```

#### Test Web Server
```bash
curl -X POST https://ussd.farmbridgeai.com/index.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=test123&serviceCode=*384*123#&phoneNumber=250788123456&text="
```

#### Test USSD Flow
1. Dial the USSD code from your phone
2. Follow the menu prompts
3. Verify all features work correctly

### Step 9: Monitoring Setup

#### Create Log Directory
```bash
sudo mkdir -p /var/log/ussd
sudo chown www-data:www-data /var/log/ussd
```

#### Setup Log Rotation
```bash
sudo nano /etc/logrotate.d/ussd
```

```
/var/log/ussd/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

#### Setup Monitoring Service
```bash
sudo nano /etc/systemd/system/ussd-monitor.service
```

```ini
[Unit]
Description=FarmBridge AI USSD Monitor
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/ussd
ExecStart=/usr/bin/php /var/www/html/ussd/monitor.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable ussd-monitor.service
sudo systemctl start ussd-monitor.service
```

### Step 10: Backup Setup

#### Create Backup Script
```bash
sudo nano /usr/local/bin/ussd-backup.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/ussd"
DATE=$(date +%Y%m%d-%H%M%S)
BACKUP_NAME="ussd-backup-$DATE"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u ussd_user -p farmbridge_ai > $BACKUP_DIR/$BACKUP_NAME.sql

# Backup application files
tar -czf $BACKUP_DIR/$BACKUP_NAME.tar.gz /var/www/html/ussd

# Cleanup old backups (keep last 30 days)
find $BACKUP_DIR -name "ussd-backup-*" -mtime +30 -delete

echo "Backup completed: $BACKUP_NAME"
```

```bash
sudo chmod +x /usr/local/bin/ussd-backup.sh
```

#### Setup Cron Job
```bash
sudo crontab -e
```

Add the following line for daily backups:
```
0 2 * * * /usr/local/bin/ussd-backup.sh
```

## 🔧 Configuration Options

### Environment Variables
You can set environment variables in `/var/www/html/ussd/.env`:

```bash
# Database
DB_HOST=localhost
DB_NAME=farmbridge_ai
DB_USER=ussd_user
DB_PASS=secure_password_here

# Africa's Talking
AT_USERNAME=your_username
AT_API_KEY=your_api_key
AT_COMPANY=FarmBridge AI
AT_SHORT_CODE=4627

# Application
APP_ENV=production
APP_DEBUG=false
```

### Feature Flags
Enable/disable features in `config.php`:

```php
'features' => [
    'market_prices' => true,
    'farming_tips' => true,
    'sms_notifications' => true,
    'price_alerts' => true,
    'user_registration' => true,
    'product_listing' => true,
    'order_management' => true,
    'dispute_resolution' => true,
],
```

## 🚨 Troubleshooting

### Common Issues

#### Database Connection Failed
```bash
# Check MySQL service
sudo systemctl status mysql

# Test connection
mysql -u ussd_user -p farmbridge_ai

# Check credentials in config.php
```

#### USSD Not Working
```bash
# Check web server logs
sudo tail -f /var/log/apache2/ussd_error.log
sudo tail -f /var/log/nginx/ussd_error.log

# Test callback URL
curl -X POST https://ussd.farmbridgeai.com/index.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=test&serviceCode=*384*123#&phoneNumber=250788123456&text="
```

#### SMS Not Sending
```bash
# Check Africa's Talking credentials
# Verify API key and username
# Check SMS logs in database
```

#### Performance Issues
```bash
# Check system resources
htop
df -h
free -h

# Check database performance
mysql -u ussd_user -p farmbridge_ai -e "SHOW PROCESSLIST;"

# Check application logs
sudo tail -f /var/log/ussd/monitor.log
```

### Log Files
- **Application Logs**: `/var/log/ussd/`
- **Web Server Logs**: `/var/log/apache2/` or `/var/log/nginx/`
- **System Logs**: `/var/log/syslog`
- **Database Logs**: `/var/log/mysql/`

### Performance Optimization

#### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_crops_farmer_id ON crops(farmer_id);
CREATE INDEX idx_orders_buyer_id ON orders(buyer_id);
CREATE INDEX idx_ussd_sessions_phone ON ussd_sessions(phone_number);
```

#### PHP Optimization
```bash
# Edit php.ini
sudo nano /etc/php/7.4/fpm/php.ini

# Optimize settings
memory_limit = 256M
max_execution_time = 30
upload_max_filesize = 10M
post_max_size = 10M
```

#### Web Server Optimization
```bash
# Apache optimization
sudo nano /etc/apache2/apache2.conf

# Add these settings
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5
```

## 📊 Monitoring

### Health Checks
```bash
# Check application status
sudo systemctl status ussd-monitor

# Check database status
sudo systemctl status mysql

# Check web server status
sudo systemctl status apache2
# or
sudo systemctl status nginx
```

### Metrics
- **Active Sessions**: Monitor in database
- **SMS Delivery**: Check SMS logs
- **Error Rates**: Monitor error logs
- **Response Times**: Check web server logs

## 🔒 Security

### Firewall Configuration
```bash
# Ubuntu/Debian
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# CentOS/RHEL
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### File Permissions
```bash
# Set secure permissions
sudo chown -R www-data:www-data /var/www/html/ussd
sudo chmod -R 755 /var/www/html/ussd
sudo chmod -R 644 /var/www/html/ussd/*.php
sudo chmod 600 /var/www/html/ussd/config.php
```

### SSL Configuration
```bash
# Test SSL configuration
openssl s_client -connect ussd.farmbridgeai.com:443

# Check SSL rating
curl -I https://ussd.farmbridgeai.com
```

## 📞 Support

### Getting Help
- **Documentation**: Check README.md and this guide
- **Issues**: Submit bug reports on GitHub
- **Email**: support@farmbridgeai.com
- **Phone**: +250 788 123 456

### Maintenance
- **Daily**: Check logs and monitor system
- **Weekly**: Review performance metrics
- **Monthly**: Update dependencies and security patches
- **Quarterly**: Review and optimize database

---

**Installation completed successfully!** 🎉

Your FarmBridge AI USSD application is now ready for use. Remember to:
1. Test all features thoroughly
2. Monitor system performance
3. Keep backups updated
4. Apply security updates regularly
5. Monitor logs for any issues
