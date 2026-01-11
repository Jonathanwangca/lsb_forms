<?php
/**
 * RFQ 列表页面
 * LSB RFQ System V3.1
 */
$pageTitle = 'RFQ List - Inava Steel Customer Portal';
require_once dirname(__DIR__) . '/includes/functions.php';

// 获取过滤参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 获取列表数据
$filters = [];
if ($status) $filters['status'] = $status;
if ($search) $filters['search'] = $search;

$result = getRfqList($page, 20, $filters);

require_once dirname(__DIR__) . '/includes/rfq_header.php';
$lang = getLang();
?>

<div class="card">
    <div class="card-body">

        <!-- 过滤器 -->
        <div class="toolbar">
            <form class="row g-3 align-items-center" method="get">
                <div class="col-auto">
                    <h5 class="mb-0 me-3"><i class="fas fa-list me-2"></i><?php echo $lang === 'en' ? 'RFQ List' : 'RFQ 列表'; ?></h5>
                </div>
                <div class="col-auto">
                    <input type="text" class="form-control" name="search"
                           placeholder="Search RFQ No./Project/Job..."
                           value="<?php echo h($search); ?>">
                </div>
                <div class="col-auto">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <?php foreach (getRefOptions('rfq_status') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo $status == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="/aiforms/rfq/list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
            <div class="d-flex align-items-center">
                <a href="/aiforms/rfq/form_rfq.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>" class="btn btn-primary btn-sm me-3">
                    <i class="fas fa-plus me-1"></i><?php echo $lang === 'en' ? 'New RFQ' : '新建 RFQ'; ?>
                </a>
                <span class="text-muted">Total: <?php echo $result['total']; ?> records</span>
            </div>
        </div>

        <!-- 列表表格 -->
        <div class="table-responsive">
            <table class="table table-hover table-rfq">
                <thead class="table-light">
                    <tr>
                        <th>RFQ No.</th>
                        <th>Job Number</th>
                        <th>Project Name</th>
                        <th>Location</th>
                        <th>Buildings</th>
                        <th>Dimensions (L×W)</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['list'])): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            No RFQ records found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($result['list'] as $row): ?>
                    <tr>
                        <td>
                            <a href="/aiforms/rfq/form_rfq.php?id=<?php echo $row['id']; ?>">
                                <strong><?php echo h($row['rfq_no']); ?></strong>
                            </a>
                        </td>
                        <td><?php echo h($row['job_number']); ?></td>
                        <td><?php echo h($row['project_name']); ?></td>
                        <td><?php echo h($row['project_location']); ?></td>
                        <td class="text-center"><?php echo h($row['building_qty']); ?></td>
                        <td>
                            <?php if ($row['length'] && $row['width']): ?>
                            <?php echo h($row['length']); ?>m × <?php echo h($row['width']); ?>m
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['due_date'] ? date('Y-m-d', strtotime($row['due_date'])) : '-'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo h($row['status']); ?>">
                                <?php echo h(getRefValue('rfq_status', $row['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/aiforms/rfq/form_rfq.php?id=<?php echo $row['id']; ?>"
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="/aiforms/rfq/view.php?id=<?php echo $row['id']; ?>"
                                   class="btn btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-success dropdown-toggle"
                                        data-bs-toggle="dropdown" title="Export">
                                    <i class="bi bi-download"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#"
                                           onclick="RFQ.saveJsonFile(<?php echo $row['id']; ?>)">
                                            <i class="bi bi-filetype-json"></i> Export JSON
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#"
                                           onclick="RFQ.printPdf(<?php echo $row['id']; ?>, 'letter')">
                                            <i class="bi bi-file-pdf"></i> PDF (Letter)
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#"
                                           onclick="RFQ.printPdf(<?php echo $row['id']; ?>, 'a4')">
                                            <i class="bi bi-file-pdf"></i> PDF (A4)
                                        </a>
                                    </li>
                                </ul>
                                <button type="button" class="btn btn-outline-danger" title="Delete"
                                        onclick="RFQ.deleteRfq(<?php echo $row['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <?php if ($result['totalPages'] > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo h($status); ?>&search=<?php echo h($search); ?>">
                        Previous
                    </a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($result['totalPages'], $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo h($status); ?>&search=<?php echo h($search); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $result['totalPages'] ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo h($status); ?>&search=<?php echo h($search); ?>">
                        Next
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/rfq_footer.php'; ?>
