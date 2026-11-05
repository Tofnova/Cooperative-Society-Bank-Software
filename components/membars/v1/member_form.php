<?php
$member_id = isset($id) ? (int) $id : 0;
$is_edit = $member_id > 0;
$page_title = $is_edit ? 'Edit Member' : 'New Member';
require_once 'components/inc/header.php';

$member = $member_id > 0 ? get_member_info($member_id) : null;
if ($member_id > 0 && !$member) {
    http_response_code(404);
    require_once '404.php';
    require_once 'components/inc/footer.php';
    return;
}

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$branches = get_branch_info();
$centers = get_center_info();
$groups = $conn->query(
    'SELECT id, center_id, code, name FROM member_groups ORDER BY name, id'
)->fetchAll(PDO::FETCH_ASSOC);
$nominee = null;
if ($is_edit) {
    $nomineeStatement = $conn->prepare(
        'SELECT * FROM member_nominees WHERE member_id = ? ORDER BY id LIMIT 1'
    );
    $nomineeStatement->execute([$member_id]);
    $nominee = $nomineeStatement->fetch(PDO::FETCH_ASSOC) ?: null;
}
$documents = $is_edit ? get_member_documents($member_id) : [];

$fields = [
    'full_name', 'father_name', 'mother_name', 'spouse_name', 'birth_date',
    'gender', 'nid_no', 'phone', 'present_address', 'permanent_address',
    'home_branch_id', 'center_id', 'group_id', 'joined_on',
    'nominee_name', 'nominee_relation', 'nominee_nid_no', 'nominee_phone',
    'nominee_share_percent',
];
$old = [];
foreach ($fields as $field) {
    $old[$field] = (string) ($member[$field] ?? '');
}
$old['nominee_name'] = (string) ($nominee['name'] ?? '');
$old['nominee_relation'] = (string) ($nominee['relation'] ?? '');
$old['nominee_nid_no'] = (string) ($nominee['nid_no'] ?? '');
$old['nominee_phone'] = (string) ($nominee['phone'] ?? '');
$old['nominee_share_percent'] = (string) ($nominee['share_percent'] ?? '');

$errors = [];
$success = null;
$member_saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } elseif (isset($_POST['delete_document_id'])) {
        try {
            delete_member_document($member_id, (int) $_POST['delete_document_id']);
            $success = 'Document deleted successfully.';
            $documents = get_member_documents($member_id);
        } catch (PDOException $exception) {
            error_log('delete_member_document: ' . $exception->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $exception) {
            $errors[] = $exception->getMessage();
        }
    } else {
        foreach ($fields as $field) {
            $old[$field] = trim((string) ($_POST[$field] ?? ''));
        }

        try {
            if ($is_edit) {
                update_member($member_id, $old);
                $member_saved = true;
                $member = get_member_info($member_id);
            } else {
                $member_id = create_member($old);
                $member_saved = true;
                foreach ($fields as $field) {
                    $old[$field] = '';
                }
            }

            $uploaded_count = upload_member_documents($member_id, $_FILES['member_documents'] ?? []);
            $documents = get_member_documents($member_id);
            $success = $is_edit ? 'Member updated successfully.' : 'Member created successfully.';
            if (!$is_edit) {
                $success .= ' Member code: ' . get_member_info($member_id)['member_code'] . '.';
            }
            if ($uploaded_count > 0) {
                $success .= ' ' . $uploaded_count . ' document(s) uploaded.';
            }
        } catch (PDOException $exception) {
            error_log(($member_saved ? 'upload_member_documents: ' : ($is_edit ? 'update_member: ' : 'create_member: ')) . $exception->getMessage());
            $errors[] = !$member_saved && isset($exception->errorInfo[1]) && (int) $exception->errorInfo[1] === 1062
                ? 'Member code or NID number is already in use.'
                : ($member_saved
                    ? 'Member was saved, but document upload failed. Please retry the document upload.'
                    : 'Database error. Please try again.');
        } catch (Exception $exception) {
            $errors[] = $member_saved
                ? 'Member was saved, but document upload failed: ' . $exception->getMessage()
                : $exception->getMessage();
            if ($member_saved) {
                $documents = get_member_documents($member_id);
            }
        }
    }
}

