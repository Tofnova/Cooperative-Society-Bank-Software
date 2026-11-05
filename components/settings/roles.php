<?php
$page_title = "Roles & Permissions";
require_once 'components/inc/header.php';
require_once 'components/err/only_super_admin.php';

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            save_role_permissions((array) ($_POST['perm'] ?? []));
            $success = 'Permissions saved.';
        } catch (Exception $ex) {
            error_log('save_role_permissions: ' . $ex->getMessage());
            $errors[] = 'Could not save permissions. Please try again.';
        }
    }
}

$matrix = get_permission_matrix();
$roles  = array_filter(get_roles(), fn($r) => $r['name'] !== 'super_admin');
?>
<div class="w100 flex center padding">
    <div class="page page--narrow">

        <div class="page-head">
            <div class="page-title"><h2>Roles &amp; Permissions</h2></div>
        </div>

        <?php render_alerts($success, $errors); ?>
        <p class="hint" style="margin-bottom:14px">super_admin always has full access. Tick what each role can do in every module.</p>

        <form method="post" class="stack">
            <?php echo csrf_field(); ?>

            <?php foreach ($roles as $role): ?>
                <div class="card card--pad">
                    <h3 class="card-title"><i class="fa-solid fa-user-shield"></i> <?php echo e($role['name']); ?> — <?php echo e($role['description']); ?></h3>
                    <div class="overflow">
                        <table>
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <?php foreach (PERMS as $label): ?><th class="textCenter"><?php echo e($label); ?></th><?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach (MODULES as $code => $name): ?>
                                <tr>
                                    <td><?php echo e($name); ?> <span class="user-sub">(<?php echo e($code); ?>)</span></td>
                                    <?php foreach (PERMS as $perm => $label): ?>
                                        <td class="textCenter">
                                            <input type="checkbox" aria-label="<?php echo e($label); ?>"
                                                   name="perm[<?php echo (int) $role['id']; ?>][<?php echo e($code); ?>][<?php echo e($perm); ?>]" value="1"
                                                   <?php echo !empty($matrix[$role['id']][$code][$perm]) ? 'checked' : ''; ?>>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Permissions</button>
            </div>
        </form>

    </div>
</div>
<?php require_once 'components/inc/footer.php'; ?>
