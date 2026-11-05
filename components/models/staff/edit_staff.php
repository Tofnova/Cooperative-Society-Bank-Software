<?php
$page_title = "Edit Staff";
require_once 'components/inc/header.php';
require_perm('STAFF-MGMT', 'can_edit');

$staff = get_staff_info((int) ($id ?? 0));
if ($staff && !is_super() && (int) $staff['branch_id'] !== current_branch_id()) {
    $staff = false;
}
$errors  = [];
$success = null;
$old     = [];

if ($staff && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = posted(['branch_id', 'designation', 'join_date', 'base_salary', 'nid_no', 'status']);

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            update_staff((int) $staff['id'], $old);
            $success = 'Staff updated successfully.';
            $old     = [];
            $staff   = get_staff_info((int) $staff['id']);
        } catch (PDOException $ex) {
            error_log('update_staff: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$branchOpts = [];
foreach (get_branch_info() as $b) {
    if (is_super() || (int) $b['id'] === current_branch_id()) {
        $branchOpts[$b['id']] = $b['name'] . ' (' . $b['code'] . ')';
    }
}
$v = fn($key) => $old[$key] ?? ($staff[$key] ?? '');
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title">
                <h2>Edit Staff</h2>
                <?php if ($staff): ?><span class="badge"><?php echo e($staff['full_name']); ?></span><?php endif; ?>
            </div>
            <div class="page-actions">
                <button onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Back</button>
            </div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <?php if (!$staff): ?>

            <div class="card empty">
                <i class="fa-solid fa-user-slash"></i>
                <h3>Staff not found</h3>
            </div>

        <?php else: ?>

            <form method="post" class="card card--pad" autocomplete="off">
                <?php echo csrf_field(); ?>
                <h3 class="card-title"><i class="fa-solid fa-user-tie"></i> Staff Info</h3>

                <div class="form-grid">
                    <?php
                    f_input('account', 'Login Account', $staff['full_name'] . ' (@' . $staff['username'] . ')', ['disabled' => true, 'id' => 'f_account_ro']);
                    f_select('branch_id', 'Branch', $branchOpts, $v('branch_id'), ['required' => true]);
                    f_input('designation', 'Designation', $v('designation'), ['maxlength' => 100, 'required' => true]);
                    f_input('join_date', 'Join Date', $v('join_date'), ['type' => 'date', 'required' => true]);
                    f_input('base_salary', 'Base Salary (৳)', $v('base_salary'), ['type' => 'number', 'min' => 0, 'step' => '0.01', 'inputmode' => 'decimal', 'required' => true]);
                    f_input('nid_no', 'NID Number', $v('nid_no'), ['maxlength' => 17, 'pattern' => '[0-9]{10}|[0-9]{13}|[0-9]{17}', 'inputmode' => 'numeric', 'digits' => true, 'required' => true, 'hint' => '10, 13 ba 17 digit.']);
                    f_select('status', 'Status', ['active' => 'Active', 'resigned' => 'Resigned'], $v('status'), ['required' => true]);
                    ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                </div>
            </form>

        <?php endif; ?>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
