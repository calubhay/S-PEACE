// ====================================================================
// S-PEACE: Admin Central Dashboard Client Logic
// Polytechnic College of Botolan (PCB)
// ====================================================================

let activeCategory = 'all';
let activeStatus = 'all';
let currentComplaints = [];

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardData();
    initFilters();
    initModal();
    initForm();
});

// Load KPI metrics and reports list
async function loadDashboardData() {
    await fetchAnalytics();
    await fetchComplaints();
}

async function fetchAnalytics() {
    try {
        const res = await fetch('../api/analytics.php');
        const data = await res.json();

        if (data.success && data.metrics) {
            document.getElementById('kpi-total').textContent = data.metrics.total;
            document.getElementById('kpi-submitted').textContent = data.metrics.submitted;
            document.getElementById('kpi-progress').textContent = data.metrics.in_progress;
            document.getElementById('kpi-resolved').textContent = data.metrics.resolved;
            document.getElementById('kpi-rate').textContent = `${data.metrics.resolution_rate}% resolution rate`;

            // Update category badges
            let totalCats = 0;
            if (data.categories) {
                data.categories.forEach(c => {
                    const badge = document.getElementById(`cat-count-${c.slug}`);
                    if (badge) badge.textContent = c.count;
                    totalCats += parseInt(c.count);
                });
            }
            const allBadge = document.getElementById('cat-count-all');
            if (allBadge) allBadge.textContent = data.metrics.total;
        }
    } catch (err) {
        console.error('Failed to fetch analytics', err);
    }
}

async function fetchComplaints() {
    const tbody = document.getElementById('complaints-table-body');
    const searchVal = document.getElementById('admin-search-input').value.trim();

    let url = `../api/reports.php?category=${encodeURIComponent(activeCategory)}&status=${encodeURIComponent(activeStatus)}`;
    if (searchVal) {
        url += `&search=${encodeURIComponent(searchVal)}`;
    }

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            currentComplaints = data.reports || [];
            renderComplaintsTable(currentComplaints);
        } else {
            tbody.innerHTML = `<tr><td colspan="11" style="text-align:center; padding:30px; color:#ef4444;">${data.message}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center; padding:30px; color:#ef4444;">Failed to load complaints. Please refresh.</td></tr>`;
    }
}

