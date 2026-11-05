<?php
$page_title = 'Account Types';
require_once 'components/inc/header.php';
require_perm('ACC-MGMT', 'can_view');

$errors  = [];
$success = null;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('ACC-MGMT', 'can_add');
    $old = posted(['type_code', 'category', 'name', 'status']);

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        $result = addAccountType(
            $conn,
            $old['type_code'],
            $old['category'],
            $old['name'],
            $old['status'],
            current_user_id() ?: null
        );

        if ($result['success']) {
            $success = $result['message'];
            $old = [];
        } else {
            $errors[] = $result['message'];
        }
    }
}

$categoryLabels = [
    'savings' => 'সঞ্চয়',
    'loan' => 'লোন',
    'share' => 'শেয়ার',
];

$accountTypes = $conn->query(
    'SELECT at.id, at.type_code, at.category, at.name, at.status, u.full_name AS created_by_name
     FROM account_types at
     LEFT JOIN users u ON u.id = at.created_by
     ORDER BY at.type_code'
)->fetchAll();

$v = fn($key, $default = '') => $old[$key] ?? $default;
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">
        <div class="page-head">
            <div class="page-title">
                <h2>Account Types</h2>
                <span class="badge"><?php echo count($accountTypes); ?> total</span>
            </div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <div class="stack">
            <?php if (user_can('ACC-MGMT', 'can_add')): ?>
                <form method="post" class="card card--pad" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <h3 class="card-title"><i class="fa-solid fa-building-columns"></i> Account Type Information</h3>

                    <div class="form-grid">
                        <?php
                        f_input('type_code', 'Type Code', $v('type_code'), [
                            'maxlength' => 3,
                            'pattern' => '[1-3][0-9]{2}',
                            'inputmode' => 'numeric',
                            'required' => true,
                            'placeholder' => 'e.g. 101',
                            'hint' => '3 digits: 1xx = Savings, 2xx = Loan, 3xx = Share.',
                        ]);
                        f_select('category', 'Category', [
                            'savings' => 'সঞ্চয় (1xx)',
                            'loan' => 'লোন (2xx)',
                            'share' => 'শেয়ার (3xx)',
                        ], $v('category', 'savings'), ['required' => true]);
                        f_input('name', 'Name', $v('name'), [
                            'maxlength' => 100,
                            'required' => true,
                            'placeholder' => 'Enter account type name',
                        ]);
                        f_select('status', 'Status', [
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ], $v('status', 'active'), ['required' => true]);
                        ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Save Account Type</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (empty($accountTypes)): ?>
                <div class="card empty">
                    <i class="fa-solid fa-building-columns"></i>
                    <h3>No account types found</h3>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="overflow">
                        <table class="table--list">
                            <thead>
                                <tr>
                                    <th>SL &amp; ID</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accountTypes as $key => $accountType): ?>
                                    <tr>
                                        <td data-label="SL &amp; ID">
                                            <?php echo $key + 1; ?>
                                            <span class="user-sub">(<?php echo (int) $accountType['id']; ?>)</span>
                                        </td>
                                        <td data-label="Type">
                                            <div class="user-name"><?php echo e($accountType['name']); ?></div>
                                            <div class="user-sub">Code: <?php echo e($accountType['type_code']); ?></div>
                                        </td>
                                        <td data-label="Category">
                                            <?php echo e($categoryLabels[$accountType['category']] ?? $accountType['category']); ?>
                                        </td>
                                        <td data-label="Status"><?php echo status_badge($accountType['status']); ?></td>
                                        <td data-label="Created By"><?php echo e($accountType['created_by_name'] ?: '—'); ?></td>
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
