<?php
/**
 * LSB Work Order System - Preset Comments Management
 */

$pageTitle = 'Preset Comments';
require_once __DIR__ . '/includes/wo_header.php';
wo_require_admin();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-comment-dots me-2"></i>Preset Comments</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#commentModal" onclick="openCreateModal()">
        <i class="fas fa-plus me-1"></i>Add Preset
    </button>
</div>

<!-- Type Tabs -->
<ul class="nav nav-pills mb-3" id="typeTabs">
    <li class="nav-item">
        <a class="nav-link active" href="#" data-type="COMMENT">
            <i class="fas fa-comment me-1"></i>Comments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-type="CONDITION_NOTE">
            <i class="fas fa-clipboard-list me-1"></i>Condition Notes
        </a>
    </li>
</ul>

<!-- Category Filter -->
<div class="btn-group mb-3" id="categoryFilter">
    <button class="btn btn-outline-secondary btn-sm active" data-category="">All</button>
    <button class="btn btn-outline-success btn-sm" data-category="ACK">Approve</button>
    <button class="btn btn-outline-warning btn-sm" data-category="CONDITION">Conditional</button>
    <button class="btn btn-outline-danger btn-sm" data-category="REJECT">Reject</button>
    <button class="btn btn-outline-secondary btn-sm" data-category="GENERAL">General</button>
</div>

<!-- Comments List -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 40px">#</th>
                    <th style="width: 100px">Category</th>
                    <th>Text</th>
                    <th style="width: 60px">Status</th>
                    <th style="width: 70px">Actions</th>
                </tr>
            </thead>
            <tbody id="commentsTable">
                <tr><td colspan="5" class="text-center py-4">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Preset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="commentForm" onsubmit="saveComment(event)">
                <div class="modal-body">
                    <input type="hidden" id="commentId" name="id">

                    <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="commentType" name="type" required>
                            <option value="COMMENT">Comment</option>
                            <option value="CONDITION_NOTE">Condition Note</option>
                        </select>
                        <small class="text-muted">Comment: general review comments. Condition Note: specific conditions for conditional approval.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="commentCategory" name="category" required>
                            <option value="ACK">Approve</option>
                            <option value="CONDITION">Conditional Approval</option>
                            <option value="REJECT">Reject</option>
                            <option value="GENERAL">General</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Text <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="commentText" name="comment_text" rows="3" required
                                  placeholder="Enter the preset text..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="commentSortOrder" name="sort_order" value="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="commentActive" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="commentActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="saveBtn">
                        <i class="fas fa-save me-1"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let isEdit = false;
let currentType = 'COMMENT';
let currentCategory = '';

document.addEventListener('DOMContentLoaded', function() {
    loadComments();

    // Type tab click handlers
    document.querySelectorAll('#typeTabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#typeTabs .nav-link').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentType = this.dataset.type;
            loadComments();
        });
    });

    // Category filter click handlers
    document.querySelectorAll('#categoryFilter .btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#categoryFilter .btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.dataset.category;
            loadComments();
        });
    });
});

function loadComments() {
    let url = `api/preset_comment.php?action=list&all=1&type=${encodeURIComponent(currentType)}`;
    if (currentCategory) {
        url += '&category=' + encodeURIComponent(currentCategory);
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderComments(data.data.comments);
            }
        });
}

function renderComments(comments) {
    const tbody = document.getElementById('commentsTable');
    if (comments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-comment-slash fa-3x mb-3 d-block opacity-50"></i>No presets found</td></tr>';
        return;
    }

    tbody.innerHTML = comments.map((c, idx) => `
        <tr class="${!c.is_active ? 'table-secondary' : ''}">
            <td class="text-muted">${c.sort_order || idx + 1}</td>
            <td>${getCategoryBadge(c.category)}</td>
            <td>${escapeHtml(c.comment_text)}</td>
            <td>
                ${c.is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>'}
            </td>
            <td class="text-nowrap">
                <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editComment(${c.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteComment(${c.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getCategoryBadge(category) {
    const badges = {
        'ACK': '<span class="badge bg-success">Approve</span>',
        'CONDITION': '<span class="badge bg-warning text-dark">Conditional</span>',
        'REJECT': '<span class="badge bg-danger">Reject</span>',
        'GENERAL': '<span class="badge bg-secondary">General</span>'
    };
    return badges[category] || category;
}

function openCreateModal() {
    isEdit = false;
    document.getElementById('modalTitle').textContent = 'Add Preset';
    document.getElementById('commentForm').reset();
    document.getElementById('commentId').value = '';
    document.getElementById('commentType').value = currentType;
    document.getElementById('commentActive').checked = true;
}

function editComment(id) {
    fetch(`api/preset_comment.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const c = data.data.comment;
                isEdit = true;
                document.getElementById('modalTitle').textContent = 'Edit Preset';
                document.getElementById('commentId').value = c.id;
                document.getElementById('commentType').value = c.type || 'COMMENT';
                document.getElementById('commentCategory').value = c.category;
                document.getElementById('commentText').value = c.comment_text;
                document.getElementById('commentSortOrder').value = c.sort_order || 0;
                document.getElementById('commentActive').checked = c.is_active == 1;

                new bootstrap.Modal(document.getElementById('commentModal')).show();
            } else {
                alert(data.message || 'Failed to load');
            }
        });
}

function saveComment(e) {
    e.preventDefault();

    const form = document.getElementById('commentForm');
    const formData = new FormData(form);
    formData.append('action', isEdit ? 'update' : 'create');

    // Handle checkbox
    if (!document.getElementById('commentActive').checked) {
        formData.set('is_active', '0');
    }

    const btn = document.getElementById('saveBtn');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    fetch('api/preset_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = origText;

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('commentModal')).hide();
            loadComments();
        } else {
            alert(data.message || 'Failed to save');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        alert('Error: ' + err.message);
    });
}

function deleteComment(id) {
    if (!confirm('Are you sure you want to delete this preset?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    fetch('api/preset_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadComments();
        } else {
            alert(data.message || 'Failed to delete');
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
</script>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
