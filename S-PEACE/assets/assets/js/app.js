// ====================================================================
// S-PEACE: Student Portal Client Logic
// Faithful to Frame 1 and Frame 2 UI Wireframes
// ====================================================================

document.addEventListener('DOMContentLoaded', () => {
    initCategorySelector();
    initCharCounter();
    initPhotoUpload();
    initFormSubmission();
    initScreenTransitions();
    initTrackLookup();
    initAuthForms();
    initValidationGuard();
    initDeviceToggle();
    checkActiveSession();
});

// Current active view state
let currentView = 'home';
let selectedCategoryId = 1;
let currentUser = null;

// Screen Transitions
function showScreen(screenId) {
    const isGuest = window.location.search.includes('guest');
    // Enforce: Log in and register form is the FIRST to appear for both student and admin
    if (!currentUser && !isGuest && screenId !== 'screen-auth') {
        screenId = 'screen-auth';
    }

    const screens = ['screen-auth', 'screen-home', 'screen-report', 'screen-track', 'screen-success'];
    screens.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    const target = document.getElementById(screenId);
    if (target) {
        target.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Hide internal portal action icons on login screen
    const topActions = document.getElementById('portal-topbar-actions');
    if (topActions) {
        topActions.style.display = (screenId === 'screen-auth') ? 'none' : 'flex';
    }
}

function initScreenTransitions() {
    // Report Button clicks
    document.querySelectorAll('[data-action="open-report"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            showScreen('screen-report');
        });
    });

    // Track Button clicks
    document.querySelectorAll('[data-action="open-track"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            showScreen('screen-track');
            loadDefaultOrSampleTrack();
        });
    });

    // Back to Home buttons
    document.querySelectorAll('[data-action="go-home"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentUser || window.location.search.includes('guest')) {
                showScreen('screen-home');
            } else {
                showScreen('screen-auth');
            }
        });
    });

    // Open Auth modal/screen
    document.querySelectorAll('[data-action="open-auth"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            showScreen('screen-auth');
        });
    });
}

// Category Tile Selector (Matching Frame 2 Category Grid)
function initCategorySelector() {
    const tiles = document.querySelectorAll('.category-tile');
    const hiddenInput = document.getElementById('selected-category-id');

    tiles.forEach(tile => {
        tile.addEventListener('click', () => {
            tiles.forEach(t => t.classList.remove('active'));
            tile.classList.add('active');
            const catId = tile.getAttribute('data-category-id');
            if (hiddenInput) hiddenInput.value = catId;
            selectedCategoryId = catId;
        });
    });
}

// Character counter for description
function initCharCounter() {
    const descInput = document.getElementById('report-description');
    const counterDisplay = document.getElementById('desc-char-count');

    if (descInput && counterDisplay) {
        descInput.addEventListener('input', () => {
            const len = descInput.value.length;
            counterDisplay.textContent = `${len}/500`;
            if (len > 500) {
                counterDisplay.style.color = '#ef4444';
            } else {
                counterDisplay.style.color = '#4b5563';
            }
        });
    }
}

// Photo Upload & Preview
function initPhotoUpload() {
    const fileInput = document.getElementById('report-photo-input');
    const dropzone = document.getElementById('photo-dropzone');
    const previewBox = document.getElementById('photo-preview-box');
    const previewImg = document.getElementById('photo-preview-img');
    const removeBtn = document.getElementById('remove-photo-btn');

    if (!fileInput || !dropzone) return;

    dropzone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('File is too large! Maximum photo size is 5MB.');
                fileInput.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                if (previewImg && previewBox) {
                    previewImg.src = e.target.result;
                    previewBox.style.display = 'block';
                    dropzone.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        }
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.value = '';
            if (previewBox) previewBox.style.display = 'none';
            if (dropzone) dropzone.style.display = 'block';
        });
    }
}

