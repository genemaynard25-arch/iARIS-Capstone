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
  role ENUM('admin', 'iato_staff', 'program_chair', 'dean', 'president', 'chancellor') NOT NULL DEFAULT 'iato_staff',
  mfa_secret VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
