-- This script creates the 'rawa_bank_db' database and its tables,
-- including relationships and initial data.

-- 1. Create the database (if it doesn't exist)
-- Uncomment the line below if you want to drop the database for a clean start
-- DROP DATABASE IF EXISTS `rawa_bank_db`;
CREATE DATABASE IF NOT EXISTS `rawa_bank_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 2. Use the newly created database
USE `rawa_bank_db`;

-- 3. Create the Users table
-- This table stores basic login information for guardians and foster families
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `national_id` VARCHAR(10) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `user_type` ENUM('guardian', 'foster_family') NOT NULL DEFAULT 'guardian', -- User type (guardian or foster_family)
  `failed_attempts` INT(11) DEFAULT 0,
  `account_locked_until` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Create the Guardians_Profiles table
-- Stores additional guardian-specific data from the registration form
CREATE TABLE IF NOT EXISTS `guardians_profiles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE, -- Foreign key to users table (user_type must be 'guardian')
  `full_name` VARCHAR(255) NOT NULL,
  `relationship_to_orphan` ENUM('uncle', 'grandfather', 'court_guardian', 'official_entity') NOT NULL,
  `has_guardianship_document` TINYINT(1) NOT NULL, -- 1 for True, 0 for False
  `orphan_income_source` ENUM('inheritance', 'social_security', 'government_support') DEFAULT NULL,
  `has_financial_restriction` TINYINT(1) NOT NULL,
  `is_orphan_studying` TINYINT(1) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Create the Foster_Families_Profiles table
-- Stores additional foster family-specific data from the registration form
CREATE TABLE IF NOT EXISTS `foster_families_profiles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE, -- Foreign key to users table (user_type must be 'foster_family')
  `foster_family_head_name` VARCHAR(255) NOT NULL,
  `has_official_fostering_decision` TINYINT(1) NOT NULL, -- 1 for True, 0 for False
  `fostering_type` ENUM('temporary', 'permanent') NOT NULL, -- Temporary or Permanent fostering
  `child_age_group` ENUM('under_6', '6_or_more') NOT NULL, -- Under 6 years or 6 years or more
  `is_child_studying` TINYINT(1) NOT NULL, -- 1 for True, 0 for False
  `child_study_level` VARCHAR(255) DEFAULT NULL, -- Child's study level (optional)
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Create the Orphans table
-- Stores data for each orphan
CREATE TABLE IF NOT EXISTS `orphans` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `guardian_user_id` INT(11) NOT NULL, -- Foreign key to the guardian user responsible for this orphan
  `foster_family_user_id` INT(11) DEFAULT NULL, -- Foreign key to the foster family user (optional)
  `name` VARCHAR(255) NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `gender` ENUM('male', 'female') NOT NULL,
  `status` ENUM('active', 'inactive', 'graduated', 'transferred') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`guardian_user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE, -- RESTRICT to prevent deleting a guardian with active orphans
  FOREIGN KEY (`foster_family_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Create the Accounts table
-- Accounts are linked to orphans, as the funds belong to the orphan
CREATE TABLE IF NOT EXISTS `accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `orphan_id` INT(11) NOT NULL UNIQUE, -- Foreign key linking the account to the orphan (one orphan has one account)
  `account_number` VARCHAR(20) NOT NULL UNIQUE,
  `account_type` ENUM('checking', 'savings', 'current') NOT NULL DEFAULT 'checking',
  `balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`orphan_id`) REFERENCES `orphans`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. Create the Transactions table
-- Records all financial movements
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `account_id` INT(11) NOT NULL, -- Foreign key linking the transaction to an account
  `transaction_type` ENUM('deposit', 'withdrawal', 'transfer_in', 'transfer_out') NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `transaction_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `description` VARCHAR(255) DEFAULT NULL,
  `related_account_id` INT(11) DEFAULT NULL, -- For transfers, links to the other involved account
  PRIMARY KEY (`id`),
  FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`related_account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- *******************************************************************
-- 9. Initial Data for testing the system
-- *******************************************************************

-- 9.1. Insert users (one guardian, one foster family)
-- Password for '1234567890' is 'TestPassword123' (hashed)
INSERT INTO `users` (`national_id`, `password_hash`, `user_type`, `failed_attempts`, `account_locked_until`) VALUES
('1234567890', '$2b$10$s4N9O1k0D5d7j2S3m4g0u.M.N.O.P.Q.R.S.T.U.V.W.X.Y.Z.0.1.2.3.4.5.6.7.8.9.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z', 'guardian', 0, NULL);

-- Password for '9876543210' is 'AnotherSecurePass' (hashed)
INSERT INTO `users` (`national_id`, `password_hash`, `user_type`, `failed_attempts`, `account_locked_until`) VALUES
('9876543210', '$2b$10$5X.A/B.C.D.E.F.G.H.I.J.K.L.M.N.O.P.Q.R.S.T.U.V.W.X.Y.Z.0.1.2.3.4.5.6.7.8', 'foster_family', 0, NULL);

-- 9.2. Insert a guardian profile (linked to the first user)
-- Ensure user_id here is the ID of the guardian user (usually 1 if the database is empty)
INSERT INTO `guardians_profiles` (`user_id`, `full_name`, `relationship_to_orphan`, `has_guardianship_document`, `orphan_income_source`, `has_financial_restriction`, `is_orphan_studying`) VALUES
(1, 'أحمد محمد عبدالله', 'court_guardian', 1, 'government_support', 0, 1);

-- 9.3. Insert a foster family profile (linked to the second user)
-- Ensure user_id here is the ID of the foster family user (usually 2 if the database is empty)
INSERT INTO `foster_families_profiles` (`user_id`, `foster_family_head_name`, `has_official_fostering_decision`, `fostering_type`, `child_age_group`, `is_child_studying`, `child_study_level`) VALUES
(2, 'فاطمة خالد', 1, 'permanent', '6_or_more', 1, 'المرحلة الابتدائية');


-- 9.4. Insert an orphan (linked to the first guardian)
-- Ensure guardian_user_id here is the ID of the guardian user (usually 1)
INSERT INTO `orphans` (`guardian_user_id`, `name`, `date_of_birth`, `gender`, `status`) VALUES
(1, 'ليلى أحمد عبدالله', '2010-05-15', 'female', 'active');

-- 9.5. Insert an account for the orphan (linked to the first orphan)
-- Ensure orphan_id here is the ID of the orphan (usually 1)
INSERT INTO `accounts` (`orphan_id`, `account_number`, `account_type`, `balance`) VALUES
(1, '1000000001', 'savings', 7500.50);

-- 9.6. Insert some transactions for the orphan's account
-- Ensure account_id here is the ID of the account (usually 1)
INSERT INTO `transactions` (`account_id`, `transaction_type`, `amount`, `description`) VALUES
(1, 'deposit', 1000.00, 'إيداع شهري'),
(1, 'withdrawal', 250.00, 'مصروفات مدرسية'),
(1, 'deposit', 500.00, 'هدية العيد');
