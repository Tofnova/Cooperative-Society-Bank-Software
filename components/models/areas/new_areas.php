<?php
$page_title = "New Area";
require_once 'components/inc/header.php';
require_perm('AREA-MGMT', 'can_add');

$errors  = [];
$success = null;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = posted(['branch_id', 'name']);
    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            $code    = create_area($old);
            $success = 'Area created successfully. Area code: ' . $code;
            $old     = [];
        } catch (PDOException $ex) {
            error_log('create_area: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$branchOpts = [];
foreach (get_branch_info() as $b) {
    if ($b['status'] === 'active' && (is_super() || (int) $b['id'] === current_branch_id())) {
        $branchOpts[$b['id']] = $b['name'] . ' (' . $b['code'] . ')';
    }
}
$v = fn($key) => $old[$key] ?? '';
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title"><h2>Create New Area - নতুন এলাকা</h2></div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <?php if (empty($branchOpts)): ?>
            <div class="card empty">
                <i class="fa-solid fa-code-branch"></i>
                <h3>No branch found</h3>
                <p>এলাকা বানানোর আগে অন্তত একটা সক্রিয় Branch লাগবে।</p>
            </div>
        <?php else: ?>
            <form method="post" class="card card--pad" autocomplete="off">
                <?php echo csrf_field(); ?>
                <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Required Info</h3>

                <div class="form-grid">
                    <?php
                    f_select('branch_id', 'Branch', $branchOpts, $v('branch_id'), ['required' => true, 'placeholder' => '--- Select Branch ---', 'hint' => 'Je branch er under e area hobe.']);
                    f_input('name', 'Area Name', $v('name'), ['maxlength' => 100, 'required' => true, 'placeholder' => 'Area name']);
                    ?>
                </div>
                <p class="hint" style="margin-top:12px">Area code (ARE-xx) is generated automatically.</p>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Area</button>
                </div>
            </form>
        <?php endif; ?>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
