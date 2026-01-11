<?php
/**
 * LSB Work Order System - Create Work Order
 */

$pageTitle = 'Create Work Order';
require_once __DIR__ . '/includes/wo_functions.php';
require_once __DIR__ . '/includes/wo_header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-plus-circle me-2"></i>Create Work Order</h1>
    <div>
        <button type="submit" form="woForm" class="btn btn-primary" id="saveBtn">
            <i class="fas fa-save me-1"></i>Save as Draft
        </button>
        <button type="button" class="btn btn-success" id="submitBtn" onclick="saveAndSubmit()">
            <i class="fas fa-paper-plane me-1"></i>Save & Submit
        </button>
        <a href="wo_list.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to List
        </a>
    </div>
</div>

<form id="woForm" onsubmit="saveWO(event)">
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Basic Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Basic Information
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title / Description</label>
                            <input type="text" class="form-control" name="title" id="title">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LSB Job No.</label>
                            <input type="text" class="form-control" name="lsb_job_no" id="lsb_job_no">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project Code</label>
                            <input type="text" class="form-control" name="project_code" id="project_code">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Issued Date</label>
                            <input type="date" class="form-control" name="issued_date" id="issued_date">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-building me-2"></i>Project Information
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Project Name</label>
                            <input type="text" class="form-control" name="project_name" id="project_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Owner Name</label>
                            <input type="text" class="form-control" name="owner_name" id="owner_name">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Project Address</label>
                            <input type="text" class="form-control" name="project_address" id="project_address">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendor Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-truck me-2"></i>Vendor / Subcontractor Information
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vendor Name</label>
                            <input type="text" class="form-control" name="vendor_name" id="vendor_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control" name="vendor_contact" id="vendor_contact">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Vendor Address</label>
                            <input type="text" class="form-control" name="vendor_address" id="vendor_address">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="vendor_phone" id="vendor_phone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="vendor_email" id="vendor_email">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scope of Work -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tasks me-2"></i>Scope of Work
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="scope_summary" id="scope_summary" rows="5"
                              placeholder="Describe the scope of work..."></textarea>
                </div>
            </div>

            <!-- Memo -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-sticky-note me-2"></i>Internal Memo
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="memo" id="memo" rows="3"
                              placeholder="Internal notes..."></textarea>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- File Upload - Highlighted -->
            <div class="card mb-4 border-warning" style="border-width: 2px !important;">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-magic me-2"></i><strong>Upload Excel Contract</strong>
                    <span class="badge bg-dark ms-2">AI Auto-Fill</span>
                </div>
                <div class="card-body bg-warning bg-opacity-10">
                    <div class="mb-3">
                        <div class="upload-area p-3 border-2 border-dashed rounded text-center"
                             style="border: 2px dashed #ffc107; background: #fffbeb; cursor: pointer;"
                             onclick="document.getElementById('excelFile').click()">
                            <i class="fas fa-cloud-upload-alt fa-2x text-warning mb-2"></i>
                            <p class="mb-1 fw-bold">Click to upload Excel file</p>
                            <small class="text-muted">or drag and drop here</small>
                        </div>
                        <input type="file" class="form-control d-none" id="excelFile" accept=".xlsx,.xls,.xltx"
                               onchange="handleExcelUpload(this)">
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle me-1"></i>AI will auto-fill form fields from your Excel contract
                        </small>
                    </div>
                    <div id="parseResult" class="d-none">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-robot me-2"></i><span class="ai-parsing-text">AI Parsing</span><span class="ai-dots">...</span>
                        </div>
                    </div>
                    <style>
                        @keyframes aiDots {
                            0%, 20% { content: '.'; }
                            40% { content: '..'; }
                            60%, 100% { content: '...'; }
                        }
                        .ai-dots {
                            display: inline-block;
                            animation: blink 1.4s infinite;
                        }
                        @keyframes blink {
                            0%, 50% { opacity: 1; }
                            51%, 100% { opacity: 0.3; }
                        }
                        .upload-area {
                            transition: all 0.3s ease;
                        }
                        .upload-area:hover {
                            background: #fff3cd !important;
                            border-color: #ffca2c !important;
                            transform: scale(1.02);
                        }
                        .upload-area.dragover {
                            background: #fff3cd !important;
                            border-color: #ffc107 !important;
                            box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
                        }
                        @keyframes pulse {
                            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
                            50% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
                        }
                        .border-warning {
                            animation: pulse 2s infinite;
                        }
                    </style>
                </div>
            </div>

            <!-- Amount Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-dollar-sign me-2"></i>Financial Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Original Amount (CAD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="original_amount" id="original_amount"
                                   step="0.01" min="0" value="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cost Code</label>
                        <input type="text" class="form-control" name="cost_code" id="cost_code">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Holdback Percent (%)</label>
                        <input type="number" class="form-control" name="holdback_percent" id="holdback_percent"
                               step="0.01" min="0" max="100" value="10.00">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let woId = null;
let shouldSubmit = false;

