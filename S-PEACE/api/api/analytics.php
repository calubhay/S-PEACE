<?php
require_once __DIR__ . '/../config/db.php';

$db = getDb();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database offline']);
    exit;
}

$format = $_GET['format'] ?? 'json';

// Handle CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SPEACE_Complaints_Report_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    // CSV Header row
    fputcsv($output, [
        'Tracking Code',
        'Category',
        'Problem Title',
        'Location',
        'Status',
        'Urgency',
        'Up-Votes',
        'Reporter Type',
        'Assigned Staff',
        'Submitted Date',
        'In Progress Date',
        'Resolved Date',
        'Resolution Remarks'
    ]);

    $sql = "
        SELECT 
            c.tracking_code,
            cat.name AS category_name,
            c.problem,
            c.location,
            c.status,
            c.urgency,
            c.upvotes,
            CASE WHEN c.is_anonymous = 1 THEN 'Anonymous Student' ELSE COALESCE(c.student_name, 'Registered Student') END AS reporter,
            COALESCE(s.full_name, 'Unassigned') AS staff_name,
            c.submitted_at,
            c.in_progress_at,
            c.resolved_at,
            c.resolution_notes
        FROM complaints c
        JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN staff s ON c.assigned_staff_id = s.id
        ORDER BY c.submitted_at DESC
    ";

    $stmt = $db->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// JSON Analytics Response
header('Content-Type: application/json');

$total = $db->query("SELECT count(*) FROM complaints")->fetchColumn();
$submitted = $db->query("SELECT count(*) FROM complaints WHERE status = 'Submitted'")->fetchColumn();
$in_progress = $db->query("SELECT count(*) FROM complaints WHERE status = 'In progress'")->fetchColumn();
$resolved = $db->query("SELECT count(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
$staff_count = $db->query("SELECT count(*) FROM staff")->fetchColumn();

// Category distribution
$catSql = "
    SELECT 
        cat.id, 
        cat.name, 
        cat.slug, 
        cat.icon,
        COUNT(c.id) AS count
    FROM categories cat
    LEFT JOIN complaints c ON cat.id = c.category_id
    GROUP BY cat.id, cat.name, cat.slug, cat.icon
    ORDER BY count DESC, cat.display_order ASC
";
$categories = $db->query($catSql)->fetchAll();

// Top priority / Upvoted issues
$topIssues = $db->query("
    SELECT c.id, c.tracking_code, c.problem, c.location, c.status, c.upvotes, cat.name as category_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    ORDER BY c.upvotes DESC, c.submitted_at DESC
    LIMIT 5
")->fetchAll();

echo json_encode([
    'success' => true,
    'metrics' => [
        'total' => (int)$total,
        'submitted' => (int)$submitted,
        'in_progress' => (int)$in_progress,
        'resolved' => (int)$resolved,
        'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
        'staff_count' => (int)$staff_count
    ],
    'categories' => $categories,
    'top_issues' => $topIssues
]);
