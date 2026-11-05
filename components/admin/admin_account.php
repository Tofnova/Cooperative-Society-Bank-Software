<?php
$page_title = "Admin Account List";
require_once 'components/inc/header.php';
require_perm('USER-MGMT', 'can_view');

$users = get_user_info();
if (!is_super()) {
    $users = array_values(array_filter($users, fn($u) => (int) $u['branch_id'] === current_branch_id()));
}
?>
<section class="page">

    <div class="page-head noPrint">
        <div class="page-title">
            <h2>Admin Accounts</h2>
            <span class="badge"><?php echo count($users); ?> total</span>
        </div>

        <div class="page-actions">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search by name, username, phone...">
            </div>
            <?php if (user_can('USER-MGMT', 'can_add')): ?>
                <button class="btn-primary" onclick="call('/create_account')">
                    <i class="fa-solid fa-plus"></i> Create Login Account
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="overflow">
            <table class="table--list">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Role &amp; Branch</th>
                        <th>Contact</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th class="noPrint">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td data-label="#"><?php echo e($u['id']); ?></td>
                        <td data-label="User">
                            <div class="user">
                                <div class="avatar avatar--fallback noPrint"><?php echo e(mb_strtoupper(mb_substr($u['full_name'], 0, 1))); ?></div>
                                <div>
                                    <div class="user-name"><?php echo e($u['full_name']); ?></div>
                                    <div class="user-sub">@<?php echo e($u['username']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Role &amp; Branch">
                            <div class="kv kv--row"><span>Role</span><b><span class="badge"><?php echo e($u['role']); ?></span></b></div>
                            <div class="kv kv--row"><span>Branch</span><b><?php echo $u['branch_name'] ? e($u['branch_name']) . ' (' . e($u['branch_code']) . ')' : '—'; ?></b></div>
                        </td>
                        <td data-label="Contact"><?php echo e($u['phone']); ?></td>
                        <td data-label="Last Login"><?php echo e(formatDate($u['last_login_at'])); ?></td>
                        <td data-label="Status"><?php echo status_badge($u['status']); ?></td>
                        <td data-label="Action" class="noPrint">
                            <button onclick="call('/account/user/<?php echo (int) $u['id']; ?>')">
                                <i class="fa-regular fa-eye"></i> Account Info
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</section>
<?php require_once 'components/inc/footer.php'; ?>
