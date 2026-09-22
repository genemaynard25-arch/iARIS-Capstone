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
  assigned_level ENUM(
    'kindergarten', 'is', 'jhs', 'shs',
    'college', 'graduate_school', 'eteeap'
  ) NULL,
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
  level ENUM(
    'kindergarten', 'is', 'jhs', 'shs',
    'college', 'graduate_school', 'eteeap'
  ) NOT NULL,
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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
