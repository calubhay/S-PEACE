<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$db = getDb();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database offline: ' . $GLOBALS['db_status_message']]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// -------------------------------------------------------------
// POST: Upvote / Corroborate an Issue
// -------------------------------------------------------------
if ($action === 'upvote') {
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    if (!$complaint_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid complaint ID.']);
        exit;
    }

    $clientId = $_SERVER['REMOTE_ADDR'] ?? 'anonymous-client';
    if (!empty($_SESSION['user']['id'])) {
        $clientId .= '-user-' . $_SESSION['user']['id'];
    }

    // Check if already upvoted
    $check = $db->prepare("SELECT id FROM complaint_upvotes WHERE complaint_id = ? AND client_identifier = ?");
    $check->execute([$complaint_id, $clientId]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already up-voted this facility issue.']);
        exit;
    }

    try {
        $insert = $db->prepare("INSERT INTO complaint_upvotes (complaint_id, client_identifier) VALUES (?, ?)");
        $insert->execute([$complaint_id, $clientId]);

        $update = $db->prepare("UPDATE complaints SET upvotes = upvotes + 1 WHERE id = ?");
        $update->execute([$complaint_id]);

        $votes = $db->prepare("SELECT upvotes FROM complaints WHERE id = ?");
        $votes->execute([$complaint_id]);
        $newCount = $votes->fetchColumn();

        echo json_encode(['success' => true, 'message' => 'Upvote recorded! Admins will see the increased priority.', 'upvotes' => $newCount]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error recording upvote: ' . $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// GET: Fetch Single Report by ID or Tracking Code
// -------------------------------------------------------------
if ($method === 'GET' && (!empty($_GET['code']) || !empty($_GET['id']))) {
    $where = !empty($_GET['code']) ? "c.tracking_code = ?" : "c.id = ?";
    $param = !empty($_GET['code']) ? trim($_GET['code']) : intval($_GET['id']);

    $sql = "
        SELECT 
            c.*, 
            cat.name AS category_name, 
            cat.slug AS category_slug, 
            cat.icon AS category_icon,
            s.full_name AS staff_name,
            s.specialization AS staff_specialization,
            s.contact_number AS staff_contact
        FROM complaints c
        JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN staff s ON c.assigned_staff_id = s.id
        WHERE $where
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$param]);
    $report = $stmt->fetch();

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found. Please verify the tracking code.']);
        exit;
    }

    // Fetch activity logs
    $logStmt = $db->prepare("SELECT * FROM complaint_logs WHERE complaint_id = ? ORDER BY created_at ASC");
    $logStmt->execute([$report['id']]);
    $logs = $logStmt->fetchAll();

    // Mask name if anonymous
    if ($report['is_anonymous']) {
        $report['display_reporter'] = 'Anonymous Student';
        $report['student_name'] = 'Anonymous Student';
        $report['student_email'] = null;
    } else {
        $report['display_reporter'] = $report['student_name'] ?: 'Registered Student';
    }

    echo json_encode(['success' => true, 'report' => $report, 'logs' => $logs]);
    exit;
}

