<?php
    $route = $route ?? '';

    // Cholte thaka page ke .active korar jonno
    $active = function (...$paths) use ($route) {
    foreach ($paths as $path) {
        if ($route === $path || strpos($route, $path . '/') === 0) {
            return 'active';
        }
    }
    return '';
    };
?>
<!-- Dashboard -->
<div class="sidebtn open">
    <main>
        <div class="flex gap medel">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </div>
    </main>
    <subbtn class="<?php echo $route === '' ? 'active' : ''; ?>" onclick="call('/')">
        <i class="fa-solid fa-house"></i> Home
    </subbtn>
</div>

<!-- Account -->
<div class="sidebtn">
    <main>
        <div class="flex gap medel">
            <i class="fa-regular fa-user"></i> Users
        </div>
    </main>
    <subbtn class="<?php echo $route === 'account' ? 'active' : ''; ?>" onclick="call('/account')">
        <i class="fa-regular fa-circle-user"></i> Login User
    </subbtn>
    <?php if (user_can('USER-MGMT', 'can_view')): ?>
    <subbtn class="<?php echo $active('admin_account', 'account/user'); ?>" onclick="call('/admin_account')">
        <i class="fa-solid fa-list-ul"></i> Users List
    </subbtn>
    <?php endif; ?>
    <?php if (user_can('USER-MGMT', 'can_add')): ?>
    <subbtn class="<?php echo $active('create_account'); ?>" onclick="call('/create_account')">
        <i class="fa-solid fa-user-plus"></i> New Create User
    </subbtn>
    <?php endif; ?>
</div>

<?php if (user_can('BRANCH-MGMT', 'can_view')): ?>
<!-- Branch -->
<div class="sidebtn">
    <main>
        <div class="flex gap medel">
            <i class="fa-solid fa-code-branch"></i> Branch
        </div>
    </main>
    <subbtn class="<?php echo $active('branch_list', 'edit_branch'); ?>" onclick="call('/branch_list')">
        <i class="fa-solid fa-list-ul"></i> Branch List
    </subbtn>
    <?php if (user_can('BRANCH-MGMT', 'can_add')): ?>
    <subbtn class="<?php echo $active('create_branch'); ?>" onclick="call('/create_branch')">
        <i class="fa-solid fa-plus"></i> New Create Branch
    </subbtn>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (user_can('AREA-MGMT', 'can_view') || user_can('CENTER-MGMT', 'can_view')): ?>
<!-- Areas & Center -->
<div class="sidebtn">
    <main>
        <div class="flex gap medel">
            <i class="fa-solid fa-map-location-dot"></i> Areas &amp; Center
        </div>
    </main>
    <?php if (user_can('AREA-MGMT', 'can_view')): ?>
    <subbtn class="<?php echo $active('areas_list', 'edit_areas'); ?>" onclick="call('/areas_list')">
        <i class="fa-solid fa-align-left"></i> Areas List
    </subbtn>
    <?php endif; ?>
    <?php if (user_can('CENTER-MGMT', 'can_view')): ?>
    <subbtn class="<?php echo $active('center_list', 'edit_center'); ?>" onclick="call('/center_list')">
        <i class="fa-solid fa-route"></i> Center List
    </subbtn>
    <?php endif; ?>
    <?php if (user_can('AREA-MGMT', 'can_add')): ?>
    <subbtn class="<?php echo $active('create_areas'); ?>" onclick="call('/create_areas')">
        <i class="fa-solid fa-plus"></i> New Create Areas
    </subbtn>
    <?php endif; ?>
    <?php if (user_can('CENTER-MGMT', 'can_add')): ?>
    <subbtn class="<?php echo $active('create_center'); ?>" onclick="call('/create_center')">
        <i class="fa-solid fa-plus"></i> New Create Center
    </subbtn>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (user_can('M-MGMT', 'can_view')): ?>
<!-- Member -->
<div class="sidebtn">
    <main>
        <div class="flex gap medel">
            <i class="fa-solid fa-people-group"></i> Member
        </div>
    </main>
    <subbtn class="<?php echo $active('member_group'); ?>" onclick="call('/member_group')">
        <i class="fa-solid fa-people-roof"></i> Member Group
    </subbtn>
    <subbtn class="<?php echo $active('members'); ?>" onclick="call('/members')">
        <i class="fa-solid fa-users"></i> All Member List
    </subbtn>
    <?php if (user_can('M-MGMT', 'can_add')): ?>
    <subbtn class="<?php echo $active('new_member'); ?>" onclick="call('/new_member')">
        <i class="fa-solid fa-user-plus"></i> New Member
    </subbtn>
    <?php endif; ?>
    <?php if (user_can('M-MGMT', 'can_edit')): ?>
    <subbtn class="<?php echo $active('drop_member'); ?>" onclick="call('/drop_member')">
        <i class="fa-solid fa-user-xmark"></i> Deactive Member
    </subbtn>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (user_can('ACC-MGMT', 'can_view')): ?>
<div class="sidebtn">
    <main>
        <div class="flex gap medel">
            <i class="fa-solid fa-user-gear"></i> Account Tools
        </div>
    </main>
    <subbtn class="<?php echo $active('account_types'); ?>" onclick="call('/account_types')">
        <i class="fa-solid fa-user-tag"></i> Account Types
    </subbtn>
    <subbtn class="<?php echo $active('savings_products'); ?>" onclick="call('/savings_products')">
       <i class="fa-solid fa-piggy-bank"></i> Savings Products
    </subbtn>
    <subbtn class="<?php echo $active('savings_accounts'); ?>" onclick="call('/savings_accounts')">
       <i class="fa-solid fa-sack-dollar"></i> Savings Account
    </subbtn>
</div>
<?php endif; ?>





<?php if (user_can('STAFF-MGMT', 'can_view')): ?>
<!-- Staff -->
<div class="sidebtn">
    <main>
        <div class="flex gap medel">
            <i class="fa-solid fa-user-tie"></i> Staff
        </div>
    </main>
    <subbtn class="<?php echo $active('staff_list', 'edit_staff'); ?>" onclick="call('/staff_list')">
        <i class="fa-solid fa-list-ul"></i> Staff List
    </subbtn>
    <?php if (user_can('STAFF-MGMT', 'can_add')): ?>
    <subbtn class="<?php echo $active('create_staff'); ?>" onclick="call('/create_staff')">
        <i class="fa-solid fa-user-plus"></i> New Create Staff
    </subbtn>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (is_super() || user_can('SETTINGS', 'can_view')): ?>
<!-- System -->
<div class="sidebtn">
    <main>
        <div class="flex gap medel">
            <i class="fa-solid fa-gear"></i> System
        </div>
    </main>
    <?php if (is_super()): ?>
    <subbtn class="<?php echo $active('roles'); ?>" onclick="call('/roles')">
        <i class="fa-solid fa-user-shield"></i> Roles &amp; Permissions
    </subbtn>
    <?php endif; ?>
    <?php if (user_can('SETTINGS', 'can_view')): ?>
    <subbtn class="<?php echo $active('settings'); ?>" onclick="call('/settings')">
        <i class="fa-solid fa-sliders"></i> Settings
    </subbtn>
    <?php endif; ?>
    <?php if (is_super()): ?>
    <subbtn class="<?php echo $active('activity_log'); ?>" onclick="call('/activity_log')">
        <i class="fa-solid fa-clock-rotate-left"></i> Activity Log
    </subbtn>
    <?php endif; ?>
</div>
<?php endif; ?>