// Form Submission with Boundary & Validation
function initFormSubmission() {
    const form = document.getElementById('complaint-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-report-btn');
        const origText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting report...';

        const formData = new FormData(form);

        try {
            const res = await fetch('api/reports.php', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (data.boundary_warning) {
                // Strict Boundary Modal (PPT Requirement)
                alert('NOTICE: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.textContent = origText;
                return;
            }

            if (data.success) {
                // Save to local tracking history
                saveRecentTrackingCode(data.tracking_code);

                // Show Success Screen
                document.getElementById('success-tracking-code').textContent = data.tracking_code;
                form.reset();
                document.getElementById('photo-preview-box').style.display = 'none';
                document.getElementById('photo-dropzone').style.display = 'block';
                document.getElementById('desc-char-count').textContent = '0/500';

                showScreen('screen-success');

                // Bind Track Now Button
                document.getElementById('view-submitted-report-btn').onclick = () => {
                    fetchAndDisplayReport(data.tracking_code);
                    showScreen('screen-track');
                };
            } else {
                alert(data.message || 'Error submitting report.');
            }
        } catch (err) {
            console.error(err);
            alert('Network error while submitting report. Please check your connection.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = origText;
        }
    });
}

// Tracking Code Lookup & Live Timeline Render
function initTrackLookup() {
    const searchBtn = document.getElementById('track-search-btn');
    const searchInput = document.getElementById('tracking-code-input');

    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', () => {
            const code = searchInput.value.trim();
            if (code) {
                fetchAndDisplayReport(code);
            }
        });

        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const code = searchInput.value.trim();
                if (code) fetchAndDisplayReport(code);
            }
        });
    }

    // Upvote Button
    const upvoteBtn = document.getElementById('report-upvote-btn');
    if (upvoteBtn) {
        upvoteBtn.addEventListener('click', async () => {
            const complaintId = upvoteBtn.getAttribute('data-id');
            if (!complaintId) return;

            const fd = new FormData();
            fd.append('action', 'upvote');
            fd.append('complaint_id', complaintId);

            try {
                const res = await fetch('api/reports.php?action=upvote', {
                    method: 'POST',
                    body: fd
                });
                const d = await res.json();
                if (d.success) {
                    document.getElementById('report-upvotes-count').textContent = `${d.upvotes} up-votes`;
                    upvoteBtn.style.background = '#22c55e';
                    upvoteBtn.disabled = true;
                } else {
                    alert(d.message);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }
}

async function fetchAndDisplayReport(code) {
    try {
        const res = await fetch(`api/reports.php?code=${encodeURIComponent(code)}`);
        const data = await res.json();

        if (data.success && data.report) {
            renderReportDetails(data.report, data.logs);
        } else {
            alert(data.message || 'Report not found. Please verify the code.');
        }
    } catch (err) {
        console.error(err);
        alert('Failed to retrieve report data.');
    }
}

function renderReportDetails(report, logs) {
    // Top tag
    const reporterText = report.is_anonymous ? 'Anonymous' : (report.student_name || 'Student');
    document.getElementById('report-detail-tag').textContent = `${report.category_name} • ${reporterText}`;

    // Status Badge
    const badge = document.getElementById('report-detail-status-badge');
    badge.textContent = report.status;
    badge.className = 'badge-status';
    if (report.status === 'Submitted') badge.classList.add('badge-submitted');
    else if (report.status === 'In progress') badge.classList.add('badge-inprogress');
    else if (report.status === 'Resolved') badge.classList.add('badge-resolved');

    // Title & Location
    document.getElementById('report-detail-title').textContent = report.problem;
    document.getElementById('report-detail-location').textContent = report.location;
    document.getElementById('report-detail-desc').textContent = report.description;

    // Submission Date & Upvotes
    const dateFormatted = new Date(report.submitted_at).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
    document.getElementById('report-detail-date').textContent = `Submitted ${dateFormatted}`;
    document.getElementById('report-upvotes-count').textContent = `${report.upvotes} up-votes`;

    const upvoteBtn = document.getElementById('report-upvote-btn');
    if (upvoteBtn) {
        upvoteBtn.setAttribute('data-id', report.id);
        upvoteBtn.disabled = false;
        upvoteBtn.style.background = '';
    }

    // Photo
    const photoContainer = document.getElementById('report-detail-photo-container');
    const photoImg = document.getElementById('report-detail-photo');
    if (report.photo_url) {
        photoImg.src = report.photo_url;
        photoContainer.style.display = 'block';
    } else {
        photoContainer.style.display = 'none';
    }

    // Status Timeline Stepper
    renderStepper(report);

    // Show Report Card and Tracker Card
    document.getElementById('report-display-area').style.display = 'block';
}

function renderStepper(report) {
    const s1 = document.getElementById('step-submitted');
    const s2 = document.getElementById('step-inprogress');
    const s3 = document.getElementById('step-resolved');

    // Reset classes
    [s1, s2, s3].forEach(s => {
        s.className = 'stepper-item';
    });

    const status = report.status;

    if (status === 'Submitted') {
        s1.classList.add('completed', 'active');
        document.getElementById('step-s1-meta').textContent = 'Received by administration';
        document.getElementById('step-s2-meta').textContent = 'Pending staff review';
        document.getElementById('step-s3-meta').textContent = 'Pending maintenance';
    } else if (status === 'In progress') {
        s1.classList.add('completed');
        s2.classList.add('active');
        document.getElementById('step-s1-meta').textContent = 'Report accepted';
        document.getElementById('step-s2-meta').textContent = report.staff_name 
            ? `Assigned to ${report.staff_name} (${report.staff_specialization || 'Facilities'})` 
            : 'Maintenance crew dispatched';
        document.getElementById('step-s3-meta').textContent = 'Work in progress';
    } else if (status === 'Resolved') {
        s1.classList.add('completed');
        s2.classList.add('completed');
        s3.classList.add('completed', 'active');
        document.getElementById('step-s1-meta').textContent = 'Report accepted';
        document.getElementById('step-s2-meta').textContent = report.staff_name ? `Fixed by ${report.staff_name}` : 'Completed';
        document.getElementById('step-s3-meta').textContent = report.resolution_notes 
            ? `Fixed: ${report.resolution_notes}` 
            : 'Facility repaired and verified';
    }
}

function loadDefaultOrSampleTrack() {
    // If user has recent tracking code, load it; otherwise load sample SP-2024-8842
    const recents = getRecentTrackingCodes();
    const code = recents.length > 0 ? recents[0] : 'SP-2024-8842';
    document.getElementById('tracking-code-input').value = code;
    fetchAndDisplayReport(code);
}

function saveRecentTrackingCode(code) {
    try {
        let codes = JSON.parse(localStorage.getItem('speace_recent_codes') || '[]');
        if (!codes.includes(code)) {
            codes.unshift(code);
            if (codes.length > 5) codes.pop();
            localStorage.setItem('speace_recent_codes', JSON.stringify(codes));
        }
    } catch (e) {}
}

function getRecentTrackingCodes() {
    try {
        return JSON.parse(localStorage.getItem('speace_recent_codes') || '[]');
    } catch (e) {
        return [];
    }
}

// Authentication Forms (Sign in & Create Account from Frame 2 Top)
function initAuthForms() {
    const tabSignIn = document.getElementById('tab-btn-signin');
    const tabSignUp = document.getElementById('tab-btn-signup');
    const formSignIn = document.getElementById('form-signin');
    const formSignUp = document.getElementById('form-signup');

    // Quick Test Credential Fillers
    const btnQuickStudent = document.getElementById('quick-fill-student');
    const btnQuickAdmin = document.getElementById('quick-fill-admin');
    const emailInput = document.getElementById('signin-email');
    const passwordInput = document.getElementById('signin-password');

    if (btnQuickStudent && emailInput && passwordInput) {
        btnQuickStudent.addEventListener('click', () => {
            emailInput.value = 'student@pcb.edu.ph';
            passwordInput.value = 'student123';
        });
    }

    if (btnQuickAdmin && emailInput && passwordInput) {
        btnQuickAdmin.addEventListener('click', () => {
            emailInput.value = 'admin@pcb.edu.ph';
            passwordInput.value = 'admin123';
        });
    }

    if (tabSignIn && tabSignUp) {
        tabSignIn.addEventListener('click', () => {
            tabSignIn.classList.add('active');
            tabSignIn.style.background = '#16a34a';
            tabSignIn.style.color = '#ffffff';

            tabSignUp.classList.remove('active');
            tabSignUp.style.background = 'none';
            tabSignUp.style.color = '#94a3b8';

            formSignIn.style.display = 'block';
            formSignUp.style.display = 'none';
        });

        tabSignUp.addEventListener('click', () => {
            tabSignUp.classList.add('active');
            tabSignUp.style.background = '#16a34a';
            tabSignUp.style.color = '#ffffff';

            tabSignIn.classList.remove('active');
            tabSignIn.style.background = 'none';
            tabSignIn.style.color = '#94a3b8';

            formSignUp.style.display = 'block';
            formSignIn.style.display = 'none';
        });
    }

    // Handle Login with Dynamic Role Detection
    if (formSignIn) {
        formSignIn.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('signin-submit-btn');
            const origText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Authenticating...';

            const fd = new FormData(formSignIn);
            fd.append('action', 'login');

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: fd });
                const d = await res.json();
                if (d.success) {
                    currentUser = d.user;
                    updateUserBadge(d.user);

                    // Automatic Role Redirection: Admin vs Student
                    if (d.user.role === 'admin') {
                        submitBtn.textContent = 'Admin Verified! Redirecting...';
                        window.location.href = 'admin/index.php';
                    } else {
                        submitBtn.textContent = 'Student Verified! Entering Portal...';
                        setTimeout(() => {
                            showScreen('screen-home');
                        }, 300);
                    }
                } else {
                    alert(d.message || 'Invalid credentials. Please verify your email and password.');
                }
            } catch (err) {
                alert('Sign in request failed. Please check network connection.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = origText;
            }
        });
    }

    // Handle Student Registration
    if (formSignUp) {
        formSignUp.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('signup-submit-btn');
            const origText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating account...';

            const fd = new FormData(formSignUp);
            fd.append('action', 'register');

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: fd });
                const d = await res.json();
                if (d.success) {
                    currentUser = d.user;
                    updateUserBadge(d.user);
                    alert('Registration complete! Welcome to S-PEACE, ' + d.user.username);
                    showScreen('screen-home');
                } else {
                    alert(d.message || 'Registration failed.');
                }
            } catch (err) {
                alert('Account registration failed.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = origText;
            }
        });
    }
}

