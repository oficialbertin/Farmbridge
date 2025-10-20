#!/bin/bash

# FarmBridge AI USSD Application Deployment Script
# This script helps deploy the USSD application to production

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="FarmBridge AI USSD"
APP_DIR="/var/www/html/ussd"
BACKUP_DIR="/var/backups/ussd"
LOG_FILE="/var/log/ussd-deploy.log"

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a $LOG_FILE
}

success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a $LOG_FILE
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a $LOG_FILE
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a $LOG_FILE
    exit 1
}

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root"
    fi
}

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites..."
    
    # Check if PHP is installed
    if ! command -v php &> /dev/null; then
        error "PHP is not installed"
    fi
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    if [[ $(echo "$PHP_VERSION 7.4" | awk '{print ($1 >= $2)}') -eq 0 ]]; then
        error "PHP 7.4 or higher is required. Current version: $PHP_VERSION"
    fi
    
    # Check if Composer is installed
    if ! command -v composer &> /dev/null; then
        error "Composer is not installed"
    fi
    
    # Check if MySQL is running
    if ! systemctl is-active --quiet mysql; then
        error "MySQL is not running"
    fi
    
    success "Prerequisites check passed"
}

# Create backup
create_backup() {
    log "Creating backup..."
    
    if [ -d "$APP_DIR" ]; then
        BACKUP_NAME="ussd-backup-$(date +%Y%m%d-%H%M%S)"
        mkdir -p $BACKUP_DIR
        
        cp -r $APP_DIR $BACKUP_DIR/$BACKUP_NAME
        success "Backup created: $BACKUP_DIR/$BACKUP_NAME"
    else
        warning "No existing application found to backup"
    fi
}

# Install dependencies
install_dependencies() {
    log "Installing dependencies..."
    
    cd $APP_DIR
    
    # Install Composer dependencies
    composer install --no-dev --optimize-autoloader
    
    success "Dependencies installed"
}

