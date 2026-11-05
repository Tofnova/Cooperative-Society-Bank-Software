<?php
$page_title = 'Savings Accounts';
require_once 'components/inc/header.php';
require_perm('ACC-MGMT', 'can_view');

$hasEdit = array_key_exists('edit', $_GET);
if ($hasEdit) {
    require_perm('ACC-MGMT', 'can_edit');
}

$errors = [];
$success = null;
$old = [];
$editId = $hasEdit
    ? (is_string($_GET['edit']) || is_int($_GET['edit'])
        ? filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false)
    : null;
$account = $editId ? get_savings_account($editId) : false;
if ($account && !is_super() && (int) $account['home_branch_id'] !== current_branch_id()) {
    $account = false;
}

if ($hasEdit && (!$editId || !$account)) {
    $errors[] = 'Savings account not found.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($hasEdit) {
        require_perm('ACC-MGMT', 'can_edit');
    } else {
        require_perm('ACC-MGMT', 'can_add');
    }

    $old = posted([
        'account_no',
        'member_id',
        'product_id',
        'serial_no',
        'opened_on',
        'maturity_date',
        'status',
        'closed_on',
    ]);

    if (!$hasEdit || $account) {
        if (!csrf_ok()) {
            $errors[] = 'Session expired. Please reload the page and try again.';
        } else {
            $result = $hasEdit
                ? updateSavingsAccount($conn, (int) $editId, $old)
                : addSavingsAccount($conn, $old);

            if ($result['success']) {
                $success = $result['message'];
                $old = [];
                if ($hasEdit) {
                    $account = get_savings_account($editId);
                }
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$memberParams = [];
$memberSql = "SELECT m.id, m.member_no, m.full_name, m.status, m.home_branch_id, b.name AS branch_name, b.code AS branch_code
              FROM members m
              JOIN branches b ON b.id = m.home_branch_id
              WHERE (m.status = 'active'";
if ($account) {
    $memberSql .= ' OR m.id = ?';
    $memberParams[] = (int) $account['member_id'];
}
$memberSql .= ') AND b.status = \'active\'';
if (!is_super()) {
    $memberSql .= ' AND m.home_branch_id = ?';
    $memberParams[] = current_branch_id();
}
$memberSql .= ' ORDER BY m.full_name';
$memberOptions = [];
foreach (db_all($memberSql, $memberParams) as $member) {
    $memberOptions[$member['id']] = $member['full_name'] . ' (' . $member['member_no'] . ') — '
        . $member['branch_name'] . ' (' . $member['branch_code'] . ')';
}
$productOptions = [];

foreach (get_savings_products() as $product) {
    if ($product['status'] === 'active' || ($account && (int) $product['id'] === (int) $account['product_id'])) {
        $label = $product['name'] . ' — ' . strtoupper($product['product_type']);
        if ($product['status'] !== 'active') {
            $label .= ' (Inactive)';
        }
        $productOptions[$product['id']] = $label;
    }
}

$accountsSql = 'SELECT sa.*, m.full_name AS member_name, m.member_no, sp.name AS product_name,
                       sp.product_type, b.name AS branch_name
                FROM savings_accounts sa
                JOIN members m ON m.id = sa.member_id
                JOIN savings_products sp ON sp.id = sa.product_id
                JOIN branches b ON b.id = sa.home_branch_id';
$accounts = is_super()
    ? db_all($accountsSql . ' ORDER BY sa.id DESC')
    : db_all($accountsSql . ' WHERE sa.home_branch_id = ? ORDER BY sa.id DESC', [current_branch_id()]);

$v = fn($key, $default = '') => $old[$key] ?? ($account[$key] ?? $default);
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">
        <div class="page-head">
            <div class="page-title">
                <h2>Savings Accounts - সঞ্চয় হিসাব</h2>
                <span class="badge"><?php echo count($accounts); ?> total</span>
            </div>
            <?php if ($hasEdit): ?>
                <div class="page-actions">
                    <a class="btn" href="/savings_accounts"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
            <?php endif; ?>
        </div>

        <?php render_alerts($success, $errors); ?>

        <div class="stack">
            <?php if ($hasEdit && !$account): ?>
                <div class="card empty">
                    <i class="fa-solid fa-sack-dollar"></i>
                    <h3>Savings account not found</h3>
                </div>
            <?php elseif ($hasEdit || user_can('ACC-MGMT', 'can_add')): ?>
                <?php if (empty($memberOptions) || empty($productOptions)): ?>
                    <div class="card empty">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <h3><?php echo empty($memberOptions) ? 'No eligible members found' : 'No active savings products found'; ?></h3>
                        <p><?php echo empty($memberOptions) ? 'An active member in an active branch is required to open an account.' : 'Create an active savings product before opening an account.'; ?></p>
                    </div>
                <?php else: ?>
                    <form method="post" class="card card--pad" autocomplete="off">
                        <?php echo csrf_field(); ?>
                        <h3 class="card-title">
                            <i class="fa-solid fa-sack-dollar"></i>
                            <?php echo $hasEdit ? 'Edit Savings Account' : 'Open Savings Account'; ?>
                        </h3>

                        <div class="form-grid">
                            <?php 
                            $autoCode = "112";
                            ?>
                            <?php
                            f_input('account_no', 'Account Number', $v('account_no' ,$autoCode), [
                                'maxlength' => 20,
                                'required' => true,
                                'placeholder' => 'Enter unique account number',
                            ]);
                            f_select('member_id', 'Member', $memberOptions, $v('member_id'), [
                                'required' => true,
                                'placeholder' => '--- Select Member ---',
                            ]);
                            f_select('product_id', 'Savings Product', $productOptions, $v('product_id'), [
                                'required' => true,
                                'placeholder' => '--- Select Product ---',
                            ]);
                            f_input('serial_no', 'Member Product Serial', $v('serial_no', '1'), [
                                'type' => 'number',
                                'min' => 1,
                                'max' => 2147483647,
                                'step' => 1,
                                'inputmode' => 'numeric',
                                'required' => true,
                                'hint' => 'Unique for each member and savings product.',
                            ]);
                            f_input('opened_on', 'Opened On', $v('opened_on', date('Y-m-d')), [
                                'type' => 'date',
                                'required' => true,
                            ]);
                            f_input('maturity_date', 'Maturity Date', $v('maturity_date'), [
                                'type' => 'date',
                                'hint' => 'Leave blank if this account has no fixed maturity date.',
                            ]);
                            f_select('status', 'Status', [
                                'active' => 'Active',
                                'matured' => 'Matured',
                                'closed' => 'Closed',
                            ], $v('status', 'active'), ['required' => true]);
                            f_input('closed_on', 'Closed On', $v('closed_on'), [
                                'type' => 'date',
                                'hint' => 'Required when status is Closed.',
                            ]);
                            f_input('principal_amount', 'Principal Amount (৳)', money($account['principal_amount'] ?? 0), [
                                'disabled' => true,
                                'hint' => 'Managed through account transactions.',
                            ]);
                            f_input('balance', 'Current Balance (৳)', money($account['balance'] ?? 0), [
                                'disabled' => true,
                                'hint' => 'Managed through account transactions.',
                            ]);
                            ?>
                        </div>

                        <p class="hint" style="margin-top:12px">
                            Home branch is assigned automatically from the selected member. New accounts start with zero principal and balance.
                        </p>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fa-solid <?php echo $hasEdit ? 'fa-floppy-disk' : 'fa-plus'; ?>"></i>
                                <?php echo $hasEdit ? 'Save Changes' : 'Open Account'; ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (empty($accounts)): ?>
                <div class="card empty">
                    <i class="fa-solid fa-sack-dollar"></i>
                    <h3>No savings accounts found</h3>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="overflow">
                        <table class="table--list">
                            <thead>
                                <tr>
                                    <th>SL &amp; ID</th>
                                    <th>Account</th>
                                    <th>Member</th>
                                    <th>Product</th>
                                    <th>Principal / Balance</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <?php if (user_can('ACC-MGMT', 'can_edit')): ?><th>Action</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accounts as $index => $item): ?>
                                    <tr>
                                        <td data-label="SL &amp; ID">
                                            <?php echo $index + 1; ?>
                                            <span class="user-sub">(<?php echo (int) $item['id']; ?>)</span>
                                        </td>
                                        <td data-label="Account">
                                            <div class="user-name"><?php echo e($item['account_no']); ?></div>
                                            <div class="user-sub">Serial: <?php echo (int) $item['serial_no']; ?></div>
                                        </td>
                                        <td data-label="Member">
                                            <div class="user-name"><?php echo e($item['member_name']); ?></div>
                                            <div class="user-sub"><?php echo e($item['member_no']); ?> · <?php echo e($item['branch_name']); ?></div>
                                        </td>
                                        <td data-label="Product">
                                            <div><?php echo e($item['product_name']); ?></div>
                                            <div class="user-sub"><?php echo e(strtoupper($item['product_type'])); ?></div>
                                        </td>
                                        <td data-label="Principal / Balance">
                                            <div><?php echo money($item['principal_amount']); ?></div>
                                            <div class="user-sub">Balance: <?php echo money($item['balance']); ?></div>
                                        </td>
                                        <td data-label="Dates">
                                            <div>Opened: <?php echo e(formatDay($item['opened_on'])); ?></div>
                                            <div class="user-sub">Maturity: <?php echo e(formatDay($item['maturity_date'])); ?></div>
                                        </td>
                                        <td data-label="Status"><?php echo status_badge($item['status']); ?></td>
                                        <?php if (user_can('ACC-MGMT', 'can_edit')): ?>
                                            <td data-label="Action">
                                                <a class="btn" href="/savings_accounts?edit=<?php echo (int) $item['id']; ?>">
                                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