// Validation Guard: If student left the system, require password validation
function initValidationGuard() {
    let awayTimestamp = null;
    let idleTimer = null;

    // 1. Detect when user switches tabs or minimizes browser
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            awayTimestamp = Date.now();
        } else if (document.visibilityState === 'visible') {
            if (awayTimestamp && currentUser) {
                const elapsed = Date.now() - awayTimestamp;
                // If away for more than 40 seconds, trigger identity validation lock
                if (elapsed > 40000) {
                    lockSystemForValidation('You left the system. Please validate your identity to resume.');
                }
            }
        }
    });

    // 2. Idle Inactivity Timer (locks after 5 minutes of no activity)
    function resetIdleTimer() {
        clearTimeout(idleTimer);
        if (currentUser) {
            idleTimer = setTimeout(() => {
                lockSystemForValidation('Inactivity Timeout: Please re-validate your identity.');
            }, 5 * 60 * 1000);
        }
    }
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evt => {
        document.addEventListener(evt, resetIdleTimer, true);
    });

    // 3. Explicit Lock / Leave System buttons
    const lockBtn = document.getElementById('topbar-lock-btn');
    const heroLeaveLink = document.getElementById('hero-leave-link');
    const doExplicitLock = async (e) => {
        if (e) e.preventDefault();
        try {
            await fetch('api/auth.php?action=lock', { method: 'POST' });
        } catch (err) {}
        lockSystemForValidation('You left / locked the system. Enter your password to validate.');
    };
    if (lockBtn) lockBtn.onclick = doExplicitLock;
    if (heroLeaveLink) heroLeaveLink.onclick = doExplicitLock;

    // 4. Handle Unlock Form Submit
    const lockForm = document.getElementById('form-validation-lock');
    if (lockForm) {
        lockForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const passInput = document.getElementById('lock-input-password');
            const errorDiv = document.getElementById('lock-error-msg');
            const submitBtn = document.getElementById('lock-submit-btn');

            errorDiv.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Validating...';

            const fd = new FormData();
            fd.append('action', 'unlock');
            fd.append('password', passInput.value);

            try {
                const res = await fetch('api/auth.php?action=unlock', {
                    method: 'POST',
                    body: fd
                });
                const d = await res.json();

                if (d.success) {
                    currentUser = d.user;
                    updateUserBadge(d.user);
                    const modal = document.getElementById('validation-lock-modal');
                    modal.style.display = 'none';
                    modal.classList.remove('active');
                    passInput.value = '';
                } else {
                    errorDiv.textContent = d.message || 'Validation failed. Please verify password.';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Connection error during validation.';
                errorDiv.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Validate & Continue';
            }
        });
    }

    // 5. Switch Account / Sign Out from Lock Screen
    const switchBtn = document.getElementById('lock-switch-user-btn');
    if (switchBtn) {
        switchBtn.addEventListener('click', async () => {
            await fetch('api/auth.php?action=logout');
            window.location.href = 'login.php?reason=logged_out';
        });
    }
}

