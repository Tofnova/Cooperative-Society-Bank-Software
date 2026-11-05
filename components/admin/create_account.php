<?php
$page_title = "Create Account";
require_once 'components/inc/header.php';
require_perm('USER-MGMT', 'can_add');

$errors  = [];
$success = null;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old  = posted(['username', 'full_name', 'phone', 'role_id', 'branch_id', 'pin', 'status']);
    $data = $old + [
        'password'         => (string) ($_POST['password'] ?? ''),
        'confirm_password' => (string) ($_POST['confirm_password'] ?? ''),
    ];

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            create_user($data);
            $success = 'Account "' . strtolower($old['username']) . '" created successfully.';
            $old = [];
        } catch (PDOException $ex) {
            error_log('create_user: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

// Super admin na hole: nijer branch ar super_admin role bad
$roles    = array_filter(get_roles(), fn($r) => is_super() || $r['name'] !== 'super_admin');
$roleOpts = [];
foreach ($roles as $r) {
    $roleOpts[$r['id']] = $r['name'] . ' — ' . $r['description'];
}
$branchOpts = [];
foreach (get_branch_info() as $b) {
    if (is_super() || (int) $b['id'] === current_branch_id()) {
        $branchOpts[$b['id']] = $b['name'] . ' (' . $b['code'] . ')';
    }
}
$v = fn($key) => $old[$key] ?? '';
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title"><h2>Create Login Account</h2></div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <form method="post" class="card card--pad" autocomplete="off">
            <?php echo csrf_field(); ?>
            <h3 class="card-title"><i class="fa-solid fa-user-plus"></i> Account Information</h3>

            <div class="form-grid">
                <?php
                f_input('username', 'Username (Login ID)', $v('username'), ['maxlength' => 50, 'required' => true, 'autocomplete' => 'off', 'hint' => 'a-z, 0-9, _ and . only.']);
                f_input('full_name', 'Full Name', $v('full_name'), ['maxlength' => 100, 'required' => true]);
                f_input('phone', 'Phone Number', $v('phone'), ['type' => 'tel', 'maxlength' => 11, 'pattern' => '[0-9]{11}', 'inputmode' => 'numeric', 'digits' => true, 'required' => true, 'hint' => '11 digits.']);
                f_select('role_id', 'Role', $roleOpts, $v('role_id'), ['required' => true, 'placeholder' => '--- Select Role ---']);
                f_select('branch_id', 'Branch', $branchOpts, $v('branch_id'), is_super()
                    ? ['empty' => '— None (super admin only) —', 'hint' => 'Only the super_admin role can have no branch.']
                    : ['required' => true]);
                f_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $v('status') ?: 'active', ['required' => true]);
                f_input('password', 'Password', '', ['type' => 'password', 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password', 'required' => true, 'hint' => 'Minimum 8 characters.']);
                f_input('confirm_password', 'Confirm Password', '', ['type' => 'password', 'maxlength' => 72, 'autocomplete' => 'new-password', 'required' => true]);
                f_input('pin', 'Approval PIN (optional)', $v('pin'), ['type' => 'password', 'maxlength' => 6, 'pattern' => '[0-9]{4,6}', 'inputmode' => 'numeric', 'digits' => true, 'autocomplete' => 'new-password', 'hint' => '4 to 6 digits, for transaction/voucher approval.']);
                ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Account</button>
            </div>
        </form>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
