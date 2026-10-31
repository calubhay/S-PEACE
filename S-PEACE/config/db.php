<?php
// ====================================================================
// S-PEACE: Database Connection & Initialization Layer
// Supports MySQL (Primary) with Automatic Auto-Setup
// & Graceful Fallback for Seamless Instant Testing
// ====================================================================

require_once __DIR__ . '/config.php';

$pdo = null;
$db_driver = 'unknown';
$db_status_message = '';
$db_connected = false;

function initDatabase() {
    global $pdo, $db_driver, $db_status_message, $db_connected;

    if ($pdo !== null) {
        return $pdo;
    }

    // 1. Try MySQL First (Primary Database as requested)
    try {
        // Connect to MySQL server (without selecting DB first)
        $dsnNoDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ];

        $rootPdo = new PDO($dsnNoDb, DB_USER, DB_PASS, $options);
        
        // Ensure Database exists
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Connect to target database
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        $db_driver = 'mysql';
        $db_connected = true;
        $db_status_message = 'Connected to MySQL (' . DB_NAME . ' on ' . DB_HOST . ':' . DB_PORT . ')';

        // Check if tables exist in MySQL
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'complaints'")->fetch();
        if (!$tableCheck) {
            setupMySQLTables($pdo);
        }

        return $pdo;

    } catch (Throwable $e) {
        // MySQL connection failed (e.g. XAMPP MySQL service not currently running)
        // Switch to SQLite fallback so system never crashes
        return setupSQLiteFallback($e->getMessage());
    }
}

function setupMySQLTables($pdo) {
    $sqlFile = BASE_PATH . '/database/speace_db.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        // Execute multi-query statements
        $pdo->exec($sql);
    }
}

function setupSQLiteFallback($mysqlError = '') {
    global $pdo, $db_driver, $db_status_message, $db_connected;

    try {
        $sqliteFile = BASE_PATH . '/database/speace.sqlite';
        $needsInit = !file_exists($sqliteFile) || filesize($sqliteFile) < 1000;

        $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $pdo->exec("PRAGMA foreign_keys = ON;");

        $db_driver = 'sqlite';
        $db_connected = true;
        $db_status_message = 'Running in SQLite mode (MySQL offline: ' . htmlspecialchars($mysqlError) . '). Start MySQL in XAMPP to connect to MySQL.';

        if ($needsInit) {
            initSQLiteSchema($pdo);
        }

        return $pdo;
    } catch (Throwable $ex) {
        $db_connected = false;
        $db_status_message = 'Database Initialization Error: ' . $ex->getMessage();
        return null;
    }
}