function lockSystemForValidation(reason) {
    if (!currentUser) return;

    const modal = document.getElementById('validation-lock-modal');
    if (!modal) return;

    document.getElementById('lock-display-name').textContent = currentUser.username;
    document.getElementById('lock-display-email').textContent = currentUser.email;
    document.getElementById('lock-avatar-letter').textContent = currentUser.username.charAt(0).toUpperCase();
    document.getElementById('lock-input-password').value = '';
    document.getElementById('lock-error-msg').style.display = 'none';

    modal.style.display = 'flex';
    modal.classList.add('active');
}

async function checkActiveSession() {
    try {
        const res = await fetch('api/auth.php?action=check');
        const data = await res.json();

        if (data.authenticated && data.user) {
            currentUser = data.user;
            updateUserBadge(data.user);

            // Automatically route Admin users to Central Dashboard!
            if (data.user.role === 'admin') {
                window.location.href = 'admin/index.php';
                return;
            }

            // If user left and was flagged locked by session timeout
            if (data.locked) {
                lockSystemForValidation('You left the system. Please validate to resume.');
            } else {
                // If student is logged in, show student portal
                const authEl = document.getElementById('screen-auth');
                if (authEl && authEl.style.display !== 'none') {
                    showScreen('screen-home');
                }
            }
        } else {
            currentUser = null;
            updateUserBadge(null);

            // Not logged in: Show Log In & Register form first!
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('guest')) {
                showScreen('screen-auth');
            }
        }
    } catch (e) {}
}

