<?php
$page_title = "Create Branch";
require_once 'components/inc/header.php';
require_perm('BRANCH-MGMT', 'can_add');

$errors  = [];
$success = null;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = posted(['name', 'is_main', 'address', 'phone', 'opened_on', 'status']);

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            $newId   = create_branch($old);
            $success = 'Branch created successfully. Branch code: ' . get_branch_info($newId)['code'];
            $old     = [];
        } catch (PDOException $ex) {
            error_log('create_branch: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}
$v = fn($key) => $old[$key] ?? '';
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title"><h2>Create a new Branch</h2></div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <form method="post" class="card card--pad" autocomplete="off">
            <?php echo csrf_field(); ?>
            <h3 class="card-title"><i class="fa-solid fa-building"></i> Branch Information</h3>

            <div class="form-grid">
                <?php
                f_input('name', 'Branch Name', $v('name'), ['maxlength' => 100, 'required' => true, 'placeholder' => 'Enter branch name']);
                f_select('is_main', 'Branch Type', ['1' => 'Main Branch (Head Office)', '0' => 'Sub Branch'], $v('is_main'), ['required' => true, 'placeholder' => '--- Select Type ---']);
                f_input('phone', 'Phone Number', $v('phone'), ['type' => 'tel', 'maxlength' => 11, 'pattern' => '[0-9]{11}', 'inputmode' => 'numeric', 'digits' => true, 'required' => true, 'hint' => '11 digits.']);
                f_input('address', 'Address', $v('address'), ['maxlength' => 100, 'required' => true]);
                f_input('opened_on', 'Opening Date', $v('opened_on') ?: date('Y-m-d'), ['type' => 'date', 'required' => true]);
                f_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $v('status') ?: 'active', ['required' => true]);
                ?>
            </div>
            <p class="hint" style="margin-top:12px">Three-digit branch code (101, 102, ...) is generated automatically.</p>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Branch</button>
            </div>
        </form>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