function initSQLiteSchema($pdo) {
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'student',
        student_id_number TEXT,
        department TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        icon TEXT NOT NULL,
        description TEXT,
        display_order INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS staff (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        specialization TEXT NOT NULL,
        contact_number TEXT,
        email TEXT,
        status TEXT NOT NULL DEFAULT 'available',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS complaints (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tracking_code TEXT NOT NULL UNIQUE,
        student_id INTEGER,
        student_name TEXT,
        student_email TEXT,
        is_anonymous INTEGER NOT NULL DEFAULT 0,
        category_id INTEGER NOT NULL,
        problem TEXT NOT NULL,
        location TEXT NOT NULL,
        description TEXT NOT NULL,
        photo_url TEXT,
        status TEXT NOT NULL DEFAULT 'Submitted',
        urgency TEXT NOT NULL DEFAULT 'Medium',
        upvotes INTEGER NOT NULL DEFAULT 0,
        assigned_staff_id INTEGER,
        admin_notes TEXT,
        resolution_notes TEXT,
        resolution_photo_url TEXT,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        in_progress_at DATETIME,
        resolved_at DATETIME,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories (id),
        FOREIGN KEY (assigned_staff_id) REFERENCES staff (id),
        FOREIGN KEY (student_id) REFERENCES users (id)
    );

    CREATE TABLE IF NOT EXISTS complaint_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        complaint_id INTEGER NOT NULL,
        action_type TEXT NOT NULL,
        performed_by TEXT NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints (id)
    );

    CREATE TABLE IF NOT EXISTS complaint_upvotes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        complaint_id INTEGER NOT NULL,
        client_identifier TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(complaint_id, client_identifier),
        FOREIGN KEY (complaint_id) REFERENCES complaints (id)
    );
    ";

    $pdo->exec($schema);

    // Insert initial categories
    $pdo->exec("
    INSERT OR IGNORE INTO categories (id, name, slug, icon, description, display_order) VALUES
    (1, 'Comfort rooms', 'comfort-rooms', 'droplet', 'Restrooms, water faucets, flush toilets, and sanitation fixtures', 1),
    (2, 'Facilities', 'facilities', 'building', 'Campus buildings, roofs, walkways, staircases, doors and security gates', 2),
    (3, 'Classrooms', 'classrooms', 'book-open', 'Student armchairs, teacher tables, whiteboards, lighting, and windows', 3),
    (4, 'Equipment', 'equipment', 'monitor', 'Ceiling fans, air conditioning units, LCD projectors, and lab computers', 4),
    (5, 'Cleanliness', 'cleanliness', 'trash-2', 'Waste bins, garbage segregation, corridor tidiness, and campus grounds', 5),
    (6, 'Safety', 'safety', 'shield-alert', 'Electrical outlets, exposed wirings, slippery ramps, and fire safety', 6),
    (7, 'Other', 'other', 'more-horizontal', 'General campus maintenance issues requiring administration review', 7);
    ");

    // Insert staff
    $pdo->exec("
    INSERT OR IGNORE INTO staff (id, full_name, specialization, contact_number, email, status) VALUES
    (1, 'Juan Dela Cruz', 'Plumbing & Water Systems', '0917-123-4567', 'jdelacruz@pcb.edu.ph', 'available'),
    (2, 'Mark Angelo Santos', 'Electrical & Power Fixtures', '0918-234-5678', 'msantos@pcb.edu.ph', 'on_task'),
    (3, 'Pedro Reyes', 'Carpentry & Structural Repairs', '0920-345-6789', 'preyes@pcb.edu.ph', 'available'),
    (4, 'Maria Bautista', 'Campus Sanitation & Janitorial Lead', '0922-456-7890', 'mbautista@pcb.edu.ph', 'available'),
    (5, 'Robert Garcia', 'Cooling, HVAC & Technical Equipment', '0925-567-8901', 'rgarcia@pcb.edu.ph', 'available');
    ");

    // Insert default users (Admin: admin123, Student: student123)
    $hashAdmin = password_hash('admin123', PASSWORD_BCRYPT);
    $hashStudent = password_hash('student123', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (id, username, email, password, role, student_id_number, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'PCB Admin', 'admin@pcb.edu.ph', $hashAdmin, 'admin', 'EMP-001', 'Facilities and General Services']);
    $stmt->execute([2, 'Juan Student', 'student@pcb.edu.ph', $hashStudent, 'student', '2024-00142', 'Institute of Computing Studies']);
    $stmt->execute([3, 'Maria Santos', 'maria.s@pcb.edu.ph', $hashStudent, 'student', '2024-00189', 'College of Teacher Education']);

    // Insert sample complaints (exact match to user UI wireframe mockup)
    $stmtCompl = $pdo->prepare("
        INSERT OR IGNORE INTO complaints 
        (id, tracking_code, student_id, student_name, student_email, is_anonymous, category_id, problem, location, description, photo_url, status, urgency, upvotes, assigned_staff_id, admin_notes, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtCompl->execute([
        1,
        'SP-2024-8842',
        null,
        'Anonymous',
        null,
        1,
        1,
        'No water in comfort room',
        'ICS building, 2nd floor',
        'The comfort room has no water, so we can\'t use it, in th past 3 days. Student forced to go to other comfort room which is far from our classroom',
        'assets/images/samples/leaking_faucet.svg',
        'Submitted',
        'High',
        5,
        1,
        'Assigned to Plumbing staff for immediate inspection of main cistern valves.',
        '2024-09-08 09:12:00'
    ]);

    $stmtCompl->execute([
        2,
        'SP-2024-5120',
        2,
        'Juan Student',
        'student@pcb.edu.ph',
        0,
        3,
        'Broken wooden armchairs',
        'Main Academic Building, Room 204',
        'Several chairs have detached armrests and loose screws causing students discomfort and torn uniforms during morning lectures.',
        'assets/images/samples/broken_chair.svg',
        'In progress',
        'Medium',
        2,
        3,
        'Carpentry team dispatched to replace 6 defective armchairs.',
        '2024-09-09 11:30:00'
    ]);

    $stmtCompl->execute([
        3,
        'SP-2024-3401',
        3,
        'Maria Santos',
        'maria.s@pcb.edu.ph',
        0,
        4,
        'Ceiling fan vibrating loudly and sparking',
        'CTE Laboratory Rm 102',
        'The front ceiling fan makes an alarming buzzing noise and occasionally sparks when set to maximum speed. Potential hazard.',
        'assets/images/samples/electrical_issue.svg',
        'Resolved',
        'Critical',
        8,
        2,
        'Emergency electrical check completed.',
        '2024-09-05 14:00:00'
    ]);

    $pdo->exec("
        UPDATE complaints SET 
        resolution_notes = 'Replaced capacitor and secured loose fan motor mounting bracket. Tested safe for student operation.',
        resolved_at = '2024-09-06 10:00:00'
        WHERE id = 3
    ");
}

// Global accessor
function getDb() {
    return initDatabase();
}
