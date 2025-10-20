# 🚀 FarmBridge AI - VS Code Deployment Guide

Deploy your FarmBridge AI platform to production using VS Code Remote-SSH and SFTP extensions.

## 📋 Prerequisites

- VS Code installed
- Server access details from [CloudPanel](https://server.hdevtech.cloud/dashboard)
- FarmBridge AI project ready

## 🔧 Step 1: Install VS Code Extensions

1. Open VS Code
2. Go to Extensions (Ctrl+Shift+X)
3. Install these extensions:
   - **Remote - SSH** (by Microsoft)
   - **SFTP** (by liximomo)

## 🔐 Step 2: Configure Remote-SSH Connection

1. Press `Ctrl + Shift + P`
2. Type: `Remote-SSH: Connect to Host`
3. Select `Add New SSH Host`
4. Enter: `ssh root@server.hdevtech.cloud`
5. Choose SSH config file (default)
6. Connect and enter your password

## 📁 Step 3: Configure SFTP Upload

1. Press `Ctrl + Shift + P`
2. Type: `SFTP: Config`
3. It creates `.vscode/sftp.json`
4. Update with your server details:

```json
{
  "name": "FarmBridge Production Server",
  "host": "server.hdevtech.cloud",
  "protocol": "sftp",
  "port": 22,
  "username": "root",
  "password": "your_server_password",
  "remotePath": "/var/www/html/farmbridge",
  "uploadOnSave": true,
  "ignore": [
    ".vscode",
    ".git",
    "node_modules",
    "*.log",
    "test_email.php",
    "email_secret.php"
  ]
}
```

## 🚀 Step 4: Prepare Files for Production

1. Run the deployment helper:
   ```bash
   php deploy_to_production.php
   ```

2. Update production config files:
   - Edit `production/config/db_production.php`
   - Edit `production/config/email_production.php`

## 📤 Step 5: Upload Files via SFTP

1. Right-click on `production` folder
2. Select `SFTP: Upload Folder`
3. Files upload to `/var/www/html/farmbridge/`

## 🗄️ Step 6: Set Up Database on Server

1. Connect via Remote-SSH
2. Open terminal in VS Code
3. Run database setup:
   ```bash
   mysql -u root -p < setup_production_database.sql
   ```

## ⚙️ Step 7: Configure Production Settings

1. Update `db_production.php`:
   ```php
   return [
       'host' => 'localhost',
       'username' => 'farmbridge_user',
       'password' => 'your_secure_password',
       'database' => 'farmbridge_production',
       'port' => 3306,
       'charset' => 'utf8mb4'
   ];
   ```

2. Update `email_production.php`:
   ```php
   return [
       'smtp_username' => 'your-production-email@gmail.com',
       'smtp_password' => 'your-gmail-app-password',
       'from_email' => 'your-production-email@gmail.com',
       'from_name' => 'FarmBridge AI Rwanda',
       'smtp_host' => 'smtp.gmail.com',
       'smtp_port' => 587,
       'smtp_secure' => 'tls',
   ];
   ```

## 🔒 Step 8: Set File Permissions

In VS Code terminal (connected to server):
```bash
chmod 755 /var/www/html/farmbridge/
chmod 644 /var/www/html/farmbridge/*.php
chmod 755 /var/www/html/farmbridge/uploads/
chmod 600 /var/www/html/farmbridge/config/*.php
```

## 🧪 Step 9: Test Your Live Website

1. Visit: `https://www.farmbridge.rw`
2. Test registration/login
3. Test admin functions
4. Test email sending
5. Test marketplace functionality

## 🔄 Step 10: Live Development Workflow

Once set up, you can:

- **Edit files live**: Changes sync automatically
- **Run commands remotely**: Use VS Code terminal
- **Debug on server**: Use Remote-SSH debugging
- **Upload instantly**: Save files to upload

## 🛠️ Useful Commands (Run in VS Code Terminal)

```bash
# Check PHP version
php -v

# Restart web server
sudo systemctl restart apache2
# or
sudo systemctl restart nginx

# Check database connection
mysql -u farmbridge_user -p farmbridge_production

# View error logs
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log

# Check disk space
df -h

# Check memory usage
free -h
```

## 🔐 Security Best Practices

1. **Change default admin password** after first login
2. **Use SSH keys** instead of passwords
3. **Enable firewall** and restrict SSH access
4. **Regular backups** of database and files
5. **Keep software updated**

## 🆘 Troubleshooting

### Connection Issues
- Check server credentials
- Verify SSH port (usually 22)
- Check firewall settings

### Upload Issues
- Verify SFTP configuration
- Check remote path exists
- Verify file permissions

### Database Issues
- Check database credentials
- Verify database exists
- Check MySQL service status

### Email Issues
- Test SMTP configuration
- Check Gmail App Password
- Verify firewall allows port 587

## 📞 Support

If you encounter issues:
1. Check VS Code output panel for errors
2. Review server logs
3. Test individual components
4. Contact hosting provider if needed

---

**🎉 Congratulations!** Your FarmBridge AI platform is now live and ready for farmers and buyers in Rwanda!

