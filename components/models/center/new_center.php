<?php
$page_title = "New Center";
require_once 'components/inc/header.php';
require_perm('CENTER-MGMT', 'can_add');

$errors  = [];
$success = null;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = posted(['branch_id', 'area_id', 'name', 'meeting_day', 'meeting_time', 'field_officer_id', 'status']);
    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            $code    = create_center($old);
            $success = 'Center created successfully. Center code: ' . $code;
            $old     = [];
        } catch (PDOException $ex) {
            error_log('create_center: ' . $ex->getMessage());
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
$areaOpts = [];
foreach (get_area_info() as $a) {
    if (isset($branchOpts[$a['branch_id']])) {
        $areaOpts[$a['id']] = ['label' => $a['name'] . ' (' . $a['code'] . ')', 'data' => ['branch' => $a['branch_id']]];
    }
}
$officerOpts = [];
foreach (get_active_staff() as $s) {
    if (isset($branchOpts[$s['branch_id']])) {
        $officerOpts[$s['id']] = ['label' => $s['full_name'] . ' — ' . $s['designation'], 'data' => ['branch' => $s['branch_id']]];
    }
}
$days = array_combine(MEETING_DAYS, MEETING_DAYS);
$v    = fn($key) => $old[$key] ?? '';
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title"><h2>Create New Center - নতুন কেন্দ্র</h2></div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <?php if (empty($branchOpts) || empty($areaOpts)): ?>
            <div class="card empty">
                <i class="fa-solid fa-map-location-dot"></i>
                <h3>No area found</h3>
                <p>কেন্দ্র বানানোর আগে Branch এবং Area তৈরি করুন।</p>
            </div>
        <?php else: ?>
            <form method="post" class="card card--pad" autocomplete="off">
                <?php echo csrf_field(); ?>
                <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Center Info</h3>

                <div class="form-grid">
                    <?php
                    f_select('branch_id', 'Branch', $branchOpts, $v('branch_id'), ['required' => true, 'placeholder' => '--- Select Branch ---']);
                    f_select('area_id', 'Area', $areaOpts, $v('area_id'), ['required' => true, 'placeholder' => '--- Select Area ---', 'attrs' => 'data-filter-by="f_branch_id"']);
                    f_input('name', 'Center Name', $v('name'), ['maxlength' => 100, 'required' => true]);
                    f_select('meeting_day', 'Meeting Day', $days, $v('meeting_day'), ['required' => true, 'placeholder' => '--- Select Day ---']);
                    f_input('meeting_time', 'Meeting Time', $v('meeting_time') ?: '10:00', ['type' => 'time', 'required' => true]);
                    f_select('field_officer_id', 'Field Officer', $officerOpts, $v('field_officer_id'), ['empty' => '— None —', 'attrs' => 'data-filter-by="f_branch_id"', 'hint' => 'Same branch er active staff.']);
                    f_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $v('status') ?: 'active', ['required' => true]);
                    ?>
                </div>
                <p class="hint" style="margin-top:12px">Center code (CNT-xx) is generated automatically.</p>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Center</button>
                </div>
            </form>
        <?php endif; ?>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
