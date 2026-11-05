<?php
// Parent (view_user_account.php) theke $user, $id paoa jay.
$userId  = (int) $user['id'];
$isSelf  = $userId === current_user_id();
$errors  = [];
$success = null;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = trim((string) ($_POST['form'] ?? ''));

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            if ($form === 'profile') {
                $old = posted(['full_name', 'phone']);
                update_user_info($userId, $old);
                $success = 'Profile information updated.';
            } elseif ($form === 'settings') {
                $old = array_intersect_key(posted(['role_id', 'branch_id', 'status']), $_POST);
                update_user_info($userId, $old);
                $success = 'Role, branch and status updated.';
            } elseif ($form === 'password') {
                update_user_password($userId, password_hash(passwordInput($errors), PASSWORD_DEFAULT));
                $success = 'Password changed.';
            } else {
                $errors[] = 'Invalid request.';
            }
            if ($success) {
                $old  = [];
                $user = get_user_info($userId);
            }
        } catch (PDOException $ex) {
            error_log('update_user_account: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

// Password form: duita ghor mile ar 8 akkhor hole hash er jonno ney
function passwordInput(array &$errors): string
{
    $pw  = (string) ($_POST['new_password'] ?? '');
    $pw2 = (string) ($_POST['confirm_password'] ?? '');
    need(strlen($pw) >= 8 && strlen($pw) <= 72, 'Password must be 8 to 72 characters.');
    need($pw === $pw2, 'Passwords do not match.');
    return $pw;
}

$roleOpts = [];
foreach (get_roles() as $r) {
    if (is_super() || $r['name'] !== 'super_admin') {
        $roleOpts[$r['id']] = $r['name'] . ' — ' . $r['description'];
    }
}
$branchOpts = [];
foreach (get_branch_info() as $b) {
    if (is_super() || (int) $b['id'] === current_branch_id()) {
        $branchOpts[$b['id']] = $b['name'] . ' (' . $b['code'] . ')';
    }
}
$v = fn($key) => $old[$key] ?? ($user[$key] ?? '');
?>

<?php render_alerts($success, $errors); ?>

<div class="stack">

    <!-- Profile -->
    <form method="post" class="card card--pad" autocomplete="off">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form" value="profile">
        <h3 class="card-title"><i class="fa-solid fa-user-pen"></i> Profile Information</h3>

        <div class="form-grid">
            <?php
            f_input('username', 'Username', $user['username'], ['disabled' => true, 'id' => 'f_username_ro', 'hint' => 'Username cannot be changed.']);
            f_input('full_name', 'Full Name', $v('full_name'), ['maxlength' => 100, 'required' => true]);
            f_input('phone', 'Phone Number', $v('phone'), ['type' => 'tel', 'maxlength' => 11, 'pattern' => '[0-9]{11}', 'inputmode' => 'numeric', 'digits' => true, 'required' => true]);
            ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
    </form>

    <!-- Role + Branch + Status -->
    <form method="post" class="card card--pad">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form" value="settings">
        <h3 class="card-title"><i class="fa-solid fa-user-shield"></i> Role, Branch &amp; Status</h3>

        <div class="form-grid">
            <?php
            $lock = $isSelf ? ['disabled' => true] : [];
            f_select('role_id', 'Role', $roleOpts, $v('role_id'), ['required' => true] + $lock);
            f_select('branch_id', 'Branch', $branchOpts, $v('branch_id'), (is_super() ? ['empty' => '— None (super admin only) —'] : ['required' => true]) + $lock);
            f_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive', 'locked' => 'Locked'], $v('status'),
                ['required' => true, 'hint' => 'Inactive / Locked account login korte parbe na.'] + $lock);
            ?>
        </div>
        <?php if ($isSelf): ?>
            <p class="hint" style="margin-top:12px">You cannot change your own role, branch or status.</p>
        <?php else: ?>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button>
            </div>
        <?php endif; ?>
    </form>

    <!-- Password reset -->
    <form method="post" class="card card--pad" autocomplete="off">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form" value="password">
        <h3 class="card-title"><i class="fa-solid fa-key"></i> Reset Password</h3>

        <div class="form-grid">
            <?php
            f_input('new_password', 'New Password', '', ['type' => 'password', 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password', 'required' => true, 'hint' => 'Minimum 8 characters.']);
            f_input('confirm_password', 'Confirm Password', '', ['type' => 'password', 'maxlength' => 72, 'autocomplete' => 'new-password', 'required' => true]);
            ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-lock"></i> Update Password</button>
        </div>
    </form>

</div>