function saveWO(e) {
    e.preventDefault();
    const form = document.getElementById('woForm');
    const formData = new FormData(form);
    formData.append('action', woId ? 'update' : 'create');
    if (woId) formData.append('id', woId);

    const btn = document.getElementById('saveBtn');
    const willSubmit = shouldSubmit; // 保存当前状态
    shouldSubmit = false; // 立即重置，防止重复提交

    showLoading(btn);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) {
            return r.text().then(text => {
                throw new Error('Server error (' + r.status + '): ' + text.substring(0, 200));
            });
        }
        return r.json();
    })
    .then(data => {
        hideLoading(btn);
        if (data.success) {
            const isNewWO = !woId && data.data;
            if (isNewWO) {
                woId = data.data.id;
                showAlert('Work Order created: ' + data.data.wo_no, 'success');

                // 上传Excel文件作为附件（如果有）
                uploadExcelAsAttachment(woId).then(() => {
                    // 使用保存的状态判断是否需要提交
                    if (willSubmit && woId) {
                        submitWO();
                    }
                });
            } else {
                showAlert(data.message, 'success');
                // 使用保存的状态判断是否需要提交
                if (willSubmit && woId) {
                    submitWO();
                }
            }
        } else {
            showAlert(data.message || 'Save failed', 'danger');
        }
    })
    .catch(err => {
        hideLoading(btn);
        console.error('Save error:', err);
        showAlert('Error: ' + err.message, 'danger');
    });
}

function saveAndSubmit() {
    shouldSubmit = true;
    // 直接调用saveWO而不是通过dispatchEvent
    saveWO(new Event('submit'));
}

function submitWO() {
    if (!woId) {
        showAlert('Cannot submit: Work Order not saved yet', 'danger');
        return;
    }

    const btn = document.getElementById('submitBtn');
    showLoading(btn);

    const formData = new FormData();
    formData.append('action', 'submit');
    formData.append('id', woId);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) {
            throw new Error('Server error: ' + r.status);
        }
        return r.json();
    })
    .then(data => {
        hideLoading(btn);
        if (data.success) {
            showAlert('Work Order submitted successfully!', 'success');
            setTimeout(() => window.location.href = 'wo_list.php', 1500);
        } else {
            showAlert(data.message || 'Submit failed', 'danger');
        }
    })
    .catch(err => {
        hideLoading(btn);
        console.error('Submit error:', err);
        showAlert('Error submitting: ' + err.message, 'danger');
    });
}

// 保存上传的Excel文件用于后续作为附件
let uploadedExcelFile = null;

function handleExcelUpload(input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    uploadedExcelFile = file; // 保存文件引用

    const parseResult = document.getElementById('parseResult');
    parseResult.classList.remove('d-none');
    parseResult.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-robot me-2"></i>AI Parsing<span style="animation: blink 1.4s infinite">...</span></div>';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'parse');

    fetch('api/excel_parse.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) {
            return r.text().then(text => {
                throw new Error('Server error (' + r.status + '): ' + text.substring(0, 200));
            });
        }
        return r.json();
    })
    .then(data => {
        if (data.success && data.data) {
            fillFormFromExcel(data.data);
            parseResult.innerHTML = '<div class="alert alert-success mb-0"><i class="fas fa-check me-2"></i>Fields auto-filled. File will be attached when saved.</div>';
        } else {
            parseResult.innerHTML = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>' + (data.message || 'Could not parse Excel file') + '</div>';
        }
    })
    .catch(err => {
        console.error('Excel parse error:', err);
        parseResult.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-times me-2"></i>Error: ' + err.message + '</div>';
    });
}

// 上传Excel作为附件
function uploadExcelAsAttachment(woId) {
    if (!uploadedExcelFile) return Promise.resolve();

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('wo_id', woId);
    formData.append('file', uploadedExcelFile);
    formData.append('category', 'contract');

    return fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            console.log('Excel file attached successfully');
        } else {
            console.error('Failed to attach Excel:', data.message);
        }
    })
    .catch(err => {
        console.error('Error attaching Excel:', err);
    });
}

function fillFormFromExcel(data) {
    const fieldMap = {
        'wo_no': 'title',  // Use WO No as title if extracted
        'lsb_job_no': 'lsb_job_no',
        'project_name': 'project_name',
        'project_address': 'project_address',
        'owner_name': 'owner_name',
        'vendor_name': 'vendor_name',
        'vendor_contact': 'vendor_contact',
        'vendor_phone': 'vendor_phone',
        'vendor_email': 'vendor_email',
        'original_amount': 'original_amount',
        'scope_summary': 'scope_summary',
        'issued_date': 'issued_date',
        'cost_code': 'cost_code'
    };

    for (const [key, fieldId] of Object.entries(fieldMap)) {
        if (data[key] !== undefined && data[key] !== null) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = data[key];
            }
        }
    }
}

// Set today's date as default
document.getElementById('issued_date').value = new Date().toISOString().split('T')[0];

// Drag and drop support for upload area
const uploadArea = document.querySelector('.upload-area');
if (uploadArea) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
    });

    uploadArea.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            const ext = file.name.split('.').pop().toLowerCase();
            if (['xlsx', 'xls', 'xltx'].includes(ext)) {
                document.getElementById('excelFile').files = files;
                handleExcelUpload(document.getElementById('excelFile'));
            } else {
                showAlert('Please upload an Excel file (.xlsx, .xls, .xltx)', 'warning');
            }
        }
    }, false);
}
</script>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
