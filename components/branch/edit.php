<?php
$page_title = "Edit Branch";
require_once 'components/inc/header.php';
require_perm('BRANCH-MGMT', 'can_edit');

$branch  = get_branch_info((int) ($id ?? 0));
$errors  = [];
$success = null;
$old     = [];

if ($branch && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = posted(['name', 'is_main', 'address', 'phone', 'opened_on', 'status']);

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            update_branch((int) $branch['id'], $old);
            $success = 'Branch updated successfully.';
            $old     = [];
            $branch  = get_branch_info((int) $branch['id']);
        } catch (PDOException $ex) {
            error_log('update_branch: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}
$v = fn($key) => $old[$key] ?? ($branch[$key] ?? '');
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title">
                <h2>Edit Branch</h2>
                <?php if ($branch): ?><span class="badge">Code: <?php echo e($branch['code']); ?></span><?php endif; ?>
            </div>
            <div class="page-actions">
                <button onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Back</button>
            </div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <?php if (!$branch): ?>

            <div class="card empty">
                <i class="fa-solid fa-code-branch"></i>
                <h3>Branch not found</h3>
            </div>

        <?php else: ?>

            <form method="post" class="card card--pad" autocomplete="off">
                <?php echo csrf_field(); ?>
                <h3 class="card-title"><i class="fa-solid fa-building"></i> Branch Information</h3>

                <div class="form-grid">
                    <?php
                    f_input('code', 'Branch Code', $branch['code'], ['disabled' => true, 'id' => 'f_code_ro', 'hint' => 'Branch code cannot be changed.']);
                    f_input('name', 'Branch Name', $v('name'), ['maxlength' => 100, 'required' => true]);
                    f_select('is_main', 'Branch Type', ['1' => 'Main Branch (Head Office)', '0' => 'Sub Branch'], $v('is_main'), ['required' => true]);
                    f_input('phone', 'Phone Number', $v('phone'), ['type' => 'tel', 'maxlength' => 11, 'pattern' => '[0-9]{11}', 'inputmode' => 'numeric', 'digits' => true, 'required' => true, 'hint' => '11 digits.']);
                    f_input('address', 'Address', $v('address'), ['maxlength' => 100, 'required' => true]);
                    f_input('opened_on', 'Opening Date', $v('opened_on'), ['type' => 'date', 'required' => true]);
                    f_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $v('status'), ['required' => true]);
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
