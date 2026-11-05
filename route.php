<?php

$route = trim(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    '/'
);

switch ($route) {

    case '':
        require_once 'dashbord.php';
        break;

    case 'logout':
        require_once 'components/logout.php';
        break;

    // ---- Account ----
    case 'account':
        require_once 'components/admin/login_account.php';
        break;
    case 'admin_account':
        require_once 'components/admin/admin_account.php';
        break;
    case 'create_account':
        require_once 'components/admin/create_account.php';
        break;

    // ---- Branch ----
    case 'branch_list':
        require_once 'components/branch/branch_list.php';
        break;
    case 'create_branch':
        require_once 'components/branch/create_branch.php';
        break;

    // ---- Areas & Center ----
    case 'areas_list':
        require_once 'components/models/areas/areas_list.php';
        break;
    case 'create_areas':
        require_once 'components/models/areas/new_areas.php';
        break;
    case 'center_list':
        require_once 'components/models/center/center_list.php';
        break;
    case 'create_center':
        require_once 'components/models/center/new_center.php';
        break;

    // ---- Member ----
    case 'members':
        require_once 'components/membars/members.php';
        break;
    case 'new_member':
        require_once 'components/membars/new_member.php';
        break;
    case 'drop_member':
        require_once 'components/membars/drop_member.php';
        break;
    case 'member_group':
        require_once 'components/membars/member_group.php';
        break;

    // ---- Staff ----
    case 'staff_list':
        require_once 'components/models/staff/staff.php';
        break;
    case 'create_staff':
        require_once 'components/models/staff/new_staff.php';
        break;

    // ---- System ----
    case 'roles':
        require_once 'components/settings/roles.php';
        break;
    case 'settings':
        require_once 'components/settings/settings.php';
        break;
    case 'activity_log':
        require_once 'components/settings/activity_log.php';
        break;
    case 'account_types':
        require_once 'components/models/account/account_types.php';
        break;
    case 'savings_products':
        require_once 'components/models/account/savings_products.php';
        break;
    case 'savings_accounts':
        require_once 'components/models/account/saviing_accounts.php';
        break;
    default:

        // /account/user/1
        if (preg_match('#^account/user/([0-9]+)$#', $route, $match)) {
            $id = $match[1];
            require_once 'components/admin/view_user_account.php';
            break;
        }
          if (preg_match('#^members/profile/([0-9]+)$#', $route, $match)) {
            $id = $match[1];
            require_once 'components/membars/member_profile.php';
            break;
        }


        // /edit_branch/1
        if (preg_match('#^edit_branch/([0-9]+)$#', $route, $match)) {
            $id = $match[1];
            require_once 'components/branch/edit.php';
            break;
        }

        // /edit_areas/1
        if (preg_match('#^edit_areas/([0-9]+)$#', $route, $match)) {
            $id = $match[1];
            require_once 'components/models/areas/edit_areas.php';
            break;
        }

        // /edit_center/1
        if (preg_match('#^edit_center/([0-9]+)$#', $route, $match)) {
            $id = $match[1];
            require_once 'components/models/center/edit_center.php';
            break;
        }

        // /edit_staff/1
        if (preg_match('#^edit_staff/([0-9]+)$#', $route, $match)) {
            $id = $match[1];
            require_once 'components/models/staff/edit_staff.php';
            break;
        }
          // /member documents/1
        if (preg_match('#^member_document/([0-9]+)$#', $route, $match)) {
            $id = $match[1];
            require_once 'components/membars/member_document.php';
            break;
        }
               // /member documents/1
        if (preg_match('#^members/profile/edit/([0-9]+)$#', $route, $match)) {
            $id = $match[1];
            require_once 'components/membars/edit_member.php';
            break;
        }

        http_response_code(404);
        require_once '404.php';
        break;
}
