<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$db = getDb();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database offline: ' . $GLOBALS['db_status_message']]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$adminName = $_SESSION['user']['username'] ?? 'Campus Administrator';

// -------------------------------------------------------------
// GET: Fetch Available Maintenance Staff
// -------------------------------------------------------------
if ($action === 'get_staff') {
    $staff = $db->query("SELECT * FROM staff ORDER BY full_name ASC")->fetchAll();
    echo json_encode(['success' => true, 'staff' => $staff]);
    exit;
}

// -------------------------------------------------------------
// POST: Assign Staff to Issue
// -------------------------------------------------------------
if ($action === 'assign_staff') {
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    $staff_id = intval($_POST['staff_id'] ?? 0);
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    if (!$complaint_id || !$staff_id) {
        echo json_encode(['success' => false, 'message' => 'Complaint ID and Staff ID are required.']);
        exit;
    }

    $staffQuery = $db->prepare("SELECT full_name, specialization FROM staff WHERE id = ?");
    $staffQuery->execute([$staff_id]);
    $staff = $staffQuery->fetch();

    if (!$staff) {
        echo json_encode(['success' => false, 'message' => 'Staff member not found.']);
        exit;
    }

    try {
        // Also move status to 'In progress' if it was 'Submitted'
        $stmt = $db->prepare("
            UPDATE complaints SET 
                assigned_staff_id = ?,
                admin_notes = COALESCE(NULLIF(?, ''), admin_notes),
                status = CASE WHEN status = 'Submitted' THEN 'In progress' ELSE status END,
                in_progress_at = CASE WHEN in_progress_at IS NULL THEN CURRENT_TIMESTAMP ELSE in_progress_at END
            WHERE id = ?
        ");
        $stmt->execute([$staff_id, $admin_notes, $complaint_id]);

        // Add log
        $logNote = "Task assigned to " . $staff['full_name'] . " (" . $staff['specialization'] . ").";
        if (!empty($admin_notes)) {
            $logNote .= " Note: " . $admin_notes;
        }
        $log = $db->prepare("INSERT INTO complaint_logs (complaint_id, action_type, performed_by, notes) VALUES (?, 'Staff Assigned', ?, ?)");
        $log->execute([$complaint_id, $adminName, $logNote]);

        echo json_encode(['success' => true, 'message' => 'Staff assigned successfully! Workflow status updated to In Progress.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to assign staff: ' . $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// POST: Update Status (Submitted -> In progress -> Resolved -> Rejected)
// -------------------------------------------------------------
if ($action === 'update_status') {
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $allowed = ['Submitted', 'In progress', 'Resolved', 'Rejected'];
    if (!in_array($new_status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status option.']);
        exit;
    }

    try {
        $timestampCol = '';
        if ($new_status === 'In progress') {
            $timestampCol = ", in_progress_at = CURRENT_TIMESTAMP";
        } elseif ($new_status === 'Resolved') {
            $timestampCol = ", resolved_at = CURRENT_TIMESTAMP";
        }

        $stmt = $db->prepare("UPDATE complaints SET status = ?, admin_notes = COALESCE(NULLIF(?, ''), admin_notes) $timestampCol WHERE id = ?");
        $stmt->execute([$new_status, $notes, $complaint_id]);

        // Record log
        $log = $db->prepare("INSERT INTO complaint_logs (complaint_id, action_type, performed_by, notes) VALUES (?, 'Status Change', ?, ?)");
        $log->execute([$complaint_id, $adminName, "Status moved to " . $new_status . ($notes ? ": " . $notes : "")]);

        echo json_encode(['success' => true, 'message' => 'Report status updated to ' . $new_status]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// POST: Mark Resolved with Proof Photo & Notes
// -------------------------------------------------------------
if ($action === 'resolve') {
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    $resolution_notes = trim($_POST['resolution_notes'] ?? '');

    if (!$complaint_id || empty($resolution_notes)) {
        echo json_encode(['success' => false, 'message' => 'Complaint ID and Resolution remarks are required.']);
        exit;
    }

    $proofPhotoUrl = null;
    if (!empty($_FILES['resolution_photo']['name']) && $_FILES['resolution_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resolution_photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName)) {
            $proofPhotoUrl = 'uploads/' . $newName;
        }
    }

    try {
        $stmt = $db->prepare("
            UPDATE complaints SET 
                status = 'Resolved',
                resolution_notes = ?,
                resolution_photo_url = COALESCE(?, resolution_photo_url),
                resolved_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$resolution_notes, $proofPhotoUrl, $complaint_id]);

        $log = $db->prepare("INSERT INTO complaint_logs (complaint_id, action_type, performed_by, notes) VALUES (?, 'Resolved', ?, ?)");
        $log->execute([$complaint_id, $adminName, "Resolution confirmed: " . $resolution_notes]);

        echo json_encode(['success' => true, 'message' => 'Issue marked as RESOLVED! Student tracker updated with resolution details.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to resolve issue: ' . $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// GET: Diagnostics / System Info
// -------------------------------------------------------------
if ($action === 'system_info') {
    $counts = [
        'total' => $db->query("SELECT count(*) FROM complaints")->fetchColumn(),
        'submitted' => $db->query("SELECT count(*) FROM complaints WHERE status = 'Submitted'")->fetchColumn(),
        'in_progress' => $db->query("SELECT count(*) FROM complaints WHERE status = 'In progress'")->fetchColumn(),
        'resolved' => $db->query("SELECT count(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn(),
    ];

    echo json_encode([
        'success' => true,
        'driver' => $GLOBALS['db_driver'],
        'status_message' => $GLOBALS['db_status_message'],
        'counts' => $counts,
        'server_time' => date('Y-m-d H:i:s')
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unrecognized admin action.']);
