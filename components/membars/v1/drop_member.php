<?php
$page_title = 'Deactivate Members';
require_once 'components/inc/header.php';

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = trim((string) ($_POST['member_id'] ?? ''));
    if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } elseif (!ctype_digit($member_id)) {
        $errors[] = 'Invalid member.';
    } else {
        try {
            update_member_status((int) $member_id, 'closed');
            $success = 'Member deactivated successfully.';
        } catch (PDOException $exception) {
            error_log('deactivate_member: ' . $exception->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}

$members = get_member_info();
$deactivatable = array_values(array_filter($members, static function ($member) {
    return in_array($member['status'], ['pending', 'active'], true);
}));
?>
<section class="page">
    <div class="page-head">
        <div class="page-title">
            <h2>Deactivate Member - সদস্য নিষ্ক্রিয় করুন</h2>
            <span class="badge"><?php echo count($deactivatable); ?> active / pending</span>
        </div>
        <div class="page-actions">
            <button class="btn" type="button" onclick="call('/members')">
                <i class="fa-solid fa-users"></i> All Members
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert--success">
            <i class="fa-solid fa-circle-check"></i>
            <div><?php echo e($success); ?></div>
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
        </div>
    <?php endif; ?>

    <?php if (empty($deactivatable)): ?>
        <div class="card empty">
            <i class="fa-solid fa-user-check"></i>
            <h3>No active or pending members</h3>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr>
                            <th>SL &amp; ID</th>
                            <th>Member</th>
                            <th>Phone</th>
                            <th>Branch / Center</th>
                            <th>Status</th>
                            <th class="noPrint">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($deactivatable as $key => $member): ?>
                        <tr>
                            <td data-label="SL &amp; ID">
                                <?php echo $key + 1; ?>
                                <span class="user-sub">(<?php echo (int) $member['id']; ?>)</span>
                            </td>
                            <td data-label="Member">
                                <div class="user-name"><?php echo e($member['full_name']); ?></div>
                                <div class="user-sub"><?php echo e($member['member_code']); ?></div>
                            </td>
                            <td data-label="Phone"><?php echo e($member['phone'] ?: '—'); ?></td>
                            <td data-label="Branch / Center">
                                <?php echo e($member['branch_name'] ?? '—'); ?>
                                <div class="user-sub"><?php echo e($member['center_name'] ?? '—'); ?></div>
                            </td>
                            <td data-label="Status"><span class="badge"><?php echo e(ucfirst($member['status'])); ?></span></td>
                            <td data-label="Action" class="noPrint">
                                <a class="btn" href="/member_profile/<?php echo (int) $member['id']; ?>">
                                    <i class="fa-regular fa-id-card"></i> Profile
                                </a>
                                <form method="post" onsubmit="return confirm('Deactivate this member?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="member_id" value="<?php echo (int) $member['id']; ?>">
                                    <button type="submit" class="btn">
                                        <i class="fa-solid fa-user-xmark"></i> Deactivate
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
</section>

<?php require_once 'components/inc/footer.php'; ?>
