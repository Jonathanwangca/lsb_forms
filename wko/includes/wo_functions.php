<?php
/**
 * LSB Work Order System - Core Functions
 */

require_once __DIR__ . '/../config/wo_config.php';

/**
 * Create WO draft
 */
function wo_create($data) {
    $pdo = wo_get_db();

    // Generate WO number
    $woNo = wo_generate_number();

    $stmt = $pdo->prepare("
        INSERT INTO lsb_wo_header (
            wo_no, title, lsb_job_no, project_code, project_name, project_address, owner_name,
            vendor_name, vendor_address, vendor_contact, vendor_phone, vendor_email,
            original_amount, cost_code, holdback_percent, scope_summary,
            requester_id, requester_name, requester_email, requester_department,
            status, memo
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            'DRAFT', ?
        )
    ");

    $stmt->execute([
        $woNo,
        $data['title'] ?? null,
        $data['lsb_job_no'] ?? null,
        $data['project_code'] ?? null,
        $data['project_name'] ?? null,
        $data['project_address'] ?? null,
        $data['owner_name'] ?? null,
        $data['vendor_name'] ?? null,
        $data['vendor_address'] ?? null,
        $data['vendor_contact'] ?? null,
        $data['vendor_phone'] ?? null,
        $data['vendor_email'] ?? null,
        $data['original_amount'] ?? 0,
        $data['cost_code'] ?? null,
        $data['holdback_percent'] ?? 10.00,
        $data['scope_summary'] ?? null,
        $data['requester_id'] ?? null,
        $data['requester_name'] ?? '',
        $data['requester_email'] ?? null,
        $data['requester_department'] ?? null,
        $data['memo'] ?? null
    ]);

    $woId = $pdo->lastInsertId();

    return [
        'success' => true,
        'message' => 'Work Order created successfully',
        'data' => ['id' => $woId, 'wo_no' => $woNo]
    ];
}

/**
 * Update WO
 */
function wo_update($id, $data) {
    $pdo = wo_get_db();

    // Check if WO exists and status allows editing
    $wo = wo_get($id);
    if (!$wo) {
        return ['success' => false, 'message' => 'Work Order not found'];
    }
    if ($wo['status'] !== 'DRAFT') {
        return ['success' => false, 'message' => 'Only draft Work Orders can be edited'];
    }

    $fields = [
        'title', 'lsb_job_no', 'project_code', 'project_name', 'project_address', 'owner_name',
        'vendor_name', 'vendor_address', 'vendor_contact', 'vendor_phone', 'vendor_email',
        'original_amount', 'cost_code', 'holdback_percent', 'scope_summary', 'memo', 'issued_date'
    ];

    $updates = [];
    $params = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $data)) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($updates)) {
        return ['success' => false, 'message' => 'No fields to update'];
    }

    $params[] = $id;
    $sql = "UPDATE lsb_wo_header SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return ['success' => true, 'message' => 'Work Order updated successfully'];
}

/**
 * Get single WO
 */
