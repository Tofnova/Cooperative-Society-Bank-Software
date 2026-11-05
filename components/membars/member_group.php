<?php
$page_title = 'Member Groups';
require_once 'components/inc/header.php';
require_perm('M-MGMT', 'can_view');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$canCreateGroups = user_can('M-MGMT', 'can_add');
$errors = [];
$success = null;
$old = [
    'center_id' => '',
    'code' => '',
    'name' => '',
    'leader_member_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('M-MGMT', 'can_add');

    foreach (array_keys($old) as $field) {
        $old[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } elseif (
        !ctype_digit($old['center_id']) ||
        preg_match('/^.{1,100}$/us', $old['code']) !== 1 ||
        preg_match('/^.{1,100}$/us', $old['name']) !== 1
    ) {
        $errors[] = 'Select a center and enter a group code and name (maximum 100 characters each).';
    } elseif ($old['leader_member_id'] !== '' && !ctype_digit($old['leader_member_id'])) {
        $errors[] = 'Invalid group leader.';
    } else {
        try {
            $centerSql = "SELECT id FROM centers WHERE id = ? AND status = 'active'";
            $centerParams = [$old['center_id']];
            if (!is_super()) {
                $centerSql .= ' AND branch_id = ?';
                $centerParams[] = current_branch_id();
            }
            $centerStatement = $conn->prepare($centerSql . ' LIMIT 1');
            $centerStatement->execute($centerParams);
            if (!$centerStatement->fetchColumn()) {
                throw new InvalidArgumentException('Select an active center you are allowed to manage.');
            }

            $leaderId = $old['leader_member_id'] === '' ? null : $old['leader_member_id'];
            if ($leaderId !== null) {
                $leaderStatement = $conn->prepare(
                    "SELECT id FROM members WHERE id = ? AND center_id = ? AND status = 'active' LIMIT 1"
                );
                $leaderStatement->execute([$leaderId, $old['center_id']]);
                if (!$leaderStatement->fetchColumn()) {
                    throw new InvalidArgumentException('Select an active member from the selected center as group leader.');
                }
            }

            $insert = $conn->prepare(
                'INSERT INTO member_groups (center_id, code, name, leader_member_id) VALUES (?, ?, ?, ?)'
            );
            $insert->execute([$old['center_id'], $old['code'], $old['name'], $leaderId]);
            $success = 'Member group created successfully.';
            $old = ['center_id' => '', 'code' => '', 'name' => '', 'leader_member_id' => ''];
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log('create_member_group: ' . $exception->getMessage());
            $errors[] = isset($exception->errorInfo[1]) && (int) $exception->errorInfo[1] === 1062
                ? 'This group code is already in use.'
                : 'Database error. Please try again.';
        }
    }
}

$branchFilter = is_super() ? '' : ' AND c.branch_id = ?';
$branchParams = is_super() ? [] : [current_branch_id()];

$centerStatement = $conn->prepare(
    "SELECT c.id, c.code, c.name
     FROM centers c
     WHERE c.status = 'active'{$branchFilter}
     ORDER BY c.name, c.id"
);
$centerStatement->execute($branchParams);
$centers = $centerStatement->fetchAll(PDO::FETCH_ASSOC);

$memberStatement = $conn->prepare(
    "SELECT m.id, m.center_id, m.member_no, m.full_name
     FROM members m
     JOIN centers c ON c.id = m.center_id
     WHERE m.status = 'active' AND c.status = 'active'{$branchFilter}
     ORDER BY m.full_name, m.id"
);
$memberStatement->execute($branchParams);
$members = $memberStatement->fetchAll(PDO::FETCH_ASSOC);

$groupStatement = $conn->prepare(
    "SELECT
        g.id,
        g.code,
        g.name,
        c.name AS center_name,
        c.code AS center_code,
        m.member_no AS leader_no,
        m.full_name AS leader_name
     FROM member_groups g
     JOIN centers c ON c.id = g.center_id
     LEFT JOIN members m ON m.id = g.leader_member_id
     WHERE 1 = 1{$branchFilter}
     ORDER BY g.id DESC"
);
$groupStatement->execute($branchParams);
$groups = $groupStatement->fetchAll(PDO::FETCH_ASSOC);

$v = static fn($key) => e($old[$key] ?? '');
?>
<section class="page">
    <div class="page-head">
        <div class="page-title">
            <h2>Member Groups - সদস্য গ্রুপ</h2>
            <span class="badge"><?php echo count($groups); ?> total</span>
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

    <?php if (empty($centers) && $canCreateGroups): ?>
        <div class="card empty">
            <i class="fa-solid fa-route"></i>
            <h3>No center found</h3>
            <p>Create a center before adding a member group.</p>
        </div>
    <?php elseif ($canCreateGroups): ?>
        <form method="post" class="card card--pad" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Create Member Group</h3>

            <div class="form-grid">
                <div class="field">
                    <label for="center_id">Center</label>
                    <select id="center_id" name="center_id" required>
                        <option value="">--- Select Center ---</option>
                        <?php foreach ($centers as $center): ?>
                            <option value="<?php echo (int) $center['id']; ?>"
                                <?php echo $old['center_id'] === (string) $center['id'] ? 'selected' : ''; ?>>
                                <?php echo e($center['name']); ?> (<?php echo e($center['code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="code">Group Code</label>
                    <input type="text" id="code" name="code" value="<?php echo $v('code'); ?>"
                        maxlength="100" required>
                </div>

                <div class="field">
                    <label for="name">Group Name</label>
                    <input type="text" id="name" name="name" value="<?php echo $v('name'); ?>"
                        maxlength="100" required>
                </div>

                <div class="field">
                    <label for="leader_member_id">Group Leader (optional)</label>
                    <select id="leader_member_id" name="leader_member_id">
                        <option value="">--- No leader selected ---</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo (int) $member['id']; ?>"
                                data-center="<?php echo (int) $member['center_id']; ?>"
                                <?php echo $old['leader_member_id'] === (string) $member['id'] ? 'selected' : ''; ?>>
                                <?php echo e($member['full_name']); ?> (<?php echo e($member['member_no']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-plus"></i> Create Group
                </button>
            </div>
        </form>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <div class="card empty">
            <i class="fa-solid fa-people-group"></i>
            <h3>No member group found</h3>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr>
                            <th>SL &amp; ID</th>
                            <th>Group</th>
                            <th>Center</th>
                            <th>Group Leader</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($groups as $key => $group): ?>
                        <tr>
                            <td data-label="SL &amp; ID">
                                <?php echo $key + 1; ?>
                                <span class="user-sub">(<?php echo (int) $group['id']; ?>)</span>
                            </td>
                            <td data-label="Group">
                                <div class="user-name"><?php echo e($group['name']); ?></div>
                                <div class="user-sub">Code: <?php echo e($group['code']); ?></div>
                            </td>
                            <td data-label="Center">
                                <?php if ($group['center_name'] !== null): ?>
                                    <div class="user-name"><?php echo e($group['center_name']); ?></div>
                                    <div class="user-sub">Code: <?php echo e($group['center_code']); ?></div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td data-label="Group Leader">
                                <?php if ($group['leader_name'] !== null): ?>
                                    <div class="user-name"><?php echo e($group['leader_name']); ?></div>
                                    <div class="user-sub">Member No: <?php echo e($group['leader_no']); ?></div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
var centerSelect = document.getElementById('center_id');
var leaderSelect = document.getElementById('leader_member_id');

function filterGroupLeaders() {
    if (!centerSelect || !leaderSelect) return;

    Array.prototype.forEach.call(leaderSelect.options, function (option) {
        if (!option.value) return;
        option.hidden = option.dataset.center !== centerSelect.value;
        if (option.hidden && option.selected) leaderSelect.value = '';
    });
}

if (centerSelect && leaderSelect) {
    centerSelect.addEventListener('change', filterGroupLeaders);
    filterGroupLeaders();
}
</script>

<?php require_once 'components/inc/footer.php'; ?>