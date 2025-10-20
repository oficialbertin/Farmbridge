<?php
// session_helper.php - Fix session path for production server
if (session_status() === PHP_SESSION_NONE) {
    // Fix session path for production server
    if (in_array($_SERVER['HTTP_HOST'] ?? '', ['web.farmbridgeai.rw', 'www.farmbridgeai.rw'])) {
        session_save_path('/tmp');
    }
    session_start();
}
?>
