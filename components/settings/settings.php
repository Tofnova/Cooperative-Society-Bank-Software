<?php
$page_title = "Settings";
require_once 'components/inc/header.php';
require_perm('SETTINGS', 'can_view');

$canEdit = user_can('SETTINGS', 'can_edit');
$errors  = [];
$success = null;
$old     = [];

if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = trim((string) ($_POST['form'] ?? ''));

    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            if ($form === 'update') {
                update_setting((int) ($_POST['id'] ?? 0), (string) ($_POST['setting_value'] ?? ''));
                $success = 'Setting updated.';
            } elseif ($form === 'create' && user_can('SETTINGS', 'can_add')) {
                $old = posted(['setting_key', 'setting_value', 'description']);
                create_setting($old['setting_key'], $old['setting_value'], $old['description']);
                $success = 'Setting added.';
                $old     = [];
            } else {
                $errors[] = 'Invalid request.';
            }
        } catch (PDOException $ex) {
            error_log('settings: ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$settings = get_all_settings();
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title">
                <h2>Settings</h2>
                <span class="badge"><?php echo count($settings); ?> total</span>
            </div>
        </div>

        <?php render_alerts($success, $errors); ?>

        <div class="stack">
            <div class="card">
                <div class="overflow">
                    <table>
                        <thead>
                            <tr><th>Key</th><th>Value</th><th>Description</th><th>Updated</th><?php if ($canEdit): ?><th class="noPrint"></th><?php endif; ?></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($settings as $s): ?>
                            <tr>
                                <td><b><?php echo e($s['setting_key']); ?></b></td>
                                <?php if ($canEdit): ?>
                                    <td colspan="1">
                                        <form method="post" id="sf_<?php echo (int) $s['id']; ?>" class="flex gap medel">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="form" value="update">
                                            <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                                            <input type="text" name="setting_value" value="<?php echo e($s['setting_value']); ?>" maxlength="100" required style="margin:0">
                                        </form>
                                    </td>
                                <?php else: ?>
                                    <td><?php echo e($s['setting_value']); ?></td>
                                <?php endif; ?>
                                <td><?php echo e($s['description']); ?></td>
                                <td><?php echo e(formatDate($s['updated_at'])); ?></td>
                                <?php if ($canEdit): ?>
                                    <td class="noPrint">
                                        <button type="submit" form="sf_<?php echo (int) $s['id']; ?>"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (user_can('SETTINGS', 'can_add')): ?>
                <form method="post" class="card card--pad" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="form" value="create">
                    <h3 class="card-title"><i class="fa-solid fa-plus"></i> Add Setting</h3>
                    <div class="form-grid">
                        <?php
                        f_input('setting_key', 'Key', $old['setting_key'] ?? '', ['maxlength' => 100, 'required' => true, 'placeholder' => 'e.g. late_fine_rate', 'hint' => 'a-z, 0-9 and _ only.']);
                        f_input('setting_value', 'Value', $old['setting_value'] ?? '', ['maxlength' => 100, 'required' => true, 'id' => 'f_new_value']);
                        f_input('description', 'Description', $old['description'] ?? '', ['maxlength' => 100]);
                        ?>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Add Setting</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
