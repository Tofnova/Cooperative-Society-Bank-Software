# গ্রাম বাংলা সঞ্চয় ও ঋণদান সমবায় সমিতি — Admin Panel

PHP 7.4+ (PDO MySQL) · MySQL 8 / MariaDB 10.5+ · কোনো framework নেই।

## Setup (প্রথমবার)

1. **Database বানান** (utf8mb4) এবং আপনার **পুরো SQL file** (সব table + trigger + seed সহ) import করুন।
2. `database/seed.sql` import করুন — এতে আছে: app নাম/লোগো setting, প্রধান কার্যালয় (101),
   প্রথম super admin, আর manager/cashier/field_officer এর শুরুর permission।
   (আপনার SQL এর শেষের role আর min_balance insert এর সাথে মিলে যাবে — `INSERT IGNORE` ব্যবহার করা হয়েছে।)
   আগে থেকে তৈরি database-এ `database/migration_roles_data_scope.sql` এবং `database/migration_branch_code_numeric.sql` একবার করে import করুন।
3. `config.php` এ database নাম/ইউজার/পাসওয়ার্ড ঠিক আছে কিনা দেখুন
   (server এ ENV থেকেও নেয়: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`)।
4. Login: `/login`

   | | |
   |---|---|
   | Username | `admin` |
   | Temporary password | `dbiUdqrIjagL1x` |

   **প্রথম login করেই** *My Account → Password Update* থেকে পাসওয়ার্ড বদলান।
5. Setup শেষে `database/seed.sql` server থেকে মুছে দিন।
6. `uploads/members/` লেখার অনুমতি (chmod 755/775) থাকতে হবে।

## Folder

| Path | কাজ |
|---|---|
| `index.php`, `route.php` | সব URL এখান দিয়ে যায় |
| `function/function.php` | সব helper, login, permission, database function |
| `components/admin` | Login account (list, create, info, update, নিজের account) |
| `components/branch` | Branch (list, create, edit) |
| `components/models/{areas,center,staff}` | এলাকা, কেন্দ্র, স্টাফ |
| `components/membars` | সদস্য (list, নতুন, deactive) |
| `components/settings` | Roles & Permissions, Settings |
| `style/style.css` | একটাই stylesheet (tokens `:root` এ) |

## নিয়মকানুন (code এ আছে)

- **Permission:** `role_permissions` table দিয়ে (module: USER-MGMT, BRANCH-MGMT, STAFF-MGMT, AREA-MGMT, CENTER-MGMT, M-MGMT, ACC-MGMT, SETTINGS)।
  `super_admin` সবসময় সব পায়। বদলাতে: *System → Roles & Permissions*।
- **Branch scope:** super admin ছাড়া কেউ শুধু নিজের branch এর user/staff/area/center ম্যানেজ করতে পারে।
- **Role data scope:** super_admin সব branch দেখে; manager/cashier নিজের branch দেখে; field_officer assigned-only এবং member own-only scope পায়।
- **সদস্য:** নতুন সদস্য `pending` থাকে; manager approve করলে `active`। সদস্যের নম্বর প্রতি home branch-এ ছয় অঙ্কে `000001` থেকে শুরু হয়।
  শুধু **home branch** থেকেই সদস্যের profile edit ও close করা যায় (super admin-সহ); home branch পরিবর্তন করা যায় না।
  Approval home branch-এর manager করে; super admin-ও approve করতে পারে।
  সক্রিয় সঞ্চয় হিসাব বা চালু লোন থাকলে সদস্য close করা যায় না।
- **কোড অটো:** Branch `101`, `102`-এর মতো তিন অঙ্কের numeric code; Area `ARE-xx`, Center `CNT-xx` (`id_sequences`), সদস্যের নম্বর `000001`-এর মতো branch-wise, আর member code `<branch-code>-<member-no>`।
- **একটাই Main branch**, এক branch এ একটাই active manager।
- **Login নিরাপত্তা:** CSRF token, bcrypt, ১৫ মিনিটে ভুল পাসওয়ার্ড লিমিট (user ৫ বার / IP ১০ বার), session fixation protection।
- **Audit:** create/update/approve/close/login `activity_log` এ লেখা হয় (পাসওয়ার্ড কখনও নয়)।
- **ফাইল আপলোড:** শুধু JPG/PNG/WEBP, ২ MB, random নাম; `uploads/members/.htaccess` এ script চালানো বন্ধ।

## নতুন মডিউল যোগ করতে

1. `function/function.php` এ function লিখুন (`need()`, `db_*()` helper ব্যবহার করুন)।
2. `MODULES` এ module code যোগ করুন, `route.php` এ route, `side_bar.php` এ মেনু।
3. page এ `require_perm('MODULE', 'can_view')` দিয়ে শুরু করুন।
