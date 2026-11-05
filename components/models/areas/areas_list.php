<?php
$page_title = "Area List";
require_once 'components/inc/header.php';
require_perm('AREA-MGMT', 'can_view');

$areas = get_area_info();
if (!is_super()) {
    $areas = array_values(array_filter($areas, fn($a) => (int) $a['branch_id'] === current_branch_id()));
}
?>
<section class="page">

    <div class="page-head noPrint">
        <div class="page-title">
            <h2>Area List - এলাকার তালিকা</h2>
            <span class="badge"><?php echo count($areas); ?> total</span>
        </div>

        <div class="page-actions">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search by area, code or branch...">
            </div>
            <?php if (user_can('AREA-MGMT', 'can_add')): ?>
                <button class="btn-primary" onclick="call('/create_areas')">
                    <i class="fa-solid fa-plus"></i> Create New Area
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($areas)): ?>
        <div class="card empty">
            <i class="fa-solid fa-map-location-dot"></i>
            <h3>No area found</h3>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr>
                            <th>SL &amp; ID</th>
                            <th>Area</th>
                            <th>Branch</th>
                            <th>Centers</th>
                            <th class="noPrint">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($areas as $key => $a): ?>
                        <tr>
                            <td data-label="SL &amp; ID"><?php echo $key + 1; ?> <span class="user-sub">(<?php echo e($a['id']); ?>)</span></td>
                            <td data-label="Area">
                                <div class="user-name"><?php echo e($a['name']); ?></div>
                                <div class="user-sub">Code: <?php echo e($a['code']); ?></div>
                            </td>
                            <td data-label="Branch">
                                <div class="user-name"><?php echo e($a['branch_name']); ?></div>
                                <div class="user-sub">Code: <?php echo e($a['branch_code']); ?></div>
                            </td>
                            <td data-label="Centers"><?php echo (int) $a['center_count']; ?></td>
                            <td data-label="Action" class="noPrint">
                                <?php if (user_can('AREA-MGMT', 'can_edit')): ?>
                                    <a class="btn" href="/edit_areas/<?php echo (int) $a['id']; ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</section>
<?php require_once 'components/inc/footer.php'; ?>
