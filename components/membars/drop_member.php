<?php
$page_title = "Deactive Member";
require_once 'components/inc/header.php';
require_perm('M-MGMT', 'can_edit');

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'close') {
    if (!csrf_ok()) {
        $errors[] = 'Session expired. Please reload the page and try again.';
    } else {
        try {
            close_member((int) ($_POST['member_id'] ?? 0));
            $success = 'Member closed successfully.';
        } catch (PDOException $ex) {
            // trigger er message (Only the home branch...) o ekhane ashe
            error_log('close_member: ' . $ex->getMessage());
            $errors[] = 'Could not close this member. Only the home branch can close a member.';
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$view    = ($_GET['view'] ?? 'active') === 'closed' ? 'closed' : 'active';
$q       = trim((string) ($_GET['q'] ?? ''));
$result  = get_members(['q' => $q, 'status' => $view], (int) ($_GET['page'] ?? 1));
$rows    = $result['rows'];
$baseUrl = '?' . http_build_query(['view' => $view, 'q' => $q]);
?>
<section class="page">

    <div class="page-head noPrint">
        <div class="page-title">
            <h2>Deactive Member</h2>
            <span class="badge"><?php echo (int) $result['total']; ?> <?php echo e($view); ?></span>
        </div>
    </div>

    <?php render_alerts($success, $errors); ?>

    <div class="tabs noPrint">
        <button type="button" class="tab <?php echo $view === 'active' ? 'active' : ''; ?>" onclick="location.href='?view=active'">Active members</button>
        <button type="button" class="tab <?php echo $view === 'closed' ? 'active' : ''; ?>" onclick="location.href='?view=closed'">Closed members</button>
    </div>

    <form method="get" class="flex gap medel noPrint" style="margin-bottom:14px">
        <input type="hidden" name="view" value="<?php echo e($view); ?>">
        <div class="search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Name, member no, code, phone or NID" maxlength="100">
        </div>
        <button type="submit">Search</button>
    </form>

    <?php if (empty($rows)): ?>
        <div class="card empty"><i class="fa-solid fa-users"></i><h3>No member found</h3></div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr><th>Member</th><th>Branch</th><th>Contact</th><th>Status</th><?php if ($view === 'active'): ?><th class="noPrint">Action</th><?php endif; ?></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $m): ?>
                        <tr>
                            <td data-label="Member">
                                <div class="user-name"><?php echo e($m['full_name']); ?></div>
                                <div class="user-sub">Member No: <?php echo e($m['member_no']); ?></div>
                                <div class="user-sub">Code: <?php echo e($m['member_code']); ?></div>
                            </td>
                            <td data-label="Branch"><?php echo e($m['branch_name']); ?> <span class="user-sub">(<?php echo e($m['branch_code']); ?>)</span></td>
                            <td data-label="Contact"><?php echo e($m['phone']); ?></td>
                            <td data-label="Status"><?php echo status_badge($m['status']); ?></td>
                            <?php if ($view === 'active'): ?>
                                <td data-label="Action" class="noPrint">
                                    <?php if ((int) $m['home_branch_id'] === current_branch_id()): ?>
                                        <form method="post" onsubmit="return confirm('Close this member? This cannot be undone from here.')">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="close">
                                            <input type="hidden" name="member_id" value="<?php echo (int) $m['id']; ?>">
                                            <button type="submit"><i class="fa-solid fa-user-xmark"></i> Close</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="user-sub">Home branch only</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="flex medel beet mt noPrint">
                <span class="hint">Page <?php echo (int) $result['page']; ?> of <?php echo (int) $result['pages']; ?></span>
                <div class="flex gap">
                    <?php if ($result['page'] > 1): ?><a class="btn" href="<?php echo e($baseUrl . '&page=' . ($result['page'] - 1)); ?>">Previous</a><?php endif; ?>
                    <?php if ($result['page'] < $result['pages']): ?><a class="btn" href="<?php echo e($baseUrl . '&page=' . ($result['page'] + 1)); ?>">Next</a><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</section>
<?php require_once 'components/inc/footer.php'; ?>
