<?php
require_once '../config.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $error = 'Session expired. Please reload the page and try again.';
    } else {
        $result = login($_POST);
        if (!$result['success']) {
            $error = $result['message'];
        }
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(get_setting('app_name', 'Login')); ?> - Login</title>
    <link rel="stylesheet" href="/style/style.css">
</head>

<body>
    <?php if ($error): ?>
        <p class="error"><?php echo e($error); ?></p>
    <?php endif; ?>
    <div class="fullPage flex medel center column">
        <div class="box loginBox">
            <div class="flex">
                <div>
                    <img src="/asset/vector/user.png" alt="icon" class="loginIcon">
                </div>
                <div class="flex medel">
                    <div>
                        <h1 class="loginTitle">Login</h1>
                        <p>Please enter your credentials to log in.</p>
                    </div>
                </div>
            </div>
            <div class="loginFrom">
                <form action="/login" method="POST" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo e($_POST['username'] ?? ''); ?>" required autofocus>
                    </div>
                    <div>
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-primary">Login</button>
                </form>
            </div>
        </div>
        <div>
            <p style="margin:10px">
                <a href="https://www.ndsql.top" target="_blank">NdSQL</a> manages this app and provides all security.
            </p>
        </div>
    </div>
</body>

</html>
