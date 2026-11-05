<?php
/* =====================================================================
   function.php — shob helper, login, permission ar database function
   Schema: গ্রাম বাংলা সঞ্চয় ও ঋণদান সমবায় সমিতি (branches, roles, role_permissions, users, staff, settings, id_sequences)
   ===================================================================== */


/* ---------------------------------------------------------------------
   0. mbstring na thakle fallback (shared hosting e extension off thakte pare)
   --------------------------------------------------------------------- */
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null)
    {
        $n = preg_match_all('/./us', (string) $string);
        return $n === false ? strlen((string) $string) : $n;
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null)
    {
        $chars = preg_split('//u', (string) $string, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return implode('', array_slice($chars, $start, $length));
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($string, $encoding = null)
    {
        return strtoupper((string) $string);   // Bangla te upper/lower nai; shudhu English akkhor
    }
}

const MEETING_DAYS = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

/* ---------------------------------------------------------------------
   1. DB shortcut (PDO $conn)
   --------------------------------------------------------------------- */
function db_one(string $sql, array $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function db_all(string $sql, array $params = []): array
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db_val(string $sql, array $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function db_exec(string $sql, array $params = []): int
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function db_last_id(): int
{
    global $conn;
    return (int) $conn->lastInsertId();
}

function db_tx(callable $fn)
{
    global $conn;
    $conn->beginTransaction();
    try {
        $result = $fn();
        $conn->commit();
        return $result;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

/* ---------------------------------------------------------------------
   2. Small helpers
   --------------------------------------------------------------------- */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDate($date)
{
    return $date ? date("d M y g:i A", strtotime($date)) : '—';
}

function formatDay($date)
{
    return $date ? date('d M Y', strtotime($date)) : '—';
}

function money($amount)
{
    return '৳ ' . number_format((float) $amount, 2);
}

function need($condition, string $message): void
{
    if (!$condition) {
        throw new Exception($message);
    }
}

function len_ok(string $text, int $min, int $max): bool
{
    $len = mb_strlen($text);
    return $len >= $min && $len <= $max;
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function valid_phone(string $phone): bool
{
    return (bool) preg_match('/^[0-9]{11}$/', $phone);
}

function valid_nid(string $nid): bool
{
    return ctype_digit($nid) && in_array(strlen($nid), [10, 13, 17], true);
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function posted(array $fields): array
{
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    return $data;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    return hash_equals(csrf_token(), (string) ($_POST['csrf_token'] ?? ''));
}

function row_exists(string $table, $id): bool
{
    $allowed = ['branches', 'areas', 'centers', 'staff', 'users', 'roles', 'members'];
    need(in_array($table, $allowed, true), 'Invalid table.');
    return (bool) db_val("SELECT id FROM `{$table}` WHERE id = ? LIMIT 1", [$id]);
}

function get_setting(string $key, $default = '')
{
    static $all = null;
    if ($all === null) {
        $all = [];
        try {
            foreach (db_all("SELECT setting_key, setting_value FROM settings") as $row) {
                $all[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            error_log('get_setting: ' . $e->getMessage());
        }
    }
    return $all[$key] ?? $default;
}

/* ---------------------------------------------------------------------
   3. View helpers (alert, status badge, form field)
   --------------------------------------------------------------------- */
function render_alerts(?string $success, array $errors): void
{
    if ($success) {
        echo '<div class="alert alert--success"><i class="fa-solid fa-circle-check"></i><div>' . e($success) . '</div></div>';
    }
    if ($errors) {
        echo '<div class="alert alert--error"><i class="fa-solid fa-circle-exclamation"></i><div>';
        foreach ($errors as $error) {
            echo '<div>' . e($error) . '</div>';
        }
        echo '</div></div>';
    }
}

function status_badge(?string $status): string
{
    $map = [
        'active'   => 'active',
        'inactive' => 'inactive',
        'pending'  => 'inactive',
        'locked'   => 'blocked',
        'blocked'  => 'blocked',
        'closed'   => 'blocked',
        'resigned' => 'blocked',
    ];
    $class = $map[$status ?? ''] ?? 'inactive';
    return '<span class="status status--' . $class . '">' . e(ucfirst((string) $status)) . '</span>';
}

function f_input(string $name, string $label, $value = '', array $o = []): void
{
    $id    = $o['id'] ?? 'f_' . $name;
    $attrs = '';
    foreach (['type' => 'text', 'maxlength' => null, 'pattern' => null, 'placeholder' => null, 'title' => null,
              'inputmode' => null, 'min' => null, 'max' => null, 'step' => null, 'autocomplete' => null] as $key => $default) {
        $val = $o[$key] ?? $default;
        if ($val !== null) {
            $attrs .= ' ' . $key . '="' . e($val) . '"';
        }
    }
    foreach (['required', 'disabled'] as $flag) {
        if (!empty($o[$flag])) {
            $attrs .= ' ' . $flag;
        }
    }
    if (!empty($o['digits'])) {
        $attrs .= ' data-digits';
    }
    $nameAttr = empty($o['disabled']) ? ' name="' . e($name) . '"' : '';

    echo '<div class="field"><label for="' . e($id) . '">' . e($label) . '</label>';
    echo '<input id="' . e($id) . '"' . $nameAttr . $attrs . ' value="' . e($value) . '">';
    if (!empty($o['hint'])) {
        echo '<span class="hint">' . e($o['hint']) . '</span>';
    }
    echo '</div>';
}

/**
 * $options = value => label   (label array hole: ['label' => '..', 'data' => ['branch' => 1]])
 * $o: required, disabled, placeholder (disabled), empty (enabled khali option), hint, attrs (trusted raw)
 */
function f_select(string $name, string $label, array $options, $selected = '', array $o = []): void
{
    $id       = $o['id'] ?? 'f_' . $name;
    $selected = (string) $selected;
    $attrs    = !empty($o['required']) ? ' required' : '';
    $attrs   .= !empty($o['disabled']) ? ' disabled' : '';
    $attrs   .= !empty($o['attrs']) ? ' ' . $o['attrs'] : '';
    $nameAttr = empty($o['disabled']) ? ' name="' . e($name) . '"' : '';

    echo '<div class="field"><label for="' . e($id) . '">' . e($label) . '</label>';
    echo '<select id="' . e($id) . '"' . $nameAttr . $attrs . '>';
    if (isset($o['placeholder'])) {
        echo '<option value="" disabled' . ($selected === '' ? ' selected' : '') . '>' . e($o['placeholder']) . '</option>';
    }
    if (isset($o['empty'])) {
        echo '<option value=""' . ($selected === '' ? ' selected' : '') . '>' . e($o['empty']) . '</option>';
    }
    foreach ($options as $value => $text) {
        $data = '';
        if (is_array($text)) {
            foreach (($text['data'] ?? []) as $k => $v) {
                $data .= ' data-' . e($k) . '="' . e($v) . '"';
            }
            $text = $text['label'];
        }
        echo '<option value="' . e($value) . '"' . $data . ((string) $value === $selected ? ' selected' : '') . '>' . e($text) . '</option>';
    }
    echo '</select>';
    if (!empty($o['hint'])) {
        echo '<span class="hint">' . e($o['hint']) . '</span>';
    }
    echo '</div>';
}

/* ---------------------------------------------------------------------
   4. Session / Login / Permission
   --------------------------------------------------------------------- */
function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function current_branch_id(): int
{
    return (int) ($_SESSION['branch_id'] ?? 0);
}

function is_super(): bool
{
    return ($_SESSION['role'] ?? '') === 'super_admin';
}

// Trigger er jonno: home branch chara member edit/close nishiddho (super_admin bad)
function apply_branch_context(): void
{
    global $conn;
    if (!is_super() && current_branch_id() > 0) {
        $conn->exec('SET @current_branch_id = ' . current_branch_id());
    }
}

// Audit trail (activity_log) — fail hole app thambe na, shudhu error_log e jabe
function log_activity(string $action, ?string $table = null, ?int $record_id = null, $old = null, $new = null, ?int $user_id = null): void
{
    try {
        db_exec(
            "INSERT INTO activity_log (user_id, branch_id, action, table_name, record_id, old_value, new_value, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $user_id ?? (current_user_id() ?: null),
                current_branch_id() > 0 ? current_branch_id() : null,
                $action,
                $table,
                $record_id,
                $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
                $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
                client_ip(),
            ]
        );
    } catch (Throwable $e) {
        error_log('log_activity: ' . $e->getMessage());
    }
}

function get_activity_logs(int $limit = 100): array
{
    $limit = max(1, min($limit, 500));

    return db_all(
        "SELECT
            al.id,
            al.created_at,
            al.action,
            al.table_name,
            al.record_id,
            al.old_value,
            al.new_value,
            al.ip_address,
            u.full_name AS user_name,
            b.name AS branch_name
         FROM activity_log al
         LEFT JOIN users u ON u.id = al.user_id
         LEFT JOIN branches b ON b.id = al.branch_id
         ORDER BY al.id DESC
         LIMIT {$limit}"
    );
}

/* ---- Login throttle (file-based, DB table lagbe na) ---- */
function throttle_file(string $key): string
{
    return sys_get_temp_dir() . '/coop_login_' . hash('sha256', $key) . '.json';
}

function throttle_count(string $key, int $window = 900): int
{
    $file = throttle_file($key);
    $list = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    $list = array_filter(is_array($list) ? $list : [], fn($t) => $t > time() - $window);
    return count($list);
}

function throttle_hit(string $key, int $window = 900): void
{
    $file = throttle_file($key);
    $list = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    $list = array_values(array_filter(is_array($list) ? $list : [], fn($t) => $t > time() - $window));
    $list[] = time();
    @file_put_contents($file, json_encode($list), LOCK_EX);
}

function throttle_clear(string $key): void
{
    @unlink(throttle_file($key));
}

function login(array $data): array
{
    $username = strtolower(trim($data['username'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $fail     = fn(string $m) => ['success' => false, 'message' => $m];

    if ($username === '' || $password === '') {
        return $fail('Username and password are required.');
    }

    // Brute-force guard: ek IP theke 15 minute e 10 bar, ek username e 5 bar bhul hole bondho
    if (throttle_count('ip:' . client_ip()) >= 10) {
        return $fail('Too many failed attempts. Please try again after 15 minutes.');
    }
    if (throttle_count('user:' . $username) >= 5) {
        return $fail('Too many failed attempts for this account. Please try again after 15 minutes.');
    }

    $user = db_one(
        "SELECT u.id, u.username, u.password_hash, u.status, u.branch_id, u.full_name, r.name AS role
         FROM users u LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.username = ? LIMIT 1",
        [$username]
    );

    // User na thakleo hash verify kori (timing diye username bojha jate na pare)
    $dummy = '$2y$10$usesomesillystringforsaltuYkS3jD6a1Wq0vQ2q0m9wJQ8yQ7bS';
    $ok    = password_verify($password, $user['password_hash'] ?? $dummy);

    if (!$user || !$ok) {
        throttle_hit('ip:' . client_ip());
        throttle_hit('user:' . $username);
        log_activity('login_failed', 'users', $user ? (int) $user['id'] : null, null, null, $user ? (int) $user['id'] : null);
        return $fail('Invalid username or password.');
    }

    if ($user['status'] !== 'active') {
        return $fail('Your account is not active. Please contact the administrator.');
    }

    throttle_clear('user:' . $username);
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['branch_id'] = $user['branch_id'];
    $_SESSION['full_name'] = $user['full_name'];

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db_exec("UPDATE users SET password_hash = ? WHERE id = ?", [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    db_exec("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);
    log_activity('login', 'users', (int) $user['id']);

    return ['success' => true, 'message' => 'Login successful.'];
}

function user_can(string $module, string $perm = 'can_view'): bool
{
    static $cache = [];
    if (is_super()) {
        return true;
    }
    $allowed = ['can_view', 'can_add', 'can_edit', 'can_delete', 'can_approve'];
    if (!in_array($perm, $allowed, true)) {
        return false;
    }
    $role = (string) ($_SESSION['role'] ?? '');
    if (!isset($cache[$role])) {
        $cache[$role] = [];
        foreach (db_all(
            "SELECT rp.* FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.name = ?",
            [$role]
        ) as $row) {
            $cache[$role][$row['module_code']] = $row;
        }
    }
    return !empty($cache[$role][$module][$perm]);
}

function deny_access(): void
{
    echo '<div class="flex center medel w100 padding"><div class="box textCenter">
            <i class="fa-solid fa-ban" style="font-size:64px;color:var(--red)"></i>
            <h1>This page is not allowed!</h1>
            <p>You don\'t have permission to access this page.</p>
          </div></div>';
    require_once __DIR__ . '/../components/inc/footer.php';
    exit;
}

function require_perm(string $module, string $perm = 'can_view'): void
{
    if (!user_can($module, $perm)) {
        deny_access();
    }
}

/* ---------------------------------------------------------------------
   5. Sequence / code generator (id_sequences)
   --------------------------------------------------------------------- */
function next_sequence(string $prefix, ?int $branch_id = null): int
{
    $row = db_one("SELECT id, last_no FROM id_sequences WHERE prefix = ? AND branch_id <=> ? FOR UPDATE", [$prefix, $branch_id]);
    if ($row) {
        $n = (int) $row['last_no'] + 1;
        db_exec("UPDATE id_sequences SET last_no = ? WHERE id = ?", [$n, $row['id']]);
        return $n;
    }
    db_exec("INSERT INTO id_sequences (prefix, branch_id, last_no) VALUES (?, ?, 1)", [$prefix, $branch_id]);
    return 1;
}

// Transaction er bhitore chalate hobe
// Transaction er bhitore chalate hobe
function new_code(string $table): string
{
    $max  = (int) db_val("SELECT MAX(CAST(code AS UNSIGNED)) FROM `{$table}` WHERE code REGEXP '^[0-9]{3}$'");
    $next = max(101, $max + 1);
    need($next <= 999, 'Code limit reached. Three-digit codes are limited to 101 through 999.');
    return (string) $next;
}

/* ---------------------------------------------------------------------
   6. Users (login account)
   --------------------------------------------------------------------- */
const USER_COLUMNS = "u.id, u.branch_id, u.role_id, u.username, u.full_name, u.phone, u.status, u.last_login_at, u.created_at, u.updated_at,
                      r.name AS role, r.description AS role_description, b.name AS branch_name, b.code AS branch_code";

function get_roles(): array
{
    return db_all("SELECT id, name, description FROM roles ORDER BY id");
}

function get_user_info($id = null)
{
    $sql = "SELECT " . USER_COLUMNS . " FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN branches b ON b.id = u.branch_id";
    return $id ? db_one($sql . " WHERE u.id = ? LIMIT 1", [$id]) : db_all($sql . " ORDER BY u.id");
}

function check_single_manager(?string $roleName, $branch_id, ?int $excludeUserId): void
{
    if ($roleName === 'manager' && $branch_id) {
        need(!db_val(
            "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.name = 'manager' AND u.branch_id = ? AND u.status <> 'inactive' AND u.id <> ? LIMIT 1",
            [$branch_id, (int) $excludeUserId]
        ), 'An active manager already exists for this branch.');
    }
}

function create_user(array $d): int
{
    $username  = strtolower(trim($d['username'] ?? ''));
    $full_name = trim($d['full_name'] ?? '');
    $phone     = trim($d['phone'] ?? '');
    $role_id   = trim($d['role_id'] ?? '');
    $branch_id = trim($d['branch_id'] ?? '');
    $password  = (string) ($d['password'] ?? '');
    $confirm   = (string) ($d['confirm_password'] ?? '');
    $pin       = trim($d['pin'] ?? '');
    $status    = trim($d['status'] ?? 'active');

    need($username !== '' && $full_name !== '' && $phone !== '' && $role_id !== '' && $password !== '', 'Please fill in all the required fields.');
    need(preg_match('/^[a-z0-9_.]{3,50}$/', $username), 'Username must be 3 to 50 characters (a-z, 0-9, _ and .).');
    need(len_ok($full_name, 2, 100), 'Full name must be 2 to 100 characters.');
    need(valid_phone($phone), 'Phone number must be 11 digits.');
    need(ctype_digit($role_id) && row_exists('roles', $role_id), 'Invalid role.');
    $role = (string) db_val("SELECT name FROM roles WHERE id = ?", [$role_id]);

    if ($branch_id === '') {
        need($role === 'super_admin', 'Branch is required for this role.');
        $branch_id = null;
    } else {
        need(ctype_digit($branch_id) && row_exists('branches', $branch_id), 'Invalid branch.');
    }

    need(is_super() || $role !== 'super_admin', 'Only a super admin can create a super admin account.');
    need(is_super() || (int) $branch_id === current_branch_id(), 'You can only create accounts for your own branch.');
    need(strlen($password) >= 8 && strlen($password) <= 72, 'Password must be 8 to 72 characters.');
    need($password === $confirm, 'Password and confirm password do not match.');
    need($pin === '' || preg_match('/^[0-9]{4,6}$/', $pin), 'PIN must be 4 to 6 digits.');
    need(in_array($status, ['active', 'inactive'], true), 'Invalid status.');
    need(!db_val("SELECT id FROM users WHERE username = ? LIMIT 1", [$username]), 'This username is already in use.');
    check_single_manager($role, $branch_id, null);

    db_exec(
        "INSERT INTO users (branch_id, role_id, username, password_hash, pin_hash, full_name, phone, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$branch_id, $role_id, $username, password_hash($password, PASSWORD_DEFAULT),
         $pin === '' ? null : password_hash($pin, PASSWORD_DEFAULT), $full_name, $phone, $status]
    );
    $id = db_last_id();
    log_activity('create', 'users', $id, null, ['username' => $username, 'role' => $role, 'branch_id' => $branch_id]);
    return $id;
}

function update_user_info(int $id, array $d): bool
{
    $current = get_user_info($id);
    need($current, 'Account not found.');

    $new = [
        'full_name' => trim($d['full_name'] ?? $current['full_name']),
        'phone'     => trim($d['phone'] ?? $current['phone']),
        'role_id'   => trim((string) ($d['role_id'] ?? $current['role_id'])),
        'branch_id' => array_key_exists('branch_id', $d) ? trim((string) $d['branch_id']) : (string) $current['branch_id'],
        'status'    => trim($d['status'] ?? $current['status']),
    ];

    need(len_ok($new['full_name'], 2, 100), 'Full name must be 2 to 100 characters.');
    need(valid_phone($new['phone']), 'Phone number must be 11 digits.');
    need(ctype_digit($new['role_id']) && row_exists('roles', $new['role_id']), 'Invalid role.');
    need(in_array($new['status'], ['active', 'inactive', 'locked'], true), 'Invalid status.');
    $role = (string) db_val("SELECT name FROM roles WHERE id = ?", [$new['role_id']]);

    if ($new['branch_id'] === '') {
        need($role === 'super_admin', 'Branch is required for this role.');
        $new['branch_id'] = null;
    } else {
        need(ctype_digit($new['branch_id']) && row_exists('branches', $new['branch_id']), 'Invalid branch.');
    }

    need(is_super() || ((int) $current['branch_id'] === current_branch_id() && $current['role'] !== 'super_admin'), 'You can only manage accounts of your own branch.');
    need(is_super() || ($role !== 'super_admin' && (int) $new['branch_id'] === current_branch_id()), 'You cannot assign this role or branch.');

    $roleChanged   = (int) $new['role_id'] !== (int) $current['role_id'];
    $statusChanged = $new['status'] !== $current['status'];

    // Nijeke lock kora jabe na
    need(!($id === current_user_id() && ($roleChanged || $statusChanged)), 'You cannot change your own role or status.');

    // Shesh super_admin ke nambano/bondho kora jabe na
    if ($current['role'] === 'super_admin' && ($role !== 'super_admin' || $new['status'] !== 'active')) {
        need(db_val(
            "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.name = 'super_admin' AND u.status = 'active' AND u.id <> ? LIMIT 1",
            [$id]
        ), 'At least one active super admin must remain.');
    }

    if ($new['status'] !== 'inactive') {
        check_single_manager($role, $new['branch_id'], $id);
    }

    db_exec(
        "UPDATE users SET full_name = ?, phone = ?, role_id = ?, branch_id = ?, status = ? WHERE id = ?",
        [$new['full_name'], $new['phone'], $new['role_id'], $new['branch_id'], $new['status'], $id]
    );
    log_activity('update', 'users', $id,
        ['full_name' => $current['full_name'], 'role_id' => $current['role_id'], 'branch_id' => $current['branch_id'], 'status' => $current['status']], $new);
    return true;
}

function update_user_password(int $id, string $password_hash): bool
{
    need($id > 0 && password_get_info($password_hash)['algo'] !== null, 'Invalid password hash.');
    $target = get_user_info($id);
    need($target, 'Account not found.');
    need(is_super() || ((int) $target['branch_id'] === current_branch_id() && $target['role'] !== 'super_admin'), 'You can only manage accounts of your own branch.');
    db_exec("UPDATE users SET password_hash = ? WHERE id = ?", [$password_hash, $id]);
    log_activity('password_reset', 'users', $id);
    return true;
}

function changePassword(int $user_id, string $old_password, string $new_password, string $confirm_password): array
{
    if ($new_password !== $confirm_password) {
        return ['success' => false, 'message' => 'New password and confirm password do not match.'];
    }
    if (strlen($new_password) < 8 || strlen($new_password) > 72) {
        return ['success' => false, 'message' => 'Password must be 8 to 72 characters.'];
    }
    $hash = db_val("SELECT password_hash FROM users WHERE id = ? LIMIT 1", [$user_id]);
    if (!$hash) {
        return ['success' => false, 'message' => 'Account not found.'];
    }
    if (!password_verify($old_password, $hash)) {
        return ['success' => false, 'message' => 'Old password is incorrect.'];
    }
    db_exec("UPDATE users SET password_hash = ? WHERE id = ?", [password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
    log_activity('password_change', 'users', $user_id);
    return ['success' => true, 'message' => 'Password changed successfully.'];
}

/* ---------------------------------------------------------------------
   7. Branches
   --------------------------------------------------------------------- */
function get_branch_info($id = null)
{
    $sql = "SELECT b.*,
                   (SELECT COUNT(*) FROM users u WHERE u.branch_id = b.id AND u.status = 'active')  AS user_count,
                   (SELECT COUNT(*) FROM staff s WHERE s.branch_id = b.id AND s.status = 'active')  AS staff_count,
                   (SELECT COUNT(*) FROM areas a WHERE a.branch_id = b.id)                           AS area_count,
                   (SELECT COUNT(*) FROM centers c WHERE c.branch_id = b.id)                         AS center_count,
                   (SELECT COUNT(*) FROM members m WHERE m.home_branch_id = b.id AND m.status <> 'closed') AS member_count
            FROM branches b";
    return $id ? db_one($sql . " WHERE b.id = ? LIMIT 1", [$id]) : db_all($sql . " ORDER BY b.is_main DESC, b.id");
}

function branch_clean(array $d, int $ignoreId = 0): array
{
    $row = [
        'name'      => trim($d['name'] ?? ''),
        'is_main'   => trim((string) ($d['is_main'] ?? '')),
        'address'   => trim($d['address'] ?? ''),
        'phone'     => trim($d['phone'] ?? ''),
        'opened_on' => trim($d['opened_on'] ?? ''),
        'status'    => trim($d['status'] ?? 'active'),
    ];
    need(!in_array('', array_diff_key($row, ['status' => 1]), true), 'Please fill in all the required fields.');
    need(len_ok($row['name'], 2, 100), 'Branch name must be 2 to 100 characters.');
    need(len_ok($row['address'], 2, 100), 'Address must be 2 to 100 characters.');
    need(valid_phone($row['phone']), 'Phone number must be 11 digits.');
    need(valid_date($row['opened_on']), 'Invalid opening date.');
    need(in_array($row['is_main'], ['0', '1'], true), 'Invalid branch type.');
    need(in_array($row['status'], ['active', 'inactive'], true), 'Invalid status.');
    need(!db_val("SELECT id FROM branches WHERE name = ? AND id <> ? LIMIT 1", [$row['name'], $ignoreId]), 'A branch with this name already exists.');
    if ($row['is_main'] === '1') {
        need(!db_val("SELECT id FROM branches WHERE is_main = 1 AND id <> ? LIMIT 1", [$ignoreId]), 'A main branch already exists.');
    }
    $row['is_main'] = (int) $row['is_main'];
    return $row;
}

function create_branch(array $d): int
{
    $r = branch_clean($d);
    return db_tx(function () use ($r) {
        $code = new_code('branches');
        $statusChangedBy = current_user_id() ?: null;
        db_exec(
            "INSERT INTO branches (code, name, is_main, address, phone, opened_on, status, status_changed_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$code, $r['name'], $r['is_main'], $r['address'], $r['phone'], $r['opened_on'], $r['status'], $statusChangedBy]
        );
        $id = db_last_id();
        log_activity('create', 'branches', $id, null, ['code' => $code, 'status_changed_by' => $statusChangedBy] + $r);
        return $id;
    });
}

function update_branch(int $id, array $d): bool
{
    $old = db_one("SELECT code FROM branches WHERE id = ?", [$id]);
    need($old, 'Branch not found.');
    $r = branch_clean($d, $id);
    $statusChangedBy = current_user_id() ?: null;
    db_exec(
        "UPDATE branches SET name = ?, is_main = ?, address = ?, phone = ?, opened_on = ?, status = ?, status_changed_by = ? WHERE id = ?",
        [$r['name'], $r['is_main'], $r['address'], $r['phone'], $r['opened_on'], $r['status'], $statusChangedBy, $id]
    );
    log_activity('update', 'branches', $id, ['code' => $old['code'], 'status_changed_by' => $statusChangedBy], $r + ['status_changed_by' => $statusChangedBy]);
    return true;
}

/* ---------------------------------------------------------------------
   9. Staff
   --------------------------------------------------------------------- */
function get_staff_info($id = null)
{
    $sql = "SELECT s.*, u.username, u.full_name, u.phone, r.name AS role, b.name AS branch_name, b.code AS branch_code
            FROM staff s
            LEFT JOIN users u ON u.id = s.user_id
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN branches b ON b.id = s.branch_id";
    return $id ? db_one($sql . " WHERE s.id = ? LIMIT 1", [$id]) : db_all($sql . " ORDER BY s.id DESC");
}

function get_staff_available_users(): array
{
    return db_all(
        "SELECT u.id, u.username, u.full_name, u.branch_id, r.name AS role
         FROM users u JOIN roles r ON r.id = u.role_id
         LEFT JOIN staff s ON s.user_id = u.id
         WHERE s.id IS NULL AND u.status = 'active' AND r.name <> 'member'
         ORDER BY u.full_name"
    );
}

function staff_clean(array $d, int $ignoreId = 0): array
{
    $row = [
        'branch_id'   => trim((string) ($d['branch_id'] ?? '')),
        'designation' => trim($d['designation'] ?? ''),
        'join_date'   => trim($d['join_date'] ?? ''),
        'base_salary' => trim((string) ($d['base_salary'] ?? '')),
        'nid_no'      => trim($d['nid_no'] ?? ''),
    ];
    need(!in_array('', $row, true), 'Please fill in all the required fields.');
    need(ctype_digit($row['branch_id']) && row_exists('branches', $row['branch_id']), 'Invalid branch.');
    need(is_super() || (int) $row['branch_id'] === current_branch_id(), 'You can only manage staff of your own branch.');
    need(len_ok($row['designation'], 2, 100), 'Designation must be 2 to 100 characters.');
    need(valid_date($row['join_date']), 'Invalid join date.');
    need(preg_match('/^[0-9]{1,12}(\.[0-9]{1,2})?$/', $row['base_salary']), 'Invalid base salary.');
    need(valid_nid($row['nid_no']), 'NID number must be 10, 13 or 17 digits.');
    need(!db_val("SELECT id FROM staff WHERE nid_no = ? AND id <> ? LIMIT 1", [$row['nid_no'], $ignoreId]), 'This NID number is already used by another staff.');
    return $row;
}

function create_staff(array $d): int
{
    $user_id = trim((string) ($d['user_id'] ?? ''));
    need($user_id !== '' && ctype_digit($user_id), 'Please select a login account.');
    need(db_val("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND u.status = 'active' AND r.name <> 'member' LIMIT 1", [$user_id]),
        'Selected login account is not available for staff.');
    need(!db_val("SELECT id FROM staff WHERE user_id = ? LIMIT 1", [$user_id]), 'This login account is already added as staff.');
    $r = staff_clean($d);

    db_exec(
        "INSERT INTO staff (user_id, branch_id, designation, join_date, base_salary, nid_no, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
        [$user_id, $r['branch_id'], $r['designation'], $r['join_date'], $r['base_salary'], $r['nid_no']]
    );
    $id = db_last_id();
    log_activity('create', 'staff', $id, null, ['user_id' => $user_id] + $r);
    return $id;
}

function update_staff(int $id, array $d): bool
{
    $old = db_one("SELECT branch_id, designation, join_date, base_salary, nid_no, status FROM staff WHERE id = ?", [$id]);
    need($old, 'Staff not found.');
    need(is_super() || (int) $old['branch_id'] === current_branch_id(), 'You can only manage staff of your own branch.');
    $r      = staff_clean($d, $id);
    $status = trim($d['status'] ?? $old['status']);
    need(in_array($status, ['active', 'resigned'], true), 'Invalid status.');

    if ($status === 'resigned' && $old['status'] !== 'resigned') {
        need(!db_val("SELECT id FROM centers WHERE field_officer_id = ? AND status = 'active' LIMIT 1", [$id]),
            'This staff is a field officer of an active center. Reassign the center first.');
    }

    db_exec(
        "UPDATE staff SET branch_id = ?, designation = ?, join_date = ?, base_salary = ?, nid_no = ?, status = ? WHERE id = ?",
        [$r['branch_id'], $r['designation'], $r['join_date'], $r['base_salary'], $r['nid_no'], $status, $id]
    );
    log_activity('update', 'staff', $id, $old, $r + ['status' => $status]);
    return true;
}

/* ---------------------------------------------------------------------
   9. Areas
   --------------------------------------------------------------------- */
function get_area_info($id = null)
{
    $sql = "SELECT a.*, b.name AS branch_name, b.code AS branch_code,
                   (SELECT COUNT(*) FROM centers c WHERE c.area_id = a.id) AS center_count
            FROM areas a LEFT JOIN branches b ON b.id = a.branch_id";
    return $id ? db_one($sql . " WHERE a.id = ? LIMIT 1", [$id]) : db_all($sql . " ORDER BY a.id DESC");
}

function create_area(array $d): string
{
    $branch_id = trim((string) ($d['branch_id'] ?? ''));
    $name      = trim($d['name'] ?? '');
    need($branch_id !== '' && $name !== '', 'Please fill in all the required fields.');
    need(ctype_digit($branch_id) && row_exists('branches', $branch_id), 'Invalid branch.');
    need(is_super() || (int) $branch_id === current_branch_id(), 'You can only manage areas of your own branch.');
    need(len_ok($name, 2, 100), 'Area name must be 2 to 100 characters.');
    need(!db_val("SELECT id FROM areas WHERE branch_id = ? AND name = ? LIMIT 1", [$branch_id, $name]), 'This area already exists in the selected branch.');

    return db_tx(function () use ($branch_id, $name) {
        $code = new_code('areas');
        db_exec("INSERT INTO areas (branch_id, code, name) VALUES (?, ?, ?)", [$branch_id, $code, $name]);
        log_activity('create', 'areas', db_last_id(), null, ['branch_id' => $branch_id, 'code' => $code, 'name' => $name]);
        return $code;
    });
}

function update_area(int $id, array $d): bool
{
    $old = db_one("SELECT branch_id, code, name FROM areas WHERE id = ?", [$id]);
    need($old, 'Area not found.');
    need(is_super() || (int) $old['branch_id'] === current_branch_id(), 'You can only manage areas of your own branch.');
    $branch_id = trim((string) ($d['branch_id'] ?? $old['branch_id']));
    $name      = trim($d['name'] ?? '');
    need($name !== '', 'Please fill in all the required fields.');
    need(ctype_digit($branch_id) && row_exists('branches', $branch_id), 'Invalid branch.');
    need(is_super() || (int) $branch_id === current_branch_id(), 'You can only manage areas of your own branch.');
    need(len_ok($name, 2, 100), 'Area name must be 2 to 100 characters.');
    if ((int) $branch_id !== (int) $old['branch_id']) {
        need(!db_val("SELECT id FROM centers WHERE area_id = ? LIMIT 1", [$id]), 'This area has centers, so its branch cannot be changed.');
    }
    need(!db_val("SELECT id FROM areas WHERE branch_id = ? AND name = ? AND id <> ? LIMIT 1", [$branch_id, $name, $id]), 'This area already exists in the selected branch.');

    db_exec("UPDATE areas SET branch_id = ?, name = ? WHERE id = ?", [$branch_id, $name, $id]);
    log_activity('update', 'areas', $id, $old, ['branch_id' => $branch_id, 'name' => $name]);
    return true;
}

/* ---------------------------------------------------------------------
   9c. Centers
   --------------------------------------------------------------------- */
function get_center_info($id = null)
{
    $sql = "SELECT c.*, b.name AS branch_name, b.code AS branch_code, a.name AS area_name, a.code AS area_code,
                   u.full_name AS officer_name,
                   (SELECT COUNT(*) FROM members m WHERE m.center_id = c.id AND m.status <> 'closed') AS member_count
            FROM centers c
            LEFT JOIN branches b ON b.id = c.branch_id
            LEFT JOIN areas a ON a.id = c.area_id
            LEFT JOIN staff s ON s.id = c.field_officer_id
            LEFT JOIN users u ON u.id = s.user_id";
    return $id ? db_one($sql . " WHERE c.id = ? LIMIT 1", [$id]) : db_all($sql . " ORDER BY c.id DESC");
}

function get_active_staff(): array
{
    return db_all(
        "SELECT s.id, s.branch_id, u.full_name, s.designation FROM staff s JOIN users u ON u.id = s.user_id
         WHERE s.status = 'active' ORDER BY u.full_name"
    );
}

function center_clean(array $d): array
{
    $row = [
        'name'             => trim($d['name'] ?? ''),
        'meeting_day'      => trim($d['meeting_day'] ?? ''),
        'meeting_time'     => trim($d['meeting_time'] ?? ''),
        'field_officer_id' => trim((string) ($d['field_officer_id'] ?? '')),
        'status'           => trim($d['status'] ?? 'active'),
    ];
    need($row['name'] !== '' && $row['meeting_day'] !== '' && $row['meeting_time'] !== '', 'Please fill in all the required fields.');
    need(len_ok($row['name'], 2, 100), 'Center name must be 2 to 100 characters.');
    need(in_array($row['meeting_day'], MEETING_DAYS, true), 'Invalid meeting day.');
    need(preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $row['meeting_time']), 'Invalid meeting time.');
    need(in_array($row['status'], ['active', 'inactive'], true), 'Invalid status.');
    return $row;
}

function check_officer(string $officerId, int $branchId): ?int
{
    if ($officerId === '') {
        return null;
    }
    need(ctype_digit($officerId) && db_val("SELECT id FROM staff WHERE id = ? AND branch_id = ? AND status = 'active' LIMIT 1", [$officerId, $branchId]),
        'Field officer must be an active staff of the same branch.');
    return (int) $officerId;
}

// staff_assignments e ke kon center dekhchhe tar itihash
function sync_assignment(int $centerId, ?int $oldOfficer, ?int $newOfficer): void
{
    if ($oldOfficer === $newOfficer) {
        return;
    }
    db_exec("UPDATE staff_assignments SET to_date = CURDATE() WHERE center_id = ? AND to_date IS NULL", [$centerId]);
    if ($newOfficer) {
        db_exec("INSERT INTO staff_assignments (staff_id, center_id, from_date, assigned_by) VALUES (?, ?, CURDATE(), ?)",
            [$newOfficer, $centerId, current_user_id() ?: null]);
    }
}

function create_center(array $d): string
{
    $branch_id = trim((string) ($d['branch_id'] ?? ''));
    $area_id   = trim((string) ($d['area_id'] ?? ''));
    need($branch_id !== '' && $area_id !== '', 'Please fill in all the required fields.');
    need(ctype_digit($branch_id) && row_exists('branches', $branch_id), 'Invalid branch.');
    need(is_super() || (int) $branch_id === current_branch_id(), 'You can only manage centers of your own branch.');
    need(ctype_digit($area_id) && db_val("SELECT id FROM areas WHERE id = ? AND branch_id = ? LIMIT 1", [$area_id, $branch_id]), 'Selected area does not belong to the selected branch.');
    $r       = center_clean($d);
    $officer = check_officer($r['field_officer_id'], (int) $branch_id);
    need(!db_val("SELECT id FROM centers WHERE area_id = ? AND name = ? LIMIT 1", [$area_id, $r['name']]), 'This center already exists in the selected area.');

    return db_tx(function () use ($branch_id, $area_id, $r, $officer) {
        $code = new_code('centers');
        db_exec(
            "INSERT INTO centers (branch_id, area_id, code, name, meeting_day, meeting_time, field_officer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$branch_id, $area_id, $code, $r['name'], $r['meeting_day'], $r['meeting_time'], $officer, $r['status']]
        );
        $id = db_last_id();
        sync_assignment($id, null, $officer);
        log_activity('create', 'centers', $id, null, ['branch_id' => $branch_id, 'area_id' => $area_id, 'code' => $code] + $r);
        return $code;
    });
}

function update_center(int $id, array $d): bool
{
    $old = db_one("SELECT branch_id, area_id, name, meeting_day, meeting_time, field_officer_id, status FROM centers WHERE id = ?", [$id]);
    need($old, 'Center not found.');
    need(is_super() || (int) $old['branch_id'] === current_branch_id(), 'You can only manage centers of your own branch.');
    $r       = center_clean($d);
    $officer = check_officer($r['field_officer_id'], (int) $old['branch_id']);
    need(!db_val("SELECT id FROM centers WHERE area_id = ? AND name = ? AND id <> ? LIMIT 1", [$old['area_id'], $r['name'], $id]), 'This center already exists in the selected area.');

    return db_tx(function () use ($id, $old, $r, $officer) {
        db_exec(
            "UPDATE centers SET name = ?, meeting_day = ?, meeting_time = ?, field_officer_id = ?, status = ? WHERE id = ?",
            [$r['name'], $r['meeting_day'], $r['meeting_time'], $officer, $r['status'], $id]
        );
        sync_assignment($id, $old['field_officer_id'] === null ? null : (int) $old['field_officer_id'], $officer);
        log_activity('update', 'centers', $id, $old, $r);
        return true;
    });
}

/* ---------------------------------------------------------------------
   9d. Members
   --------------------------------------------------------------------- */
function get_members(array $f, int $page = 1, int $per = 25): array
{
    $where  = ['1 = 1'];
    $params = [];

    if (($f['q'] ?? '') !== '') {
        $like    = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(m.full_name LIKE ? OR m.member_no LIKE ? OR m.member_code LIKE ? OR m.phone LIKE ? OR m.nid_no LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like);
    }
    if (in_array($f['status'] ?? '', ['pending', 'active', 'closed'], true)) {
        $where[]  = 'm.status = ?';
        $params[] = $f['status'];
    }
    if (ctype_digit((string) ($f['branch_id'] ?? ''))) {
        $where[]  = 'm.home_branch_id = ?';
        $params[] = $f['branch_id'];
    }
    if (ctype_digit((string) ($f['center_id'] ?? ''))) {
        $where[]  = 'm.center_id = ?';
        $params[] = $f['center_id'];
    }
    $cond  = implode(' AND ', $where);
    $total = (int) db_val("SELECT COUNT(*) FROM members m WHERE {$cond}", $params);
    $pages = max(1, (int) ceil($total / $per));
    $page  = min(max(1, $page), $pages);
    $off   = ($page - 1) * $per;

    $rows = db_all(
        "SELECT m.*, b.name AS branch_name, b.code AS branch_code, c.name AS center_name, c.code AS center_code
         FROM members m
         LEFT JOIN branches b ON b.id = m.home_branch_id
         LEFT JOIN centers c ON c.id = m.center_id
         WHERE {$cond} ORDER BY m.id DESC LIMIT {$per} OFFSET {$off}",
        $params
    );
    return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function save_member_photo(array $file): ?string
{
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    need($file['error'] === UPLOAD_ERR_OK, 'Photo upload failed. Please try again.');
    need($file['size'] <= 2 * 1024 * 1024, 'Photo must be 2 MB or smaller.');
    need(is_uploaded_file($file['tmp_name']), 'Invalid upload.');

    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';
    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    need($ext && $info, 'Photo must be a JPG, PNG or WEBP image.');

    $dir = dirname(__DIR__) . '/uploads/members';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    need(move_uploaded_file($file['tmp_name'], $dir . '/' . $name), 'Could not save the photo.');
    return '/uploads/members/' . $name;
}

function create_member(array $d, array $nominee = [], array $photo = []): array
{
    $m = [];
    foreach (['full_name', 'father_name', 'mother_name', 'spouse_name', 'birth_date', 'gender', 'nid_no', 'phone',
              'present_address', 'permanent_address', 'home_branch_id', 'center_id', 'joined_on'] as $key) {
        $m[$key] = trim((string) ($d[$key] ?? ''));
    }
    need($m['full_name'] !== '' && $m['father_name'] !== '' && $m['birth_date'] !== '' && $m['gender'] !== '' && $m['nid_no'] !== ''
        && $m['phone'] !== '' && $m['present_address'] !== '' && $m['permanent_address'] !== '' && $m['home_branch_id'] !== '' && $m['joined_on'] !== '',
        'Please fill in all the required fields.');
    need(len_ok($m['full_name'], 2, 100), 'Name must be 2 to 100 characters.');
    foreach (['father_name', 'mother_name', 'spouse_name'] as $k) {
        need(mb_strlen($m[$k]) <= 100, 'Parent/spouse name must be 100 characters or fewer.');
    }
    need(valid_date($m['birth_date']) && (new DateTime($m['birth_date']))->diff(new DateTime('today'))->y >= 18
        && new DateTime($m['birth_date']) < new DateTime('today'), 'Member must be at least 18 years old.');
    need(in_array($m['gender'], ['male', 'female', 'other'], true), 'Invalid gender.');
    need(valid_nid($m['nid_no']), 'NID number must be 10, 13 or 17 digits.');
    need(!db_val("SELECT id FROM members WHERE nid_no = ? LIMIT 1", [$m['nid_no']]), 'A member with this NID already exists.');
    need(valid_phone($m['phone']), 'Phone number must be 11 digits.');
    need(len_ok($m['present_address'], 2, 255) && len_ok($m['permanent_address'], 2, 255), 'Address must be 2 to 255 characters.');
    need(valid_date($m['joined_on']), 'Invalid joining date.');
    need(ctype_digit($m['home_branch_id']) && db_val("SELECT id FROM branches WHERE id = ? AND status = 'active' LIMIT 1", [$m['home_branch_id']]), 'Invalid branch.');
    need(is_super() || (int) $m['home_branch_id'] === current_branch_id(), 'You can only register members in your own branch.');

    $center = null;
    if ($m['center_id'] !== '') {
        need(ctype_digit($m['center_id']) && db_val("SELECT id FROM centers WHERE id = ? AND branch_id = ? AND status = 'active' LIMIT 1", [$m['center_id'], $m['home_branch_id']]),
            'Selected center does not belong to the selected branch.');
        $center = (int) $m['center_id'];
    }

    // Nominee (optional)
    $nom = null;
    if (trim($nominee['name'] ?? '') !== '') {
        $nom = [
            'name'          => trim($nominee['name']),
            'relation'      => trim($nominee['relation'] ?? ''),
            'nid_no'        => trim($nominee['nid_no'] ?? ''),
            'phone'         => trim($nominee['phone'] ?? ''),
            'share_percent' => trim((string) ($nominee['share_percent'] ?? '100')),
        ];
        need(len_ok($nom['name'], 2, 100) && len_ok($nom['relation'], 2, 100), 'Nominee name and relation are required.');
        need($nom['nid_no'] === '' || valid_nid($nom['nid_no']), 'Nominee NID must be 10, 13 or 17 digits.');
        need($nom['phone'] === '' || valid_phone($nom['phone']), 'Nominee phone must be 11 digits.');
        need(is_numeric($nom['share_percent']) && $nom['share_percent'] > 0 && $nom['share_percent'] <= 100, 'Nominee share must be between 0 and 100.');
    }

    $photoPath = save_member_photo($photo);

    try {
        return db_tx(function () use ($m, $center, $nom, $photoPath) {
            $branch = db_one("SELECT code FROM branches WHERE id = ?", [$m['home_branch_id']]);
            need($branch, 'Selected home branch was not found.');
            $memberSequence = 'MEM-NO';
            $memberNumber = next_sequence($memberSequence, (int) $m['home_branch_id']);
            $lastMemberNumber = (int) db_val(
                'SELECT MAX(CAST(member_no AS UNSIGNED)) FROM members WHERE home_branch_id = ?',
                [$m['home_branch_id']]
            );
            if ($lastMemberNumber >= $memberNumber) {
                $memberNumber = $lastMemberNumber + 1;
                db_exec(
                    'UPDATE id_sequences SET last_no = ? WHERE prefix = ? AND branch_id = ?',
                    [$memberNumber, $memberSequence, $m['home_branch_id']]
                );
            }
            need($memberNumber <= 999999, 'This branch has reached the six-digit member number limit.');
            $memberNo = sprintf('%06d', $memberNumber);
            $code = $branch['code'] . '-' . $memberNo;
            need(mb_strlen($code) <= 12, 'Branch code is too long to generate a member code.');

            db_exec(
                "INSERT INTO members (member_no, member_code, registered_by, full_name, father_name, mother_name, spouse_name, birth_date, gender, nid_no, phone,
                                      present_address, permanent_address, photo_path, home_branch_id, center_id, joined_on, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [$memberNo, $code, current_user_id() ?: null, $m['full_name'], $m['father_name'], $m['mother_name'] ?: null,
                 $m['spouse_name'] ?: null, $m['birth_date'], $m['gender'], $m['nid_no'], $m['phone'], $m['present_address'],
                 $m['permanent_address'], $photoPath, $m['home_branch_id'], $center, $m['joined_on']]
            );
            $id = db_last_id();

            if ($nom) {
                db_exec("INSERT INTO member_nominees (member_id, name, relation, nid_no, phone, share_percent) VALUES (?, ?, ?, ?, ?, ?)",
                    [$id, $nom['name'], $nom['relation'], $nom['nid_no'] ?: null, $nom['phone'] ?: null, $nom['share_percent']]);
            }
            log_activity('create', 'members', $id, null, [
                'member_no' => $memberNo,
                'member_code' => $code,
                'full_name' => $m['full_name'],
                'home_branch_id' => $m['home_branch_id'],
            ]);
            return ['id' => $id, 'number' => $memberNo, 'code' => $code];
        });
    } catch (Throwable $e) {
        if ($photoPath) {
            @unlink(dirname(__DIR__) . $photoPath);
        }
        throw $e;
    }
}

function approve_member(int $id): bool
{
    $m = db_one("SELECT id, status, home_branch_id FROM members WHERE id = ?", [$id]);
    need($m, 'Member not found.');
    need($m['status'] === 'pending', 'Only pending members can be approved.');
    need(is_super() || (int) $m['home_branch_id'] === current_branch_id(), 'Only the home branch can approve this member.');
    db_exec("UPDATE members SET status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'", [current_user_id(), $id]);
    log_activity('approve', 'members', $id, ['status' => 'pending'], ['status' => 'active']);
    return true;
}

function close_member(int $id): bool
{
    $m = db_one("SELECT id, status, home_branch_id FROM members WHERE id = ?", [$id]);
    need($m, 'Member not found.');
    need($m['status'] === 'active', 'Only active members can be closed.');
    need((int) $m['home_branch_id'] === current_branch_id(), 'Only the home branch can close this member.');
    need(!db_val("SELECT id FROM savings_accounts WHERE member_id = ? AND status <> 'closed' LIMIT 1", [$id]), 'Close all savings accounts of this member first.');
    need(!db_val("SELECT id FROM loan_accounts WHERE member_id = ? AND status IN ('active','defaulted') LIMIT 1", [$id]), 'This member has an active loan.');
    db_exec("UPDATE members SET status = 'closed' WHERE id = ? AND status = 'active'", [$id]);
    log_activity('close', 'members', $id, ['status' => 'active'], ['status' => 'closed']);
    return true;
}

function get_member_info(?int $member_id = null)
{
    global $conn;

    $sql = "
        SELECT
            m.*,
            b.name AS branch_name,
            b.code AS branch_code,
            c.name AS center_name,
            c.code AS center_code,
            g.name AS group_name,
            g.code AS group_code
        FROM members m
        LEFT JOIN branches b ON b.id = m.home_branch_id
        LEFT JOIN centers c ON c.id = m.center_id
        LEFT JOIN member_groups g ON g.id = m.group_id
    ";

    if ($member_id !== null) {
        $statement = $conn->prepare($sql . ' WHERE m.id = ? LIMIT 1');
        $statement->execute([$member_id]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    $statement = $conn->query($sql . ' ORDER BY m.id DESC');
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function get_member_documents(int $member_id): array
{
    global $conn;

    $statement = $conn->prepare(
        'SELECT id, member_id, doc_type, file_path, created_at
         FROM member_documents WHERE member_id = ? ORDER BY id DESC'
    );
    $statement->execute([$member_id]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function validate_member_relations(array $data): void
{
    global $conn;

    $branchStatement = $conn->prepare('SELECT id FROM branches WHERE id = ? LIMIT 1');
    $branchStatement->execute([$data['home_branch_id']]);
    if (!$branchStatement->fetchColumn()) {
        throw new Exception('Selected home branch was not found.');
    }

    $centerStatement = $conn->prepare('SELECT id FROM centers WHERE id = ? AND branch_id = ? LIMIT 1');
    $centerStatement->execute([$data['center_id'], $data['home_branch_id']]);
    if (!$centerStatement->fetchColumn()) {
        throw new Exception('Selected center does not belong to the home branch.');
    }

    if ($data['group_id'] !== '') {
        $groupStatement = $conn->prepare('SELECT id FROM member_groups WHERE id = ? AND center_id = ? LIMIT 1');
        $groupStatement->execute([$data['group_id'], $data['center_id']]);
        if (!$groupStatement->fetchColumn()) {
            throw new Exception('Selected group does not belong to the selected center.');
        }
    }
}

function save_member_nominee(int $member_id, array $data, ?int $nominee_id = null): void
{
    global $conn;

    if ($data['nominee_name'] === '') {
        if ($nominee_id !== null) {
            $delete = $conn->prepare('DELETE FROM member_nominees WHERE id = ? AND member_id = ?');
            $delete->execute([$nominee_id, $member_id]);
        }
        return;
    }

    $values = [
        $data['nominee_name'],
        $data['nominee_relation'] !== '' ? $data['nominee_relation'] : null,
        $data['nominee_nid_no'] !== '' ? $data['nominee_nid_no'] : null,
        $data['nominee_phone'] !== '' ? $data['nominee_phone'] : null,
        $data['nominee_share_percent'] !== '' ? $data['nominee_share_percent'] : null,
    ];

    if ($nominee_id !== null) {
        $statement = $conn->prepare(
            'UPDATE member_nominees SET name = ?, relation = ?, nid_no = ?, phone = ?, share_percent = ?
             WHERE id = ? AND member_id = ?'
        );
        $statement->execute(array_merge($values, [$nominee_id, $member_id]));
        return;
    }

    $statement = $conn->prepare(
        'INSERT INTO member_nominees (member_id, name, relation, nid_no, phone, share_percent)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $statement->execute(array_merge([$member_id], $values));
}

function update_member(int $member_id, array $data): bool
{
    global $conn;
    if ($member_id < 1) {
        throw new Exception('Invalid member.');
    }

    $clean = validate_member_data($data);
    validate_member_relations($clean);
    $conn->beginTransaction();
    try {
        $memberStatement = $conn->prepare('SELECT id, home_branch_id FROM members WHERE id = ? FOR UPDATE');
        $memberStatement->execute([$member_id]);
        $existingMember = $memberStatement->fetch(PDO::FETCH_ASSOC);
        if (!$existingMember) {
            throw new Exception('Member was not found.');
        }
        if ((int) $existingMember['home_branch_id'] !== current_branch_id()) {
            throw new Exception('Only the home branch can edit this member.');
        }
        if ((int) $clean['home_branch_id'] !== (int) $existingMember['home_branch_id']) {
            throw new Exception('A member home branch cannot be changed.');
        }

        $nomineeStatement = $conn->prepare(
            'SELECT id FROM member_nominees WHERE member_id = ? ORDER BY id LIMIT 1'
        );
        $nomineeStatement->execute([$member_id]);
        $nominee_id = $nomineeStatement->fetchColumn();

        $statement = $conn->prepare(
            'UPDATE members SET
                full_name = ?, father_name = ?, mother_name = ?, spouse_name = ?, birth_date = ?,
                gender = ?, nid_no = ?, phone = ?, present_address = ?, permanent_address = ?,
                home_branch_id = ?, center_id = ?, group_id = ?, joined_on = ?
             WHERE id = ?'
        );
        $statement->execute([
            $clean['full_name'],
            $clean['father_name'] !== '' ? $clean['father_name'] : null,
            $clean['mother_name'] !== '' ? $clean['mother_name'] : null,
            $clean['spouse_name'] !== '' ? $clean['spouse_name'] : null,
            $clean['birth_date'] !== '' ? $clean['birth_date'] : null,
            $clean['gender'] !== '' ? $clean['gender'] : null,
            $clean['nid_no'] !== '' ? $clean['nid_no'] : null,
            $clean['phone'] !== '' ? $clean['phone'] : null,
            $clean['present_address'] !== '' ? $clean['present_address'] : null,
            $clean['permanent_address'] !== '' ? $clean['permanent_address'] : null,
            $clean['home_branch_id'],
            $clean['center_id'],
            $clean['group_id'] !== '' ? $clean['group_id'] : null,
            $clean['joined_on'] !== '' ? $clean['joined_on'] : null,
            $member_id,
        ]);
        save_member_nominee($member_id, $clean, $nominee_id === false ? null : (int) $nominee_id);
        $conn->commit();
        return true;
    } catch (Throwable $exception) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $exception;
    }
}

function validate_member_data(array $data): array
{
    $fields = [
        'full_name', 'father_name', 'mother_name', 'spouse_name', 'birth_date',
        'gender', 'nid_no', 'phone', 'present_address', 'permanent_address',
        'home_branch_id', 'center_id', 'group_id', 'joined_on',
        'nominee_name', 'nominee_relation', 'nominee_nid_no', 'nominee_phone',
        'nominee_share_percent',
    ];
    $clean = [];
    foreach ($fields as $field) {
        $clean[$field] = trim((string) ($data[$field] ?? ''));
    }

    if ($clean['full_name'] === '' || $clean['home_branch_id'] === '' || $clean['center_id'] === '') {
        throw new Exception('Full name, home branch, and center are required.');
    }

    foreach (['home_branch_id', 'center_id'] as $field) {
        if (!ctype_digit($clean[$field])) {
            throw new Exception('Invalid home branch or center.');
        }
    }
    if ($clean['group_id'] !== '' && !ctype_digit($clean['group_id'])) {
        throw new Exception('Invalid member group.');
    }

    foreach ([
        'full_name' => 100,
        'father_name' => 100,
        'mother_name' => 100,
        'spouse_name' => 100,
        'nid_no' => 100,
        'phone' => 100,
        'present_address' => 255,
        'permanent_address' => 255,
    ] as $field => $maxLength) {
        if (mb_strlen($clean[$field]) > $maxLength) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$maxLength} characters.");
        }
    }
    if (mb_strlen($clean['full_name']) < 2) {
        throw new Exception('Member name must be at least 2 characters.');
    }

    if ($clean['gender'] !== '' && !in_array($clean['gender'], ['male', 'female', 'other'], true)) {
        throw new Exception('Invalid gender.');
    }
    foreach (['birth_date', 'joined_on'] as $field) {
        if ($clean[$field] !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $clean[$field]);
            if (!$date || $date->format('Y-m-d') !== $clean[$field]) {
                throw new Exception('Invalid ' . str_replace('_', ' ', $field) . '.');
            }
        }
    }

    $nomineeFields = ['nominee_relation', 'nominee_nid_no', 'nominee_phone', 'nominee_share_percent'];
    $hasNomineeDetails = false;
    foreach ($nomineeFields as $field) {
        if ($clean[$field] !== '') {
            $hasNomineeDetails = true;
        }
    }
    if ($clean['nominee_name'] === '' && $hasNomineeDetails) {
        throw new Exception('Enter nominee name to save nominee information.');
    }
    if ($clean['nominee_name'] !== '' && mb_strlen($clean['nominee_name']) > 100) {
        throw new Exception('Nominee name cannot exceed 100 characters.');
    }
    if ($clean['nominee_relation'] !== '' && mb_strlen($clean['nominee_relation']) > 100) {
        throw new Exception('Nominee relation cannot exceed 100 characters.');
    }
    foreach (['nominee_nid_no', 'nominee_phone'] as $field) {
        if (mb_strlen($clean[$field]) > 100) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' cannot exceed 100 characters.');
        }
    }
    if ($clean['nominee_share_percent'] !== '') {
        if (!is_numeric($clean['nominee_share_percent']) ||
            (float) $clean['nominee_share_percent'] < 0 ||
            (float) $clean['nominee_share_percent'] > 100) {
            throw new Exception('Nominee share must be between 0 and 100 percent.');
        }
    }

    return $clean;
}

// function save_member_nominee(int $member_id, array $data, ?int $nominee_id = null): void
// {
//     global $conn;

//     if ($data['nominee_name'] === '') {
//         if ($nominee_id !== null) {
//             $delete = $conn->prepare('DELETE FROM member_nominees WHERE id = ? AND member_id = ?');
//             $delete->execute([$nominee_id, $member_id]);
//         }
//         return;
//     }

//     $values = [
//         $data['nominee_name'],
//         $data['nominee_relation'] !== '' ? $data['nominee_relation'] : null,
//         $data['nominee_nid_no'] !== '' ? $data['nominee_nid_no'] : null,
//         $data['nominee_phone'] !== '' ? $data['nominee_phone'] : null,
//         $data['nominee_share_percent'] !== '' ? $data['nominee_share_percent'] : null,
//     ];

//     if ($nominee_id !== null) {
//         $statement = $conn->prepare(
//             'UPDATE member_nominees SET name = ?, relation = ?, nid_no = ?, phone = ?, share_percent = ?
//              WHERE id = ? AND member_id = ?'
//         );
//         $statement->execute(array_merge($values, [$nominee_id, $member_id]));
//         return;
//     }

//     $statement = $conn->prepare(
//         'INSERT INTO member_nominees (member_id, name, relation, nid_no, phone, share_percent)
//          VALUES (?, ?, ?, ?, ?, ?)'
//     );
//     $statement->execute(array_merge([$member_id], $values));
// }


function member_document_storage_directory(): string
{
    return dirname(__DIR__, 2) . '/storage/member_documents';
}






function upload_member_documents(int $member_id, array $uploads): int
{
    global $conn;

    if ($member_id < 1) {
        throw new Exception('Save the member before uploading documents.');
    }

    $labels = [
        'nid' => 'NID Document',
        'photo' => 'Member Photo',
        'signature' => 'Signature',
        'other' => 'Other Document',
    ];
    $allowed_mimes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $max_size = 5 * 1024 * 1024;
    $validated = [];

    if (isset($uploads['name']) && is_array($uploads['name'])) {
        $normalized = [];
        foreach ($uploads['name'] as $key => $name) {
            $normalized[$key] = [
                'name' => $name,
                'type' => $uploads['type'][$key] ?? '',
                'tmp_name' => $uploads['tmp_name'][$key] ?? '',
                'error' => $uploads['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                'size' => $uploads['size'][$key] ?? 0,
            ];
        }
        $uploads = $normalized;
    }

    foreach ($labels as $key => $label) {
        if (!isset($uploads[$key]) || !is_array($uploads[$key])) {
            continue;
        }
        $file = $uploads[$key];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error === UPLOAD_ERR_INI_SIZE) {
            throw new Exception(
                $label . ' exceeds the server upload limit (' . ini_get('upload_max_filesize') .
                '). Please use a smaller file or ask the hosting provider to apply the PHP upload settings.'
            );
        }
        if ($error === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception($label . ' exceeds the maximum file size allowed by the form.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new Exception($label . ' upload failed (upload error ' . $error . ').');
        }
        if (
            !isset($file['tmp_name'], $file['size']) ||
            !is_uploaded_file($file['tmp_name']) ||
            (int) $file['size'] < 1 ||
            (int) $file['size'] > $max_size
        ) {
            throw new Exception($label . ' must be a valid file no larger than 5 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset($allowed_mimes[$mime])) {
            throw new Exception($label . ' must be a PDF, JPG, PNG, or WEBP file.');
        }

        $validated[] = [
            'doc_type' => $label,
            'temporary_path' => $file['tmp_name'],
            'extension' => $allowed_mimes[$mime],
        ];
    }

    if (!$validated) {
        return 0;
    }

    $directory = member_document_storage_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create private document storage directory.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('Private document storage directory is not writable.');
    }

    $saved_paths = [];
    $conn->beginTransaction();
    try {
        $insert = $conn->prepare(
            'INSERT INTO member_documents (member_id, doc_type, file_path) VALUES (?, ?, ?)'
        );
        foreach ($validated as $file) {
            $filename = bin2hex(random_bytes(16)) . '.' . $file['extension'];
            $destination = $directory . '/' . $filename;
            if (!move_uploaded_file($file['temporary_path'], $destination)) {
                throw new RuntimeException('Unable to save uploaded ' . $file['doc_type'] . '.');
            }
            $saved_paths[] = $destination;
            $insert->execute([$member_id, $file['doc_type'], $filename]);
        }
        $conn->commit();
        return count($saved_paths);
    } catch (Throwable $exception) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        foreach ($saved_paths as $path) {
            if (is_file($path) && !unlink($path)) {
                error_log('Unable to remove uncommitted member document: ' . $path);
            }
        }
        throw $exception;
    }
}

function delete_member_document(int $member_id, int $document_id): bool
{
    global $conn;

    if ($member_id < 1 || $document_id < 1) {
        throw new Exception('Invalid member document.');
    }

    $select = $conn->prepare(
        'SELECT file_path FROM member_documents WHERE id = ? AND member_id = ? LIMIT 1'
    );
    $select->execute([$document_id, $member_id]);
    $filename = $select->fetchColumn();
    if ($filename === false) {
        throw new Exception('Document was not found for this member.');
    }
    if (!preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png|webp)$/', (string) $filename)) {
        throw new RuntimeException('Stored document path is invalid; refusing to delete it.');
    }

    $path = member_document_storage_directory() . '/' . $filename;
    $conn->beginTransaction();
    try {
        $delete = $conn->prepare('DELETE FROM member_documents WHERE id = ? AND member_id = ?');
        $delete->execute([$document_id, $member_id]);
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Unable to remove the stored document file.');
        }
        $conn->commit();
        return true;
    } catch (Throwable $exception) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $exception;
    }
}


/* ---------------------------------------------------------------------
   10. Roles & Permissions
   --------------------------------------------------------------------- */
const MODULES = [
    'USER-MGMT'   => 'Login Accounts',
    'BRANCH-MGMT' => 'Branches',
    'STAFF-MGMT'  => 'Staff',
    'AREA-MGMT'   => 'Areas',
    'CENTER-MGMT' => 'Centers',
    'M-MGMT'      => 'Members',
    'SETTINGS'    => 'Settings',
    'ACC-MGMT'    => 'Account Management'
];
const PERMS = ['can_view' => 'View', 'can_add' => 'Add', 'can_edit' => 'Edit', 'can_delete' => 'Delete', 'can_approve' => 'Approve'];

// [role_id][module_code] => row
function get_permission_matrix(): array
{
    $matrix = [];
    foreach (db_all("SELECT * FROM role_permissions") as $row) {
        $matrix[$row['role_id']][$row['module_code']] = $row;
    }
    return $matrix;
}

function save_role_permissions(array $input): bool
{
    $roles = db_all("SELECT id, name FROM roles WHERE name <> 'super_admin'");
    return db_tx(function () use ($roles, $input) {
        foreach ($roles as $role) {
            foreach (array_keys(MODULES) as $module) {
                $flags = [];
                foreach (array_keys(PERMS) as $perm) {
                    $flags[] = !empty($input[$role['id']][$module][$perm]) ? 1 : 0;
                }
                db_exec(
                    "INSERT INTO role_permissions (role_id, module_code, can_view, can_add, can_edit, can_delete, can_approve)
                     VALUES (?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_add = VALUES(can_add), can_edit = VALUES(can_edit),
                                             can_delete = VALUES(can_delete), can_approve = VALUES(can_approve)",
                    array_merge([$role['id'], $module], $flags)
                );
            }
        }
        log_activity('update', 'role_permissions', null, null, ['saved_roles' => array_column($roles, 'name')]);
        return true;
    });
}

/* ---------------------------------------------------------------------
   11. Settings
   --------------------------------------------------------------------- */
function get_all_settings(): array
{
    return db_all("SELECT id, setting_key, setting_value, description, updated_at FROM settings ORDER BY setting_key");
}

function setting_value_check(string $key, string $value): void
{
    need(mb_strlen($value) <= 100, 'Value must be 100 characters or fewer.');
    if ($key === 'min_balance') {
        need(preg_match('/^[0-9]{1,12}(\.[0-9]{1,2})?$/', $value), 'Minimum balance must be a valid amount.');
    }
}

function update_setting(int $id, string $value, ?string $description = null): bool
{
    $row = db_one("SELECT setting_key FROM settings WHERE id = ?", [$id]);
    need($row, 'Setting not found.');
    $value = trim($value);
    need($value !== '', 'Value is required.');
    setting_value_check($row['setting_key'], $value);
    if ($description !== null) {
        need(mb_strlen($description) <= 100, 'Description must be 100 characters or fewer.');
        db_exec("UPDATE settings SET setting_value = ?, description = ? WHERE id = ?", [$value, $description, $id]);
    } else {
        db_exec("UPDATE settings SET setting_value = ? WHERE id = ?", [$value, $id]);
    }
    log_activity('update', 'settings', $id, null, ['key' => $row['setting_key'], 'value' => $value]);
    return true;
}

function create_setting(string $key, string $value, string $description = ''): int
{
    $key   = strtolower(trim($key));
    $value = trim($value);
    $description = trim($description);
    need($key !== '' && $value !== '', 'Key and value are required.');
    need(preg_match('/^[a-z][a-z0-9_]{1,99}$/', $key), 'Key must be 2 to 100 characters (a-z, 0-9 and _).');
    need(mb_strlen($description) <= 100, 'Description must be 100 characters or fewer.');
    setting_value_check($key, $value);
    need(!db_val("SELECT id FROM settings WHERE setting_key = ? LIMIT 1", [$key]), 'This setting key already exists.');
    db_exec("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)", [$key, $value, $description ?: null]);
    $id = db_last_id();
    log_activity('create', 'settings', $id, null, ['key' => $key, 'value' => $value]);
    return $id;
}

/* ---------------------------------------------------------------------
   12. Dashboard
   --------------------------------------------------------------------- */
function get_dashboard_counts(): array
{
    return [
        'branches'       => (int) db_val("SELECT COUNT(*) FROM branches WHERE status = 'active'"),
        'users'          => (int) db_val("SELECT COUNT(*) FROM users WHERE status = 'active'"),
        'users_locked'   => (int) db_val("SELECT COUNT(*) FROM users WHERE status = 'locked'"),
        'staff'          => (int) db_val("SELECT COUNT(*) FROM staff WHERE status = 'active'"),
        'staff_resigned' => (int) db_val("SELECT COUNT(*) FROM staff WHERE status = 'resigned'"),
        'roles'          => (int) db_val("SELECT COUNT(*) FROM roles"),
        'areas'          => (int) db_val("SELECT COUNT(*) FROM areas"),
        'centers'        => (int) db_val("SELECT COUNT(*) FROM centers WHERE status = 'active'"),
        'members_active' => (int) db_val("SELECT COUNT(*) FROM members WHERE status = 'active'"),
        'members_pending' => (int) db_val("SELECT COUNT(*) FROM members WHERE status = 'pending'"),
    ];
}






/* ---------------------------------------------------------------------------------
  13. Account ACC-MGMT
 ----------------------------------------------------------------------------------*/


// Account Types

/**
 * account_types table e notun record insert kore.
 * Return: ['success' => bool, 'id' => int|null, 'message' => string]
 */
function addAccountType(PDO $pdo, string $typeCode, string $category, string $name, string $status = 'active', ?int $createdBy = null): array
{
    $typeCode = trim($typeCode);
    $category = trim($category);
    $name     = trim($name);
    $status   = trim($status);

    // Validation
    if (!preg_match('/^[1-3][0-9]{2}$/', $typeCode)) {
        return ['success' => false, 'id' => null, 'message' => 'Type code 3 digit hote hobe (1xx, 2xx ba 3xx).'];
    }
    if (!in_array($category, ['savings', 'loan', 'share'], true)) {
        return ['success' => false, 'id' => null, 'message' => 'Category invalid.'];
    }
    // 1xx = savings, 2xx = loan, 3xx = share
    $prefixMap = ['1' => 'savings', '2' => 'loan', '3' => 'share'];
    if ($prefixMap[$typeCode[0]] !== $category) {
        return ['success' => false, 'id' => null, 'message' => 'Type code er shuru-r digit category-r sathe mile na.'];
    }
    if ($name === '' || mb_strlen($name) > 100) {
        return ['success' => false, 'id' => null, 'message' => 'Name khali ba 100 character er beshi hobe na.'];
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        return ['success' => false, 'id' => null, 'message' => 'Status invalid.'];
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO account_types (type_code, category, name, status, created_by)
             VALUES (:type_code, :category, :name, :status, :created_by)'
        );
        $stmt->bindValue(':type_code', $typeCode);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':created_by', $createdBy, $createdBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        return ['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => 'Account type add hoyeche.'];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' && ($e->errorInfo[1] ?? 0) === 1062) {
            return ['success' => false, 'id' => null, 'message' => 'Ei type code age thekei ache.'];
        }
        if ($e->getCode() === '23000' && ($e->errorInfo[1] ?? 0) === 1452) {
            return ['success' => false, 'id' => null, 'message' => 'created_by user khuje paoa jai ni.'];
        }
        error_log('addAccountType: ' . $e->getMessage());
        return ['success' => false, 'id' => null, 'message' => 'Database error hoyeche.'];
    }
}

// savings_products system and get

function get_savings_products($id = null)
{
    $sql = "SELECT * FROM savings_products";
    return $id
        ? db_one($sql . " WHERE id = ? LIMIT 1", [$id])
        : db_all($sql . " ORDER BY id DESC");
}

function validateSavingsProductData(array $data): array
{
    $typeId = trim((string) ($data['type_id'] ?? ''));
    $name = trim((string) ($data['name'] ?? ''));
    $productType = trim((string) ($data['product_type'] ?? ''));
    $interestRate = trim((string) ($data['interest_rate'] ?? ''));
    $installmentAmount = trim((string) ($data['installment_amount'] ?? ''));
    $termMonths = trim((string) ($data['term_months'] ?? ''));
    $minDeposit = trim((string) ($data['min_deposit'] ?? ''));
    $status = trim((string) ($data['status'] ?? ''));

    if (!ctype_digit($typeId) || (int) $typeId < 1) {
        return ['success' => false, 'message' => 'Select a valid savings account type.'];
    }
    if ($name === '' || mb_strlen($name) > 100) {
        return ['success' => false, 'message' => 'Product name is required and must be 100 characters or fewer.'];
    }
    if (!in_array($productType, ['daily', 'weekly', 'monthly', 'fdr', 'dps'], true)) {
        return ['success' => false, 'message' => 'Select a valid product type.'];
    }
    if (!preg_match('/^\d{1,4}(?:\.\d{1,2})?$/D', $interestRate)) {
        return ['success' => false, 'message' => 'Interest rate must be between 0 and 9999.99.'];
    }
    if (!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $installmentAmount)) {
        return ['success' => false, 'message' => 'Installment amount must be between 0 and 999999999999.99.'];
    }
    if ($termMonths !== '' && (!ctype_digit($termMonths) || (int) $termMonths < 1 || (int) $termMonths > 2147483647)) {
        return ['success' => false, 'message' => 'Term must be a positive whole number of months.'];
    }
    if (!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $minDeposit)) {
        return ['success' => false, 'message' => 'Minimum deposit must be between 0 and 999999999999.99.'];
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        return ['success' => false, 'message' => 'Select a valid status.'];
    }

    return [
        'success' => true,
        'data' => [
            'type_id' => (int) $typeId,
            'name' => $name,
            'product_type' => $productType,
            'interest_rate' => $interestRate,
            'installment_amount' => $installmentAmount,
            'term_months' => $termMonths === '' ? null : (int) $termMonths,
            'min_deposit' => $minDeposit,
            'status' => $status,
        ],
    ];
}

function addSavingsProduct(PDO $pdo, array $data): array
{
    try {
        $validated = validateSavingsProductData($data);
        if (!$validated['success']) {
            return ['success' => false, 'id' => null, 'message' => $validated['message']];
        }
        $typeStmt = $pdo->prepare("SELECT id FROM account_types WHERE id = ? AND category = 'savings' LIMIT 1");
        $typeStmt->execute([$validated['data']['type_id']]);
        if (!$typeStmt->fetchColumn()) {
            return ['success' => false, 'id' => null, 'message' => 'Selected account type is not a savings account type.'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO savings_products
                (type_id, name, product_type, interest_rate, installment_amount, term_months, min_deposit, status)
             VALUES
                (:type_id, :name, :product_type, :interest_rate, :installment_amount, :term_months, :min_deposit, :status)'
        );
        foreach ($validated['data'] as $key => $value) {
            $stmt->bindValue(
                ':' . $key,
                $value,
                $value === null ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR)
            );
        }
        $stmt->execute();

        return ['success' => true, 'id' => (int) $pdo->lastInsertId(), 'message' => 'Savings product created successfully.'];
    } catch (PDOException $e) {
        error_log('addSavingsProduct: ' . $e->getMessage());
        return ['success' => false, 'id' => null, 'message' => 'Database error. Please try again.'];
    }
}

function updateSavingsProduct(PDO $pdo, int $id, array $data): array
{
    try {
        $validated = validateSavingsProductData($data);
        if (!$validated['success']) {
            return ['success' => false, 'id' => null, 'message' => $validated['message']];
        }
        $typeStmt = $pdo->prepare("SELECT id FROM account_types WHERE id = ? AND category = 'savings' LIMIT 1");
        $typeStmt->execute([$validated['data']['type_id']]);
        if (!$typeStmt->fetchColumn()) {
            return ['success' => false, 'id' => null, 'message' => 'Selected account type is not a savings account type.'];
        }

        $values = $validated['data'];
        $stmt = $pdo->prepare(
            'UPDATE savings_products
             SET type_id = :type_id,
                 name = :name,
                 product_type = :product_type,
                 interest_rate = :interest_rate,
                 installment_amount = :installment_amount,
                 term_months = :term_months,
                 min_deposit = :min_deposit,
                 status = :status
             WHERE id = :id'
        );
        foreach ($values as $key => $value) {
            $stmt->bindValue(
                ':' . $key,
                $value,
                $value === null ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR)
            );
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return ['success' => true, 'id' => $id, 'message' => 'Savings product updated successfully.'];
    } catch (PDOException $e) {
        error_log('updateSavingsProduct: ' . $e->getMessage());
        return ['success' => false, 'id' => null, 'message' => 'Database error. Please try again.'];
    }
}

function get_account_types($id = null)
{
    $sql = "SELECT * FROM account_types";
    return $id
        ? db_one($sql . " WHERE id = ? LIMIT 1", [$id])
        : db_all($sql . " ORDER BY id DESC");
}
function get_savings_account($id = null)
{
    $sql = "SELECT * FROM savings_accounts";
    return $id
        ? db_one($sql . " WHERE id = ? LIMIT 1", [$id])
        : db_all($sql . " ORDER BY id DESC");
}

function validateSavingsAccountData(array $data, int $accountId = 0): array
{
    $accountNo = trim((string) ($data['account_no'] ?? ''));
    $memberId = trim((string) ($data['member_id'] ?? ''));
    $productId = trim((string) ($data['product_id'] ?? ''));
    $serialNo = trim((string) ($data['serial_no'] ?? ''));
    $openedOn = trim((string) ($data['opened_on'] ?? ''));
    $maturityDate = trim((string) ($data['maturity_date'] ?? ''));
    $status = trim((string) ($data['status'] ?? ''));
    $closedOn = trim((string) ($data['closed_on'] ?? ''));

    if ($accountNo === '' || mb_strlen($accountNo) > 20) {
        return ['success' => false, 'message' => 'Account number is required and must be 20 characters or fewer.'];
    }
    if (!ctype_digit($memberId) || (int) $memberId < 1) {
        return ['success' => false, 'message' => 'Select a valid member.'];
    }
    if (!ctype_digit($productId) || (int) $productId < 1) {
        return ['success' => false, 'message' => 'Select a valid savings product.'];
    }
    if (!ctype_digit($serialNo) || (int) $serialNo < 1 || (int) $serialNo > 2147483647) {
        return ['success' => false, 'message' => 'Serial number must be a positive whole number.'];
    }
    $validDate = static function (string $date): bool {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    };
    if (!$validDate($openedOn)) {
        return ['success' => false, 'message' => 'Enter a valid opening date.'];
    }
    if ($maturityDate !== '' && !$validDate($maturityDate)) {
        return ['success' => false, 'message' => 'Enter a valid maturity date.'];
    }
    if ($maturityDate !== '' && $maturityDate < $openedOn) {
        return ['success' => false, 'message' => 'Maturity date cannot be before the opening date.'];
    }
    if (!in_array($status, ['active', 'matured', 'closed'], true)) {
        return ['success' => false, 'message' => 'Select a valid account status.'];
    }
    if ($accountId === 0 && $status !== 'active') {
        return ['success' => false, 'message' => 'A new savings account must start with active status.'];
    }
    if ($status === 'closed') {
        if (!$validDate($closedOn) || $closedOn < $openedOn) {
            return ['success' => false, 'message' => 'A valid closing date on or after the opening date is required for a closed account.'];
        }
    } else {
        $closedOn = '';
    }

    $member = db_one(
        'SELECT m.id, m.home_branch_id, m.status AS member_status, b.status AS branch_status
         FROM members m
         JOIN branches b ON b.id = m.home_branch_id
         WHERE m.id = ? LIMIT 1',
        [(int) $memberId]
    );
    if (!$member || (!is_super() && (int) $member['home_branch_id'] !== current_branch_id())) {
        return ['success' => false, 'message' => 'Selected member is not available in your branch.'];
    }
    if ($member['branch_status'] !== 'active') {
        return ['success' => false, 'message' => 'Member home branch is inactive.'];
    }
    if ($member['member_status'] !== 'active' && $accountId === 0) {
        return ['success' => false, 'message' => 'Only active members can open a savings account.'];
    }

    $product = db_one(
        'SELECT id, status FROM savings_products WHERE id = ? LIMIT 1',
        [(int) $productId]
    );
    if (!$product || ($product['status'] !== 'active' && $accountId === 0)) {
        return ['success' => false, 'message' => 'Select an active savings product.'];
    }
    if ($product['status'] !== 'active') {
        $currentProductId = db_val('SELECT product_id FROM savings_accounts WHERE id = ?', [$accountId]);
        if ((int) $currentProductId !== (int) $productId) {
            return ['success' => false, 'message' => 'An inactive product cannot be assigned to this account.'];
        }
    }
    if ($status === 'closed') {
        $balance = db_val('SELECT balance FROM savings_accounts WHERE id = ?', [$accountId]);
        if ($balance !== false && (float) $balance !== 0.0) {
            return ['success' => false, 'message' => 'An account must have a zero balance before it can be closed.'];
        }
    }

    return [
        'success' => true,
        'data' => [
            'account_no' => $accountNo,
            'member_id' => (int) $memberId,
            'product_id' => (int) $productId,
            'home_branch_id' => (int) $member['home_branch_id'],
            'serial_no' => (int) $serialNo,
            'opened_on' => $openedOn,
            'maturity_date' => $maturityDate === '' ? null : $maturityDate,
            'status' => $status,
            'closed_on' => $status === 'closed' ? $closedOn : null,
            'closed_by' => $status === 'closed' ? current_user_id() : null,
        ],
    ];
}

function addSavingsAccount(PDO $pdo, array $data): array
{
    try {
        $validated = validateSavingsAccountData($data);
        if (!$validated['success']) {
            return ['success' => false, 'id' => null, 'message' => $validated['message']];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO savings_accounts
                (account_no, member_id, product_id, home_branch_id, serial_no, opened_on, maturity_date,
                 principal_amount, balance, status, closed_on, closed_by)
             VALUES
                (:account_no, :member_id, :product_id, :home_branch_id, :serial_no, :opened_on, :maturity_date,
                 0, 0, :status, :closed_on, :closed_by)'
        );
        foreach ($validated['data'] as $key => $value) {
            $stmt->bindValue(
                ':' . $key,
                $value,
                $value === null ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR)
            );
        }
        $stmt->execute();

        return ['success' => true, 'id' => (int) $pdo->lastInsertId(), 'message' => 'Savings account created successfully.'];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' && ($e->errorInfo[1] ?? 0) === 1062) {
            return ['success' => false, 'id' => null, 'message' => 'Account number or member/product serial number already exists.'];
        }
        error_log('addSavingsAccount: ' . $e->getMessage());
        return ['success' => false, 'id' => null, 'message' => 'Database error. Please try again.'];
    }
}

function updateSavingsAccount(PDO $pdo, int $id, array $data): array
{
    try {
        $account = db_one(
            'SELECT id, home_branch_id, member_id, product_id, balance, status, closed_by
             FROM savings_accounts WHERE id = ? LIMIT 1',
            [$id]
        );
        if (!$account || (!is_super() && (int) $account['home_branch_id'] !== current_branch_id())) {
            return ['success' => false, 'id' => null, 'message' => 'Savings account not found or not available in your branch.'];
        }

        $validated = validateSavingsAccountData($data, $id);
        if (!$validated['success']) {
            return ['success' => false, 'id' => null, 'message' => $validated['message']];
        }
        $data = $validated['data'];
        if (((float) $account['balance'] !== 0.0 || db_val(
            'SELECT id FROM savings_transactions WHERE account_id = ? LIMIT 1',
            [$id]
        )) && (
            (int) $data['member_id'] !== (int) $account['member_id']
            || (int) $data['product_id'] !== (int) $account['product_id']
        )) {
            return ['success' => false, 'id' => null, 'message' => 'Member and product cannot be changed after account transactions exist or the account has a balance.'];
        }
        if ($account['status'] === 'closed' && $data['status'] === 'closed') {
            $data['closed_by'] = $account['closed_by'];
        }

        $stmt = $pdo->prepare(
            'UPDATE savings_accounts
             SET account_no = :account_no,
                 member_id = :member_id,
                 product_id = :product_id,
                 home_branch_id = :home_branch_id,
                 serial_no = :serial_no,
                 opened_on = :opened_on,
                 maturity_date = :maturity_date,
                 status = :status,
                 closed_on = :closed_on,
                 closed_by = :closed_by
             WHERE id = :id'
        );
        foreach ($data as $key => $value) {
            $stmt->bindValue(
                ':' . $key,
                $value,
                $value === null ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR)
            );
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return ['success' => true, 'id' => $id, 'message' => 'Savings account updated successfully.'];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' && ($e->errorInfo[1] ?? 0) === 1062) {
            return ['success' => false, 'id' => null, 'message' => 'Account number or member/product serial number already exists.'];
        }
        error_log('updateSavingsAccount: ' . $e->getMessage());
        return ['success' => false, 'id' => null, 'message' => 'Database error. Please try again.'];
    }
}