// -------------------------------------------------------------
// GET: List Reports with Filters (Category, Status, Keyword)
// -------------------------------------------------------------
if ($method === 'GET') {
    $category_id = intval($_GET['category_id'] ?? 0);
    $category_slug = trim($_GET['category'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $search = trim($_GET['search'] ?? '');
    $limit = min(intval($_GET['limit'] ?? 50), 100);
    $offset = max(intval($_GET['offset'] ?? 0), 0);

    $conditions = [];
    $params = [];

    if ($category_id > 0) {
        $conditions[] = "c.category_id = ?";
        $params[] = $category_id;
    } elseif (!empty($category_slug) && $category_slug !== 'all') {
        $conditions[] = "cat.slug = ?";
        $params[] = $category_slug;
    }

    if (!empty($status) && $status !== 'all') {
        $conditions[] = "c.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $conditions[] = "(c.problem LIKE ? OR c.location LIKE ? OR c.description LIKE ? OR c.tracking_code LIKE ?)";
        $wildcard = "%" . $search . "%";
        $params[] = $wildcard;
        $params[] = $wildcard;
        $params[] = $wildcard;
        $params[] = $wildcard;
    }

    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "
        SELECT 
            c.*, 
            cat.name AS category_name, 
            cat.slug AS category_slug, 
            cat.icon AS category_icon,
            s.full_name AS staff_name
        FROM complaints c
        JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN staff s ON c.assigned_staff_id = s.id
        $whereClause
        ORDER BY c.submitted_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

    // Anonymize records where appropriate
    foreach ($reports as &$r) {
        if ($r['is_anonymous']) {
            $r['display_reporter'] = 'Anonymous Student';
            $r['student_name'] = 'Anonymous Student';
            $r['student_email'] = null;
        } else {
            $r['display_reporter'] = $r['student_name'] ?: 'Registered Student';
        }
    }

    // Get categories for navigation
    $cats = $db->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();

    echo json_encode(['success' => true, 'reports' => $reports, 'categories' => $cats]);
    exit;
}

// -------------------------------------------------------------
// POST: Submit a New Complaint / Report
// -------------------------------------------------------------
if ($method === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $problem = trim($_POST['problem'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_anonymous = (!empty($_POST['is_anonymous']) && $_POST['is_anonymous'] !== 'false') ? 1 : 0;
    $urgency = trim($_POST['urgency'] ?? 'Medium');

    // System Boundary Validation (From PPT: Strict Boundary limitation)
    $academicKeywords = ['grade', 'grading', 'exam score', 'professor', 'instructor', 'cheating', 'tuition', 'scholarship', 'gpa'];
    $combinedText = strtolower($problem . ' ' . $description);
    foreach ($academicKeywords as $kw) {
        if (preg_match("/\b" . preg_quote($kw, '/') . "\b/", $combinedText)) {
            echo json_encode([
                'success' => false, 
                'boundary_warning' => true,
                'message' => 'Scope Notice: S-PEACE is exclusively designed for campus facilities, infrastructure, and physical maintenance. Academic disputes, grades, and personal misconduct must be reported directly to your Department Dean or Registrar Office.'
            ]);
            exit;
        }
    }

    if (empty($category_id) || empty($problem) || empty($location) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in Category, Problem, Location, and Description.']);
        exit;
    }

    // Student identification
    $student_id = null;
    $student_name = null;
    $student_email = null;

    if (!empty($_SESSION['user'])) {
        $student_id = $_SESSION['user']['id'];
        $student_name = $_SESSION['user']['username'];
        $student_email = $_SESSION['user']['email'];
    } elseif (!$is_anonymous) {
        $student_name = trim($_POST['student_name'] ?? 'Student');
        $student_email = trim($_POST['student_email'] ?? '');
    }

    // Handle Photo Upload
    $photoUrl = null;
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        
        if ($file['size'] > MAX_FILE_SIZE) {
            echo json_encode(['success' => false, 'message' => 'Uploaded photo exceeds the 5MB limit.']);
            exit;
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExts)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format. Allowed formats: JPG, PNG, WEBP, SVG.']);
            exit;
        }

        $newFileName = 'complaint_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
        $targetPath = UPLOAD_DIR . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $photoUrl = 'uploads/' . $newFileName;
        }
    }

    // Generate unique tracking code (e.g. SP-2024-8842)
    $trackingCode = 'SP-' . date('Y') . '-' . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    // Ensure uniqueness
    while ($db->query("SELECT id FROM complaints WHERE tracking_code = '$trackingCode'")->fetch()) {
        $trackingCode = 'SP-' . date('Y') . '-' . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    $insertSql = "
        INSERT INTO complaints (
            tracking_code, student_id, student_name, student_email, is_anonymous,
            category_id, problem, location, description, photo_url, status, urgency, submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?, CURRENT_TIMESTAMP)
    ";

    try {
        $stmt = $db->prepare($insertSql);
        $stmt->execute([
            $trackingCode,
            $student_id,
            $is_anonymous ? null : $student_name,
            $is_anonymous ? null : $student_email,
            $is_anonymous,
            $category_id,
            $problem,
            $location,
            $description,
            $photoUrl,
            $urgency
        ]);

        $complaintId = $db->lastInsertId();

        // Add initial log
        $actor = $is_anonymous ? 'Student (Anonymous)' : ($student_name ?: 'Student');
        $logStmt = $db->prepare("INSERT INTO complaint_logs (complaint_id, action_type, performed_by, notes) VALUES (?, 'Report Submitted', ?, 'Facility issue registered into S-PEACE workflow.')");
        $logStmt->execute([$complaintId, $actor]);

        echo json_encode([
            'success' => true,
            'message' => 'Your complaint has been submitted successfully!',
            'tracking_code' => $trackingCode,
            'complaint_id' => $complaintId,
            'status' => 'Submitted'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save complaint: ' . $e->getMessage()]);
    }
    exit;
}
