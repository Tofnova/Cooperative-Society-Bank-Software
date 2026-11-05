<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once 'config.php';

if (!isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])) {
    header("Location: /login");
    exit();
}

// Prottek request e DB theke fresh user — status/role bodle sathe sathe kaj korbe
$me = get_user_info((int) $_SESSION['user_id']);
if (!$me || $me['status'] !== 'active') {
    session_unset();
    session_destroy();
    header("Location: /login");
    exit();
}
$_SESSION['role']      = $me['role'];
$_SESSION['branch_id'] = $me['branch_id'];
$_SESSION['full_name'] = $me['full_name'];
apply_branch_context();   // members trigger er jonno @current_branch_id

$app_name   = get_setting('app_name', 'NdSQL Cooperative Society');
$app_logo   = get_setting('app_logo', 'https://file.ndsql.top/1481104e-7bdd-4b4d-82df-479c017e79b8.jpg');
$my_initial = mb_strtoupper(mb_substr($me['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($app_name) . (isset($page_title) ? ' - ' . e($page_title) : ''); ?></title>
    <link rel="stylesheet" href="/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css"
        integrity="sha512-QeR2VH+lsBE5LSAe1Q5EnTBbe7XTBubt8dG93Y7gidSgdMCr8nVqKcfKAMyN96SV8KDbZVTDXChatu5G2KQGzg=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
    toastr.options = {
        closeButton: true,
        progressBar: true,
        newestOnTop: true,
        preventDuplicates: true,
        positionClass: "toast-top-center",
        timeOut: 4000,
        extendedTimeOut: 1000,
        showDuration: 300,
        hideDuration: 1000,
        showMethod: "slideDown",
        hideMethod: "slideUp"
    };
    </script>
    <script>
    // Form submit hole button disable (double submit atkate)
    document.addEventListener('submit', function(e) {
        document.body.style.cursor = 'wait';
        const button = e.target.querySelector('button[type="submit"]');
        if (button) {
            setTimeout(function() {
                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            }, 0);
        }
    });
    window.addEventListener('load', function() {
        const loading = document.getElementById('top-loading');
        if (loading) {
            loading.style.transition = 'opacity 0.3s';
            loading.style.opacity = '0';
            setTimeout(() => loading.remove(), 300);
        }
    });
    </script>
</head>

<body>
    <div id="top-loading"></div>
    <div class="flex">
        <div class="sideNav">
            <div class="flex center medel navImgBox">
                <img src="<?php echo e($app_logo); ?>" alt="" class="navImg">
            </div>

            <div class="siderbar">
                <?php require_once 'components/inc/side_bar.php'; ?>
            </div>
        </div>
        <div class="mainContent">
            <nav class="nav flex beet medel">
                <div class="flex gap medel">
                    <div onclick="backHistory()" class="back_icon">
                        <i class="fa-solid fa-arrow-left"></i>
                    </div>
                    <h2><?php echo e($page_title ?? ''); ?></h2>
                </div>
                <div class="flex medel gap">
                    <div class="border-right pointer" onclick="contentPrint()">
                        <i class="fa-solid fa-print icon-lg opacity07"></i>
                    </div>
                    <div class="border-right flex medel gap">
                        <div>
                            <i class="fa-regular fa-calendar-days icon-lg opacity07"></i>
                        </div>
                        <div>
                            <b> <?php echo date(' j F, Y'); ?></b>
                            <br>
                            <small><?php echo date('l, h:i A'); ?></small>
                        </div>
                    </div>
                    <div class="pointer" onclick="toggleMenu()">
                        <div class="flex gap medel">
                            <div class="avatar avatar--fallback"><?php echo e($my_initial); ?></div>
                            <div class="flex medel gap">
                                <div>
                                    <h4><?php echo e($me['full_name']); ?></h4>
                                    <p><?php echo e($me['role']); ?></p>
                                </div>
                                <div>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                        <div id="account" class="account">
                            <div onclick="call('/account')" class="abtn flex medel gap">
                                <i class="fa-regular fa-user"></i> My Account
                            </div>
                            <?php if (user_can('USER-MGMT', 'can_add')): ?>
                            <div onclick="call('/create_account')" class="abtn flex medel gap">
                                <i class="fa-solid fa-plus"></i> Create Account
                            </div>
                            <?php endif; ?>
                            <div onclick="logout()" class="abtn flex medel gap">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                            </div>
                            <script>
                            function logout() {
                                if (confirm("Are you sure you want to logout?")) {
                                    window.location.href = "/logout";
                                }
                            }
                            </script>
                        </div>
                    </div>
                    <script>
                    function toggleMenu() {
                        document.getElementById('account').classList.toggle('show');
                    }
                    </script>
                </div>
            </nav>
            <div id="content">
                <?php require_once 'components/print/ui.php'; ?>
