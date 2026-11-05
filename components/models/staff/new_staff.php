<?php
$page_title = "New Staff";
require_once 'components/inc/header.php';
require_perm('STAFF-MGMT', 'can_add');

$errors  = [];
$success = null;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = posted(['user_id', 'branch_id', 'designation', 'join_date', 'base_salary', 'nid_no']);

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            create_staff($old);
            $success = 'Staff created successfully.';
            $old     = [];
        } catch (PDOException $ex) {
            error_log('create_staff: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

// POST er por load — jate notun staff hoye jaoa account list theke bad jay
$userOpts = [];
foreach (get_staff_available_users() as $u) {
    if (is_super() || (int) $u['branch_id'] === current_branch_id()) {
        $userOpts[$u['id']] = $u['full_name'] . ' (@' . $u['username'] . ' · ' . $u['role'] . ')';
    }
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
            <div class="page-title"><h2>Create New Staff</h2></div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <?php if (empty($userOpts)): ?>

            <div class="card empty">
                <i class="fa-solid fa-user-check"></i>
                <h3>No login account available</h3>
                <p>Shob login account already staff hishebe jukto ache. Age notun Login Account toiri korun.</p>
            </div>

        <?php else: ?>

            <form method="post" class="card card--pad" autocomplete="off">
                <?php echo csrf_field(); ?>
                <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Staff Info</h3>

                <div class="form-grid">
                    <?php
                    f_select('user_id', 'Login Account', $userOpts, $v('user_id'), ['required' => true, 'placeholder' => '--- Select Account ---', 'hint' => 'Je login account ta staff hobe.']);
                    f_select('branch_id', 'Branch', $branchOpts, $v('branch_id'), ['required' => true, 'placeholder' => '--- Select Branch ---', 'hint' => 'Kormorto branch.']);
                    f_input('designation', 'Designation', $v('designation'), ['maxlength' => 100, 'required' => true]);
                    f_input('join_date', 'Join Date', $v('join_date'), ['type' => 'date', 'required' => true]);
                    f_input('base_salary', 'Base Salary (৳)', $v('base_salary'), ['type' => 'number', 'min' => 0, 'step' => '0.01', 'inputmode' => 'decimal', 'placeholder' => '0.00', 'required' => true]);
                    f_input('nid_no', 'NID Number', $v('nid_no'), ['maxlength' => 17, 'pattern' => '[0-9]{10}|[0-9]{13}|[0-9]{17}', 'inputmode' => 'numeric', 'digits' => true, 'title' => '10, 13 or 17 digit NID number', 'required' => true, 'hint' => '10, 13 ba 17 digit.']);
                    ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Staff</button>
                </div>
            </form>

        <?php endif; ?>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
