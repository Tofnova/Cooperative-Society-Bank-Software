<?php
$page_title = "Staff List";
require_once 'components/inc/header.php';
require_perm('STAFF-MGMT', 'can_view');

$all_staff = get_staff_info();
if (!is_super()) {
    $all_staff = array_values(array_filter($all_staff, fn($s) => (int) $s['branch_id'] === current_branch_id()));
}
?>
<section class="page">

    <div class="page-head noPrint">
        <div class="page-title">
            <h2>Staff List</h2>
            <span class="badge"><?php echo count($all_staff); ?> total</span>
        </div>

        <div class="page-actions">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search by name, designation, NID...">
            </div>
            <?php if (user_can('STAFF-MGMT', 'can_add')): ?>
                <button class="btn-primary" onclick="call('/create_staff')">
                    <i class="fa-solid fa-plus"></i> Create New Staff
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($all_staff)): ?>
        <div class="card empty">
            <i class="fa-solid fa-users"></i>
            <h3>No staff found</h3>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr>
                            <th>SL &amp; ID</th>
                            <th>Staff</th>
                            <th>Branch</th>
                            <th>Job</th>
                            <th>Salary &amp; ID</th>
                            <th>Status</th>
                            <th class="noPrint">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($all_staff as $key => $s): ?>
                        <tr>
                            <td data-label="SL &amp; ID"><?php echo $key + 1; ?> <span class="user-sub">(<?php echo e($s['id']); ?>)</span></td>
                            <td data-label="Staff">
                                <div class="user">
                                    <div class="avatar avatar--fallback noPrint"><?php echo e(mb_strtoupper(mb_substr((string) $s['full_name'], 0, 1))); ?></div>
                                    <div>
                                        <div class="user-name"><?php echo e($s['full_name']); ?></div>
                                        <div class="user-sub">@<?php echo e($s['username']); ?> · <?php echo e($s['role']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Branch">
                                <div class="user-name"><?php echo e($s['branch_name']); ?></div>
                                <div class="user-sub">Code: <?php echo e($s['branch_code']); ?></div>
                            </td>
                            <td data-label="Job">
                                <div class="kv kv--row"><span>Designation</span><b><?php echo e($s['designation']); ?></b></div>
                                <div class="kv kv--row"><span>Joined</span><b><?php echo e(formatDay($s['join_date'])); ?></b></div>
                            </td>
                            <td data-label="Salary &amp; ID">
                                <div class="kv kv--row"><span>Base Salary</span><b><?php echo e(money($s['base_salary'])); ?></b></div>
                                <div class="kv kv--row"><span>NID</span><b><?php echo e($s['nid_no']); ?></b></div>
                            </td>
                            <td data-label="Status"><?php echo status_badge($s['status']); ?></td>
                            <td data-label="Action" class="noPrint">
                                <?php if (user_can('STAFF-MGMT', 'can_edit')): ?>
                                    <a class="btn" href="/edit_staff/<?php echo (int) $s['id']; ?>">
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