function renderComplaintsTable(complaints) {
    const tbody = document.getElementById('complaints-table-body');

    if (!complaints || complaints.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" style="text-align:center; padding:40px; color:#64748b;">
                    <div style="font-size:16px; font-weight:700; margin-bottom:4px;">No matching reports found</div>
                    <div style="font-size:12px;">Try selecting another category or resetting the search filter.</div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = complaints.map(c => {
        let statusBadgeClass = 'badge-submitted';
        if (c.status === 'In progress') statusBadgeClass = 'badge-inprogress';
        else if (c.status === 'Resolved') statusBadgeClass = 'badge-resolved';

        const photoHtml = c.photo_url ? 
            `<img src="../${c.photo_url}" class="thumb-img" onclick="openPhotoViewer('../${c.photo_url}')" title="Click to enlarge" alt="Photo">` : 
            `<div class="no-thumb">No photo</div>`;

        const reporterHtml = c.is_anonymous ? 
            `<span style="color:#64748b; font-style:italic; font-size:12px;">Anonymous</span>` : 
            `<span style="font-weight:600; font-size:12px; color:#1e293b;">${escapeHtml(c.student_name || 'Student')}</span>`;

        const dateFormatted = new Date(c.submitted_at).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        const staffHtml = c.staff_name ? 
            `<span style="color:#0f766e; font-weight:600; font-size:12px;">${escapeHtml(c.staff_name)}</span>` : 
            `<span style="color:#94a3b8; font-size:12px;">Unassigned</span>`;

        return `
            <tr>
                <td><strong style="color:#0f172a; font-family:monospace; font-size:13px;">${c.tracking_code}</strong></td>
                <td>
                    <span style="background:#f1f5f9; color:#334155; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:600;">
                        ${escapeHtml(c.category_name)}
                    </span>
                </td>
                <td style="max-width:240px;">
                    <div style="font-weight:700; color:#111827; margin-bottom:2px;">${escapeHtml(c.problem)}</div>
                    <div style="font-size:11px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(c.description)}</div>
                </td>
                <td>
                    <div style="font-size:12px; color:#334155; display:flex; align-items:center; gap:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        ${escapeHtml(c.location)}
                    </div>
                </td>
                <td>${photoHtml}</td>
                <td>${reporterHtml}</td>
                <td>
                    <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:12px; font-weight:700; font-size:11px;">
                        &uarr; ${c.upvotes || 0}
                    </span>
                </td>
                <td><span class="badge-status ${statusBadgeClass}">${c.status}</span></td>
                <td>${staffHtml}</td>
                <td style="font-size:11px; color:#64748b;">${dateFormatted}</td>
                <td>
                    <button class="action-btn-manage" onclick="openManageModal(${c.id})">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        Manage
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Filters & Search
function initFilters() {
    // Category pills
    document.querySelectorAll('.cat-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeCategory = pill.getAttribute('data-category');
            fetchComplaints();
        });
    });

    // Status tabs
    document.querySelectorAll('.status-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeStatus = tab.getAttribute('data-status');
            fetchComplaints();
        });
    });

    // Search input
    let searchTimeout;
    const searchInput = document.getElementById('admin-search-input');
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchComplaints();
        }, 300);
    });

    document.getElementById('admin-refresh-btn').addEventListener('click', () => {
        loadDashboardData();
    });
}

// Modal Management
function initModal() {
    const modal = document.getElementById('manage-modal');
    const closeBtn = document.getElementById('m-close-btn');
    const cancelBtn = document.getElementById('m-cancel-btn');

    closeBtn.addEventListener('click', () => modal.classList.remove('active'));
    cancelBtn.addEventListener('click', () => modal.classList.remove('active'));

    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('active');
    });
}

async function openManageModal(complaintId) {
    const modal = document.getElementById('manage-modal');

    try {
        const res = await fetch(`../api/reports.php?id=${complaintId}`);
        const data = await res.json();

        if (data.success && data.report) {
            const r = data.report;

            document.getElementById('manage-complaint-id').value = r.id;
            document.getElementById('m-tracking-code').textContent = r.tracking_code;
            document.getElementById('m-category-reporter').textContent = `${r.category_name} • ${r.display_reporter}`;
            document.getElementById('m-problem').textContent = r.problem;
            document.getElementById('m-location').textContent = `Location: ${r.location}`;
            document.getElementById('m-description').textContent = r.description;

            // Photo
            const photoEl = document.getElementById('m-photo');
            if (r.photo_url) {
                photoEl.src = `../${r.photo_url}`;
                photoEl.style.display = 'block';
            } else {
                photoEl.src = '';
                photoEl.style.display = 'none';
            }

            // Proof photo
            const proofEl = document.getElementById('m-resolution-photo');
            const noProofEl = document.getElementById('m-no-proof-text');
            if (r.resolution_photo_url) {
                proofEl.src = `../${r.resolution_photo_url}`;
                proofEl.style.display = 'block';
                noProofEl.style.display = 'none';
            } else {
                proofEl.style.display = 'none';
                noProofEl.style.display = 'block';
            }

            // Set inputs
            document.getElementById('manage-status').value = r.status;
            document.getElementById('manage-staff').value = r.assigned_staff_id || '';
            document.getElementById('manage-admin-notes').value = r.admin_notes || '';
            document.getElementById('manage-resolution-notes').value = r.resolution_notes || '';

            // Render logs
            renderLogs(data.logs || []);

            modal.classList.add('active');
        }
    } catch (err) {
        alert('Failed to load complaint details.');
    }
}

function renderLogs(logs) {
    const container = document.getElementById('m-logs-container');
    if (logs.length === 0) {
        container.innerHTML = '<div style="font-size:12px; color:#94a3b8;">No activity logged yet.</div>';
        return;
    }

    container.innerHTML = logs.map(l => {
        const d = new Date(l.created_at).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        return `
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; font-size:12px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                    <strong style="color:#0f172a;">${escapeHtml(l.action_type)}</strong>
                    <span style="color:#64748b; font-size:11px;">${d} by ${escapeHtml(l.performed_by)}</span>
                </div>
                <div style="color:#475569;">${escapeHtml(l.notes || '')}</div>
            </div>
        `;
    }).join('');
}

// Form Submission
function initForm() {
    const form = document.getElementById('manage-complaint-form');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const saveBtn = document.getElementById('m-save-btn');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        const complaintId = document.getElementById('manage-complaint-id').value;
        const status = document.getElementById('manage-status').value;
        const staffId = document.getElementById('manage-staff').value;
        const adminNotes = document.getElementById('manage-admin-notes').value;
        const resolutionNotes = document.getElementById('manage-resolution-notes').value;
        const proofPhotoInput = document.getElementById('manage-proof-photo');

        try {
            // If Resolved was chosen
            if (status === 'Resolved') {
                const fd = new FormData();
                fd.append('action', 'resolve');
                fd.append('complaint_id', complaintId);
                fd.append('resolution_notes', resolutionNotes || 'Facility issue repaired and confirmed operational.');
                if (proofPhotoInput.files[0]) {
                    fd.append('resolution_photo', proofPhotoInput.files[0]);
                }

                await fetch('../api/admin_reports.php?action=resolve', { method: 'POST', body: fd });
            } else {
                // Update status
                const fd = new FormData();
                fd.append('action', 'update_status');
                fd.append('complaint_id', complaintId);
                fd.append('status', status);
                fd.append('notes', adminNotes);
                await fetch('../api/admin_reports.php?action=update_status', { method: 'POST', body: fd });
            }

            // Assign staff if selected
            if (staffId) {
                const fdStaff = new FormData();
                fdStaff.append('action', 'assign_staff');
                fdStaff.append('complaint_id', complaintId);
                fdStaff.append('staff_id', staffId);
                fdStaff.append('admin_notes', adminNotes);
                await fetch('../api/admin_reports.php?action=assign_staff', { method: 'POST', body: fdStaff });
            }

            alert('Report updated successfully! Workflow status synced.');
            document.getElementById('manage-modal').classList.remove('active');
            loadDashboardData();

        } catch (err) {
            console.error(err);
            alert('Failed to save update.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
        }
    });
}

function openPhotoViewer(url) {
    window.open(url, '_blank');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[m]));
}
