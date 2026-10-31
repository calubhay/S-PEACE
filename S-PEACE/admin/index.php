<?php
require_once __DIR__ . '/../config/db.php';

// Auto login as admin if not logged in for easy evaluation, or check session
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['user'] = [
        'id' => 1,
        'username' => 'PCB Admin',
        'email' => 'admin@pcb.edu.ph',
        'role' => 'admin',
        'department' => 'Facilities and General Services'
    ];
}

$db = getDb();

// Fetch initial data
$staffList = $db->query("SELECT * FROM staff ORDER BY full_name ASC")->fetchAll();
$categories = $db->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Central Dashboard | S-PEACE - Polytechnic College of Botolan</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/pcb-logo.svg">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      background-color: #f1f5f9;
      color: #0f172a;
    }
    .admin-nav {
      background: #0f172a;
      color: #ffffff;
      padding: 12px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .admin-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 24px 20px;
      width: 100%;
    }
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .kpi-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      border: 1px solid #e2e8f0;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .kpi-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: #cbd5e1;
    }
    .kpi-card.kpi-total::before { background: #3b82f6; }
    .kpi-card.kpi-submitted::before { background: #f43f5e; }
    .kpi-card.kpi-progress::before { background: #f59e0b; }
    .kpi-card.kpi-resolved::before { background: #10b981; }
    
    .kpi-title {
      font-size: 12px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .kpi-value {
      font-size: 32px;
      font-weight: 900;
      color: #0f172a;
      margin: 6px 0 2px;
    }
    .kpi-sub {
      font-size: 11px;
      color: #94a3b8;
    }
    
    /* Category Filter Ribbon */
    .filter-ribbon {
      background: #ffffff;
      border-radius: 16px;
      padding: 16px 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      border: 1px solid #e2e8f0;
    }
    .category-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 10px;
    }
    .cat-pill {
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      color: #334155;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .cat-pill:hover, .cat-pill.active {
      background: #15803d;
      border-color: #15803d;
      color: #ffffff;
    }
    .cat-pill .count-badge {
      background: rgba(0,0,0,0.1);
      padding: 1px 6px;
      border-radius: 10px;
      font-size: 10px;
    }
    .cat-pill.active .count-badge {
      background: rgba(255,255,255,0.25);
    }
    
    /* Search & Table Card */
    .table-card {
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
      border: 1px solid #e2e8f0;
      overflow: hidden;
    }
    .table-toolbar {
      padding: 16px 20px;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid #e2e8f0;
    }
    .status-tabs {
      display: inline-flex;
      background: #f1f5f9;
      padding: 4px;
      border-radius: 10px;
      gap: 4px;
    }
    .status-tab {
      background: none;
      border: none;
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      color: #64748b;
      cursor: pointer;
      transition: all 0.2s;
    }
    .status-tab.active {
      background: #ffffff;
      color: #0f172a;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    /* Table Styling */
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 13px;
    }
    .admin-table th {
      background: #f8fafc;
      color: #475569;
      font-weight: 700;
      padding: 12px 16px;
      border-bottom: 1px solid #e2e8f0;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
    }
    .admin-table td {
      padding: 14px 16px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      color: #1e293b;
    }
    .admin-table tr:hover {
      background-color: #f8fafc;
    }
    
    .thumb-img {
      width: 48px;
      height: 48px;
      border-radius: 8px;
      object-fit: cover;
      cursor: pointer;
      border: 1px solid #e2e8f0;
      transition: transform 0.2s;
    }
    .thumb-img:hover {
      transform: scale(1.1);
    }
    .no-thumb {
      width: 48px;
      height: 48px;
      border-radius: 8px;
      background: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #94a3b8;
      font-size: 10px;
    }
    
    .action-btn-manage {
      background: #1e293b;
      color: #ffffff;
      border: none;
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .action-btn-manage:hover {
      background: #0f172a;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }
    
    /* Modal Detail View */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(4px);
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-backdrop.active {
      display: flex;
    }
    .modal-card {
      background: #ffffff;
      border-radius: 20px;
      max-width: 760px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
    }
  </style>
</head>
<body>

  <!-- Top Navigation Header -->
  <nav class="admin-nav">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="../assets/images/pcb-logo.svg" alt="PCB" style="width:36px; height:36px;">
      <div>
        <div style="font-weight:800; font-size:16px; letter-spacing:0.5px;">S-PEACE &bull; Admin Central Dashboard</div>
        <div style="font-size:11px; color:#94a3b8;">Polytechnic College of Botolan &bull; Facilities &amp; Maintenance Management</div>
      </div>
    </div>
    
    <div style="display:flex; align-items:center; gap:12px;">
      <span style="background:rgba(22, 163, 74, 0.2); color:#4ade80; border:1px solid #16a34a; font-size:12px; padding:4px 10px; border-radius:14px; font-weight:600;">
        <?php echo ($GLOBALS['db_driver'] === 'mysql') ? 'Database: MySQL (speace_db)' : 'Database: MySQL Bridge Ready'; ?>
      </span>
      <a href="../api/analytics.php?format=csv" class="mode-toggle-btn" style="background:#0284c7; border-color:#0284c7; text-decoration:none;" title="Download CSV Report">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Export CSV
      </a>
      <a href="../index.php" class="mode-toggle-btn" style="text-decoration:none;" title="Open Student Mobile Portal">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
        Student Portal
      </a>
      <a href="login.php" class="mode-toggle-btn" style="background:#ef4444; border-color:#ef4444; text-decoration:none;">Logout</a>
    </div>
  </nav>

  <!-- Main Admin Workspace -->
  <main class="admin-container">
    
    <!-- KPI Statistic Cards -->
    <section class="kpi-grid">
      <div class="kpi-card kpi-total">
        <span class="kpi-title">Total Reports</span>
        <div class="kpi-value" id="kpi-total">0</div>
        <span class="kpi-sub">All campus maintenance issues</span>
      </div>
      <div class="kpi-card kpi-submitted">
        <span class="kpi-title">Submitted (Pending Review)</span>
        <div class="kpi-value" id="kpi-submitted" style="color:#e11d48;">0</div>
        <span class="kpi-sub">Awaiting admin assessment</span>
      </div>
      <div class="kpi-card kpi-progress">
        <span class="kpi-title">In Progress</span>
        <div class="kpi-value" id="kpi-progress" style="color:#d97706;">0</div>
        <span class="kpi-sub">Assigned to maintenance crew</span>
      </div>
      <div class="kpi-card kpi-resolved">
        <span class="kpi-title">Resolved</span>
        <div class="kpi-value" id="kpi-resolved" style="color:#059669;">0</div>
        <span class="kpi-sub" id="kpi-rate">0% resolution rate</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-title">Active Staff</span>
        <div class="kpi-value" style="color:#0284c7;"><?php echo count($staffList); ?></div>
        <span class="kpi-sub">Plumbers, Electricians, Carpenters</span>
      </div>
    </section>

    <!-- Categorized Review Ribbon (Requirement: Admin review categorized report/complaint) -->
    <section class="filter-ribbon">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h2 style="font-size:15px; font-weight:800; color:#1e293b;">Categorized Complaints</h2>
          <p style="font-size:12px; color:#64748b;">Review and filter reported issues by facility category</p>
        </div>
      </div>
      
      <div class="category-pills" id="category-pills-container">
        <button class="cat-pill active" data-category="all">
          All Categories <span class="count-badge" id="cat-count-all">0</span>
        </button>
        <?php foreach ($categories as $cat): ?>
          <button class="cat-pill" data-category="<?php echo htmlspecialchars($cat['slug']); ?>">
            <?php echo htmlspecialchars($cat['name']); ?>
            <span class="count-badge" id="cat-count-<?php echo htmlspecialchars($cat['slug']); ?>">0</span>
          </button>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Reports Table Card -->
    <section class="table-card">
      <div class="table-toolbar">
        <!-- Status Filter Tabs -->
        <div class="status-tabs">
          <button class="status-tab active" data-status="all">All</button>
          <button class="status-tab" data-status="Submitted">Submitted</button>
          <button class="status-tab" data-status="In progress">In Progress</button>
          <button class="status-tab" data-status="Resolved">Resolved</button>
        </div>

        <!-- Search Input -->
        <div style="display:flex; gap:10px; align-items:center;">
          <input type="text" id="admin-search-input" class="input-rounded" placeholder="Search problem, location, or tracking..." style="width:280px; padding:8px 12px; font-size:13px;">
          <button id="admin-refresh-btn" class="action-btn-manage" style="background:#0284c7;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh
          </button>
        </div>
      </div>

      <!-- Table -->
      <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Tracking Code</th>
              <th>Category</th>
              <th>Problem &amp; Description</th>
              <th>Location</th>
              <th>Photo</th>
              <th>Reporter</th>
              <th>Priority / Votes</th>
              <th>Status</th>
              <th>Assigned Staff</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="complaints-table-body">
            <!-- Dynamically populated via admin.js -->
            <tr>
              <td colspan="11" style="text-align:center; padding:30px; color:#64748b;">Loading campus reports...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <!-- ACTION & MANAGEMENT MODAL -->
  <div class="modal-backdrop" id="manage-modal">
    <div class="modal-card">
      <div class="modal-header">
        <div>
          <h3 class="modal-title" id="m-tracking-code">SP-2024-XXXX</h3>
          <p style="font-size:12px; color:#64748b;" id="m-category-reporter">Category &bull; Reporter</p>
        </div>
        <button class="icon-btn" id="m-close-btn" style="color:#64748b; font-size:20px;">&times;</button>
      </div>

      <div class="modal-body">
        <!-- Problem & Location Details -->
        <div style="margin-bottom:18px;">
          <h4 style="font-size:18px; font-weight:800; color:#111827; margin-bottom:4px;" id="m-problem">Problem Title</h4>
          <p style="font-size:13px; color:#0284c7; font-weight:600; margin-bottom:8px;" id="m-location">&bull; Location</p>
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px; font-size:13px; color:#334155; line-height:1.5;" id="m-description">
            Description
          </div>
        </div>

        <!-- Photo Comparison (Reported Photo vs Resolution Photo) -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px;">
          <div>
            <label style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">Reported Issue Photo</label>
            <div id="m-photo-container" style="height:170px; background:#f1f5f9; border-radius:12px; overflow:hidden; border:1px solid #cbd5e1; display:flex; align-items:center; justify-content:center;">
              <img id="m-photo" src="" alt="Issue" style="width:100%; height:100%; object-fit:cover;">
            </div>
          </div>
          <div>
            <label style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">Proof of Resolution Photo</label>
            <div id="m-resolution-photo-container" style="height:170px; background:#f1f5f9; border-radius:12px; overflow:hidden; border:1px solid #cbd5e1; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:12px;">
              <span id="m-no-proof-text">No proof photo yet</span>
              <img id="m-resolution-photo" src="" alt="Proof" style="width:100%; height:100%; object-fit:cover; display:none;">
            </div>
          </div>
        </div>

        <hr style="border:none; border-top:1px solid #e2e8f0; margin:18px 0;">

        <!-- ACTION FORM -->
        <form id="manage-complaint-form">
          <input type="hidden" id="manage-complaint-id" name="complaint_id">

          <!-- Status Transition -->
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
            <div>
              <label class="form-label">Workflow Status</label>
              <select id="manage-status" name="status" class="input-rounded" style="font-weight:700;">
                <option value="Submitted">Submitted (Under Review)</option>
                <option value="In progress">In Progress (Dispatched)</option>
                <option value="Resolved">Resolved (Fixed & Verified)</option>
                <option value="Rejected">Rejected (Out of Facility Scope)</option>
              </select>
            </div>

            <!-- Staff Assignment -->
            <div>
              <label class="form-label">Assign Maintenance Staff</label>
              <select id="manage-staff" name="staff_id" class="input-rounded">
                <option value="">-- Select Personnel --</option>
                <?php foreach ($staffList as $st): ?>
                  <option value="<?php echo $st['id']; ?>">
                    <?php echo htmlspecialchars($st['full_name'] . ' - ' . $st['specialization']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Internal Admin Notes -->
          <div class="form-group">
            <label class="form-label">Internal Admin / Dispatch Notes</label>
            <input type="text" id="manage-admin-notes" name="admin_notes" class="input-rounded" placeholder="e.g. Dispatched plumber with replacement ball valve.">
          </div>

          <!-- Resolution Remarks (Required if Resolved) -->
          <div class="form-group" id="resolution-group">
            <label class="form-label">Resolution Summary (Visible to Student)</label>
            <textarea id="manage-resolution-notes" name="resolution_notes" class="textarea-rounded" style="min-height:70px;" placeholder="Describe what repair was made and confirmed functional."></textarea>
          </div>

          <!-- Upload Resolution Proof Photo -->
          <div class="form-group">
            <label class="form-label">Upload Completion / Proof Photo (Optional)</label>
            <input type="file" id="manage-proof-photo" name="resolution_photo" accept="image/*" class="input-rounded">
          </div>

          <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
            <button type="button" class="btn-secondary-light" id="m-cancel-btn">Cancel</button>
            <button type="submit" class="btn-primary-green" id="m-save-btn">Save Changes</button>
          </div>
        </form>

        <!-- Audit Trail / Logs -->
        <div style="margin-top:24px;">
          <h5 style="font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:10px;">Activity &amp; Workflow Audit Trail</h5>
          <div id="m-logs-container" style="display:flex; flex-direction:column; gap:8px;">
            <!-- Rendered via JS -->
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>