# Set permissions
set_permissions() {
    log "Setting permissions..."
    
    # Set ownership
    sudo chown -R www-data:www-data $APP_DIR
    
    # Set permissions
    sudo chmod -R 755 $APP_DIR
    sudo chmod -R 644 $APP_DIR/*.php
    sudo chmod -R 644 $APP_DIR/*.json
    sudo chmod -R 644 $APP_DIR/*.md
    
    # Make scripts executable
    sudo chmod +x $APP_DIR/test_ussd.php
    
    success "Permissions set"
}

# Configure application
configure_app() {
    log "Configuring application..."
    
    # Copy configuration file if it doesn't exist
    if [ ! -f "$APP_DIR/config.php" ]; then
        cp $APP_DIR/config.example.php $APP_DIR/config.php
        warning "Configuration file created. Please update config.php with your settings"
    fi
    
    # Create log directory
    sudo mkdir -p /var/log/ussd
    sudo chown www-data:www-data /var/log/ussd
    
    success "Application configured"
}

# Test application
test_application() {
    log "Testing application..."
    
    cd $APP_DIR
    
    # Run PHP syntax check
    php -l index.php
    php -l menu.php
    php -l util.php
    php -l database.php
    php -l sms.php
    
    # Run test script
    php test_ussd.php
    
    success "Application tests passed"
}

# Configure web server
configure_webserver() {
    log "Configuring web server..."
    
    # Check if Apache is installed
    if command -v apache2 &> /dev/null; then
        # Create Apache virtual host
        sudo tee /etc/apache2/sites-available/ussd.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName ussd.farmbridgeai.com
    DocumentRoot $APP_DIR
    
    <Directory $APP_DIR>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/ussd_error.log
    CustomLog \${APACHE_LOG_DIR}/ussd_access.log combined
</VirtualHost>
EOF
        
        # Enable site
        sudo a2ensite ussd.conf
        sudo systemctl reload apache2
        
        success "Apache configured"
    elif command -v nginx &> /dev/null; then
        # Create Nginx virtual host
        sudo tee /etc/nginx/sites-available/ussd > /dev/null <<EOF
server {
    listen 80;
    server_name ussd.farmbridgeai.com;
    root $APP_DIR;
    index index.php;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
EOF
        
        # Enable site
        sudo ln -sf /etc/nginx/sites-available/ussd /etc/nginx/sites-enabled/
        sudo nginx -t
        sudo systemctl reload nginx
        
        success "Nginx configured"
    else
        warning "No web server found. Please configure manually"
    fi
}

# Setup SSL
setup_ssl() {
    log "Setting up SSL..."
    
    if command -v certbot &> /dev/null; then
        sudo certbot --nginx -d ussd.farmbridgeai.com --non-interactive --agree-tos --email admin@farmbridgeai.com
        success "SSL certificate installed"
    else
        warning "Certbot not found. Please install SSL certificate manually"
    fi
}

# Setup monitoring
setup_monitoring() {
    log "Setting up monitoring..."
    
    # Create logrotate configuration
    sudo tee /etc/logrotate.d/ussd > /dev/null <<EOF
/var/log/ussd/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
EOF
    
    # Create systemd service for log monitoring
    sudo tee /etc/systemd/system/ussd-monitor.service > /dev/null <<EOF
[Unit]
Description=FarmBridge AI USSD Monitor
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php $APP_DIR/monitor.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
    
    sudo systemctl daemon-reload
    sudo systemctl enable ussd-monitor.service
    
    success "Monitoring configured"
}

# Main deployment function
deploy() {
    log "Starting deployment of $APP_NAME..."
    
    check_root
    check_prerequisites
    create_backup
    install_dependencies
    set_permissions
    configure_app
    test_application
    configure_webserver
    setup_ssl
    setup_monitoring
    
    success "Deployment completed successfully!"
    
    log "Next steps:"
    log "1. Update config.php with your actual settings"
    log "2. Configure Africa's Talking USSD service"
    log "3. Test the USSD flow"
    log "4. Monitor logs for any issues"
}

# Rollback function
rollback() {
    log "Rolling back deployment..."
    
    if [ -d "$BACKUP_DIR" ]; then
        LATEST_BACKUP=$(ls -t $BACKUP_DIR | head -n1)
        if [ -n "$LATEST_BACKUP" ]; then
            rm -rf $APP_DIR
            cp -r $BACKUP_DIR/$LATEST_BACKUP $APP_DIR
            success "Rollback completed"
        else
            error "No backup found for rollback"
        fi
    else
        error "Backup directory not found"
    fi
}

# Status check function
status() {
    log "Checking application status..."
    
    # Check if application is running
    if [ -d "$APP_DIR" ]; then
        success "Application directory exists"
    else
        error "Application directory not found"
    fi
    
    # Check web server status
    if systemctl is-active --quiet apache2; then
        success "Apache is running"
    elif systemctl is-active --quiet nginx; then
        success "Nginx is running"
    else
        warning "Web server is not running"
    fi
    
    # Check database connection
    cd $APP_DIR
    php -r "
        require_once 'database.php';
        try {
            \$db = new Database();
            \$conn = \$db->getConnection();
            if (\$conn) {
                echo '✅ Database connection successful\n';
            } else {
                echo '❌ Database connection failed\n';
            }
        } catch (Exception \$e) {
            echo '❌ Database error: ' . \$e->getMessage() . '\n';
        }
    "
}

# Main script logic
case "${1:-deploy}" in
    deploy)
        deploy
        ;;
    rollback)
        rollback
        ;;
    status)
        status
        ;;
    test)
        test_application
        ;;
    *)
        echo "Usage: $0 {deploy|rollback|status|test}"
        echo ""
        echo "Commands:"
        echo "  deploy   - Deploy the application"
        echo "  rollback - Rollback to previous version"
        echo "  status   - Check application status"
        echo "  test     - Run application tests"
        exit 1
        ;;
esac
