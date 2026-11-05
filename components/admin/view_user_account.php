<?php
$page_title = 'Account Information';
require_once 'components/inc/header.php';
require_perm('USER-MGMT', 'can_view');

$user = get_user_info((int) ($id ?? 0));
// Non-super shudhu nijer branch er user dekhte parbe
if ($user && !is_super() && (int) $user['branch_id'] !== current_branch_id()) {
    $user = false;
}

$tabs = ['info' => 'Info and Details'];
if (user_can('USER-MGMT', 'can_edit')) {
    $tabs['settings'] = 'Update and Settings';
}
$tab = $_GET['tab'] ?? 'info';
if (!isset($tabs[$tab])) {
    $tab = 'info';
}
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title"><h2>Account Information</h2></div>
            <div class="page-actions noPrint">
                <button onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Back</button>
                <?php if ($user && $tab === 'info'): ?>
                    <button class="btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$user): ?>

            <div class="card empty">
                <i class="fa-solid fa-user-slash"></i>
                <h3>Account not found</h3>
            </div>

        <?php else: ?>

            <?php if (count($tabs) > 1): ?>
                <div class="tabs noPrint">
                    <?php foreach ($tabs as $key => $label): ?>
                        <button type="button" class="tab <?php echo $tab === $key ? 'active' : ''; ?>" onclick="tab('<?php echo e($key); ?>')">
                            <?php echo e($label); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            require $tab === 'info' ? 'components/admin/info_user_account.php' : 'components/admin/update_user_account.php';
            ?>

        <?php endif; ?>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
