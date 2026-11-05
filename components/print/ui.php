<div class="printModiul flex beet medel">
    <div>
        <h1><?php echo e($app_name ?? ''); ?></h1>
        <p><?php echo e($page_title ?? ''); ?></p>
    </div>
    <div>
        <p>Printed by: <?php echo e($me['full_name'] ?? ''); ?></p>
        <p><?php echo date('d M Y, h:i A'); ?></p>
    </div>
</div>
