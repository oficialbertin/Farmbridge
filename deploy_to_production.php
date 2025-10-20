<?php
// Production deployment helper script
// Run this on your local machine to prepare files for production

echo "🚀 FarmBridge AI Production Deployment Helper\n";
echo "============================================\n\n";

// Check if we're in the right directory
if (!file_exists('index.php')) {
    die("❌ Error: Please run this script from the FarmBridge AI root directory\n");
}

echo "✅ Found FarmBridge AI project\n";

// Create production directory structure
$production_dirs = [
    'production',
    'production/config',
    'production/uploads',
    'production/assets'
];

foreach ($production_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "📁 Created directory: $dir\n";
    }
}

// Copy essential files
$files_to_copy = [
    'index.php' => 'production/',
    'login.php' => 'production/',
    'register.php' => 'production/',
    'admin.php' => 'production/',
    'buyer.php' => 'production/',
    'farmer.php' => 'production/',
    'crops.php' => 'production/',
    'product.php' => 'production/',
    'checkout.php' => 'production/',
    'process_order.php' => 'production/',
    'thanks.php' => 'production/',
    'profile.php' => 'production/',
    'logout.php' => 'production/',
    'header.php' => 'production/',
    'footer.php' => 'production/',
    'db.php' => 'production/',
    'settings_helpers.php' => 'production/',
    'email_verification_helpers.php' => 'production/',
    'email_smtp_working.php' => 'production/',
    'email_simple_helpers.php' => 'production/',
    'admin_settings.php' => 'production/',
    'admin_orders.php' => 'production/',
    'admin_payments.php' => 'production/',
    'admin_register.php' => 'production/',
    'payments_status.php' => 'production/',
    'afripay_callback.php' => 'production/',
    'verify_email.php' => 'production/',
    'db_production.php' => 'production/config/',
    'email_production.php' => 'production/config/',
    'config_production.php' => 'production/config/'
];

foreach ($files_to_copy as $source => $dest_dir) {
    if (file_exists($source)) {
        $dest = $dest_dir . basename($source);
        copy($source, $dest);
        echo "📄 Copied: $source → $dest\n";
    } else {
        echo "⚠️  Warning: $source not found\n";
    }
}

// Copy assets directory
if (is_dir('assets')) {
    copy_dir('assets', 'production/assets');
    echo "🎨 Copied assets directory\n";
}

// Copy uploads directory (if exists)
if (is_dir('uploads')) {
    copy_dir('uploads', 'production/uploads');
    echo "📁 Copied uploads directory\n";
}

echo "\n✅ Production files prepared!\n";
echo "📂 Check the 'production' folder for all files ready to upload\n";
echo "🔧 Next steps:\n";
echo "   1. Update production/config/db_production.php with your database credentials\n";
echo "   2. Update production/config/email_production.php with your email settings\n";
echo "   3. Use VS Code SFTP to upload the production folder contents\n";
echo "   4. Set up database on remote server\n";
echo "   5. Test the live website\n\n";

function copy_dir($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            if (is_dir($src . '/' . $file)) {
                copy_dir($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}
?>

