<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$db = getDb();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $GLOBALS['db_status_message']]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Check session timeout (15 minutes of inactivity)
$timeout_duration = 900;
if (!empty($_SESSION['user']) && !empty($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $timeout_duration) {
        $_SESSION['locked'] = true;
    }
}
if (!empty($_SESSION['user'])) {
    $_SESSION['last_activity'] = time();
}

// -------------------------------------------------------------
// POST: Register Account (Matching Frame 2 Top Left)
// -------------------------------------------------------------
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $student_id = trim($_POST['student_id_number'] ?? '');
    $department = trim($_POST['department'] ?? 'General Student Body');

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long (8 characters recommended).']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }

    // Check if email already registered
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please sign in.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, role, student_id_number, department) VALUES (?, ?, ?, 'student', ?, ?)");
    
    try {
        $stmt->execute([$username, $email, $hash, $student_id ?: null, $department]);
        $newId = $db->lastInsertId();

        $_SESSION['user'] = [
            'id' => $newId,
            'username' => $username,
            'email' => $email,
            'role' => 'student',
            'department' => $department
        ];
        $_SESSION['locked'] = false;
        $_SESSION['last_activity'] = time();

        echo json_encode(['success' => true, 'message' => 'Account created successfully!', 'user' => $_SESSION['user']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Registration error: ' . $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// POST: Login (Matching Frame 2 Top Right "Welcome back")
// -------------------------------------------------------------
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address or password.']);
        exit;
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'department' => $user['department'] ?? ''
    ];
    $_SESSION['locked'] = false;
    $_SESSION['last_activity'] = time();

    echo json_encode(['success' => true, 'message' => 'Signed in successfully!', 'user' => $_SESSION['user']]);
    exit;
}

// -------------------------------------------------------------
// POST: Re-Validate / Unlock Password When Returning
// -------------------------------------------------------------
if ($action === 'unlock') {
    if (empty($_SESSION['user']['id'])) {
        echo json_encode(['success' => false, 'message' => 'No active user found. Please log in with email and password.']);
        exit;
    }

    $password = $_POST['password'] ?? '';
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your password for validation.']);
        exit;
    }

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $hash = $stmt->fetchColumn();

    if ($hash && password_verify($password, $hash)) {
        $_SESSION['locked'] = false;
        $_SESSION['last_activity'] = time();
        echo json_encode(['success' => true, 'message' => 'Validation successful! Session restored.', 'user' => $_SESSION['user']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Validation failed.']);
    }
    exit;
}

// -------------------------------------------------------------
// POST: Lock Session (When user leaves tab / window)
// -------------------------------------------------------------
if ($action === 'lock') {
    if (!empty($_SESSION['user'])) {
        $_SESSION['locked'] = true;
    }
    echo json_encode(['success' => true, 'locked' => true]);
    exit;
}

// -------------------------------------------------------------
// POST: Logout / Exit System
// -------------------------------------------------------------
if ($action === 'logout') {
    unset($_SESSION['user']);
    unset($_SESSION['locked']);
    unset($_SESSION['last_activity']);
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    exit;
}

// -------------------------------------------------------------
// GET: Check Auth & Lock Status
// -------------------------------------------------------------
if ($action === 'check') {
    if (!empty($_SESSION['user'])) {
        $isLocked = !empty($_SESSION['locked']);
        echo json_encode([
            'authenticated' => true,
            'locked' => $isLocked,
            'user' => $_SESSION['user']
        ]);
    } else {
        echo json_encode([
            'authenticated' => false,
            'locked' => false
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid auth action specified.']);
