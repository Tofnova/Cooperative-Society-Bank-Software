<?php
$page_title = "Branch List";
require_once 'components/inc/header.php';
require_perm('BRANCH-MGMT', 'can_view');

$dataScope = db_val("SELECT data_scope FROM roles WHERE name = ?", [$_SESSION['role'] ?? '']);
if ($dataScope === 'all_branches') {
    $branches = get_branch_info();
} else {
    $branchId = (int) ($_SESSION['branch_id'] ?? 0);
    $branch = $branchId > 0 ? get_branch_info($branchId) : null;
    $branches = $branch ? [$branch] : [];
}
?>
<section class="page">

    <div class="page-head noPrint">
        <div class="page-title">
            <h2>Branch List</h2>
            <span class="badge"><?php echo count($branches); ?> total</span>
        </div>

        <div class="page-actions">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" onkeyup="searchTable()"
                    placeholder="Search by name, code, phone...">
            </div>
            <?php if (user_can('BRANCH-MGMT', 'can_add')): ?>
            <button class="btn-primary" onclick="call('/create_branch')">
                <i class="fa-solid fa-plus"></i> Create New Branch
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($branches)): ?>
    <div class="card empty">
        <i class="fa-solid fa-code-branch"></i>
        <h3>No branch found</h3>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="overflow">
            <table class="table--list">
                <thead>
                    <tr>
                        <th>SL &amp; ID</th>
                        <th>Branch</th>
                        <th>Type &amp; Status</th>
                        <th>Contact</th>
                        <th>People</th>
                        <th class="noPrint">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branches as $key => $b): ?>
                    <tr>
                        <td data-label="SL &amp; ID"><?php echo $key + 1; ?> <span
                                class="user-sub">(<?php echo e($b['id']); ?>)</span></td>
                        <td data-label="Branch">
                            <div class="user-name"><?php echo e($b['name']); ?></div>
                            <div class="user-sub">Code: <?php echo e($b['code']); ?></div>
                            <div class="flex medel">
                                <b><span class="badge <?php echo $b['is_main'] ? '' : 'badge--gray'; ?>"><?php echo $b['is_main'] ? 'Main' : 'Sub'; ?></span></b>
                                <b><?php echo status_badge($b['status']); ?></b>
                            </div>
                        </td>
                        <td data-label="Type &amp; Status">
                            <div class="kv kv--row">
                                <span>Opened</span><b><?php echo e(formatDay($b['opened_on'])); ?></b>
                            </div>
                        </td>
                        <td data-label="Contact">
                            <div class="kv kv--row"><span><i class="fa-solid fa-phone"></i>
                                    Phone</span><b><?php echo e($b['phone']); ?></b></div>
                            <div class="kv kv--row"><span><i class="fa-solid fa-location-dot"></i>
                                    Address</span><b><?php echo e($b['address']); ?></b></div>
                        </td>
                        <td data-label="People">
                            <div class="kv kv--row"><span>Accounts</span><b><?php echo (int) $b['user_count']; ?></b>
                            </div>
                            <div class="kv kv--row"><span>Staff</span><b><?php echo (int) $b['staff_count']; ?></b>
                            </div>
                        </td>
                        <td data-label="Action" class="noPrint">
                            <?php if (user_can('BRANCH-MGMT', 'can_edit')): ?>
                            <a class="btn" href="/edit_branch/<?php echo (int) $b['id']; ?>">
                                <i class="fa-regular fa-pen-to-square"></i> Edit
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</section>
<?php require_once 'components/inc/footer.php'; ?>