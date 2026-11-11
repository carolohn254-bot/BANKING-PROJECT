-- Real-Time Mobile Banking Account Balance Notification System (MySQL / MariaDB)
CREATE DATABASE IF NOT EXISTS mobile_banking CHARACTER SET utf8mb4;
USE mobile_banking;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS audit_logs, complaints, notifications, transactions, accounts, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(100) NOT NULL,
  phone_number VARCHAR(15) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  notify_preference VARCHAR(20) NOT NULL DEFAULT 'sms',
  status ENUM('active','suspended') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE accounts (
  account_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  account_number VARCHAR(20) NOT NULL UNIQUE,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_ref VARCHAR(30) NOT NULL UNIQUE,
  account_id INT NOT NULL,
  type ENUM('deposit','withdrawal','transfer') NOT NULL,
  direction ENUM('credit','debit') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  balance_after DECIMAL(12,2) NOT NULL,
  counterparty_account VARCHAR(20) NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (account_id) REFERENCES accounts(account_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL UNIQUE,
  message TEXT NOT NULL,
  channel VARCHAR(20) NOT NULL,
  status ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending',
  retry_count INT NOT NULL DEFAULT 0,
  sent_at DATETIME NULL,
  FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
) ENGINE=InnoDB;

CREATE TABLE complaints (
  complaint_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  transaction_id INT NULL,
  issue_type ENUM('missing','delayed','duplicate','incorrect') NOT NULL,
  description TEXT NOT NULL,
  status ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  admin_response TEXT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  details TEXT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Simulated starting data (passwords/PINs are stored hashed). Logins: admin/admin123, john/1234, mary/2345, peter/3456
INSERT INTO users (username, full_name, phone_number, password_hash, role, notify_preference, status) VALUES
('admin','Bank Administrator','+254700000000','$2y$10$G0yW/erO1hp4gq6eZlmEXeQiBKkL5tZ4TK.DQURMdIw9oATx0S.fS','admin','sms','active'),
('john','John Kamau','+254711000001','$2y$10$yu8TmwWLBhvog4C0l1VSC.RXBEWubki4JRr7KUQyN5W46nZOJaYty','customer','sms','active'),
('mary','Mary Wanjiru','+254711000002','$2y$10$2W2IxdAtJjkpLFv/iiSAOu9bQ.uryGmYOGzCxc9L96knBA0XIjfUK','customer','both','active'),
('peter','Peter Otieno','+254711000003','$2y$10$WK.VVpISpOqwMHbic8n2Q.CuOurN.pgKk/q0hHrpb0d0OrdF8CZ9G','customer','push','active'),
('sam','Sam Mutua (suspended)','+254711000004','$2y$10$.C7SRau9It91Ah0ruXsleOfvM0Sv8fYsxWER5CQLdn2a3vdVrS1HK','customer','sms','suspended');
INSERT INTO accounts (user_id, account_number, balance) VALUES
(2,'0100000001',25000.00),
(3,'0100000002',48500.50),
(4,'0100000003',9000.00),
(5,'0100000004',1500.00);
