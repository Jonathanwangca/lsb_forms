<?php
/**
 * LSB Work Order System - My Work Orders
 */

$pageTitle = 'My Work Orders';
require_once __DIR__ . '/includes/wo_functions.php';
require_once __DIR__ . '/includes/wo_header.php';

$status = $_GET['status'] ?? '';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-list me-2"></i>My Work Orders</h1>
    <a href="wo_create.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Create New
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0">Status:</label>
            </div>
            <div class="col-auto">
                <select class="form-select form-select-sm" id="statusFilter" onchange="loadWOs()">
                    <option value="">All</option>
                    <option value="DRAFT" <?= $status === 'DRAFT' ? 'selected' : '' ?>>Draft</option>
                    <option value="SUBMITTED" <?= $status === 'SUBMITTED' ? 'selected' : '' ?>>Submitted</option>
                    <option value="DONE" <?= $status === 'DONE' ? 'selected' : '' ?>>Completed</option>
                    <option value="REJECTED" <?= $status === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-auto">
                <input type="text" class="form-control form-control-sm" id="searchInput"
                       placeholder="Search..." onkeyup="debounceSearch()">
            </div>
        </div>
    </div>
</div>

<!-- WO List -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>WO No.</th>
                    <th>Title</th>
                    <th>Project</th>
                    <th>Vendor</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="woTable">
                <tr><td colspan="8" class="text-center py-4">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<nav class="mt-3" id="pagination"></nav>

<script>
let searchTimeout;
let currentPage = 1;

document.addEventListener('DOMContentLoaded', loadWOs);

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadWOs();
    }, 300);
}

function loadWOs(page = 1) {
    currentPage = page;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;

    let url = `api/wo.php?action=list&page=${page}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderWOs(data.data.items);
                renderPagination(data.data);
            }
        });
}

function renderWOs(items) {
    const tbody = document.getElementById('woTable');
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 d-block opacity-50"></i>No work orders found</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(wo => `
        <tr>
            <td><a href="wo_view.php?id=${wo.id}" class="fw-bold text-decoration-none">${escapeHtml(wo.wo_no)}</a></td>
            <td>${escapeHtml(wo.title || '-')}</td>
            <td>${escapeHtml(wo.project_name || '-')}</td>
            <td>${escapeHtml(wo.vendor_name || '-')}</td>
            <td class="text-end">${formatCurrency(wo.original_amount)}</td>
            <td>${getStatusBadge(wo.status, wo)}</td>
            <td>${formatDate(wo.created_at)}</td>
            <td>
                <a href="wo_view.php?id=${wo.id}" class="btn btn-sm btn-outline-primary" title="View">
                    <i class="fas fa-eye"></i>
                </a>
                ${wo.status === 'DRAFT' || wo.status === 'REJECTED' ? `
                <a href="wo_edit.php?id=${wo.id}" class="btn btn-sm btn-outline-secondary" title="Edit">
                    <i class="fas fa-edit"></i>
                </a>
                ` : ''}
            </td>
        </tr>
    `).join('');
}

function renderPagination(data) {
    const nav = document.getElementById('pagination');
    if (data.pages <= 1) {
        nav.innerHTML = '';
        return;
    }

    let html = '<ul class="pagination justify-content-center">';
    html += `<li class="page-item ${data.page <= 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadWOs(${data.page - 1}); return false;">Previous</a>
    </li>`;

    for (let i = 1; i <= data.pages; i++) {
        if (i === 1 || i === data.pages || (i >= data.page - 2 && i <= data.page + 2)) {
            html += `<li class="page-item ${i === data.page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadWOs(${i}); return false;">${i}</a>
            </li>`;
        } else if (i === data.page - 3 || i === data.page + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    html += `<li class="page-item ${data.page >= data.pages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadWOs(${data.page + 1}); return false;">Next</a>
    </li>`;
    html += '</ul>';

    nav.innerHTML = html;
}

function getStatusBadge(status, wo = null) {
    if (status === 'SUBMITTED' && wo && wo.approval_total > 0) {
        const approved = wo.approval_approved || 0;
        const total = wo.approval_total;
        const percent = Math.round((approved / total) * 100);
        return `<span class="badge bg-primary">Submitted</span>
                <span class="badge bg-info ms-1" title="${approved} of ${total} departments approved">
                    <i class="fas fa-tasks me-1"></i>${approved}/${total}
                </span>`;
    }
    const badges = {
        'DRAFT': '<span class="badge bg-secondary">Draft</span>',
        'SUBMITTED': '<span class="badge bg-primary">Submitted</span>',
        'DONE': '<span class="badge bg-success">Completed</span>',
        'REJECTED': '<span class="badge bg-danger">Rejected</span>'
    };
    return badges[status] || status;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-CA');
}
</script>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
