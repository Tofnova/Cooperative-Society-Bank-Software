<?php
$page_title = "Edit Center";
require_once 'components/inc/header.php';
require_perm('CENTER-MGMT', 'can_edit');

$center = get_center_info((int) ($id ?? 0));
if ($center && !is_super() && (int) $center['branch_id'] !== current_branch_id()) {
    $center = false;
}
$errors  = [];
$success = null;
$old     = [];

if ($center && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = posted(['name', 'meeting_day', 'meeting_time', 'field_officer_id', 'status']);
    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            update_center((int) $center['id'], $old);
            $success = 'Center updated successfully.';
            $old     = [];
            $center  = get_center_info((int) $center['id']);
        } catch (PDOException $ex) {
            error_log('update_center: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$officerOpts = [];
if ($center) {
    foreach (get_active_staff() as $s) {
        if ((int) $s['branch_id'] === (int) $center['branch_id']) {
            $officerOpts[$s['id']] = $s['full_name'] . ' — ' . $s['designation'];
        }
    }
}
$days = array_combine(MEETING_DAYS, MEETING_DAYS);
$v    = fn($key) => $old[$key] ?? ($center[$key] ?? '');
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title">
                <h2>Edit Center</h2>
                <?php if ($center): ?><span class="badge">Code: <?php echo e($center['code']); ?></span><?php endif; ?>
            </div>
            <div class="page-actions"><button onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Back</button></div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <?php if (!$center): ?>
            <div class="card empty"><i class="fa-solid fa-route"></i><h3>Center not found</h3></div>
        <?php else: ?>
            <form method="post" class="card card--pad" autocomplete="off">
                <?php echo csrf_field(); ?>
                <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Center Info</h3>
                <div class="form-grid">
                    <?php
                    f_input('branch_ro', 'Branch', $center['branch_name'] . ' (' . $center['branch_code'] . ')', ['disabled' => true, 'hint' => 'Branch o Area bodlano jabe na.']);
                    f_input('area_ro', 'Area', $center['area_name'] . ' (' . $center['area_code'] . ')', ['disabled' => true]);
                    f_input('name', 'Center Name', $v('name'), ['maxlength' => 100, 'required' => true]);
                    f_select('meeting_day', 'Meeting Day', $days, $v('meeting_day'), ['required' => true]);
                    f_input('meeting_time', 'Meeting Time', $v('meeting_time'), ['type' => 'time', 'required' => true]);
                    f_select('field_officer_id', 'Field Officer', $officerOpts, $v('field_officer_id'), ['empty' => '— None —']);
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
