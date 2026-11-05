-- =====================================================================
-- গ্রাম বাংলা সঞ্চয় ও ঋণদান সমবায় সমিতি লিমিটেড — ডাটাবেস v3 (সম্পূর্ণ)
-- ৬২ টেবিল · ১১ ভিউ · ট্রিগার · সিকোয়েন্স প্রসিডিউর · প্রাথমিক ডেটা
-- XAMPP (MariaDB/MySQL) · phpMyAdmin > Import দিয়ে চালান (ফাইল ইনকোডিং UTF-8)
-- ডিফল্ট লগইন: admin / Admin@123  (প্রথম লগইনে পাসওয়ার্ড বদলাবেন)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

USE myTableName;

-- T01 branches [BRN-MGMT]
CREATE TABLE IF NOT EXISTS branches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code CHAR(3) NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_main TINYINT(1) NOT NULL DEFAULT 0,
  address VARCHAR(255) NULL,
  phone VARCHAR(30) NULL,
  opened_on DATE NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  status_changed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_branches_code (code),
  CONSTRAINT fk_branches_status_changed_by FOREIGN KEY (status_changed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='শাখা তালিকা (প্রধান কার্যালয় + উপ-শাখা)';

-- T02 roles [BRN-MGMT]
CREATE TABLE IF NOT EXISTS roles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  description VARCHAR(255) NULL,
  data_scope ENUM('all_branches','own_branch','assigned_only','own_only') NOT NULL DEFAULT 'own_branch',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ইউজার রোল';

-- T03 role_permissions [BRN-MGMT]
CREATE TABLE IF NOT EXISTS role_permissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id BIGINT UNSIGNED NOT NULL,
  module_code VARCHAR(30) NOT NULL,
  can_view TINYINT(1) NOT NULL DEFAULT 0,
  can_add TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  can_approve TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_role_permissions_role_id_module_code (role_id, module_code),
  CONSTRAINT fk_role_permissions_role_id FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='রোল অনুযায়ী মডিউল অনুমতি';

-- T04 users [BRN-MGMT]
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  pin_hash VARCHAR(255) NULL,
  full_name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NULL,
  status ENUM('active','locked','inactive') NOT NULL DEFAULT 'active',
  status_changed_by BIGINT UNSIGNED NULL,
  status_changed_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  CONSTRAINT fk_users_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles (id),
  CONSTRAINT fk_users_status_changed_by FOREIGN KEY (status_changed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='লগইন ইউজার';

-- T05 user_status_log [BRN-MGMT]
CREATE TABLE IF NOT EXISTS user_status_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(20) NULL,
  new_status VARCHAR(20) NOT NULL,
  changed_by BIGINT UNSIGNED NOT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reason VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_user_status_log_user_id FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT fk_user_status_log_changed_by FOREIGN KEY (changed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ইউজার চালু-বন্ধের স্থায়ী লগ (মোছা যাবে না)';

-- T06 staff [BRN-MGMT]
CREATE TABLE IF NOT EXISTS staff (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  designation VARCHAR(100) NULL,
  join_date DATE NULL,
  base_salary DECIMAL(14,2) NOT NULL DEFAULT 0,
  nid_no VARCHAR(20) NULL,
  status ENUM('active','resigned') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_staff_user_id (user_id),
  CONSTRAINT fk_staff_user_id FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT fk_staff_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='স্টাফ ও ফিল্ড অফিসার';

-- T07 staff_task_permissions [BRN-MGMT]
CREATE TABLE IF NOT EXISTS staff_task_permissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  task_code VARCHAR(50) NOT NULL,
  assigned_by BIGINT UNSIGNED NOT NULL,
  from_date DATE NOT NULL,
  to_date DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_staff_task_permissions_user_id_task_code (user_id, task_code),
  CONSTRAINT fk_staff_task_permissions_user_id FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT fk_staff_task_permissions_assigned_by FOREIGN KEY (assigned_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='অফিস সহকারীর বরাদ্দ কাজ';

-- T08 settings [BRN-MGMT]
CREATE TABLE IF NOT EXISTS settings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(100) NOT NULL,
  setting_value VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সিস্টেমের নিয়ম-মান';

-- T09 id_sequences [BRN-MGMT]
CREATE TABLE IF NOT EXISTS id_sequences (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  prefix VARCHAR(20) NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  seq_date DATE NOT NULL DEFAULT '1000-01-01',
  last_no INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_id_sequences_prefix_branch_id_seq_date (prefix, branch_id, seq_date),
  CONSTRAINT fk_id_sequences_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='স্বয়ংক্রিয় আইডি জেনারেটর (seq_date = 1000-01-01 মানে তারিখহীন ক্রম)';

-- T10 members [M-MGMT]
CREATE TABLE IF NOT EXISTS members (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_no CHAR(6) NOT NULL,
  member_code VARCHAR(12) NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  full_name VARCHAR(100) NOT NULL,
  father_name VARCHAR(100) NULL,
  mother_name VARCHAR(100) NULL,
  spouse_name VARCHAR(100) NULL,
  birth_date DATE NULL,
  gender ENUM('male','female','other') NULL,
  nid_no VARCHAR(20) NULL,
  phone VARCHAR(30) NULL,
  present_address VARCHAR(255) NULL,
  permanent_address VARCHAR(255) NULL,
  photo_path VARCHAR(255) NULL,
  home_branch_id BIGINT UNSIGNED NOT NULL,
  registered_by BIGINT UNSIGNED NOT NULL,
  center_id BIGINT UNSIGNED NULL,
  group_id BIGINT UNSIGNED NULL,
  joined_on DATE NULL,
  status ENUM('pending','active','closed') NOT NULL DEFAULT 'pending',
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_members_member_code (member_code),
  UNIQUE KEY uq_members_nid_no (nid_no),
  UNIQUE KEY uq_members_home_branch_id_member_no (home_branch_id, member_no),
  CONSTRAINT fk_members_user_id FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT fk_members_home_branch_id FOREIGN KEY (home_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_members_registered_by FOREIGN KEY (registered_by) REFERENCES users (id),
  CONSTRAINT fk_members_center_id FOREIGN KEY (center_id) REFERENCES centers (id),
  CONSTRAINT fk_members_group_id FOREIGN KEY (group_id) REFERENCES member_groups (id),
  CONSTRAINT fk_members_approved_by FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সদস্যের মূল প্রোফাইল';

-- T11 member_nominees [M-MGMT]
CREATE TABLE IF NOT EXISTS member_nominees (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  relation VARCHAR(50) NULL,
  nid_no VARCHAR(20) NULL,
  phone VARCHAR(30) NULL,
  share_percent DECIMAL(6,2) NOT NULL DEFAULT 100,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_member_nominees_member_id FOREIGN KEY (member_id) REFERENCES members (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='নমিনীর তথ্য';

-- T12 member_documents [M-MGMT]
CREATE TABLE IF NOT EXISTS member_documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id BIGINT UNSIGNED NOT NULL,
  doc_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_member_documents_member_id FOREIGN KEY (member_id) REFERENCES members (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সদস্যের স্ক্যান কাগজপত্র';

-- T13 account_types [ACC-MGMT]
CREATE TABLE IF NOT EXISTS account_types (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  type_code CHAR(3) NOT NULL,
  category ENUM('savings','loan','share') NOT NULL,
  name VARCHAR(100) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_account_types_type_code (type_code),
  CONSTRAINT fk_account_types_created_by FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='হিসাবের প্রকার কোড (1xx সঞ্চয়, 2xx ঋণ, 3xx শেয়ার)';

-- T14 savings_products [ACC-MGMT]
CREATE TABLE IF NOT EXISTS savings_products (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  type_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  product_type ENUM('daily','weekly','monthly','fdr','dps') NOT NULL,
  interest_rate DECIMAL(6,2) NOT NULL DEFAULT 0,
  installment_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  term_months INT NULL,
  min_deposit DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_savings_products_type_id FOREIGN KEY (type_id) REFERENCES account_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সঞ্চয় পণ্য ও নিয়ম';

-- T15 savings_accounts [ACC-MGMT]
CREATE TABLE IF NOT EXISTS savings_accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_no VARCHAR(20) NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  home_branch_id BIGINT UNSIGNED NOT NULL,
  serial_no INT NOT NULL DEFAULT 1,
  opened_on DATE NOT NULL,
  maturity_date DATE NULL,
  principal_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('active','matured','closed') NOT NULL DEFAULT 'active',
  closed_on DATE NULL,
  closed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_savings_accounts_account_no (account_no),
  UNIQUE KEY uq_savings_accounts_member_id_product_id_serial_no (member_id, product_id, serial_no),
  CONSTRAINT fk_savings_accounts_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_savings_accounts_product_id FOREIGN KEY (product_id) REFERENCES savings_products (id),
  CONSTRAINT fk_savings_accounts_home_branch_id FOREIGN KEY (home_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_savings_accounts_closed_by FOREIGN KEY (closed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সদস্যের সঞ্চয় হিসাব (একজনের একাধিক হতে পারে)';

-- T16 savings_transactions [ACC-MGMT]
CREATE TABLE IF NOT EXISTS savings_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id BIGINT UNSIGNED NOT NULL,
  transaction_id BIGINT UNSIGNED NOT NULL,
  txn_type ENUM('deposit','withdraw','interest') NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  balance_after DECIMAL(14,2) NOT NULL DEFAULT 0,
  txn_branch_id BIGINT UNSIGNED NOT NULL,
  txn_date DATE NOT NULL,
  collection_sheet_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_savings_transactions_account_id FOREIGN KEY (account_id) REFERENCES savings_accounts (id),
  CONSTRAINT fk_savings_transactions_transaction_id FOREIGN KEY (transaction_id) REFERENCES transactions (id),
  CONSTRAINT fk_savings_transactions_txn_branch_id FOREIGN KEY (txn_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_savings_transactions_collection_sheet_id FOREIGN KEY (collection_sheet_id) REFERENCES collection_sheets (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সঞ্চয় জমা/উত্তোলন খতিয়ান (শুধু নতুন সারি)';

-- T17 dps_schedule [ACC-MGMT]
CREATE TABLE IF NOT EXISTS dps_schedule (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id BIGINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  due_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('due','paid','late') NOT NULL DEFAULT 'due',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_dps_schedule_account_id FOREIGN KEY (account_id) REFERENCES savings_accounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='DPS মাসিক কিস্তির সিডিউল';

-- T18 interest_postings [ACC-MGMT]
CREATE TABLE IF NOT EXISTS interest_postings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id BIGINT UNSIGNED NOT NULL,
  period_label VARCHAR(20) NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  posted_on DATE NOT NULL,
  posted_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_interest_postings_account_id FOREIGN KEY (account_id) REFERENCES savings_accounts (id),
  CONSTRAINT fk_interest_postings_posted_by FOREIGN KEY (posted_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সঞ্চয়ের মুনাফা যোগ রেকর্ড';

-- T19 areas [CEN-MGMT]
CREATE TABLE IF NOT EXISTS areas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_areas_code (code),
  CONSTRAINT fk_areas_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='এলাকা / জোন';

-- T20 centers [CEN-MGMT]
CREATE TABLE IF NOT EXISTS centers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id BIGINT UNSIGNED NOT NULL,
  area_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  meeting_day VARCHAR(20) NULL,
  meeting_time VARCHAR(20) NULL,
  field_officer_id BIGINT UNSIGNED NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_centers_code (code),
  CONSTRAINT fk_centers_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_centers_area_id FOREIGN KEY (area_id) REFERENCES areas (id),
  CONSTRAINT fk_centers_field_officer_id FOREIGN KEY (field_officer_id) REFERENCES staff (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='কেন্দ্র';

-- T21 member_groups [CEN-MGMT]
CREATE TABLE IF NOT EXISTS member_groups (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  center_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  leader_member_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_member_groups_code (code),
  CONSTRAINT fk_member_groups_center_id FOREIGN KEY (center_id) REFERENCES centers (id),
  CONSTRAINT fk_member_groups_leader_member_id FOREIGN KEY (leader_member_id) REFERENCES members (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='কেন্দ্রের অধীন গ্রুপ';

-- T22 staff_assignments [CEN-MGMT]
CREATE TABLE IF NOT EXISTS staff_assignments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_id BIGINT UNSIGNED NOT NULL,
  center_id BIGINT UNSIGNED NOT NULL,
  from_date DATE NOT NULL,
  to_date DATE NULL,
  assigned_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_staff_assignments_staff_id FOREIGN KEY (staff_id) REFERENCES staff (id),
  CONSTRAINT fk_staff_assignments_center_id FOREIGN KEY (center_id) REFERENCES centers (id),
  CONSTRAINT fk_staff_assignments_assigned_by FOREIGN KEY (assigned_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='কোন স্টাফ কোন কেন্দ্র দেখবে (ইতিহাসসহ)';

-- T23 collection_sheets [CEN-MGMT]
CREATE TABLE IF NOT EXISTS collection_sheets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sheet_no VARCHAR(30) NOT NULL,
  center_id BIGINT UNSIGNED NOT NULL,
  staff_id BIGINT UNSIGNED NOT NULL,
  collect_date DATE NOT NULL,
  total_savings DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_loan DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('draft','submitted','approved') NOT NULL DEFAULT 'draft',
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_collection_sheets_sheet_no (sheet_no),
  CONSTRAINT fk_collection_sheets_center_id FOREIGN KEY (center_id) REFERENCES centers (id),
  CONSTRAINT fk_collection_sheets_staff_id FOREIGN KEY (staff_id) REFERENCES staff (id),
  CONSTRAINT fk_collection_sheets_approved_by FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='কেন্দ্রভিত্তিক আদায় শিট';

-- T24 collection_sheet_lines [CEN-MGMT]
CREATE TABLE IF NOT EXISTS collection_sheet_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sheet_id BIGINT UNSIGNED NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  savings_account_id BIGINT UNSIGNED NULL,
  loan_account_id BIGINT UNSIGNED NULL,
  savings_due DECIMAL(14,2) NOT NULL DEFAULT 0,
  loan_due DECIMAL(14,2) NOT NULL DEFAULT 0,
  collected DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_collection_sheet_lines_sheet_id FOREIGN KEY (sheet_id) REFERENCES collection_sheets (id),
  CONSTRAINT fk_collection_sheet_lines_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_collection_sheet_lines_savings_account_id FOREIGN KEY (savings_account_id) REFERENCES savings_accounts (id),
  CONSTRAINT fk_collection_sheet_lines_loan_account_id FOREIGN KEY (loan_account_id) REFERENCES loan_accounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='শিটের সদস্যভিত্তিক লাইন';

-- T25 meeting_attendance [CEN-MGMT]
CREATE TABLE IF NOT EXISTS meeting_attendance (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  center_id BIGINT UNSIGNED NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  meeting_date DATE NOT NULL,
  status ENUM('present','absent','leave') NOT NULL DEFAULT 'present',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_meeting_attendance_center_id_member_id_meeting_date (center_id, member_id, meeting_date),
  CONSTRAINT fk_meeting_attendance_center_id FOREIGN KEY (center_id) REFERENCES centers (id),
  CONSTRAINT fk_meeting_attendance_member_id FOREIGN KEY (member_id) REFERENCES members (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='কেন্দ্র সভায় উপস্থিতি';

-- T26 loan_products [LOAN-MGMT]
CREATE TABLE IF NOT EXISTS loan_products (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  type_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  loan_type ENUM('daily','monthly','yearly','cc','home','shop') NOT NULL,
  interest_rate DECIMAL(6,2) NOT NULL DEFAULT 0,
  interest_method ENUM('flat','declining') NOT NULL DEFAULT 'flat',
  installment_freq ENUM('daily','weekly','monthly','yearly','bullet') NOT NULL DEFAULT 'monthly',
  max_term INT NULL,
  processing_fee_pct DECIMAL(6,2) NOT NULL DEFAULT 0,
  fine_rate DECIMAL(6,2) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_loan_products_type_id FOREIGN KEY (type_id) REFERENCES account_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ঋণ পণ্য ও সুদের নিয়ম';

-- T27 loan_applications [LOAN-MGMT]
CREATE TABLE IF NOT EXISTS loan_applications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  application_no VARCHAR(30) NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  amount_applied DECIMAL(14,2) NOT NULL DEFAULT 0,
  amount_approved DECIMAL(14,2) NOT NULL DEFAULT 0,
  purpose VARCHAR(255) NULL,
  applied_on DATE NOT NULL,
  status ENUM('applied','verified','approved','rejected','disbursed') NOT NULL DEFAULT 'applied',
  verified_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  reject_reason VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_loan_applications_application_no (application_no),
  CONSTRAINT fk_loan_applications_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_loan_applications_product_id FOREIGN KEY (product_id) REFERENCES loan_products (id),
  CONSTRAINT fk_loan_applications_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_loan_applications_verified_by FOREIGN KEY (verified_by) REFERENCES users (id),
  CONSTRAINT fk_loan_applications_approved_by FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ঋণ আবেদন ও অনুমোদন ধাপ';

-- T28 loan_guarantors [LOAN-MGMT]
CREATE TABLE IF NOT EXISTS loan_guarantors (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  application_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  relation VARCHAR(50) NULL,
  nid_no VARCHAR(20) NULL,
  phone VARCHAR(30) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_loan_guarantors_application_id FOREIGN KEY (application_id) REFERENCES loan_applications (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='জামিনদার';

-- T29 loan_accounts [LOAN-MGMT]
CREATE TABLE IF NOT EXISTS loan_accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_no VARCHAR(24) NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  application_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  home_branch_id BIGINT UNSIGNED NOT NULL,
  serial_no INT NOT NULL DEFAULT 1,
  center_id BIGINT UNSIGNED NULL,
  principal DECIMAL(14,2) NOT NULL DEFAULT 0,
  interest_rate DECIMAL(6,2) NOT NULL DEFAULT 0,
  total_interest DECIMAL(14,2) NOT NULL DEFAULT 0,
  disbursed_on DATE NOT NULL,
  first_due_date DATE NULL,
  maturity_date DATE NULL,
  outstanding_principal DECIMAL(14,2) NOT NULL DEFAULT 0,
  outstanding_interest DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('active','closed','defaulted','written_off') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_loan_accounts_loan_no (loan_no),
  UNIQUE KEY uq_loan_accounts_member_id_product_id_serial_no (member_id, product_id, serial_no),
  CONSTRAINT fk_loan_accounts_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_loan_accounts_application_id FOREIGN KEY (application_id) REFERENCES loan_applications (id),
  CONSTRAINT fk_loan_accounts_product_id FOREIGN KEY (product_id) REFERENCES loan_products (id),
  CONSTRAINT fk_loan_accounts_home_branch_id FOREIGN KEY (home_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_loan_accounts_center_id FOREIGN KEY (center_id) REFERENCES centers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='চালু ঋণ হিসাব (একজনের একাধিক হতে পারে)';

-- T30 loan_schedule [LOAN-MGMT]
CREATE TABLE IF NOT EXISTS loan_schedule (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_id BIGINT UNSIGNED NOT NULL,
  installment_no INT NOT NULL,
  due_date DATE NOT NULL,
  principal_due DECIMAL(14,2) NOT NULL DEFAULT 0,
  interest_due DECIMAL(14,2) NOT NULL DEFAULT 0,
  fine_due DECIMAL(14,2) NOT NULL DEFAULT 0,
  paid_principal DECIMAL(14,2) NOT NULL DEFAULT 0,
  paid_interest DECIMAL(14,2) NOT NULL DEFAULT 0,
  paid_fine DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('due','partial','paid','overdue') NOT NULL DEFAULT 'due',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_loan_schedule_loan_id_installment_no (loan_id, installment_no),
  CONSTRAINT fk_loan_schedule_loan_id FOREIGN KEY (loan_id) REFERENCES loan_accounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='কিস্তির সিডিউল';

-- T31 loan_repayments [LOAN-MGMT]
CREATE TABLE IF NOT EXISTS loan_repayments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_id BIGINT UNSIGNED NOT NULL,
  schedule_id BIGINT UNSIGNED NULL,
  transaction_id BIGINT UNSIGNED NOT NULL,
  pay_date DATE NOT NULL,
  principal_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
  interest_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
  fine_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
  txn_branch_id BIGINT UNSIGNED NOT NULL,
  collection_sheet_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_loan_repayments_loan_id FOREIGN KEY (loan_id) REFERENCES loan_accounts (id),
  CONSTRAINT fk_loan_repayments_schedule_id FOREIGN KEY (schedule_id) REFERENCES loan_schedule (id),
  CONSTRAINT fk_loan_repayments_transaction_id FOREIGN KEY (transaction_id) REFERENCES transactions (id),
  CONSTRAINT fk_loan_repayments_txn_branch_id FOREIGN KEY (txn_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_loan_repayments_collection_sheet_id FOREIGN KEY (collection_sheet_id) REFERENCES collection_sheets (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='কিস্তি আদায় খতিয়ান (শুধু নতুন সারি)';

-- T32 vouchers [EXP-MGMT]
CREATE TABLE IF NOT EXISTS vouchers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  voucher_no VARCHAR(30) NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  voucher_date DATE NOT NULL,
  voucher_type ENUM('debit','credit') NOT NULL,
  head_id BIGINT UNSIGNED NOT NULL,
  party_id BIGINT UNSIGNED NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  description VARCHAR(255) NULL,
  attachment_path VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_by BIGINT UNSIGNED NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vouchers_voucher_no (voucher_no),
  CONSTRAINT fk_vouchers_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_vouchers_head_id FOREIGN KEY (head_id) REFERENCES account_heads (id),
  CONSTRAINT fk_vouchers_party_id FOREIGN KEY (party_id) REFERENCES parties (id),
  CONSTRAINT fk_vouchers_created_by FOREIGN KEY (created_by) REFERENCES users (id),
  CONSTRAINT fk_vouchers_approved_by FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='আয়-ব্যয় ভাউচার (পিন ছাড়া approved হবে না)';

-- T33 staff_salary [EXP-MGMT]
CREATE TABLE IF NOT EXISTS staff_salary (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_id BIGINT UNSIGNED NOT NULL,
  salary_month VARCHAR(7) NOT NULL,
  basic DECIMAL(14,2) NOT NULL DEFAULT 0,
  allowance DECIMAL(14,2) NOT NULL DEFAULT 0,
  deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  advance_adjust DECIMAL(14,2) NOT NULL DEFAULT 0,
  net_pay DECIMAL(14,2) NOT NULL DEFAULT 0,
  paid_on DATE NULL,
  voucher_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_staff_salary_staff_id_salary_month (staff_id, salary_month),
  CONSTRAINT fk_staff_salary_staff_id FOREIGN KEY (staff_id) REFERENCES staff (id),
  CONSTRAINT fk_staff_salary_voucher_id FOREIGN KEY (voucher_id) REFERENCES vouchers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='মাসিক বেতন ও অগ্রিম';

-- T34 assets [AST-MGMT]
CREATE TABLE IF NOT EXISTS assets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  asset_code VARCHAR(30) NOT NULL,
  category ENUM('land_building','it','furniture','other') NOT NULL,
  name VARCHAR(100) NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  purchase_date DATE NULL,
  cost DECIMAL(14,2) NOT NULL DEFAULT 0,
  supplier_id BIGINT UNSIGNED NULL,
  useful_life_years INT NULL,
  depreciation_rate DECIMAL(6,2) NOT NULL DEFAULT 0,
  salvage_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('in_use','disposed','damaged') NOT NULL DEFAULT 'in_use',
  voucher_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_assets_asset_code (asset_code),
  CONSTRAINT fk_assets_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_assets_supplier_id FOREIGN KEY (supplier_id) REFERENCES parties (id),
  CONSTRAINT fk_assets_voucher_id FOREIGN KEY (voucher_id) REFERENCES vouchers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সম্পত্তির খতিয়ান';

-- T35 asset_depreciation [AST-MGMT]
CREATE TABLE IF NOT EXISTS asset_depreciation (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  asset_id BIGINT UNSIGNED NOT NULL,
  fiscal_year_id BIGINT UNSIGNED NOT NULL,
  opening_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  depreciation DECIMAL(14,2) NOT NULL DEFAULT 0,
  closing_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_asset_depreciation_asset_id_fiscal_year_id (asset_id, fiscal_year_id),
  CONSTRAINT fk_asset_depreciation_asset_id FOREIGN KEY (asset_id) REFERENCES assets (id),
  CONSTRAINT fk_asset_depreciation_fiscal_year_id FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='বছরভিত্তিক অবচয়';

-- T36 stock_items [AST-MGMT]
CREATE TABLE IF NOT EXISTS stock_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='স্টেশনারি ও ফরম আইটেম';

-- T37 stock_movements [AST-MGMT]
CREATE TABLE IF NOT EXISTS stock_movements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  move_type ENUM('in','out') NOT NULL,
  quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
  move_date DATE NOT NULL,
  remark VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_stock_movements_item_id FOREIGN KEY (item_id) REFERENCES stock_items (id),
  CONSTRAINT fk_stock_movements_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='মজুদ আসা-যাওয়া';

-- T38 transactions [TRX-GW]
CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  txn_no VARCHAR(20) NOT NULL,
  txn_type ENUM('savings_deposit','savings_withdraw','loan_repayment','share_purchase','fee','other') NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  account_code VARCHAR(24) NULL,
  home_branch_id BIGINT UNSIGNED NOT NULL,
  txn_branch_id BIGINT UNSIGNED NOT NULL,
  is_cross_branch TINYINT(1) NOT NULL DEFAULT 0,
  entry_channel ENUM('branch_entry','cross_branch_page','collection_sheet','mobile') NOT NULL DEFAULT 'branch_entry',
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  txn_date DATE NOT NULL,
  txn_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ref_table VARCHAR(50) NULL,
  ref_id BIGINT UNSIGNED NULL,
  receipt_no VARCHAR(30) NOT NULL,
  entered_by BIGINT UNSIGNED NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  reversal_of BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_transactions_txn_no (txn_no),
  UNIQUE KEY uq_transactions_receipt_no (receipt_no),
  CONSTRAINT fk_transactions_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_transactions_home_branch_id FOREIGN KEY (home_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_transactions_txn_branch_id FOREIGN KEY (txn_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_transactions_entered_by FOREIGN KEY (entered_by) REFERENCES users (id),
  CONSTRAINT fk_transactions_approved_by FOREIGN KEY (approved_by) REFERENCES users (id),
  CONSTRAINT fk_transactions_reversal_of FOREIGN KEY (reversal_of) REFERENCES transactions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সব লেনদেনের মাস্টার টেবিল (এডিট/ডিলিট নিষিদ্ধ, ভুল হলে reversal)';

-- T39 statements_issued [TRX-GW]
CREATE TABLE IF NOT EXISTS statements_issued (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id BIGINT UNSIGNED NOT NULL,
  account_kind ENUM('savings','loan','all') NOT NULL DEFAULT 'all',
  account_id BIGINT UNSIGNED NULL,
  from_date DATE NOT NULL,
  to_date DATE NOT NULL,
  purpose VARCHAR(100) NULL,
  issued_branch_id BIGINT UNSIGNED NOT NULL,
  issued_by BIGINT UNSIGNED NOT NULL,
  fee_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_statements_issued_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_statements_issued_issued_branch_id FOREIGN KEY (issued_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_statements_issued_issued_by FOREIGN KEY (issued_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='অনলাইন স্টেটমেন্ট ইস্যুর স্থায়ী রেকর্ড';

-- T40 share_holdings [CSH-MGMT]
CREATE TABLE IF NOT EXISTS share_holdings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id BIGINT UNSIGNED NOT NULL,
  shares INT NOT NULL DEFAULT 0,
  face_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  purchase_date DATE NOT NULL,
  transaction_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_share_holdings_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_share_holdings_transaction_id FOREIGN KEY (transaction_id) REFERENCES transactions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সদস্যের শেয়ার';

-- T41 share_transfers [CSH-MGMT]
CREATE TABLE IF NOT EXISTS share_transfers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  from_member_id BIGINT UNSIGNED NOT NULL,
  to_member_id BIGINT UNSIGNED NOT NULL,
  shares INT NOT NULL,
  transfer_date DATE NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_share_transfers_from_member_id FOREIGN KEY (from_member_id) REFERENCES members (id),
  CONSTRAINT fk_share_transfers_to_member_id FOREIGN KEY (to_member_id) REFERENCES members (id),
  CONSTRAINT fk_share_transfers_approved_by FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='শেয়ার হস্তান্তর';

-- T42 dividend_declarations [CSH-MGMT]
CREATE TABLE IF NOT EXISTS dividend_declarations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  fiscal_year_id BIGINT UNSIGNED NOT NULL,
  rate_percent DECIMAL(6,2) NOT NULL,
  declared_on DATE NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dividend_declarations_fiscal_year_id (fiscal_year_id),
  CONSTRAINT fk_dividend_declarations_fiscal_year_id FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years (id),
  CONSTRAINT fk_dividend_declarations_approved_by FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='বার্ষিক লভ্যাংশ ঘোষণা';

-- T43 dividend_payments [CSH-MGMT]
CREATE TABLE IF NOT EXISTS dividend_payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  declaration_id BIGINT UNSIGNED NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  paid_on DATE NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_dividend_payments_declaration_id FOREIGN KEY (declaration_id) REFERENCES dividend_declarations (id),
  CONSTRAINT fk_dividend_payments_member_id FOREIGN KEY (member_id) REFERENCES members (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সদস্যভিত্তিক লভ্যাংশ বিতরণ';

-- T44 cash_register [CSH-MGMT]
CREATE TABLE IF NOT EXISTS cash_register (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id BIGINT UNSIGNED NOT NULL,
  business_date DATE NOT NULL,
  opening_cash DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_in DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_out DECIMAL(14,2) NOT NULL DEFAULT 0,
  closing_system DECIMAL(14,2) NOT NULL DEFAULT 0,
  closing_physical DECIMAL(14,2) NOT NULL DEFAULT 0,
  difference DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  closed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cash_register_branch_id_business_date (branch_id, business_date),
  CONSTRAINT fk_cash_register_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_cash_register_closed_by FOREIGN KEY (closed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='শাখার দৈনিক ক্যাশ ও মিলকরণ';

-- T45 bank_accounts [CSH-MGMT]
CREATE TABLE IF NOT EXISTS bank_accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  bank_name VARCHAR(100) NOT NULL,
  bank_branch VARCHAR(100) NULL,
  account_no VARCHAR(30) NOT NULL,
  account_type VARCHAR(50) NULL,
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  head_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bank_accounts_account_no (account_no),
  CONSTRAINT fk_bank_accounts_head_id FOREIGN KEY (head_id) REFERENCES account_heads (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সমিতির ব্যাংক হিসাব';

-- T46 bank_transactions [CSH-MGMT]
CREATE TABLE IF NOT EXISTS bank_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  bank_account_id BIGINT UNSIGNED NOT NULL,
  txn_date DATE NOT NULL,
  txn_type ENUM('deposit','withdraw','transfer') NOT NULL,
  cheque_no VARCHAR(30) NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  voucher_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_bank_transactions_bank_account_id FOREIGN KEY (bank_account_id) REFERENCES bank_accounts (id),
  CONSTRAINT fk_bank_transactions_voucher_id FOREIGN KEY (voucher_id) REFERENCES vouchers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ব্যাংক জমা-উত্তোলন ও চেক রেজিস্টার';

-- T47 mobile_banking_txns [CSH-MGMT]
CREATE TABLE IF NOT EXISTS mobile_banking_txns (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider VARCHAR(30) NOT NULL,
  wallet_no VARCHAR(30) NULL,
  gateway_ref VARCHAR(100) NOT NULL,
  direction ENUM('in','out') NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  member_id BIGINT UNSIGNED NULL,
  transaction_id BIGINT UNSIGNED NULL,
  status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mobile_banking_txns_gateway_ref (gateway_ref),
  CONSTRAINT fk_mobile_banking_txns_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_mobile_banking_txns_transaction_id FOREIGN KEY (transaction_id) REFERENCES transactions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='মোবাইল ব্যাংকিং গেটওয়ে লেনদেন';

-- T48 saved_reports [REP-MGMT]
CREATE TABLE IF NOT EXISTS saved_reports (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_type VARCHAR(50) NOT NULL,
  fiscal_year_id BIGINT UNSIGNED NULL,
  branch_id BIGINT UNSIGNED NULL,
  period_from DATE NULL,
  period_to DATE NULL,
  file_path VARCHAR(255) NULL,
  generated_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_saved_reports_fiscal_year_id FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years (id),
  CONSTRAINT fk_saved_reports_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_saved_reports_generated_by FOREIGN KEY (generated_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='সংরক্ষিত কনসোলিডেটেড রিপোর্ট';

-- T49 backup_logs [REP-MGMT]
CREATE TABLE IF NOT EXISTS backup_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  backup_type ENUM('auto_cloud','manual','mirror_sync') NOT NULL,
  target_server VARCHAR(50) NULL,
  file_name VARCHAR(100) NULL,
  size_mb DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('running','success','failed') NOT NULL DEFAULT 'running',
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  triggered_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_backup_logs_triggered_by FOREIGN KEY (triggered_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ব্যাকআপ ও মিরর সিঙ্ক লগ';

-- T50 fiscal_years [LDG-MGMT]
CREATE TABLE IF NOT EXISTS fiscal_years (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(20) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  closed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fiscal_years_name (name),
  CONSTRAINT fk_fiscal_years_closed_by FOREIGN KEY (closed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='অর্থবছর';

-- T51 account_heads [LDG-MGMT]
CREATE TABLE IF NOT EXISTS account_heads (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  head_type ENUM('asset','liability','equity','income','expense') NOT NULL,
  is_cash TINYINT(1) NOT NULL DEFAULT 0,
  is_interbranch TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_account_heads_code (code),
  CONSTRAINT fk_account_heads_parent_id FOREIGN KEY (parent_id) REFERENCES account_heads (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='হিসাব খাতের তালিকা (Chart of Accounts)';

-- T52 parties [LDG-MGMT]
CREATE TABLE IF NOT EXISTS parties (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  party_type ENUM('supplier','creditor','debtor','other') NOT NULL,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NULL,
  address VARCHAR(255) NULL,
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='পক্ষ / সাব-লেজার (সরবরাহকারী, পাওনাদার)';

-- T53 journal_entries [LDG-MGMT]
CREATE TABLE IF NOT EXISTS journal_entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_no VARCHAR(30) NOT NULL,
  entry_date DATE NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  fiscal_year_id BIGINT UNSIGNED NOT NULL,
  source_type VARCHAR(30) NULL,
  source_id BIGINT UNSIGNED NULL,
  narration VARCHAR(255) NULL,
  posted_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_journal_entries_entry_no (entry_no),
  CONSTRAINT fk_journal_entries_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_journal_entries_fiscal_year_id FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years (id),
  CONSTRAINT fk_journal_entries_posted_by FOREIGN KEY (posted_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='জার্নাল এন্ট্রি (হেডার)';

-- T54 journal_lines [LDG-MGMT]
CREATE TABLE IF NOT EXISTS journal_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  member_id BIGINT UNSIGNED NULL,
  party_id BIGINT UNSIGNED NULL,
  center_id BIGINT UNSIGNED NULL,
  debit DECIMAL(14,2) NOT NULL DEFAULT 0,
  credit DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_journal_lines_entry_id FOREIGN KEY (entry_id) REFERENCES journal_entries (id),
  CONSTRAINT fk_journal_lines_account_id FOREIGN KEY (account_id) REFERENCES account_heads (id),
  CONSTRAINT fk_journal_lines_member_id FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_journal_lines_party_id FOREIGN KEY (party_id) REFERENCES parties (id),
  CONSTRAINT fk_journal_lines_center_id FOREIGN KEY (center_id) REFERENCES centers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='জার্নালের ডেবিট/ক্রেডিট লাইন';

-- T55 interbranch_clearing [LDG-MGMT]
CREATE TABLE IF NOT EXISTS interbranch_clearing (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  transaction_id BIGINT UNSIGNED NOT NULL,
  holding_branch_id BIGINT UNSIGNED NOT NULL,
  home_branch_id BIGINT UNSIGNED NOT NULL,
  direction ENUM('in','out') NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  entry_date DATE NOT NULL,
  status ENUM('open','settled') NOT NULL DEFAULT 'open',
  settlement_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_interbranch_clearing_transaction_id (transaction_id),
  CONSTRAINT fk_interbranch_clearing_transaction_id FOREIGN KEY (transaction_id) REFERENCES transactions (id),
  CONSTRAINT fk_interbranch_clearing_holding_branch_id FOREIGN KEY (holding_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_interbranch_clearing_home_branch_id FOREIGN KEY (home_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_interbranch_clearing_settlement_id FOREIGN KEY (settlement_id) REFERENCES interbranch_settlements (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='অন্য শাখার লেনদেনের আন্তঃশাখা পাওনা-দেনা';

-- T56 interbranch_settlements [LDG-MGMT]
CREATE TABLE IF NOT EXISTS interbranch_settlements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  settlement_no VARCHAR(30) NOT NULL,
  from_branch_id BIGINT UNSIGNED NOT NULL,
  to_branch_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  settled_on DATE NOT NULL,
  method ENUM('cash','bank','adjustment') NOT NULL DEFAULT 'cash',
  voucher_id BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_interbranch_settlements_settlement_no (settlement_no),
  CONSTRAINT fk_interbranch_settlements_from_branch_id FOREIGN KEY (from_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_interbranch_settlements_to_branch_id FOREIGN KEY (to_branch_id) REFERENCES branches (id),
  CONSTRAINT fk_interbranch_settlements_voucher_id FOREIGN KEY (voucher_id) REFERENCES vouchers (id),
  CONSTRAINT fk_interbranch_settlements_approved_by FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='দুই শাখার পাওনা-দেনা মেটানো';

-- T57 loan_classifications [AUD-MGMT]
CREATE TABLE IF NOT EXISTS loan_classifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_id BIGINT UNSIGNED NOT NULL,
  as_of_date DATE NOT NULL,
  days_overdue INT NOT NULL DEFAULT 0,
  overdue_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  loan_class ENUM('regular','watch','substandard','doubtful','bad') NOT NULL DEFAULT 'regular',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_loan_classifications_loan_id FOREIGN KEY (loan_id) REFERENCES loan_accounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='খেলাপি ঋণের শ্রেণি বিন্যাস';

-- T58 sms_templates [AUD-MGMT]
CREATE TABLE IF NOT EXISTS sms_templates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(30) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sms_templates_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='এসএমএস বার্তার ছাঁচ';

-- T59 sms_logs [AUD-MGMT]
CREATE TABLE IF NOT EXISTS sms_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id BIGINT UNSIGNED NULL,
  phone VARCHAR(30) NOT NULL,
  message TEXT NOT NULL,
  sms_type VARCHAR(30) NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  sent_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_sms_logs_member_id FOREIGN KEY (member_id) REFERENCES members (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='পাঠানো এসএমএসের লগ';

-- T60 notices [AUD-MGMT]
CREATE TABLE IF NOT EXISTS notices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id BIGINT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  body TEXT NULL,
  publish_date DATE NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_notices_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_notices_created_by FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='নোটিস বোর্ড';

-- T61 activity_log [AUD-MGMT]
CREATE TABLE IF NOT EXISTS activity_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  branch_id BIGINT UNSIGNED NULL,
  action ENUM('create','update','delete','login','print','status_change') NOT NULL,
  table_name VARCHAR(64) NULL,
  record_id BIGINT UNSIGNED NULL,
  old_value JSON NULL,
  new_value JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_activity_log_user_id FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT fk_activity_log_branch_id FOREIGN KEY (branch_id) REFERENCES branches (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='অডিট ট্রেইল (মোছা বা বদলানো যাবে না)';

-- T62 year_closing_log [AUD-MGMT]
CREATE TABLE IF NOT EXISTS year_closing_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  fiscal_year_id BIGINT UNSIGNED NOT NULL,
  closed_by BIGINT UNSIGNED NOT NULL,
  closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  net_surplus DECIMAL(14,2) NOT NULL DEFAULT 0,
  carry_forward_note TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_year_closing_log_fiscal_year_id (fiscal_year_id),
  CONSTRAINT fk_year_closing_log_fiscal_year_id FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years (id),
  CONSTRAINT fk_year_closing_log_closed_by FOREIGN KEY (closed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='অর্থবছর বন্ধের রেকর্ড';


-- =====================================================================
-- ফাংশন: ভিউতে বর্তমান শাখা জানার জন্য (অ্যাপ লগইনের পর SET @current_branch_id = ইউজারের branch_id দেবে)
-- =====================================================================
DROP FUNCTION IF EXISTS fn_current_branch;
CREATE FUNCTION fn_current_branch() RETURNS BIGINT UNSIGNED DETERMINISTIC NO SQL RETURN @current_branch_id;

-- =====================================================================
-- ভিউ V01–V11
-- =====================================================================
-- V01 সদস্যের সব সঞ্চয় হিসাব যোগ করে 'মোট সঞ্চয়'
CREATE OR REPLACE VIEW v_member_total_savings AS
SELECT m.id AS member_id, m.member_code, m.full_name, m.home_branch_id,
       COUNT(a.id) AS account_count, COALESCE(SUM(a.balance),0) AS total_savings
FROM members m LEFT JOIN savings_accounts a ON a.member_id=m.id AND a.status<>'closed'
GROUP BY m.id, m.member_code, m.full_name, m.home_branch_id;

-- V02 সদস্যের সব ঋণ যোগ করে 'মোট ঋণ'
CREATE OR REPLACE VIEW v_member_total_loan AS
SELECT m.id AS member_id, m.member_code, m.full_name, m.home_branch_id,
       COUNT(l.id) AS loan_count,
       COALESCE(SUM(l.outstanding_principal),0) AS total_outstanding_principal,
       COALESCE(SUM(l.outstanding_interest),0) AS total_outstanding_interest,
       COALESCE(SUM(l.outstanding_principal+l.outstanding_interest),0) AS total_loan
FROM members m LEFT JOIN loan_accounts l ON l.member_id=m.id AND l.status IN ('active','defaulted')
GROUP BY m.id, m.member_code, m.full_name, m.home_branch_id;

-- V03 সদস্যের বহুশাখা লেজার (লেনদেনকারী শাখার নামসহ)
CREATE OR REPLACE VIEW v_member_ledger AS
SELECT t.member_id, m.member_code, m.full_name, t.txn_no, t.txn_date, t.txn_at, t.txn_type, t.account_code,
       t.amount, t.receipt_no, t.is_cross_branch, t.entry_channel,
       hb.code AS home_branch_code, hb.name AS home_branch_name,
       tb.code AS txn_branch_code, tb.name AS txn_branch_name, t.reversal_of
FROM transactions t
JOIN members m ON m.id=t.member_id
JOIN branches hb ON hb.id=t.home_branch_id
JOIN branches tb ON tb.id=t.txn_branch_id;

-- V04 বকেয়া কিস্তি ও খেলাপি তালিকা
CREATE OR REPLACE VIEW v_overdue_loans AS
SELECT l.id AS loan_id, l.loan_no, l.member_id, m.full_name, m.phone, l.home_branch_id, l.center_id,
       MIN(s.due_date) AS oldest_due_date, DATEDIFF(CURDATE(), MIN(s.due_date)) AS days_overdue,
       COUNT(s.id) AS overdue_installments,
       SUM((s.principal_due+s.interest_due+s.fine_due)-(s.paid_principal+s.paid_interest+s.paid_fine)) AS overdue_amount
FROM loan_schedule s
JOIN loan_accounts l ON l.id=s.loan_id
JOIN members m ON m.id=l.member_id
WHERE s.status IN ('due','partial','overdue') AND s.due_date<CURDATE() AND l.status IN ('active','defaulted')
GROUP BY l.id, l.loan_no, l.member_id, m.full_name, m.phone, l.home_branch_id, l.center_id;

-- V05 রেওয়ামিল (তলিমিল)
CREATE OR REPLACE VIEW v_trial_balance AS
SELECT h.id AS head_id, h.code, h.name, h.head_type,
       COALESCE(SUM(jl.debit),0) AS total_debit, COALESCE(SUM(jl.credit),0) AS total_credit,
       COALESCE(SUM(jl.debit),0)-COALESCE(SUM(jl.credit),0) AS net_balance
FROM account_heads h LEFT JOIN journal_lines jl ON jl.account_id=h.id
GROUP BY h.id, h.code, h.name, h.head_type;

-- V06 ক্যাশ বুক / ডে বুক (ক্যাশ-ব্যাংক খাতের সব এন্ট্রি)
CREATE OR REPLACE VIEW v_cash_book AS
SELECT je.entry_date, je.entry_no, je.branch_id, h.code AS head_code, h.name AS head_name,
       jl.debit AS cash_in, jl.credit AS cash_out, je.narration, je.source_type, je.source_id
FROM journal_lines jl
JOIN journal_entries je ON je.id=jl.entry_id
JOIN account_heads h ON h.id=jl.account_id
WHERE h.is_cash=1;

-- V07 কেন্দ্রভিত্তিক লেজার
CREATE OR REPLACE VIEW v_center_ledger AS
SELECT c.id AS center_id, c.code AS center_code, c.name AS center_name, c.branch_id,
       je.entry_date, je.entry_no, h.name AS head_name, jl.member_id, jl.debit, jl.credit, je.narration
FROM journal_lines jl
JOIN centers c ON c.id=jl.center_id
JOIN journal_entries je ON je.id=jl.entry_id
JOIN account_heads h ON h.id=jl.account_id;

-- V08 আজকের ড্যাশবোর্ড (শাখা-ভিত্তিক সারি; সুপার অ্যাডমিন সব সারি দেখবে, ম্যানেজার নিজ শাখার সারি)
CREATE OR REPLACE VIEW v_dashboard_today AS
SELECT b.id AS branch_id, b.code AS branch_code, b.name AS branch_name,
  (SELECT COALESCE(SUM(t.amount),0) FROM transactions t WHERE t.txn_branch_id=b.id AND t.txn_date=CURDATE() AND t.txn_type='savings_deposit') AS today_savings_collection,
  (SELECT COALESCE(SUM(t.amount),0) FROM transactions t WHERE t.txn_branch_id=b.id AND t.txn_date=CURDATE() AND t.txn_type='loan_repayment') AS today_loan_collection,
  (SELECT COUNT(*) FROM members m WHERE m.home_branch_id=b.id AND m.status='active') AS active_members,
  (SELECT COALESCE(SUM(a.balance),0) FROM savings_accounts a WHERE a.home_branch_id=b.id AND a.status<>'closed') AS total_savings,
  (SELECT COALESCE(SUM(l.outstanding_principal),0) FROM loan_accounts l WHERE l.home_branch_id=b.id AND l.status IN ('active','defaulted')) AS total_loan_outstanding
FROM branches b WHERE b.status='active';

-- V09 কেন্দ্রীয় অনলাইন খতিয়ান — শুধু সুপার অ্যাডমিন
CREATE OR REPLACE VIEW v_central_ledger AS
SELECT t.id AS transaction_id, t.txn_no, t.txn_date, t.txn_at, t.txn_type, t.account_code,
       t.member_id, m.member_code, m.full_name,
       t.home_branch_id, hb.name AS home_branch_name, t.txn_branch_id, tb.name AS txn_branch_name,
       t.is_cross_branch, t.amount, t.receipt_no, t.entered_by, u.username AS entered_by_username, t.reversal_of
FROM transactions t
JOIN members m ON m.id=t.member_id
JOIN branches hb ON hb.id=t.home_branch_id
JOIN branches tb ON tb.id=t.txn_branch_id
JOIN users u ON u.id=t.entered_by;

-- V10 শাখা খতিয়ান — ম্যানেজারের জন্য, শুধু @current_branch_id শাখার লেনদেন
CREATE OR REPLACE VIEW v_branch_ledger AS
SELECT * FROM v_central_ledger WHERE txn_branch_id = fn_current_branch();

-- V11 দুই শাখার মধ্যে বর্তমান আন্তঃশাখা পাওনা-দেনা (নিষ্পত্তি বাদে)
-- net_payable_by_holding > 0 মানে লেনদেনকারী শাখা নিজ শাখাকে (home) এই টাকা দেবে
CREATE OR REPLACE VIEW v_interbranch_balance AS
SELECT c.holding_branch_id, c.home_branch_id,
       SUM(CASE WHEN c.direction='in'  THEN c.amount ELSE 0 END) AS total_in,
       SUM(CASE WHEN c.direction='out' THEN c.amount ELSE 0 END) AS total_out,
       SUM(CASE WHEN c.direction='in' THEN c.amount ELSE -c.amount END) AS net_payable_by_holding
FROM interbranch_clearing c WHERE c.status='open'
GROUP BY c.holding_branch_id, c.home_branch_id;

-- =====================================================================
-- প্রসিডিউর: স্বয়ংক্রিয় আইডি (সদস্য নম্বর, লেনদেন আইডি, রসিদ নম্বর)
-- ব্যবহার: CALL sp_next_seq('TXN', 1, CURDATE(), @n);  SELECT @n;
-- তারিখহীন ক্রম (সদস্য নম্বর) হলে তারিখ দিন NULL: CALL sp_next_seq('MEM', 1, NULL, @n);
-- =====================================================================
DROP PROCEDURE IF EXISTS sp_next_seq;
DELIMITER $$
CREATE PROCEDURE sp_next_seq(IN p_prefix VARCHAR(20), IN p_branch BIGINT UNSIGNED, IN p_date DATE, OUT p_no INT UNSIGNED)
BEGIN
  DECLARE d DATE;
  SET d = IFNULL(p_date, '1000-01-01');
  INSERT INTO id_sequences(prefix, branch_id, seq_date, last_no) VALUES (p_prefix, p_branch, d, 1)
    ON DUPLICATE KEY UPDATE last_no = last_no + 1;
  SELECT last_no INTO p_no FROM id_sequences WHERE prefix=p_prefix AND branch_id=p_branch AND seq_date=d;
END$$
DELIMITER ;

-- =====================================================================
-- ট্রিগার (ডাটাবেস নিজেই নিয়ম বাস্তবায়ন করে)
-- =====================================================================
DELIMITER $$

-- নিয়ম ১: সঞ্চয় উত্তোলনের পর ব্যালেন্স settings.min_balance (১০০) এর নিচে নামবে না
DROP TRIGGER IF EXISTS trg_sav_min_balance$$
CREATE TRIGGER trg_sav_min_balance BEFORE INSERT ON savings_transactions FOR EACH ROW
BEGIN
  DECLARE minbal DECIMAL(14,2);
  IF NEW.txn_type = 'withdraw' THEN
    SELECT CAST(setting_value AS DECIMAL(14,2)) INTO minbal FROM settings WHERE setting_key='min_balance';
    IF NEW.balance_after < IFNULL(minbal,100) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'উত্তোলনের পর সর্বনিম্ন ব্যালেন্স বজায় থাকছে না';
    END IF;
  END IF;
END$$

-- নিয়ম ৪: লেনদেন এডিট/ডিলিট নিষিদ্ধ (ভুল হলে reversal_of দিয়ে উল্টো এন্ট্রি)
DROP TRIGGER IF EXISTS trg_txn_no_update$$
CREATE TRIGGER trg_txn_no_update BEFORE UPDATE ON transactions FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'লেনদেন এডিট করা যাবে না — উল্টো (reversal) এন্ট্রি দিন'; END$$
DROP TRIGGER IF EXISTS trg_txn_no_delete$$
CREATE TRIGGER trg_txn_no_delete BEFORE DELETE ON transactions FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'লেনদেন ডিলিট করা যাবে না — উল্টো (reversal) এন্ট্রি দিন'; END$$

-- লেনদেনকারী শাখা ≠ নিজ শাখা হলে is_cross_branch স্বয়ংক্রিয় ১
DROP TRIGGER IF EXISTS trg_txn_cross_flag$$
CREATE TRIGGER trg_txn_cross_flag BEFORE INSERT ON transactions FOR EACH ROW
BEGIN SET NEW.is_cross_branch = IF(NEW.home_branch_id <> NEW.txn_branch_id, 1, 0); END$$

-- সঞ্চয় খতিয়ান ও কিস্তি আদায়ও শুধু নতুন সারি
DROP TRIGGER IF EXISTS trg_savtxn_no_update$$
CREATE TRIGGER trg_savtxn_no_update BEFORE UPDATE ON savings_transactions FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'সঞ্চয় লেনদেন এডিট করা যাবে না'; END$$
DROP TRIGGER IF EXISTS trg_savtxn_no_delete$$
CREATE TRIGGER trg_savtxn_no_delete BEFORE DELETE ON savings_transactions FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'সঞ্চয় লেনদেন ডিলিট করা যাবে না'; END$$
DROP TRIGGER IF EXISTS trg_repay_no_update$$
CREATE TRIGGER trg_repay_no_update BEFORE UPDATE ON loan_repayments FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'কিস্তি আদায় এডিট করা যাবে না'; END$$
DROP TRIGGER IF EXISTS trg_repay_no_delete$$
CREATE TRIGGER trg_repay_no_delete BEFORE DELETE ON loan_repayments FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'কিস্তি আদায় ডিলিট করা যাবে না'; END$$

-- নিয়ম ৫: ম্যানেজারের পিন/অনুমোদন ছাড়া ভাউচার approved হবে না
DROP TRIGGER IF EXISTS trg_voucher_approve_ins$$
CREATE TRIGGER trg_voucher_approve_ins BEFORE INSERT ON vouchers FOR EACH ROW
BEGIN
  IF NEW.status='approved' AND (NEW.approved_by IS NULL OR NEW.approved_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'অনুমোদনকারী ছাড়া ভাউচার approved করা যাবে না';
  END IF;
END$$
DROP TRIGGER IF EXISTS trg_voucher_approve_upd$$
CREATE TRIGGER trg_voucher_approve_upd BEFORE UPDATE ON vouchers FOR EACH ROW
BEGIN
  IF NEW.status='approved' AND (NEW.approved_by IS NULL OR NEW.approved_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'অনুমোদনকারী ছাড়া ভাউচার approved করা যাবে না';
  END IF;
END$$

-- নিয়ম ১৫: ইউজার চালু/বন্ধ শুধু সুপার অ্যাডমিন করবে — status_changed_by বাধ্যতামূলক, লগ স্বয়ংক্রিয়
DROP TRIGGER IF EXISTS trg_user_status_before$$
CREATE TRIGGER trg_user_status_before BEFORE UPDATE ON users FOR EACH ROW
BEGIN
  IF NEW.status <> OLD.status THEN
    IF NEW.status_changed_by IS NULL OR NEW.status_changed_by = OLD.status_changed_by AND NEW.status_changed_at <=> OLD.status_changed_at THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'অবস্থা বদলাতে status_changed_by ও status_changed_at দিতে হবে';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM users su JOIN roles r ON r.id=su.role_id WHERE su.id=NEW.status_changed_by AND r.name='super_admin') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'শুধু সুপার অ্যাডমিন ইউজারের অবস্থা বদলাতে পারবেন';
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS trg_user_status_after$$
CREATE TRIGGER trg_user_status_after AFTER UPDATE ON users FOR EACH ROW
BEGIN
  IF NEW.status <> OLD.status THEN
    INSERT INTO user_status_log(user_id, old_status, new_status, changed_by, changed_at)
    VALUES (NEW.id, OLD.status, NEW.status, NEW.status_changed_by, IFNULL(NEW.status_changed_at, NOW()));
  END IF;
END$$

-- স্থায়ী লগ: মোছা যায় না (user_status_log, activity_log); activity_log বদলানোও যায় না
DROP TRIGGER IF EXISTS trg_usl_no_delete$$
CREATE TRIGGER trg_usl_no_delete BEFORE DELETE ON user_status_log FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ইউজার অবস্থা লগ মোছা যাবে না'; END$$
DROP TRIGGER IF EXISTS trg_actlog_no_update$$
CREATE TRIGGER trg_actlog_no_update BEFORE UPDATE ON activity_log FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'অডিট লগ বদলানো যাবে না'; END$$
DROP TRIGGER IF EXISTS trg_actlog_no_delete$$
CREATE TRIGGER trg_actlog_no_delete BEFORE DELETE ON activity_log FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'অডিট লগ মোছা যাবে না'; END$$

DELIMITER ;


-- =====================================================================
-- প্রাথমিক ডেটা (INSERT IGNORE — আবার ইমপোর্ট করলে ডুপ্লিকেট হবে না)
-- =====================================================================
INSERT IGNORE INTO branches (id, code, name, is_main, address, opened_on, status) VALUES
(1,'101','প্রধান কার্যালয়',1,'পূর্ব গ্রাম, আইকুলা, সাঁথিয়া, পাবনা','2026-01-01','active');

INSERT IGNORE INTO roles (id, name, description, data_scope) VALUES
(1,'super_admin','সুপার অ্যাডমিন — সব শাখা ও কেন্দ্রীয় রিপোর্ট','all_branches'),
(2,'manager','শাখা ম্যানেজার — শুধু নিজ শাখা','own_branch'),
(3,'cashier','অফিস সহকারী — ম্যানেজারের বরাদ্দ করা কাজ','own_branch'),
(4,'field_officer','ফিল্ড অফিসার — শুধু নিজ কেন্দ্র','assigned_only'),
(5,'member','সদস্য — শুধু নিজের তথ্য','own_only');

INSERT IGNORE INTO users (id, branch_id, role_id, username, password_hash, pin_hash, full_name, status) VALUES
(1,1,1,'admin','$6$GVCUg7UWKqacOH7r$GRK.pV4HmClARflp1HMBxempwHNMAmSkpAWJ7BR4xrsnT8yqFGKPwVEqB4ZRjAY6hZSb7Dl2UOrWSvvmyCo6o1','$6$.B4fM5HhEmX8FPYX$c7ekfq34otOczFBvHSgKTfizrk4vkDcHcsmuME2KM/J3HvK5VesrCNm10QJi.sdMX5qcF0ol4gIX6VUlF4guX/','সুপার অ্যাডমিন','active');

INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
('min_balance','100','সঞ্চয়ে সর্বনিম্ন ব্যালেন্স (টাকা)'),
('society_name','গ্রাম বাংলা সঞ্চয় ও ঋণদান সমবায় সমিতি লিমিটেড','সমিতির নাম');

INSERT IGNORE INTO account_types (type_code, category, name, created_by) VALUES
('101','savings','দৈনিক সঞ্চয়',1),('102','savings','সাপ্তাহিক সঞ্চয়',1),('103','savings','মাসিক সঞ্চয়',1),
('104','savings','DPS',1),('105','savings','FDR',1),
('201','loan','দৈনিক ঋণ',1),('202','loan','মাসিক ঋণ',1),('203','loan','বাৎসরিক ঋণ',1),
('204','loan','সিসি ঋণ',1),('205','loan','হোম ঋণ',1),('206','loan','দোকান ঋণ',1),
('301','share','শেয়ার',1);

INSERT INTO savings_products (type_id, name, product_type, interest_rate, installment_amount, term_months, min_deposit)
SELECT t.id, p.name, p.pt, p.rate, p.inst, p.term, 0 FROM (
 SELECT '101' c,'দৈনিক সঞ্চয়' name,'daily' pt,6.00 rate,0 inst,NULL term UNION ALL
 SELECT '102','সাপ্তাহিক সঞ্চয়','weekly',6.00,0,NULL UNION ALL
 SELECT '103','মাসিক সঞ্চয়','monthly',6.00,0,NULL UNION ALL
 SELECT '104','DPS (৫ বছর)','dps',8.00,1000,60 UNION ALL
 SELECT '105','FDR (১ বছর)','fdr',9.00,0,12) p
JOIN account_types t ON t.type_code=p.c
WHERE NOT EXISTS (SELECT 1 FROM savings_products);

INSERT INTO loan_products (type_id, name, loan_type, interest_rate, interest_method, installment_freq, max_term, processing_fee_pct, fine_rate)
SELECT t.id, p.name, p.lt, p.rate, p.im, p.fq, p.mt, 1.00, 1.00 FROM (
 SELECT '201' c,'দৈনিক ঋণ' name,'daily' lt,12.00 rate,'flat' im,'daily' fq,100 mt UNION ALL
 SELECT '202','মাসিক ঋণ','monthly',12.00,'flat','monthly',24 UNION ALL
 SELECT '203','বাৎসরিক ঋণ','yearly',12.00,'flat','yearly',5 UNION ALL
 SELECT '204','সিসি ঋণ','cc',13.00,'declining','bullet',12 UNION ALL
 SELECT '205','হোম ঋণ','home',11.00,'declining','monthly',120 UNION ALL
 SELECT '206','দোকান ঋণ','shop',12.00,'flat','monthly',36) p
JOIN account_types t ON t.type_code=p.c
WHERE NOT EXISTS (SELECT 1 FROM loan_products);

INSERT IGNORE INTO account_heads (code, name, head_type, is_cash, is_interbranch) VALUES
('1101','হাতে নগদ','asset',1,0),('1102','ব্যাংক হিসাব','asset',1,0),('1103','মোবাইল ব্যাংকিং ওয়ালেট','asset',1,0),
('1201','সদস্য ঋণ (আসল)','asset',0,0),('1202','ঋণের প্রাপ্য সুদ','asset',0,0),
('1301','আন্তঃশাখা সমন্বয়','asset',0,1),('1401','স্থায়ী সম্পদ','asset',0,0),
('2101','সদস্য সঞ্চয়','liability',0,0),('2102','সদস্য DPS','liability',0,0),('2103','সদস্য FDR','liability',0,0),('2201','পাওনাদার','liability',0,0),
('3101','শেয়ার মূলধন','equity',0,0),('3102','সংরক্ষিত তহবিল','equity',0,0),('3103','বছরের নিট উদ্বৃত্ত','equity',0,0),
('4101','ঋণের সুদ আয়','income',0,0),('4102','প্রসেসিং ফি আয়','income',0,0),('4103','জরিমানা আয়','income',0,0),
('4104','স্টেটমেন্ট ফি আয়','income',0,0),('4199','অন্যান্য আয়','income',0,0),
('5101','বেতন ও ভাতা','expense',0,0),('5102','অফিস ভাড়া','expense',0,0),('5103','বিদ্যুৎ ও ইউটিলিটি','expense',0,0),
('5104','স্টেশনারি ও মুদ্রণ','expense',0,0),('5105','সঞ্চয়ের মুনাফা ব্যয়','expense',0,0),('5106','অবচয় ব্যয়','expense',0,0),
('5199','অন্যান্য ব্যয়','expense',0,0);

INSERT IGNORE INTO fiscal_years (name, start_date, end_date, status) VALUES ('2026-27','2026-07-01','2027-06-30','open');

INSERT IGNORE INTO sms_templates (code, body) VALUES
('DUE_REMINDER','প্রিয় {name}, আপনার কিস্তি {amount} টাকা {date} তারিখে পরিশোধযোগ্য। — গ্রাম বাংলা সমিতি'),
('TXN_CONFIRM','প্রিয় {name}, আপনার হিসাব {account} এ {amount} টাকা লেনদেন সম্পন্ন। রসিদ: {receipt}');

INSERT IGNORE INTO role_permissions (role_id, module_code, can_view, can_add, can_edit, can_delete, can_approve) VALUES
((SELECT id FROM roles WHERE name='super_admin'),'BRN-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'M-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'ACC-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'CEN-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'LOAN-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'EXP-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'AST-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'TRX-GW',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'CSH-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'REP-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'LDG-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='super_admin'),'AUD-MGMT',1,1,1,1,1),
((SELECT id FROM roles WHERE name='manager'),'M-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'ACC-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'CEN-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'LOAN-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'EXP-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'AST-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'TRX-GW',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'CSH-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'LDG-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='manager'),'AUD-MGMT',1,1,1,0,1),
((SELECT id FROM roles WHERE name='cashier'),'M-MGMT',1,1,0,0,0),
((SELECT id FROM roles WHERE name='cashier'),'ACC-MGMT',1,1,0,0,0),
((SELECT id FROM roles WHERE name='cashier'),'LOAN-MGMT',1,1,0,0,0),
((SELECT id FROM roles WHERE name='cashier'),'TRX-GW',1,1,0,0,0),
((SELECT id FROM roles WHERE name='field_officer'),'CEN-MGMT',1,1,0,0,0),
((SELECT id FROM roles WHERE name='field_officer'),'ACC-MGMT',1,1,0,0,0),
((SELECT id FROM roles WHERE name='field_officer'),'LOAN-MGMT',1,1,0,0,0),
((SELECT id FROM roles WHERE name='field_officer'),'TRX-GW',1,1,0,0,0),
((SELECT id FROM roles WHERE name='member'),'ACC-MGMT',1,0,0,0,0),
((SELECT id FROM roles WHERE name='member'),'LOAN-MGMT',1,0,0,0,0),
((SELECT id FROM roles WHERE name='member'),'TRX-GW',1,0,0,0,0);


SET FOREIGN_KEY_CHECKS=1;
-- সম্পন্ন: ৬২ টেবিল · ১১ ভিউ · ১ ফাংশন · ১ প্রসিডিউর · ট্রিগার · প্রাথমিক ডেটা