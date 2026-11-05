<?php
$page_title = "Center List";
require_once 'components/inc/header.php';
require_perm('CENTER-MGMT', 'can_view');

$centers = get_center_info();
if (!is_super()) {
    $centers = array_values(array_filter($centers, fn($c) => (int) $c['branch_id'] === current_branch_id()));
}
?>
<section class="page">

    <div class="page-head noPrint">
        <div class="page-title">
            <h2>Center List - কেন্দ্রের তালিকা</h2>
            <span class="badge"><?php echo count($centers); ?> total</span>
        </div>

        <div class="page-actions">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search by center, area, officer...">
            </div>
            <?php if (user_can('CENTER-MGMT', 'can_add')): ?>
                <button class="btn-primary" onclick="call('/create_center')">
                    <i class="fa-solid fa-plus"></i> Create New Center
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($centers)): ?>
        <div class="card empty">
            <i class="fa-solid fa-route"></i>
            <h3>No center found</h3>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr>
                            <th>SL &amp; ID</th>
                            <th>Center</th>
                            <th>Branch &amp; Area</th>
                            <th>Meeting</th>
                            <th>Field Officer</th>
                            <th>Members</th>
                            <th>Status</th>
                            <th class="noPrint">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($centers as $key => $c): ?>
                        <tr>
                            <td data-label="SL &amp; ID"><?php echo $key + 1; ?> <span class="user-sub">(<?php echo e($c['id']); ?>)</span></td>
                            <td data-label="Center">
                                <div class="user-name"><?php echo e($c['name']); ?></div>
                                <div class="user-sub">Code: <?php echo e($c['code']); ?></div>
                            </td>
                            <td data-label="Branch &amp; Area">
                                <div class="kv kv--row"><span>Branch</span><b><?php echo e($c['branch_name']); ?></b></div>
                                <div class="kv kv--row"><span>Area</span><b><?php echo e($c['area_name']); ?> (<?php echo e($c['area_code']); ?>)</b></div>
                            </td>
                            <td data-label="Meeting">
                                <div class="kv kv--row"><span>Day</span><b><?php echo e($c['meeting_day']); ?></b></div>
                                <div class="kv kv--row"><span>Time</span><b><?php echo e($c['meeting_time']); ?></b></div>
                            </td>
                            <td data-label="Field Officer"><?php echo $c['officer_name'] ? e($c['officer_name']) : '—'; ?></td>
                            <td data-label="Members"><?php echo (int) $c['member_count']; ?></td>
                            <td data-label="Status"><?php echo status_badge($c['status']); ?></td>
                            <td data-label="Action" class="noPrint">
                                <?php if (user_can('CENTER-MGMT', 'can_edit')): ?>
                                    <a class="btn" href="/edit_center/<?php echo (int) $c['id']; ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
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
