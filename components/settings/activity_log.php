<?php
$page_title = 'Activity Log';
require_once 'components/inc/header.php';

if (!is_super()) {
    deny_access();
}

$logs = get_activity_logs();
?>
<section class="page">
    <div class="page-head">
        <div class="page-title">
            <h2>Activity Log</h2>
            <span class="badge">Latest <?php echo count($logs); ?></span>
        </div>
    </div>

    <?php if (empty($logs)): ?>
        <div class="card empty">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <h3>No activity found</h3>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>User</th>
                            <th>Activity</th>
                            <th>Record</th>
                            <th>Changes</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td data-label="Date &amp; Time"><?php echo e(formatDate($log['created_at'])); ?></td>
                            <td data-label="User">
                                <div class="user-name"><?php echo e($log['user_name'] ?: 'System / Unknown'); ?></div>
                                <div class="user-sub"><?php echo e($log['branch_name'] ?: '—'); ?></div>
                            </td>
                            <td data-label="Activity">
                                <div class="user-name"><?php echo e($log['action']); ?></div>
                                <div class="user-sub"><?php echo e($log['table_name'] ?: '—'); ?></div>
                            </td>
                            <td data-label="Record"><?php echo $log['record_id'] === null ? '—' : (int) $log['record_id']; ?></td>
                            <td data-label="Changes">
                                <?php foreach (['Before' => $log['old_value'], 'After' => $log['new_value']] as $label => $value): ?>
                                    <?php if ($value !== null && $value !== ''): ?>
                                        <details>
                                            <summary><?php echo e($label); ?></summary>
                                            <pre><?php echo e($value); ?></pre>
                                        </details>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (empty($log['old_value']) && empty($log['new_value'])): ?>—<?php endif; ?>
                            </td>
                            <td data-label="IP Address"><?php echo e($log['ip_address'] ?: '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php require_once 'components/inc/footer.php'; ?>
