<?php
$page_title = 'Closed Members';
require_once 'components/inc/header.php';

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$members = array_values(array_filter(get_member_info(), static function ($member) {
    return $member['status'] === 'closed';
}));
?>
<section class="page">
    <div class="page-head noPrint">
        <div class="page-title">
            <h2>Closed Members - বন্ধ সদস্য তালিকা</h2>
            <span class="badge"><?php echo count($members); ?> total</span>
        </div>
        <div class="page-actions">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" onkeyup="searchClosedMembers()"
                    placeholder="Search name, member code, phone, NID...">
            </div>
            <button class="btn" type="button" onclick="call('/drop_member')">
                <i class="fa-solid fa-user-xmark"></i> Deactivate Member
            </button>
        </div>
    </div>

    <?php if (empty($members)): ?>
        <div class="card empty">
            <i class="fa-solid fa-user-slash"></i>
            <h3>No closed members found</h3>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow">
                <table class="table--list">
                    <thead>
                        <tr>
                            <th>SL &amp; ID</th>
                            <th>Member</th>
                            <th>Contact</th>
                            <th>Branch / Center / Group</th>
                            <th>Status</th>
                            <th class="noPrint">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($members as $key => $member): ?>
                        <tr>
                            <td data-label="SL &amp; ID">
                                <?php echo $key + 1; ?>
                                <span class="user-sub">(<?php echo (int) $member['id']; ?>)</span>
                            </td>
                            <td data-label="Member">
                                <div class="user-name"><?php echo e($member['full_name']); ?></div>
                                <div class="user-sub">Code: <?php echo e($member['member_code']); ?></div>
                            </td>
                            <td data-label="Contact">
                                <div class="user-name"><?php echo e($member['phone'] ?: '—'); ?></div>
                                <div class="user-sub">NID: <?php echo e($member['nid_no'] ?: '—'); ?></div>
                            </td>
                            <td data-label="Branch / Center / Group">
                                <div class="user-name"><?php echo e($member['branch_name'] ?? '—'); ?></div>
                                <div class="user-sub">
                                    <?php echo e($member['center_name'] ?? '—'); ?>
                                    <?php if (!empty($member['group_name'])): ?>
                                        · <?php echo e($member['group_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Status"><span class="badge">Closed</span></td>
                            <td data-label="Action" class="noPrint">
                                <a class="btn" href="/member_profile/<?php echo (int) $member['id']; ?>">
                                    <i class="fa-regular fa-id-card"></i> Profile
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
function searchClosedMembers() {
    var query = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.table--list tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().indexOf(query) !== -1 ? '' : 'none';
    });
}
</script>

<?php require_once 'components/inc/footer.php'; ?>