function updateUserBadge(user) {
    const btn = document.getElementById('topbar-user-btn');
    const lockBtn = document.getElementById('topbar-lock-btn');
    const heroPill = document.getElementById('hero-user-session-pill');
    const heroName = document.getElementById('hero-username-span');
    const heroLeave = document.getElementById('hero-leave-link');

    if (user) {
        if (btn) {
            btn.innerHTML = `<span style="font-size:12px; font-weight:700; color:#86efac; display:inline-flex; align-items:center; gap:4px;">● ${user.username}</span>`;
            btn.title = `Validated as ${user.username} (${user.email}). Click to sign out.`;
            btn.onclick = () => {
                if (confirm(`Currently signed in as ${user.username}. Do you want to sign out?`)) {
                    fetch('api/auth.php?action=logout').then(() => {
                        window.location.href = 'index.php?logout=1';
                    });
                }
            };
        }
        if (lockBtn) {
            lockBtn.style.display = 'inline-flex';
        }
        if (heroPill && heroName) {
            heroName.textContent = user.username;
            heroPill.style.display = 'inline-block';
        }
        if (heroLeave) {
            heroLeave.onclick = (e) => {
                e.preventDefault();
                if (confirm(`Sign out ${user.username} and return to Log In form?`)) {
                    fetch('api/auth.php?action=logout').then(() => {
                        window.location.href = 'index.php?logout=1';
                    });
                }
            };
        }
    } else {
        if (btn) {
            btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`;
            btn.title = 'Sign In / Account';
            btn.onclick = () => {
                showScreen('screen-auth');
            };
        }
        if (lockBtn) {
            lockBtn.style.display = 'none';
        }
        if (heroPill) {
            heroPill.style.display = 'none';
        }
    }
}

// Device View Mode Toggle (Mobile Wireframe vs Expanded Desktop View)
function initDeviceToggle() {
    const container = document.getElementById('phone-container');
    const btnMobile = document.getElementById('btn-view-mobile');
    const btnDesktop = document.getElementById('btn-view-desktop');

    if (btnMobile && btnDesktop && container) {
        btnMobile.addEventListener('click', () => {
            container.classList.remove('expanded-mode');
            btnMobile.classList.add('active');
            btnDesktop.classList.remove('active');
        });

        btnDesktop.addEventListener('click', () => {
            container.classList.add('expanded-mode');
            btnDesktop.classList.add('active');
            btnMobile.classList.remove('active');
        });
    }
}
