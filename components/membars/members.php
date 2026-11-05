<?php
$page_title = "All Member List";
require_once 'components/inc/header.php';
require_perm('M-MGMT', 'can_view');

$errors  = [];
$success = null;

// Approve (pending -> active)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve') {
    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } elseif (!user_can('M-MGMT', 'can_approve')) {
        $errors[] = 'You do not have permission to approve members.';
    } else {
        try {
            approve_member((int) ($_POST['member_id'] ?? 0));
            $success = 'Member approved successfully.';
        } catch (PDOException $ex) {
            error_log('approve_member: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$filters = [
    'q'         => trim((string) ($_GET['q'] ?? '')),
    'status'    => trim((string) ($_GET['status'] ?? '')),
    'branch_id' => trim((string) ($_GET['branch_id'] ?? '')),
];
$result = get_members($filters, (int) ($_GET['page'] ?? 1));
$rows   = $result['rows'];

$branchOpts = [];
foreach (get_branch_info() as $b) {
    $branchOpts[$b['id']] = $b['name'] . ' (' . $b['code'] . ')';
}
$qs = function (int $page) use ($filters) {
    return '?' . http_build_query(array_filter($filters, fn($v) => $v !== '') + ['page' => $page]);
};
?>
<section class="page">

    <div class="page-head noPrint">
        <div class="page-title">
            <h2>All Member List</h2>
            <span class="badge"><?php echo (int) $result['total']; ?> total</span>
        </div>
        <div class="page-actions">
            <?php if (user_can('M-MGMT', 'can_add')): ?>
                <button class="btn-primary" onclick="call('/new_member')"><i class="fa-solid fa-user-plus"></i> New Member</button>
            <?php endif; ?>
        </div>
    </div>

    <?php render_alerts($success, $errors); ?>

    <form method="get" class="card card--pad noPrint mt" style="margin-top:0;margin-bottom:18px">
        <div class="form-grid" style="margin-top:0">
            <?php
            f_input('q', 'Search', $filters['q'], ['placeholder' => 'Name, member no, code, phone or NID', 'maxlength' => 100]);
            f_select('status', 'Status', ['pending' => 'Pending', 'active' => 'Active', 'closed' => 'Closed'], $filters['status'], ['empty' => 'All']);
            f_select('branch_id', 'Home Branch', $branchOpts, $filters['branch_id'], ['empty' => 'All branches']);
            ?>
        </div>
        <div class="form-actions">
            <a class="btn" href="/members">Reset</a>
            <button type="submit" class="btn-primary" style="margin-left:10px"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
        </div>
    </form>

    <?php if (empty($rows)): ?>
        <div class="card empty">
            <i class="fa-solid fa-users"></i>
            <h3>No member found</h3>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Branch &amp; Center</th>
                            <th>Contact</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th class="noPrint">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $m): ?>
                        <tr>
                            <td data-label="Member">
                                <div class="user">
                                    <?php if (!empty($m['photo_path'])): ?>
                                        <img src="<?php echo e($m['photo_path']); ?>" class="avatar noPrint" alt="" loading="lazy">
                                    <?php else: ?>
                                        <div class="avatar avatar--fallback noPrint"><?php echo e(mb_strtoupper(mb_substr((string) $m['full_name'], 0, 1))); ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="user-name"><?php echo e($m['full_name']); ?></div>
                                        <div class="user-sub">Member No: <?php echo e($m['member_no']); ?></div>
                                        <div class="user-sub">Code: <?php echo e($m['member_code']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Branch &amp; Center">
                                <div class="kv kv--row"><span>Branch</span><b><?php echo e($m['branch_name']); ?></b></div>
                                <div class="kv kv--row"><span>Center</span><b><?php echo $m['center_name'] ? e($m['center_name']) . ' (' . e($m['center_code']) . ')' : '—'; ?></b></div>
                            </td>
                            <td data-label="Contact">
                                <div class="kv kv--row"><span>Phone</span><b><?php echo e($m['phone']); ?></b></div>
                                <div class="kv kv--row"><span>NID</span><b><?php echo e($m['nid_no']); ?></b></div>
                            </td>
                            <td data-label="Joined"><?php echo e(formatDay($m['joined_on'])); ?></td>
                            <td data-label="Status"><?php echo status_badge($m['status']); ?></td>
                            <td data-label="Action" class="noPrint flex">
                                <button onclick="call('members/profile/<?php echo e($m['id']) ?>')" class="btn">
                                    Profile
                                </button>
                                <?php if ($m['status'] === 'pending' && user_can('M-MGMT', 'can_approve') && (is_super() || (int) $m['home_branch_id'] === current_branch_id())): ?>
                                    <form method="post" onsubmit="return confirm('Approve this member?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="member_id" value="<?php echo (int) $m['id']; ?>">
                                        <button type="submit" class="btn-primary"><i class="fa-solid fa-circle-check"></i> Approve</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="flex medel beet mt noPrint">
                <span class="hint">Page <?php echo (int) $result['page']; ?> of <?php echo (int) $result['pages']; ?></span>
                <div class="flex gap">
                    <?php if ($result['page'] > 1): ?><a class="btn" href="<?php echo e($qs($result['page'] - 1)); ?>"><i class="fa-solid fa-arrow-left"></i> Previous</a><?php endif; ?>
                    <?php if ($result['page'] < $result['pages']): ?><a class="btn" href="<?php echo e($qs($result['page'] + 1)); ?>">Next <i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</section>
<?php require_once 'components/inc/footer.php'; ?>
