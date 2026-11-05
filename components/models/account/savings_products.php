<?php
$page_title = 'Savings Products';
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
$product = $editId ? get_savings_products($editId) : false;

if ($hasEdit && (!$editId || !$product)) {
    $errors[] = 'Savings product not found.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($hasEdit) {
        require_perm('ACC-MGMT', 'can_edit');
    } else {
        require_perm('ACC-MGMT', 'can_add');
    }

    $old = posted([
        'type_id',
        'name',
        'product_type',
        'interest_rate',
        'installment_amount',
        'term_months',
        'min_deposit',
        'status',
    ]);

    if (!$hasEdit || $product) {
        if (!csrf_ok()) {
            $errors[] = 'Session expired. Please reload the page and try again.';
        } else {
            $result = $hasEdit
                ? updateSavingsProduct($conn, (int) $editId, $old)
                : addSavingsProduct($conn, $old);

            if ($result['success']) {
                $success = $result['message'];
                $old = [];
                if ($hasEdit) {
                    $product = get_savings_products($editId);
                }
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$productTypes = [
    'daily' => 'Daily',
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'fdr' => 'FDR',
    'dps' => 'DPS',
];
$accountTypeOptions = [];
$accountTypeCodes = [];
foreach (get_account_types() as $accountType) {
    if ($accountType['category'] !== 'savings') {
        continue;
    }
    $label = $accountType['name'] . ' (' . $accountType['type_code'] . ')';
    if ($accountType['status'] !== 'active') {
        $label .= ' — Inactive';
    }
    $accountTypeOptions[$accountType['id']] = $label;
    $accountTypeCodes[$accountType['id']] = $accountType['type_code'];
}

$products = get_savings_products();
$v = fn($key, $default = '') => $old[$key] ?? ($product[$key] ?? $default);
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">
        <div class="page-head">
            <div class="page-title">
                <h2>Savings Products - সঞ্চয় প্রোডাক্ট</h2>
                <span class="badge"><?php echo count($products); ?> total</span>
            </div>
            <?php if ($hasEdit): ?>
                <div class="page-actions">
                    <a class="btn" href="/savings_products"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
            <?php endif; ?>
        </div>

        <?php render_alerts($success, $errors); ?>

        <div class="stack">
            <?php if ($hasEdit && !$product): ?>
                <div class="card empty">
                    <i class="fa-solid fa-piggy-bank"></i>
                    <h3>Savings product not found</h3>
                </div>
            <?php elseif ($hasEdit || user_can('ACC-MGMT', 'can_add')): ?>
                <form method="post" class="card card--pad" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <h3 class="card-title">
                        <i class="fa-solid fa-building-columns"></i>
                        <?php echo $hasEdit ? 'Edit Savings Product' : 'Savings Product Information'; ?>
                    </h3>

                    <?php if (empty($accountTypeOptions)): ?>
                        <div class="alert alert--error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <div>Create a savings account type before adding a savings product.</div>
                        </div>
                    <?php else: ?>
                        <div class="form-grid">
                            <?php
                            f_select('type_id', 'Account Type', $accountTypeOptions, $v('type_id'), [
                                'required' => true,
                                'placeholder' => '--- Select Account Type ---',
                            ]);
                            f_input('name', 'Product Name', $v('name'), [
                                'maxlength' => 100,
                                'required' => true,
                                'placeholder' => 'Enter savings product name',
                            ]);
                            f_select('product_type', 'Product Type', $productTypes, $v('product_type'), [
                                'required' => true,
                                'placeholder' => '--- Select Product Type ---',
                            ]);
                            f_input('interest_rate', 'Interest Rate (%)', $v('interest_rate', '0.00'), [
                                'type' => 'number',
                                'min' => 0,
                                'max' => '9999.99',
                                'step' => '0.01',
                                'inputmode' => 'decimal',
                                'required' => true,
                            ]);
                            f_input('installment_amount', 'Installment Amount (৳)', $v('installment_amount', '0.00'), [
                                'type' => 'number',
                                'min' => 0,
                                'max' => '999999999999.99',
                                'step' => '0.01',
                                'inputmode' => 'decimal',
                                'required' => true,
                            ]);
                            f_input('term_months', 'Term (Months)', $v('term_months'), [
                                'type' => 'number',
                                'min' => 1,
                                'max' => 2147483647,
                                'step' => 1,
                                'inputmode' => 'numeric',
                                'hint' => 'Leave blank for no fixed term.',
                            ]);
                            f_input('min_deposit', 'Minimum Deposit (৳)', $v('min_deposit', '0.00'), [
                                'type' => 'number',
                                'min' => 0,
                                'max' => '999999999999.99',
                                'step' => '0.01',
                                'inputmode' => 'decimal',
                                'required' => true,
                            ]);
                            f_select('status', 'Status', [
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ], $v('status', 'active'), ['required' => true]);
                            ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fa-solid <?php echo $hasEdit ? 'fa-floppy-disk' : 'fa-plus'; ?>"></i>
                                <?php echo $hasEdit ? 'Save Changes' : 'Create Savings Product'; ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="card empty">
                    <i class="fa-solid fa-piggy-bank"></i>
                    <h3>No savings products found</h3>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="overflow">
                        <table class="table--list">
                            <thead>
                                <tr>
                                    <th>SL &amp; ID</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Rate / Term</th>
                                    <th>Installment / Min. Deposit</th>
                                    <th>Status</th>
                                    <?php if (user_can('ACC-MGMT', 'can_edit')): ?><th>Action</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $index => $item): ?>
                                    <tr>
                                        <td data-label="SL &amp; ID">
                                            <?php echo $index + 1; ?>
                                            <span class="user-sub">(<?php echo (int) $item['id']; ?>)</span>
                                        </td>
                                        <td data-label="Product">
                                            <div class="user-name"><?php echo e($item['name']); ?></div>
                                            <div class="user-sub">
                                                Type Code: <?php echo e($accountTypeCodes[$item['type_id']] ?? '—'); ?>
                                            </div>
                                        </td>
                                        <td data-label="Type">
                                            <?php echo e($productTypes[$item['product_type']] ?? $item['product_type']); ?>
                                        </td>
                                        <td data-label="Rate / Term">
                                            <div><?php echo e($item['interest_rate']); ?>%</div>
                                            <div class="user-sub">
                                                <?php echo $item['term_months'] !== null ? (int) $item['term_months'] . ' months' : 'No fixed term'; ?>
                                            </div>
                                        </td>
                                        <td data-label="Installment / Min. Deposit">
                                            <div><?php echo money($item['installment_amount']); ?></div>
                                            <div class="user-sub">Min: <?php echo money($item['min_deposit']); ?></div>
                                        </td>
                                        <td data-label="Status"><?php echo status_badge($item['status']); ?></td>
                                        <?php if (user_can('ACC-MGMT', 'can_edit')): ?>
                                            <td data-label="Action">
                                                <a class="btn" href="/savings_products?edit=<?php echo (int) $item['id']; ?>">
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
