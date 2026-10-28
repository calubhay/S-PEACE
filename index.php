<?php
require_once __DIR__ . '/config/db.php';
$db = getDb();

// Handle explicit logout
if (isset($_GET['logout'])) {
    unset($_SESSION['user']);
    unset($_SESSION['locked']);
    unset($_SESSION['last_activity']);
    session_destroy();
    header('Location: index.php');
    exit;
}

// If already authenticated as Admin, redirect directly to Admin Dashboard
if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$isLoggedInStudent = (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student');
$isGuestMode = isset($_GET['guest']) && $_GET['guest'] == '1';
// Make login and register form appear first unless already authenticated as student or browsing as guest
$showAuthFirst = !$isLoggedInStudent && !$isGuestMode;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>S-PEACE | Student Complaint & Concern Management System - PCB</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/pcb-logo.svg">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Evaluation Mode Switcher Bar -->
  <header class="desktop-eval-header">
    <div style="display:flex; align-items:center; gap:10px;">
      <img src="assets/images/pcb-logo.svg" alt="PCB" style="width:26px; height:26px;">
      <span style="font-weight:700; color:#ffffff;">Polytechnic College of Botolan &bull; S-PEACE</span>
      <span style="background:rgba(22, 163, 74, 0.2); color:#4ade80; border:1px solid #16a34a; font-size:11px; padding:2px 8px; border-radius:12px;">
        <?php echo ($GLOBALS['db_driver'] === 'mysql') ? 'MySQL Connected (speace_db)' : 'MySQL Auto-Bridge Ready'; ?>
      </span>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
      <div style="display:inline-flex; background:#0f172a; padding:3px; border-radius:24px; border:1px solid #334155;">
        <button id="btn-view-mobile" class="mode-toggle-btn active" title="Switch to Mobile Frame view (as shown in wireframes)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
          Mobile Frame UI
        </button>
        <button id="btn-view-desktop" class="mode-toggle-btn" title="Expand view across desktop width">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
          Expanded View
        </button>
      </div>
      <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
      <a href="admin/index.php" style="background:#f59e0b; color:#78350f; font-weight:700; text-decoration:none; padding:6px 14px; border-radius:20px; font-size:12px; display:inline-flex; align-items:center; gap:6px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        Admin Dashboard &rarr;
      </a>
      <a href="index.php?logout=1" style="background:#ef4444; color:#ffffff; font-weight:700; text-decoration:none; padding:6px 12px; border-radius:20px; font-size:12px;">Logout</a>
      <?php elseif (!empty($_SESSION['user'])): ?>
      <span style="font-size:12px; color:#86efac; font-weight:700; background:rgba(34, 197, 94, 0.2); border:1px solid #16a34a; padding:4px 10px; border-radius:12px;">
        🎓 <?php echo htmlspecialchars($_SESSION['user']['username']); ?> (Student)
      </span>
      <a href="index.php?logout=1" style="background:#ef4444; color:#ffffff; font-weight:700; text-decoration:none; padding:6px 12px; border-radius:20px; font-size:12px;">Sign Out</a>
      <?php else: ?>
      <span style="font-size:12px; color:#cbd5e1; background:rgba(255,255,255,0.1); padding:4px 12px; border-radius:14px; border:1px solid rgba(255,255,255,0.15);">
        🔒 Authentication Required
      </span>
      <?php endif; ?>
    </div>
  </header>

  <!-- Viewport Container -->
  <main class="phone-viewport-container" id="phone-container">
    <div class="phone-mockup-frame">
      <div class="phone-notch"></div>

      <!-- App Topbar (matching Frame 1) -->
      <nav class="app-topbar">
        <div class="brand-badge" data-action="go-home" style="cursor:pointer;">
          <div class="logo-box">
            <img src="assets/images/pcb-logo.svg" alt="S-PEACE" style="width:22px; height:22px;">
          </div>
          <span class="brand-text">S-<span>PEACE</span></span>
        </div>
        <div class="topbar-actions" id="portal-topbar-actions" style="<?php echo $showAuthFirst ? 'display:none;' : 'display:flex;'; ?>">
          <button class="icon-btn" data-action="open-track" title="Track a report">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </button>
          <button class="icon-btn" id="topbar-lock-btn" title="Leave / Lock System" style="display:none; color:#f87171;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          </button>
          <button class="icon-btn" id="topbar-user-btn" data-action="open-auth" title="Student Sign In / Account">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </button>
        </div>
      </nav>

      <!-- SCREEN 1: HOME / LANDING (Matching Frame 1 Mockup) -->
      <section id="screen-home" style="<?php echo $showAuthFirst ? 'display:none;' : 'display:block;'; ?>">
        <!-- Hero with Campus Backdrop -->
        <div class="hero-backdrop-container">
          <div class="hero-overlay-card">
            <h1 class="hero-title">Welcome to <span class="accent-red">S-PEACE</span></h1>
            <p class="hero-subtitle">Polytechnic College of Botolan Facility Concerns</p>
            <div class="hero-btn-group">
              <button class="btn-primary-green" data-action="open-report">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Report an issue
              </button>
              <button class="btn-secondary-light" data-action="open-track">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                Track a report
              </button>
            </div>

            <!-- Active User Session Pill / Leave System Indicator -->
            <div id="hero-user-session-pill" style="display:none; margin-top:14px; font-size:12px; background:rgba(255,255,255,0.12); padding:6px 14px; border-radius:20px; color:#e2e8f0;">
              <span>Validated as <strong id="hero-username-span" style="color:#86efac;">Student</strong></span>
              &bull; <a href="#" id="hero-leave-link" style="color:#fca5a5; text-decoration:none; font-weight:700;">Leave / Lock System</a>
            </div>
          </div>
        </div>

        <!-- How It Works Section (Warm Yellow background matching Frame 1) -->
        <div class="how-it-works-section">
          <p class="section-label">HOW IT WORKS</p>
          <h2 class="section-headline">From report to resolution in three steps</h2>

          <div class="steps-list">
            <!-- Step 1 -->
            <div class="step-card">
              <div class="step-icon-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              </div>
              <div class="step-content">
                <h3>Submit a report</h3>
                <p>Describe the issue - a leaky faucet, broken chairs, broken doorknob - pick a category and location, and send it off in under a minute</p>
              </div>
            </div>

            <!-- Step 2 -->
            <div class="step-card">
              <div class="step-icon-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
              </div>
              <div class="step-content">
                <h3>Administration reviews it</h3>
                <p>Facilities staff see your report, mark it as viewed, and move it through the workflow as they review it and fix it.</p>
              </div>
            </div>

            <!-- Step 3 -->
            <div class="step-card">
              <div class="step-icon-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              </div>
              <div class="step-content">
                <h3>Track the resolution</h3>
                <p>Follow along in real time as your report moves from Submitted to In progress to Resolved - no more guessing</p>
              </div>
            </div>
          </div>

          <!-- Anonymous Assurance Note from PPT -->
          <div style="margin-top:20px; background:rgba(0,0,0,0.06); border-radius:14px; padding:12px 14px; display:flex; gap:10px; align-items:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4b5563" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            <p style="font-size:12px; color:#4b5563; line-height:1.4;">
              <strong>Safe &amp; Confidential:</strong> Students who are shy or hesitant to speak in person can submit reports <strong>100% anonymously</strong>.
            </p>
          </div>
        </div>
      </section>

      <!-- SCREEN 2: REPORT AN ISSUE (Matching Frame 2 Middle Mockup) -->
      <section id="screen-report" class="report-screen-container" style="display:none;">
        <div class="screen-header">
          <h2 class="screen-title">Report an issue</h2>
          <button class="icon-btn" data-action="go-home" style="color:#1c1917;" title="Close">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>

        <form id="complaint-form" enctype="multipart/form-data">
          <!-- Hidden Category ID -->
          <input type="hidden" name="category_id" id="selected-category-id" value="1">

          <!-- Category Selection Card (Frame 2 Grid) -->
          <div class="category-wrapper-card">
            <p class="category-card-label">Category</p>
            <div class="category-grid">
              <!-- Comfort rooms -->
              <div class="category-tile active" data-category-id="1">
                <svg class="category-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                <span class="category-name">Comfort rooms</span>
              </div>
              <!-- Facilities -->
              <div class="category-tile" data-category-id="2">
                <svg class="category-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span class="category-name">Facilities</span>
              </div>
              <!-- Classrooms -->
              <div class="category-tile" data-category-id="3">
                <svg class="category-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                <span class="category-name">Classrooms</span>
              </div>
              <!-- Equipment -->
              <div class="category-tile" data-category-id="4">
                <svg class="category-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                <span class="category-name">Equipment</span>
              </div>
              <!-- Cleanliness -->
              <div class="category-tile" data-category-id="5">
                <svg class="category-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                <span class="category-name">Cleanliness</span>
              </div>
              <!-- Safety -->
              <div class="category-tile" data-category-id="6">
                <svg class="category-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                <span class="category-name">Safety</span>
              </div>
              <!-- Other -->
              <div class="category-tile" data-category-id="7" style="grid-column: 2;">
                <svg class="category-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                <span class="category-name">Other</span>
              </div>
            </div>
          </div>

          <!-- Problem Input -->
          <div class="form-group">
            <label class="form-label" for="report-problem">Problem</label>
            <input type="text" id="report-problem" name="problem" class="input-rounded" placeholder="e.g. Leaking faucet" required>
          </div>

          <!-- Location Input -->
          <div class="form-group">
            <label class="form-label" for="report-location">Location</label>
            <input type="text" id="report-location" name="location" class="input-rounded" placeholder="e.g. ICS building, 2nd floor" required>
          </div>

          <!-- Description Input -->
          <div class="form-group">
            <label class="form-label" for="report-description">Description</label>
            <textarea id="report-description" name="description" class="textarea-rounded" maxlength="500" placeholder="Describe the issue in details" required></textarea>
            <div class="char-counter" id="desc-char-count">0/500</div>
          </div>

          <!-- Photo (optional) Upload matching Frame 2 -->
          <div class="form-group">
            <label class="form-label">Photo (optional)</label>
            <input type="file" id="report-photo-input" name="photo" accept="image/*" capture="environment" style="display:none;">
            
            <div class="photo-upload-card" id="photo-dropzone">
              <div class="photo-camera-badge">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
              </div>
              <p class="photo-upload-title">Add a photo</p>
              <p class="photo-upload-sub">Help staff see the issue clearly (up to 5MB)</p>
            </div>

            <!-- Image Preview Box -->
            <div class="photo-preview-box" id="photo-preview-box">
              <img id="photo-preview-img" src="" alt="Issue Preview">
              <button type="button" class="remove-photo-btn" id="remove-photo-btn" title="Remove image">&times;</button>
            </div>
          </div>

          <!-- Submit Anonymously Checkbox -->
          <label class="checkbox-row">
            <input type="checkbox" name="is_anonymous" value="1" checked>
            <span>Submit anonymously - you can still track the status of your report.</span>
          </label>

          <!-- Submit Button -->
          <button type="submit" class="btn-submit-dark" id="submit-report-btn">Submit report</button>

          <!-- System Scope Notice (Limitations defined in PPT) -->
          <p style="text-align:center; font-size:11px; color:#6b7280; margin-top:14px; line-height:1.4;">
            * Boundary Notice: S-PEACE exclusively handles PCB campus physical facilities and maintenance. Academic grievances or personal disputes are not accepted.
          </p>
        </form>
      </section>

      <!-- SCREEN 3: TRACK A REPORT (Matching Frame 2 Right Mockup) -->
      <section id="screen-track" class="report-screen-container" style="display:none;">
        <div class="screen-header">
          <button class="icon-btn" data-action="go-home" style="color:#1c1917; gap:6px; font-weight:700; font-size:14px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to my reports
          </button>
        </div>

        <!-- Search Bar for Tracking Code -->
        <div style="display:flex; gap:8px; margin-bottom:16px;">
          <input type="text" id="tracking-code-input" class="input-rounded" placeholder="Enter tracking code (e.g. SP-2024-8842)" value="SP-2024-8842" style="font-weight:600; text-transform:uppercase;">
          <button id="track-search-btn" class="btn-primary-green" style="padding:0 16px; border-radius:12px;">Track</button>
        </div>

        <!-- Dynamic Report Display Area -->
        <div id="report-display-area">
          <!-- Report Card (Olive dark container from Wireframe) -->
          <div class="report-card-olive">
            <div class="report-card-top">
              <span class="report-category-tag" id="report-detail-tag">Comfort rooms &bull; Anonymous</span>
              <span class="badge-status badge-submitted" id="report-detail-status-badge">Submitted</span>
            </div>

            <h3 class="report-problem-title" id="report-detail-title">No water in comfort room</h3>

            <div class="report-location-row">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
              <span id="report-detail-location">ICS building, 2nd floor</span>
            </div>

            <p class="report-body-text" id="report-detail-desc">
              The comfort room has no water, so we can't use it, in th past 3 days. Student forced to go to other comfort room which is far from our classroom
            </p>

            <!-- Attached Photo (if exists) -->
            <div id="report-detail-photo-container" style="margin-bottom:12px; border-radius:12px; overflow:hidden; border:1px solid rgba(255,255,255,0.2); max-height:180px;">
              <img id="report-detail-photo" src="assets/images/samples/leaking_faucet.svg" alt="Facility Photo" style="width:100%; height:180px; object-fit:cover; display:block;">
            </div>

            <div class="report-footer-row">
              <span id="report-detail-date">Submitted Sept 8, 2024, 9:12 AM</span>
              <button class="upvote-pill" id="report-upvote-btn" data-id="1" title="Corroborate this issue">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>
                <span id="report-upvotes-count">0 up-votes</span>
              </button>
            </div>
          </div>

          <!-- Status Tracker Timeline Card (Matching Frame 2 Right) -->
          <div class="status-tracker-card">
            <h4 class="status-tracker-title">Status</h4>

            <div class="stepper-list">
              <!-- Step 1: Submitted -->
              <div class="stepper-item completed active" id="step-submitted">
                <div class="stepper-circle">&check;</div>
                <div class="stepper-label">Submitted</div>
                <div class="stepper-meta" id="step-s1-meta">Received by administration</div>
              </div>

              <!-- Step 2: In progress -->
              <div class="stepper-item" id="step-inprogress">
                <div class="stepper-circle">&bull;</div>
                <div class="stepper-label">In progress</div>
                <div class="stepper-meta" id="step-s2-meta">Pending staff review</div>
              </div>

              <!-- Step 3: Resolved -->
              <div class="stepper-item" id="step-resolved">
                <div class="stepper-circle">&bull;</div>
                <div class="stepper-label">Resolved</div>
                <div class="stepper-meta" id="step-s3-meta">Pending maintenance</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SCREEN 4: AUTHENTICATION FIRST (Sign in & Create Account from Frame 2 Top) -->
      <section id="screen-auth" style="<?php echo $showAuthFirst ? 'display:block;' : 'display:none;'; ?>; min-height:100%;">
        <div class="hero-backdrop-container" style="min-height:100vh; align-items:flex-start; padding-top:24px;">
          <div class="hero-overlay-card" style="max-width:390px; text-align:left;">
            
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px; border-bottom:1px solid rgba(255,255,255,0.15); padding-bottom:12px;">
              <img src="assets/images/pcb-logo.svg" alt="PCB" style="width:36px; height:36px;">
              <div>
                <h1 style="font-size:16px; font-weight:800; color:#ffffff; line-height:1.2;">S-PEACE Portal Access</h1>
                <p style="font-size:11px; color:#cbd5e1;">Polytechnic College of Botolan</p>
              </div>
            </div>

            <p style="font-size:12px; color:#cbd5e1; line-height:1.45; margin-bottom:16px; background:rgba(0,0,0,0.25); padding:8px 12px; border-radius:10px; border-left:3px solid #16a34a;">
              Please log in below. The system automatically detects whether you are a <strong>Student</strong> or an <strong>Administrator</strong> and directs you to the proper portal.
            </p>

            <!-- Tab Switcher -->
            <div style="display:flex; gap:6px; margin-bottom:16px; background:rgba(15, 23, 42, 0.6); padding:4px; border-radius:12px;">
              <button id="tab-btn-signin" class="mode-toggle-btn active" style="flex:1; justify-content:center; border:none;">Sign In</button>
              <button id="tab-btn-signup" class="mode-toggle-btn" style="flex:1; justify-content:center; border:none; background:none;">Create Account</button>
            </div>

            <!-- Sign In Form (Frame 2 Top Right) -->
            <form id="form-signin">
              <h2 style="font-size:20px; font-weight:800; margin-bottom:2px; color:#ffffff;">Welcome back</h2>
              <p style="font-size:12px; color:#94a3b8; margin-bottom:14px;">Sign in with your student or admin credentials</p>
              
              <div class="form-group" style="text-align:left;">
                <label class="form-label" style="color:#e2e8f0; font-size:12px;">Email address</label>
                <input type="email" id="signin-email" name="email" class="input-rounded" placeholder="user@pcb.edu.ph" required autocomplete="email">
              </div>

              <div class="form-group" style="text-align:left;">
                <label class="form-label" style="color:#e2e8f0; font-size:12px;">Password</label>
                <input type="password" id="signin-password" name="password" class="input-rounded" placeholder="At least 8 characters" required autocomplete="current-password">
              </div>

              <button type="submit" class="btn-primary-green" id="signin-submit-btn" style="width:100%; margin-top:6px; padding:12px; font-size:15px;">
                Sign in
              </button>

              <!-- Quick Demo Fill Pills for Testing -->
              <div style="margin-top:14px; background:rgba(255,255,255,0.06); padding:10px; border-radius:10px; font-size:11px;">
                <span style="color:#cbd5e1; font-weight:600; display:block; margin-bottom:6px;">Quick Test Login:</span>
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                  <button type="button" id="quick-fill-student" style="background:#166534; border:1px solid #22c55e; color:#bbf7d0; border-radius:8px; padding:4px 8px; font-size:11px; cursor:pointer; font-weight:600;">
                    🎓 Student Login
                  </button>
                  <button type="button" id="quick-fill-admin" style="background:#1e3a8a; border:1px solid #3b82f6; color:#bfdbfe; border-radius:8px; padding:4px 8px; font-size:11px; cursor:pointer; font-weight:600;">
                    🛡️ Admin Login
                  </button>
                </div>
              </div>

              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; font-size:12px;">
                <a href="#" id="switch-to-signup" onclick="document.getElementById('tab-btn-signup').click(); return false;" style="color:#4ade80; text-decoration:none; font-weight:700;">
                  Don't have an account? Sign up
                </a>
              </div>

              <!-- Anonymous Guest Access -->
              <div style="text-align:center; margin-top:14px; padding-top:12px; border-top:1px solid rgba(255,255,255,0.12);">
                <a href="index.php?guest=1" style="font-size:12px; color:#fde047; text-decoration:none; font-weight:600;">
                  Too shy to register? Continue as Anonymous Guest &rarr;
                </a>
              </div>
            </form>

            <!-- Create Account Form (Frame 2 Top Left) -->
            <form id="form-signup" style="display:none;">
              <h2 style="font-size:20px; font-weight:800; margin-bottom:2px; color:#ffffff;">Create your account</h2>
              <p style="font-size:12px; color:#94a3b8; margin-bottom:14px;">Register as a student to submit and track facility issues</p>

              <div class="form-group" style="text-align:left;">
                <label class="form-label" style="color:#e2e8f0; font-size:12px;">Username</label>
                <input type="text" name="username" class="input-rounded" placeholder="Enter appropriate name" required>
              </div>

              <div class="form-group" style="text-align:left;">
                <label class="form-label" style="color:#e2e8f0; font-size:12px;">Email address</label>
                <input type="email" name="email" class="input-rounded" placeholder="user@pcb.edu.ph" required>
              </div>

              <div class="form-group" style="text-align:left;">
                <label class="form-label" style="color:#e2e8f0; font-size:12px;">Password</label>
                <input type="password" name="password" class="input-rounded" placeholder="At least 8 characters" minlength="6" required>
              </div>

              <button type="submit" class="btn-primary-green" id="signup-submit-btn" style="width:100%; margin-top:6px; padding:12px; font-size:15px;">
                Create your account
              </button>

              <div style="text-align:center; margin-top:14px; font-size:12px;">
                <a href="#" id="switch-to-signin" onclick="document.getElementById('tab-btn-signin').click(); return false;" style="color:#4ade80; text-decoration:none; font-weight:700;">
                  Already have an account? Sign in
                </a>
              </div>

              <div style="text-align:center; margin-top:12px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.12);">
                <a href="index.php?guest=1" style="font-size:12px; color:#fde047; text-decoration:none; font-weight:600;">
                  Or continue as Anonymous Guest &rarr;
                </a>
              </div>
            </form>

          </div>
        </div>
      </section>

      <!-- SCREEN 5: SUBMISSION SUCCESS CONFIRMATION -->
      <section id="screen-success" class="report-screen-container" style="display:none; text-align:center; padding-top:60px;">
        <div style="width:68px; height:68px; background:#dcfce7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; color:#15803d;">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <h2 style="font-size:22px; font-weight:800; color:#111827; margin-bottom:8px;">Report Submitted!</h2>
        <p style="font-size:13px; color:#4b5563; line-height:1.45; max-width:300px; margin:0 auto 20px;">
          Your complaint has been forwarded to Polytechnic College of Botolan facilities administration.
        </p>

        <div style="background:#ffffff; border:2px dashed #16a34a; border-radius:16px; padding:18px; margin-bottom:24px;">
          <p style="font-size:12px; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Your Tracking Code</p>
          <div id="success-tracking-code" style="font-size:26px; font-weight:900; color:#15803d; letter-spacing:2px;">SP-2024-XXXX</div>
          <p style="font-size:11px; color:#9ca3af; margin-top:4px;">Keep this code to track real-time resolution updates.</p>
        </div>

        <button class="btn-primary-green" id="view-submitted-report-btn" style="width:100%; margin-bottom:10px;">Track this report now</button>
        <button class="btn-secondary-light" data-action="go-home" style="width:100%;">Return to home</button>
      </section>

      <!-- RE-VALIDATION LOCK SCREEN MODAL (Triggered when student left system) -->
      <div class="modal-overlay" id="validation-lock-modal" style="display:none;">
        <div class="modal-dialog" style="max-width:380px; text-align:center; border-radius:24px; overflow:hidden;">
          <div style="background:#1e293b; padding:22px 20px; color:#ffffff; position:relative;">
            <div style="width:54px; height:54px; background:rgba(234, 179, 8, 0.2); border:2px solid #eab308; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; color:#facc15;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h3 style="font-size:19px; font-weight:800; margin-bottom:4px;">Identity Validation</h3>
            <p style="font-size:12px; color:#94a3b8;">You left the system. Please validate to resume.</p>
          </div>

          <div style="padding:22px 20px; background:#ffffff;">
            <div style="background:#f1f5f9; border-radius:12px; padding:10px 12px; margin-bottom:16px; display:flex; align-items:center; gap:10px;">
              <div style="width:36px; height:36px; background:#16a34a; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#ffffff; font-weight:700; font-size:14px;" id="lock-avatar-letter">S</div>
              <div style="text-align:left; flex:1; overflow:hidden;">
                <div style="font-size:13px; font-weight:800; color:#111827;" id="lock-display-name">Student Name</div>
                <div style="font-size:11px; color:#64748b; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" id="lock-display-email">student@pcb.edu.ph</div>
              </div>
            </div>

            <form id="form-validation-lock">
              <div class="form-group" style="text-align:left; margin-bottom:14px;">
                <label class="form-label" style="font-size:12px; color:#374151;">Enter Password to Validate</label>
                <input type="password" id="lock-input-password" class="input-rounded" placeholder="At least 8 characters" required autocomplete="current-password">
              </div>

              <div id="lock-error-msg" style="display:none; color:#dc2626; font-size:12px; font-weight:600; margin-bottom:10px;"></div>

              <button type="submit" class="btn-primary-green" id="lock-submit-btn" style="width:100%; padding:12px; font-size:14px; margin-bottom:10px;">
                Validate &amp; Continue
              </button>
            </form>

            <button type="button" id="lock-switch-user-btn" style="background:none; border:none; color:#64748b; font-size:12px; cursor:pointer; text-decoration:underline; font-weight:600;">
              Not you? Sign in with a different account
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
