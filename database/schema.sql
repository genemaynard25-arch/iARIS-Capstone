-- iARIS: IATO Admissions Records and Information System
-- Step 1: Create the database

CREATE DATABASE IF NOT EXISTS iaris
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE iaris;

-- Step 2: Create the users table
-- This holds IATO staff accounts (the people who log in),
-- not applicant records — that table comes later.

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM(
    'admin', 'iato_staff', 'program_chair', 'dean',
    'president', 'vice_president', 'principal',
    'chancellor', 'vice_chancellor', 'registrar', 'lamp'
  ) NOT NULL DEFAULT 'iato_staff',
  access_type ENUM('viewer', 'editor') NOT NULL DEFAULT 'viewer',
  assigned_level ENUM('is', 'college', 'graduate_school', 'eteeap') NULL,
  assigned_sub_level ENUM('kindergarten', 'jhs', 'shs') NULL,
  mfa_secret VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Step 3: Create the applicants table
-- This holds the actual admissions records IATO manages,
-- one row per applicant per application cycle (school year).

CREATE TABLE applicants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_number VARCHAR(30) NOT NULL UNIQUE,
  school_year VARCHAR(9) NOT NULL,
  level ENUM('is', 'college', 'graduate_school', 'eteeap') NOT NULL,
  sub_level ENUM('kindergarten', 'jhs', 'shs') NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  gender ENUM('male', 'female') NOT NULL,
  feeder_school VARCHAR(150) NULL,
  program_or_track VARCHAR(150) NULL,
  applicant_type ENUM('regular', 'scholar') NOT NULL DEFAULT 'regular',
  admission_test_status ENUM(
    'not_taken', 'took_test', 'passed', 'failed',
    'qualified_other_degree', 'reconsidered'
  ) NOT NULL DEFAULT 'not_taken',
  noa_issued BOOLEAN NOT NULL DEFAULT FALSE,
  application_status ENUM(
    'pooling', 'submitted', 'completed', 'not_submitted'
  ) NOT NULL DEFAULT 'pooling',
  archived_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Step 4: Create the payments table
-- One applicant can have multiple fee events (application fee,
-- then later a reservation fee), so this is its own table
-- linked back to applicants instead of extra columns on applicants.

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  applicant_id BIGINT UNSIGNED NOT NULL,
  fee_type ENUM('application_fee', 'reservation_fee') NOT NULL,
  status ENUM('paid', 'waived', 'unpaid') NOT NULL DEFAULT 'unpaid',
  amount DECIMAL(10, 2) NULL,
  paid_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (applicant_id) REFERENCES applicants(id)
);

-- Step 5: Create the import_batches table
-- One row per weekly Excel upload. This traces every applicant
-- record back to exactly which upload created/updated it, and
-- who uploaded it — fixing the "lost timestamp" problem from
-- Chapter 1, where updates silently overwrote when data arrived.

CREATE TABLE import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  row_count INT UNSIGNED NULL,
  status ENUM('processing', 'completed', 'failed') NOT NULL DEFAULT 'processing',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Step 5b: Link applicants back to the import batch that created them
ALTER TABLE applicants
  ADD COLUMN imported_batch_id BIGINT UNSIGNED NULL AFTER id,
  ADD FOREIGN KEY (imported_batch_id) REFERENCES import_batches(id);

-- Step 6: Create the audit_logs table
-- Records every create/update/delete made to applicant data:
-- who did it, what changed, and when. Fixes the "no audit trail,
-- can't hold anyone accountable" problem from Chapter 1.

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  table_name VARCHAR(50) NOT NULL,
  record_id BIGINT UNSIGNED NOT NULL,
  action ENUM('create', 'update', 'delete') NOT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