$value = static fn($field) => e($old[$field] ?? '');
$selected_branch = (string) ($old['home_branch_id'] ?? '');
$selected_center = (string) ($old['center_id'] ?? '');
$selected_group = (string) ($old['group_id'] ?? '');
$selected_gender = (string) ($old['gender'] ?? '');
?>
<section class="page page--narrow">
    <div class="page-head">
        <div class="page-title">
            <h2><?php echo $is_edit ? 'Edit Member - সদস্য সম্পাদনা' : 'New Member - নতুন সদস্য'; ?></h2>
        </div>
        <div class="page-actions">
            <button class="btn" type="button" onclick="call('/members')">
                <i class="fa-solid fa-arrow-left"></i> Member List
            </button>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert--success">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo e($success); ?></div>
        <?php if (!$is_edit && $member_saved): ?>
        <button class="btn" type="button" onclick="call('/edit_member/<?php echo (int) $member_id; ?>')">
            <i class="fa-regular fa-pen-to-square"></i> Edit Member / Documents
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($errors): ?>
    <div class="alert alert--error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>
            <?php foreach ($errors as $error): ?>
            <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
        </div>
        <?php if (!$is_edit && $member_saved): ?>
        <button class="btn" type="button" onclick="call('/edit_member/<?php echo (int) $member_id; ?>')">
            <i class="fa-regular fa-pen-to-square"></i> Open Member to Retry Documents
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($branches) || empty($centers)): ?>
    <div class="card empty">
        <i class="fa-solid fa-users"></i>
        <h3>Setup incomplete</h3>
        <p>সদস্য তৈরির আগে অন্তত একটি Branch এবং Center তৈরি করুন।</p>
    </div>
    <?php else: ?>
    <form method="post" id="memberForm" autocomplete="off" enctype="multipart/form-data" class="card card--pad"
        novalidate data-initial-step="<?php echo (int) ($_POST['current_step'] ?? 0); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="current_step" id="currentStep"
            value="<?php echo (int) ($_POST['current_step'] ?? 0); ?>">

        <?php if ($is_edit): ?>
        <div class="alert">
            Member code: <strong><?php echo e($member['member_code']); ?></strong>
            · Status: <strong><?php echo e(ucfirst($member['status'])); ?></strong>
            · Status and approval are not changed by this form.
        </div>
        <?php else: ?>
        <p class="hint">Member code will be generated automatically. New members start as pending approval.</p>
        <?php endif; ?>

        <div class="stepper" id="memberStepper" aria-label="Member form steps">
            <?php
                $steps = ['Member Info', 'Address', 'Nominee', 'Documents'];
                foreach ($steps as $stepIndex => $stepLabel):
                ?>
            <?php if ($stepIndex > 0): ?><div class="stepper-line"></div><?php endif; ?>
            <div class="stepper-item<?php echo $stepIndex === 0 ? ' active' : ''; ?>"
                data-step-indicator="<?php echo $stepIndex; ?>">
                <span class="stepper-dot">
                    <span><?php echo $stepIndex + 1; ?></span>
                    <i class="fa-solid fa-check"></i>
                </span>
                <span><?php echo e($stepLabel); ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="wizard-step active padding" data-wizard-step="0">
            <h3 class="card-title"><i class="fa-solid fa-user"></i> Member Information</h3>
            <div class="form-grid">
                <div class="field">
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" maxlength="100" value="<?php echo $value('full_name'); ?>"
                        required>
                </div>
                <div class="field">
                    <label for="father_name">Father's Name</label>
                    <input id="father_name" name="father_name" maxlength="100"
                        value="<?php echo $value('father_name'); ?>">
                </div>
                <div class="field">
                    <label for="mother_name">Mother's Name</label>
                    <input id="mother_name" name="mother_name" maxlength="100"
                        value="<?php echo $value('mother_name'); ?>">
                </div>
                <div class="field">
                    <label for="spouse_name">Spouse's Name</label>
                    <input id="spouse_name" name="spouse_name" maxlength="100"
                        value="<?php echo $value('spouse_name'); ?>">
                </div>
                <div class="field">
                    <label for="birth_date">Birth Date</label>
                    <input id="birth_date" type="date" name="birth_date" value="<?php echo $value('birth_date'); ?>">
                </div>
                <div class="field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">--- Select ---</option>
                        <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $gender => $label): ?>
                        <option value="<?php echo e($gender); ?>"
                            <?php echo $selected_gender === $gender ? 'selected' : ''; ?>>
                            <?php echo e($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="nid_no">NID Number</label>
                    <input id="nid_no" name="nid_no" maxlength="100" value="<?php echo $value('nid_no'); ?>">
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" type="tel" name="phone" maxlength="100" value="<?php echo $value('phone'); ?>">
                </div>
                <div class="field">
                    <label for="home_branch_id">Home Branch</label>
                    <select id="home_branch_id" name="home_branch_id" required>
                        <option value="">--- Select Branch ---</option>
                        <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo (int) $branch['id']; ?>"
                            <?php echo $selected_branch === (string) $branch['id'] ? 'selected' : ''; ?>>
                            <?php echo e($branch['name'] ?? $branch['branch_name_bn'] ?? ''); ?>
                            (<?php echo e($branch['code']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="center_id">Center</label>
                    <select id="center_id" name="center_id" required>
                        <option value="">--- Select Center ---</option>
                        <?php foreach ($centers as $center): ?>
                        <option value="<?php echo (int) $center['id']; ?>"
                            data-branch="<?php echo (int) $center['branch_id']; ?>"
                            <?php echo $selected_center === (string) $center['id'] ? 'selected' : ''; ?>>
                            <?php echo e($center['name']); ?> (<?php echo e($center['code']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="group_id">Member Group (optional)</label>
                    <select id="group_id" name="group_id">
                        <option value="">--- No Group ---</option>
                        <?php foreach ($groups as $group): ?>
                        <option value="<?php echo (int) $group['id']; ?>"
                            data-center="<?php echo (int) $group['center_id']; ?>"
                            <?php echo $selected_group === (string) $group['id'] ? 'selected' : ''; ?>>
                            <?php echo e($group['name']); ?> (<?php echo e($group['code']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="joined_on">Joined On</label>
                    <input id="joined_on" type="date" name="joined_on" value="<?php echo $value('joined_on'); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-primary" data-step-next>
                    Next: Address <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div class="wizard-step padding" data-wizard-step="1">
            <h3 class="card-title"><i class="fa-solid fa-location-dot"></i> Address</h3>
            <div class="form-grid">
                <div class="field">
                    <label for="present_address">Present Address</label>
                    <input id="present_address" name="present_address" maxlength="255"
                        value="<?php echo $value('present_address'); ?>">
                </div>
                <div class="field">
                    <label for="permanent_address">Permanent Address</label>
                    <input id="permanent_address" name="permanent_address" maxlength="255"
                        value="<?php echo $value('permanent_address'); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn" data-step-previous>
                    <i class="fa-solid fa-arrow-left"></i> Previous
                </button>
                <button type="button" class="btn-primary" data-step-next>
                    Next: Nominee <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div class="wizard-step padding" data-wizard-step="2">
            <h3 class="card-title"><i class="fa-solid fa-user-shield"></i> Nominee (Optional)</h3>
            <div class="form-grid">
                <div class="field">
                    <label for="nominee_name">Name</label>
                    <input id="nominee_name" name="nominee_name" maxlength="100"
                        value="<?php echo $value('nominee_name'); ?>">
                </div>
                <div class="field">
                    <label for="nominee_relation">Relation</label>
                    <input id="nominee_relation" name="nominee_relation" maxlength="100"
                        value="<?php echo $value('nominee_relation'); ?>">
                </div>
                <div class="field">
                    <label for="nominee_nid_no">NID Number</label>
                    <input id="nominee_nid_no" name="nominee_nid_no" maxlength="100"
                        value="<?php echo $value('nominee_nid_no'); ?>">
                </div>
                <div class="field">
                    <label for="nominee_phone">Phone</label>
                    <input id="nominee_phone" name="nominee_phone" maxlength="100"
                        value="<?php echo $value('nominee_phone'); ?>">
                </div>
                <div class="field">
                    <label for="nominee_share_percent">Share Percent</label>
                    <input id="nominee_share_percent" type="number" name="nominee_share_percent" min="0" max="100"
                        step="0.01" value="<?php echo $value('nominee_share_percent'); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn" data-step-previous>
                    <i class="fa-solid fa-arrow-left"></i> Previous
                </button>
                <button type="button" class="btn-primary" data-step-next>
                    Next: Documents <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div class="wizard-step padding" data-wizard-step="3">
            <h3 class="card-title"><i class="fa-solid fa-file-arrow-up"></i> Documents (Optional)</h3>
            <p class="hint">PDF, JPG, PNG, or WEBP files up to 5 MB each.</p>
            <div class="form-grid">
                <?php foreach ([
                    'nid' => 'NID Document',
                    'photo' => 'Member Photo',
                    'signature' => 'Signature',
                    'other' => 'Other Document',
                ] as $docKey => $docLabel): ?>
                <div class="field">
                    <label for="document_<?php echo e($docKey); ?>"><?php echo e($docLabel); ?></label>
                    <input id="document_<?php echo e($docKey); ?>" type="file"
                        name="member_documents[<?php echo e($docKey); ?>]"
                        accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                </div>
                <?php endforeach; ?>
            </div>

            <div class="form-actions">
                <button type="button" class="btn" data-step-previous>
                    <i class="fa-solid fa-arrow-left"></i> Previous
                </button>
                <button type="submit" class="btn-primary" data-final-submit>
                    <i class="fa-solid <?php echo $is_edit ? 'fa-floppy-disk' : 'fa-plus'; ?>"></i>
                    <?php echo $is_edit ? 'Update Member' : 'Create Member'; ?>
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($is_edit): ?>
    <h3>Saved Documents</h3>
    <?php if (empty($documents)): ?>
    <div class="card empty">
        <i class="fa-solid fa-file-circle-question"></i>
        <h3>No documents uploaded</h3>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="overflow">
            <table class="table--list">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Uploaded</th>
                        <th class="noPrint">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                    <tr>
                        <td data-label="Document Type"><?php echo e($document['doc_type']); ?></td>
                        <td data-label="Uploaded"><?php echo e($document['created_at']); ?></td>
                        <td data-label="Action" class="noPrint">
                            <a class="btn" href="/member_document/<?php echo (int) $document['id']; ?>">
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                            <form method="post" action="/edit_member/<?php echo $member_id; ?>"
                                onsubmit="return confirm('Delete this document?');">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo e($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="delete_document_id"
                                    value="<?php echo (int) $document['id']; ?>">
                                <button type="submit" class="btn">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</section>

<script>
var memberBranch = document.getElementById('home_branch_id');
var memberCenter = document.getElementById('center_id');
var memberGroup = document.getElementById('group_id');
var memberForm = document.getElementById('memberForm');

function filterMemberOptions(reset) {
    if (!memberBranch || !memberCenter || !memberGroup) return;

    Array.prototype.forEach.call(memberCenter.options, function(option) {
        if (!option.value) return;
        option.hidden = option.dataset.branch !== memberBranch.value;
        option.disabled = option.hidden;
    });
    if (reset) memberCenter.value = '';

    Array.prototype.forEach.call(memberGroup.options, function(option) {
        if (!option.value) return;
        option.hidden = option.dataset.center !== memberCenter.value;
        option.disabled = option.hidden;
    });
    if (reset) memberGroup.value = '';
}

if (memberBranch && memberCenter && memberGroup) {
    memberBranch.addEventListener('change', function() {
        filterMemberOptions(true);
    });
    memberCenter.addEventListener('change', function() {
        filterMemberOptions(false);
        memberGroup.value = '';
    });
    filterMemberOptions(false);
}

if (memberForm) {
    var wizardSteps = Array.prototype.slice.call(memberForm.querySelectorAll('[data-wizard-step]'));
    var stepIndicators = Array.prototype.slice.call(memberForm.querySelectorAll('[data-step-indicator]'));
    var stepLines = Array.prototype.slice.call(memberForm.querySelectorAll('.stepper-line'));
    var currentStepInput = document.getElementById('currentStep');
    var currentStep = Math.max(0, Math.min(wizardSteps.length - 1, parseInt(memberForm.dataset.initialStep, 10) || 0));

    function showWizardStep(step) {
        currentStep = Math.max(0, Math.min(wizardSteps.length - 1, step));
        wizardSteps.forEach(function(panel, index) {
            panel.classList.toggle('active', index === currentStep);
        });
        stepIndicators.forEach(function(indicator, index) {
            indicator.classList.toggle('active', index === currentStep);
            indicator.classList.toggle('done', index < currentStep);
        });
        stepLines.forEach(function(line, index) {
            line.classList.toggle('done', index < currentStep);
        });
        currentStepInput.value = String(currentStep);
    }

    memberForm.querySelectorAll('[data-step-next]').forEach(function(button) {
        button.addEventListener('click', function() {
            var controls = wizardSteps[currentStep].querySelectorAll('input, select, textarea');
            for (var i = 0; i < controls.length; i++) {
                if (!controls[i].checkValidity()) {
                    controls[i].reportValidity();
                    return;
                }
            }
            showWizardStep(currentStep + 1);
        });
    });

    memberForm.querySelectorAll('[data-step-previous]').forEach(function(button) {
        button.addEventListener('click', function() {
            showWizardStep(currentStep - 1);
        });
    });

    memberForm.addEventListener('submit', function(event) {
        if (
            currentStep < wizardSteps.length - 1 &&
            (!event.submitter || !event.submitter.hasAttribute('data-final-submit'))
        ) {
            event.preventDefault();
            var currentControls = wizardSteps[currentStep].querySelectorAll('input, select, textarea');
            for (var currentIndex = 0; currentIndex < currentControls.length; currentIndex++) {
                if (!currentControls[currentIndex].checkValidity()) {
                    currentControls[currentIndex].reportValidity();
                    return;
                }
            }
            showWizardStep(currentStep + 1);
            return;
        }

        var controls = memberForm.querySelectorAll('input, select, textarea');
        for (var i = 0; i < controls.length; i++) {
            if (!controls[i].checkValidity()) {
                event.preventDefault();
                var invalidPanel = controls[i].closest('[data-wizard-step]');
                showWizardStep(parseInt(invalidPanel.dataset.wizardStep, 10));
                controls[i].reportValidity();
                return;
            }
        }
        currentStepInput.value = String(currentStep);
    });

    showWizardStep(currentStep);
}
</script>

<?php require_once 'components/inc/footer.php'; ?>