<?php
$page_title = "Edit Area";
require_once 'components/inc/header.php';
require_perm('AREA-MGMT', 'can_edit');

$area = get_area_info((int) ($id ?? 0));
if ($area && !is_super() && (int) $area['branch_id'] !== current_branch_id()) {
    $area = false;
}
$errors  = [];
$success = null;
$old     = [];

if ($area && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = posted(['branch_id', 'name']);
    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            update_area((int) $area['id'], $old);
            $success = 'Area updated successfully.';
            $old     = [];
            $area    = get_area_info((int) $area['id']);
        } catch (PDOException $ex) {
            error_log('update_area: ' . $ex->getMessage());
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
$v = fn($key) => $old[$key] ?? ($area[$key] ?? '');
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title">
                <h2>Edit Area</h2>
                <?php if ($area): ?><span class="badge">Code: <?php echo e($area['code']); ?></span><?php endif; ?>
            </div>
            <div class="page-actions"><button onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Back</button></div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <?php if (!$area): ?>
            <div class="card empty"><i class="fa-solid fa-map-location-dot"></i><h3>Area not found</h3></div>
        <?php else: ?>
            <form method="post" class="card card--pad" autocomplete="off">
                <?php echo csrf_field(); ?>
                <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Area Info</h3>
                <div class="form-grid">
                    <?php
                    f_input('code', 'Area Code', $area['code'], ['disabled' => true, 'id' => 'f_code_ro', 'hint' => 'Area code cannot be changed.']);
                    f_select('branch_id', 'Branch', $branchOpts, $v('branch_id'), ['required' => true, 'hint' => 'Centers thakle branch bodlano jabe na.']);
                    f_input('name', 'Area Name', $v('name'), ['maxlength' => 100, 'required' => true]);
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
