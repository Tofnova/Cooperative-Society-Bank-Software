<?php
$member_id = isset($id) ? (int) $id : 0;
$page_title = $member_id > 0 ? 'Member Profile' : 'Member Profiles';
require_once 'components/inc/header.php';

$member = $member_id > 0 ? get_member_info($member_id) : null;

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if ($member_id > 0 && !$member) {
    http_response_code(404);
    require_once '404.php';
    require_once 'components/inc/footer.php';
    return;
}

if ($member_id === 0) {
    $members = get_member_info();
    ?>
<section class="page">
    <div class="page-head noPrint">
        <div class="page-title">
            <h2>Member Profiles - সদস্য প্রোফাইল</h2>
            <span class="badge"><?php echo count($members); ?> total</span>
        </div>
        <div class="page-actions">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" onkeyup="searchMemberProfiles()"
                    placeholder="Search member name, no, code, phone...">
            </div>
        </div>
    </div>
    <?php if (empty($members)): ?>
    <div class="card empty">
        <i class="fa-solid fa-users"></i>
        <h3>No member found</h3>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="overflow">
            <table class="table--list">
                <thead>
                    <tr>
                        <th>SL &amp; ID</th>
                        <th>Member</th>
                        <th>Phone</th>
                        <th>Branch / Center</th>
                        <th>Status</th>
                        <th class="noPrint">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $key => $row): ?>
                    <tr>
                        <td data-label="SL &amp; ID"><?php echo $key + 1; ?> <span
                                class="user-sub">(<?php echo (int) $row['id']; ?>)</span></td>
                        <td data-label="Member">
                            <div class="user-name"><?php echo e($row['full_name']); ?></div>
                            <div class="user-sub">Member No: <?php echo e($row['member_no']); ?></div>
                            <div class="user-sub">Code: <?php echo e($row['member_code']); ?></div>
                        </td>
                        <td data-label="Phone"><?php echo e($row['phone'] ?: '—'); ?></td>
                        <td data-label="Branch / Center">
                            <?php echo e($row['branch_name'] ?? '—'); ?>
                            <div class="user-sub"><?php echo e($row['center_name'] ?? '—'); ?></div>
                        </td>
                        <td data-label="Status"><span class="badge"><?php echo e(ucfirst($row['status'])); ?></span>
                        </td>
                        <td data-label="Action" class="noPrint">
                            <a class="btn" href="/member_profile/<?php echo (int) $row['id']; ?>">
                                <i class="fa-regular fa-id-card"></i> View Profile
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
function searchMemberProfiles() {
    var query = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.table--list tbody tr').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().indexOf(query) !== -1 ? '' : 'none';
    });
}
</script>
<?php
    require_once 'components/inc/footer.php';
    return;
}

$nomineeStatement = $conn->prepare(
    'SELECT name, relation, nid_no, phone, share_percent
     FROM member_nominees WHERE member_id = ? ORDER BY id'
);
$nomineeStatement->execute([$member_id]);
$nominees = $nomineeStatement->fetchAll(PDO::FETCH_ASSOC);
$documents = get_member_documents($member_id);
?>
<section class="page page--narrow">
    <div class="page-head">
        <div class="page-title">
            <h2 class="padding">Member Profile - সদস্য প্রোফাইল</h2>
            <span class="badge"><?php echo e(ucfirst($member['status'])); ?></span>
        </div>
        <div class="page-actions">
            <button class="btn" type="button" onclick="backHistory()">
                <i class="fa-solid fa-arrow-left"></i> All Profiles
            </button>
            <?php if (user_can('M-MGMT', 'can_edit') && (int) $member['home_branch_id'] === current_branch_id()): ?>
            <button class="btn-primary" onclick="call('edit/<?php echo $member_id; ?>')">
                <i class="fa-regular fa-pen-to-square"></i> Edit Member
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card--pad">
        <h3 class="card-title">Member Information</h3>
        <div class="form-grid active padding">
            <?php foreach ([
                'Member No' => $member['member_no'],
                'Member Code' => $member['member_code'],
                'Full Name' => $member['full_name'],
                "Father's Name" => $member['father_name'],
                "Mother's Name" => $member['mother_name'],
                "Spouse's Name" => $member['spouse_name'],
                'Birth Date' => $member['birth_date'],
                'Gender' => $member['gender'],
                'NID Number' => $member['nid_no'],
                'Phone' => $member['phone'],
                'Home Branch' => $member['branch_name'],
                'Center' => $member['center_name'],
                'Group' => $member['group_name'],
                'Joined On' => $member['joined_on'],
                'Status' => ucfirst($member['status']),
                'Present Address' => $member['present_address'],
                'Permanent Address' => $member['permanent_address'],
            ] as $label => $value): ?>
            <div class="field">
                <label><?php echo e($label); ?></label>
                <div class="user-name"><?php echo e($value ?: '—'); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <h3 class="padding">Nominees</h3>
    <?php if (empty($nominees)): ?>
    <div class="card empty">
        <h3>No nominee information</h3>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="overflow">
            <table class="table--list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Relation</th>
                        <th>NID</th>
                        <th>Phone</th>
                        <th>Share %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nominees as $nominee): ?>
                    <tr>
                        <td><?php echo e($nominee['name']); ?></td>
                        <td><?php echo e($nominee['relation'] ?: '—'); ?></td>
                        <td><?php echo e($nominee['nid_no'] ?: '—'); ?></td>
                        <td><?php echo e($nominee['phone'] ?: '—'); ?></td>
                        <td><?php echo e($nominee['share_percent'] ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <h3 class="padding">Documents</h3>
    <?php if (empty($documents)): ?>
    <div class="card empty">
        <h3>No documents uploaded</h3>
        <a class="btn" href="edit/<?php echo $member_id; ?>">Add Documents</a>
    </div>
    <?php else: ?>
    <table class="table--list">
        <thead>
            <tr>
                <th>Document Type</th>
                <th>Preview</th>
                <th>Action</th>
            </tr>
        </thead>

        <?php foreach ($documents as $document): ?>
        <?php $url = '/member_document/' . (int) $document['id']; ?>

        <tr>
            <td>
            <b >
                <?php echo e($document['doc_type']); ?>
            </b>    
            <br>
                <span class="badge--gray">
                    <?php echo e($document['created_at']); ?>
                </span>
            </td>

            <td>
                <button type="button" class="btn" onclick="previewDocument('<?php echo e($url); ?>')">
                    <i class="fa-regular fa-eye"></i>
                    Preview
                </button>
            </td>

            <td>
                <a class="btn" href="<?php echo e($url); ?>?download=1">
                    <i class="fa-solid fa-download"></i>
                    Download
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Preview Modal -->
    <div id="documentModal" style="display:none; position:fixed; inset:0; z-index:99999;
            background:rgba(0,0,0,.8); padding:15px;">

        <div style="position:relative; width:100%; max-width:1000px;
                height:90vh; margin:auto; background:white;">

            <button type="button" onclick="closeDocumentPreview()"
                style="position:absolute; top:8px; right:8px; z-index:2;">
                Close ✕
            </button>

            <iframe id="documentFrame" src="about:blank" style="width:100%; height:100%; border:0;">
            </iframe>
        </div>
    </div>

    <script>
    function previewDocument(url) {
        document.getElementById('documentFrame').src = url;
        document.getElementById('documentModal').style.display = 'block';
    }

    function closeDocumentPreview() {
        document.getElementById('documentModal').style.display = 'none';
        document.getElementById('documentFrame').src = 'about:blank';
    }
    </script>




    <?php endif; ?>
</section>

<?php require_once 'components/inc/footer.php'; ?>