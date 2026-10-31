<?php
// ====================================================================
// S-PEACE: Configuration File
// Polytechnic College of Botolan (PCB)
// ====================================================================

// Base Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('SESSION_DIR', BASE_PATH . '/sessions/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 Megabytes as specified in UI mockup

// Ensure directories exist
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0777, true);
}
if (!is_dir(SESSION_DIR)) {
    @mkdir(SESSION_DIR, 0777, true);
}

// Session configuration using local folder to prevent C:\xampp\tmp permission issues
if (session_status() === PHP_SESSION_NONE) {
    if (is_dir(SESSION_DIR) && is_writable(SESSION_DIR)) {
        session_save_path(SESSION_DIR);
    }
    @session_start();
}

// College & System Information
define('APP_NAME', 'S-PEACE');
define('APP_FULL_NAME', 'Web-Based Student Complaint and Concern Management System');
define('COLLEGE_NAME', 'Polytechnic College of Botolan');
define('COLLEGE_SHORT', 'PCB');
define('COLLEGE_MOTTO', 'Commitment to Excellence and Quality Education');
define('APP_VERSION', '1.0.0');

// Database Credentials (Standard XAMPP default)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'speace_db');

// Timezone
date_default_timezone_set('Asia/Manila');
