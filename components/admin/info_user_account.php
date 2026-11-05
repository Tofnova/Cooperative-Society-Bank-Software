<?php
// Parent theke $user (get_user_info()) paoa jay.
$initial = mb_strtoupper(mb_substr($user['full_name'], 0, 1));

// [ section title, section icon, [ [icon, label, value, optional link] ... ] ]
$sections = [
    ['Account', 'fa-id-badge', [
        ['fa-at',          'Username', $user['username']],
        ['fa-user-shield', 'Role',     $user['role']],
    ]],
    ['Branch & Contact', 'fa-address-book', [
        ['fa-code-branch', 'Branch', $user['branch_name'] ? $user['branch_name'] . ' (' . $user['branch_code'] . ')' : ''],
        ['fa-phone',       'Phone',  $user['phone'], 'tel:' . $user['phone']],
    ]],
    ['Activity', 'fa-clock-rotate-left', [
        ['fa-right-to-bracket', 'Last Login', $user['last_login_at'] ? formatDate($user['last_login_at']) : ''],
        ['fa-calendar-plus',    'Created At', formatDate($user['created_at'])],
        ['fa-pen-to-square',    'Updated At', formatDate($user['updated_at'])],
    ]],
];
?>
<div class="card profile">
    <div class="profile-cover"></div>
    <div class="profile-body">
        <div class="avatar avatar--lg avatar--fallback"><?php echo e($initial); ?></div>

        <div class="profile-id">
            <div class="profile-name"><?php echo e($user['full_name']); ?></div>
            <div class="profile-name-bn">@<?php echo e($user['username']); ?></div>
            <div class="profile-badges">
                <span class="badge"><?php echo e($user['role']); ?></span>
                <?php echo status_badge($user['status']); ?>
            </div>
        </div>
    </div>
</div>

<div class="grid">
    <?php foreach ($sections as [$title, $titleIcon, $items]): ?>
        <div class="card card--pad">
            <h3 class="card-title"><i class="fa-solid <?php echo e($titleIcon); ?>"></i> <?php echo e($title); ?></h3>

            <?php foreach ($items as $item):
                [$icon, $label, $value] = $item;
                $href  = $item[3] ?? null;
                $value = ($value === null || $value === '') ? '—' : $value;
            ?>
                <div class="info-item">
                    <div class="icon-box"><i class="fa-solid <?php echo e($icon); ?>"></i></div>
                    <div class="kv">
                        <span><?php echo e($label); ?></span>
                        <b>
                            <?php if ($href && $value !== '—'): ?>
                                <a href="<?php echo e($href); ?>"><?php echo e($value); ?></a>
                            <?php else: ?>
                                <?php echo e($value); ?>
                            <?php endif; ?>
                        </b>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
