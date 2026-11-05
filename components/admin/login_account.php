<?php
$page_title = "Login Account";
require_once 'components/inc/header.php';

$user = get_user_info(current_user_id());

// ===============================
// Change Password
// ===============================
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    if (!csrf_ok()) {
        $result = ['success' => false, 'message' => 'Session expired. Please reload the page and try again.'];
    } else {
        $result = changePassword(
            (int) $user['id'],
            (string) ($_POST['old_password'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['confirm_password'] ?? '')
        );
    }

    $toast = $result['success'] ? 'success' : 'error';
    $msg   = json_encode($result['message'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "<script>window.addEventListener('load', function () { toastr.{$toast}({$msg}); });</script>";
}
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title"><h2>Login Account</h2></div>
        </div>

        <?php require 'components/admin/info_user_account.php'; ?>

        <form method="post" class="card card--pad mt noPrint" autocomplete="off">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="change_password" value="1">

            <h3 class="card-title"><i class="fa-solid fa-key"></i> Password Update</h3>

            <div class="form-grid">
                <?php
                f_input('old_password', 'Old Password', '', ['type' => 'password', 'autocomplete' => 'current-password', 'required' => true]);
                f_input('new_password', 'New Password', '', ['type' => 'password', 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password', 'required' => true, 'hint' => 'Minimum 8 characters.']);
                f_input('confirm_password', 'Confirm Password', '', ['type' => 'password', 'maxlength' => 72, 'autocomplete' => 'new-password', 'required' => true]);
                ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-lock"></i> Change Password</button>
            </div>
        </form>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