function wo_get($id) {
    $pdo = wo_get_db();
    $stmt = $pdo->prepare("SELECT * FROM lsb_wo_header WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get WO list (created by me, or all if showAll=true for admin)
 */
function wo_list($userId, $filters = [], $showAll = false) {
    $pdo = wo_get_db();

    $where = [];
    $params = [];

    // If not showing all, filter by requester_id
    if (!$showAll) {
        $where[] = "requester_id = ?";
        $params[] = $userId;
    }

    if (!empty($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $where[] = "(wo_no LIKE ? OR title LIKE ? OR project_name LIKE ? OR vendor_name LIKE ?)";
        $search = "%{$filters['search']}%";
        $params = array_merge($params, [$search, $search, $search, $search]);
    }

    // Build WHERE clause (handle empty where array)
    $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

    $sql = "SELECT * FROM lsb_wo_header $whereClause ORDER BY created_at DESC";

    // Pagination
    $page = max(1, intval($filters['page'] ?? 1));
    $limit = max(1, min(100, intval($filters['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM lsb_wo_header $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Get data
    $sql .= " LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit)
    ];
}

/**
 * Get pending review list - department based (my department needs to review)
 */
function wo_inbox($deptCode) {
    $pdo = wo_get_db();

    $stmt = $pdo->prepare("
        SELECT h.*, r.decision, r.reviewer_dept, r.reviewer_role
        FROM lsb_wo_header h
        JOIN lsb_wo_review r ON h.id = r.wo_id
        WHERE r.reviewer_dept = ?
          AND h.status = 'SUBMITTED'
          AND r.decision = 'PENDING'
        ORDER BY h.submitted_at DESC
    ");
    $stmt->execute([$deptCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Submit WO - department based approval
 */
function wo_submit($id) {
    $pdo = wo_get_db();

    // Check WO status
    $wo = wo_get($id);
    if (!$wo) {
        return ['success' => false, 'message' => 'Work Order not found'];
    }
    if ($wo['status'] !== 'DRAFT' && $wo['status'] !== 'REJECTED') {
        return ['success' => false, 'message' => 'Work Order cannot be submitted'];
    }

    $pdo->beginTransaction();
    try {
        // Update WO status
        $stmt = $pdo->prepare("
            UPDATE lsb_wo_header
            SET status = 'SUBMITTED', submitted_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        // Delete old review records (if resubmitting)
        $stmt = $pdo->prepare("DELETE FROM lsb_wo_review WHERE wo_id = ?");
        $stmt->execute([$id]);

        // Get all departments with approval permission
        $stmt = $pdo->prepare("
            SELECT dept_code, dept_name
            FROM lsb_wo_dept_config
            WHERE is_active = 1 AND can_approve = 1
            ORDER BY sort_order
        ");
        $stmt->execute();
        $depts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Create review record for each department
        $insertStmt = $pdo->prepare("
            INSERT INTO lsb_wo_review (wo_id, reviewer_dept, reviewer_role, reviewer_name, reviewer_email, decision)
            VALUES (?, ?, ?, ?, ?, 'PENDING')
        ");

        foreach ($depts as $dept) {
            $insertStmt->execute([
                $id,
                $dept['dept_code'],
                $dept['dept_code'], // reviewer_role = dept_code for backwards compatibility
                $dept['dept_name'], // Department name as default name
                ''  // Email empty, any department member can review
            ]);
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Work Order submitted successfully'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Failed to submit: ' . $e->getMessage()];
    }
}

/**
 * Submit review decision - department based, any department member can review on behalf of department
 */
function wo_review($woId, $deptCode, $reviewerId, $reviewerName, $reviewerEmail, $decision, $comment = null, $conditionNote = null) {
    $pdo = wo_get_db();

    // Validate decision type
    $validDecisions = ['ACK', 'ACK_WITH_CONDITION', 'REJECTED'];
    if (!in_array($decision, $validDecisions)) {
        return ['success' => false, 'message' => 'Invalid decision'];
    }

    // Check if review record exists (department based)
    $stmt = $pdo->prepare("
        SELECT id, decision FROM lsb_wo_review
        WHERE wo_id = ? AND reviewer_dept = ?
    ");
    $stmt->execute([$woId, $deptCode]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        return ['success' => false, 'message' => 'Review record not found for this department'];
    }
    if ($review['decision'] !== 'PENDING') {
        return ['success' => false, 'message' => 'Already reviewed by department'];
    }

    $pdo->beginTransaction();
    try {
        // Update review record, record actual reviewer
        $stmt = $pdo->prepare("
            UPDATE lsb_wo_review
            SET decision = ?, comment = ?, condition_note = ?,
                reviewer_id = ?, reviewer_name = ?, reviewer_email = ?,
                reviewed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$decision, $comment, $conditionNote, $reviewerId, $reviewerName, $reviewerEmail, $review['id']]);

        // Check if WO status needs update
        wo_update_status($woId);

        $pdo->commit();
        return ['success' => true, 'message' => 'Review submitted successfully'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Failed to submit review: ' . $e->getMessage()];
    }
}

/**
 * Update WO status (based on review results)
 */
function wo_update_status($woId) {
    $pdo = wo_get_db();

    // Get all review records
    $stmt = $pdo->prepare("SELECT decision FROM lsb_wo_review WHERE wo_id = ?");
    $stmt->execute([$woId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($reviews)) return;

    // If any REJECTED, WO status becomes REJECTED
    if (in_array('REJECTED', $reviews)) {
        $stmt = $pdo->prepare("UPDATE lsb_wo_header SET status = 'REJECTED' WHERE id = ?");
        $stmt->execute([$woId]);
        return;
    }

    // If still has PENDING, keep SUBMITTED status
    if (in_array('PENDING', $reviews)) {
        return;
    }

    // All reviews are ACK or ACK_WITH_CONDITION, WO status becomes DONE
    $stmt = $pdo->prepare("UPDATE lsb_wo_header SET status = 'DONE', completed_at = NOW() WHERE id = ?");
    $stmt->execute([$woId]);
}

/**
 * Get WO review records - department based
 */
function wo_get_reviews($woId) {
    $pdo = wo_get_db();
    $stmt = $pdo->prepare("
        SELECT r.*, d.dept_name, d.sort_order
        FROM lsb_wo_review r
        LEFT JOIN lsb_wo_dept_config d ON r.reviewer_dept = d.dept_code
        WHERE r.wo_id = ?
        ORDER BY d.sort_order
    ");
    $stmt->execute([$woId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get WO file list
 */
function wo_get_files($woId) {
    $pdo = wo_get_db();
    $stmt = $pdo->prepare("
        SELECT * FROM lsb_wo_files
        WHERE wo_id = ? AND is_active = 1
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$woId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Upload file
 */
function wo_upload_file($woId, $file, $category = 'attachment', $uploadedBy = null) {
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
        ];
        return ['success' => false, 'message' => $errors[$file['error']] ?? 'Upload error'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large (max 10MB)'];
    }

    // Check file type
    global $ALLOWED_FILE_TYPES;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($ALLOWED_FILE_TYPES[$ext])) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }

    // Create upload directory
    $uploadDir = WO_UPLOAD_PATH . '/' . $woId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $filePath = $uploadDir . '/' . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }

    // Save to database
    $pdo = wo_get_db();
    $stmt = $pdo->prepare("
        INSERT INTO lsb_wo_files (wo_id, file_name, file_path, file_size, file_type, file_ext, file_category, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $woId,
        $file['name'],
        $filePath,
        $file['size'],
        $file['type'],
        $ext,
        $category,
        $uploadedBy
    ]);

    return [
        'success' => true,
        'message' => 'File uploaded successfully',
        'data' => ['id' => $pdo->lastInsertId(), 'file_name' => $file['name']]
    ];
}

/**
 * Delete file
 */
function wo_delete_file($fileId) {
    $pdo = wo_get_db();

    // Get file info
    $stmt = $pdo->prepare("SELECT * FROM lsb_wo_files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        return ['success' => false, 'message' => 'File not found'];
    }

    // Soft delete
    $stmt = $pdo->prepare("UPDATE lsb_wo_files SET is_active = 0 WHERE id = ?");
    $stmt->execute([$fileId]);

    return ['success' => true, 'message' => 'File deleted successfully'];
}

/**
 * Format amount
 */
function wo_format_amount($amount, $currency = 'CAD') {
    return '$' . number_format($amount, 2) . ' ' . $currency;
}
