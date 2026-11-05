<?php
$page_title = "New Member";
require_once 'components/inc/header.php';
require_perm('M-MGMT', 'can_add');

$errors  = [];
$success = null;
$old     = [];
$oldNom  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old    = posted(['full_name', 'father_name', 'mother_name', 'spouse_name', 'birth_date', 'gender', 'nid_no', 'phone',
                      'present_address', 'permanent_address', 'home_branch_id', 'center_id', 'joined_on']);
    $oldNom = posted(['nom_name', 'nom_relation', 'nom_nid_no', 'nom_phone', 'nom_share']);

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            $res = create_member($old, [
                'name'          => $oldNom['nom_name'],
                'relation'      => $oldNom['nom_relation'],
                'nid_no'        => $oldNom['nom_nid_no'],
                'phone'         => $oldNom['nom_phone'],
                'share_percent' => $oldNom['nom_share'] ?: '100',
            ], $_FILES['photo'] ?? []);
            $success = 'Member registered (pending approval). Member No: ' . $res['number'] . ' · Code: ' . $res['code'];
            $old = $oldNom = [];
        } catch (PDOException $ex) {
            error_log('create_member: ' . $ex->getMessage());
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
$centerOpts = [];
foreach (get_center_info() as $c) {
    if ($c['status'] === 'active' && isset($branchOpts[$c['branch_id']])) {
        $centerOpts[$c['id']] = ['label' => $c['name'] . ' (' . $c['code'] . ')', 'data' => ['branch' => $c['branch_id']]];
    }
}
$v  = fn($key) => $old[$key] ?? '';
$vn = fn($key) => $oldNom[$key] ?? '';
$defaultBranch = $v('home_branch_id') ?: (count($branchOpts) === 1 ? array_key_first($branchOpts) : '');
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title"><h2>New Member - নতুন সদস্য</h2></div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <?php if (empty($branchOpts)): ?>
            <div class="card empty"><i class="fa-solid fa-code-branch"></i><h3>No active branch found</h3></div>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data" class="stack" autocomplete="off">
                <?php echo csrf_field(); ?>

                <div class="card card--pad">
                    <h3 class="card-title"><i class="fa-solid fa-user"></i> Personal Info</h3>
                    <div class="form-grid">
                        <?php
                        f_input('full_name', 'Full Name', $v('full_name'), ['maxlength' => 100, 'required' => true]);
                        f_input('father_name', "Father's Name", $v('father_name'), ['maxlength' => 100, 'required' => true]);
                        f_input('mother_name', "Mother's Name", $v('mother_name'), ['maxlength' => 100]);
                        f_input('spouse_name', "Spouse's Name", $v('spouse_name'), ['maxlength' => 100]);
                        f_input('birth_date', 'Date of Birth', $v('birth_date'), ['type' => 'date', 'max' => date('Y-m-d', strtotime('-18 years')), 'required' => true, 'hint' => 'Minimum age 18.']);
                        f_select('gender', 'Gender', ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'], $v('gender'), ['required' => true, 'placeholder' => '--- Select ---']);
                        f_input('nid_no', 'NID Number', $v('nid_no'), ['maxlength' => 17, 'pattern' => '[0-9]{10}|[0-9]{13}|[0-9]{17}', 'inputmode' => 'numeric', 'digits' => true, 'required' => true, 'hint' => '10, 13 ba 17 digit.']);
                        f_input('phone', 'Mobile', $v('phone'), ['type' => 'tel', 'maxlength' => 11, 'pattern' => '[0-9]{11}', 'inputmode' => 'numeric', 'digits' => true, 'required' => true, 'hint' => '11 digits.']);
                        ?>
                    </div>
                </div>

                <div class="card card--pad">
                    <h3 class="card-title"><i class="fa-solid fa-location-dot"></i> Address</h3>
                    <div class="form-grid">
                        <?php
                        f_input('present_address', 'Present Address', $v('present_address'), ['maxlength' => 255, 'required' => true]);
                        f_input('permanent_address', 'Permanent Address', $v('permanent_address'), ['maxlength' => 255, 'required' => true]);
                        ?>
                    </div>
                </div>

                <div class="card card--pad">
                    <h3 class="card-title"><i class="fa-solid fa-code-branch"></i> Branch &amp; Center</h3>
                    <div class="form-grid">
                        <?php
                        f_select('home_branch_id', 'Home Branch', $branchOpts, $defaultBranch, ['required' => true, 'placeholder' => '--- Select Branch ---', 'hint' => 'Shudhu home branch theke edit/close kora jabe.']);
                        f_select('center_id', 'Center', $centerOpts, $v('center_id'), ['empty' => '— None —', 'attrs' => 'data-filter-by="f_home_branch_id"']);
                        f_input('joined_on', 'Joining Date', $v('joined_on') ?: date('Y-m-d'), ['type' => 'date', 'required' => true]);
                        ?>
                    </div>
                </div>

                <div class="card card--pad">
                    <h3 class="card-title"><i class="fa-solid fa-user-shield"></i> Nominee (optional)</h3>
                    <div class="form-grid">
                        <?php
                        f_input('nom_name', 'Nominee Name', $vn('nom_name'), ['maxlength' => 100]);
                        f_input('nom_relation', 'Relation', $vn('nom_relation'), ['maxlength' => 100]);
                        f_input('nom_nid_no', 'Nominee NID', $vn('nom_nid_no'), ['maxlength' => 17, 'inputmode' => 'numeric', 'digits' => true]);
                        f_input('nom_phone', 'Nominee Phone', $vn('nom_phone'), ['type' => 'tel', 'maxlength' => 11, 'inputmode' => 'numeric', 'digits' => true]);
                        f_input('nom_share', 'Share (%)', $vn('nom_share') ?: '100', ['type' => 'number', 'min' => 1, 'max' => 100, 'step' => '0.01']);
                        ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-user-plus"></i> Register Member</button>
                </div>
            </form>
        <?php endif; ?>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
