DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS users;
-- Real-Time Mobile Banking Account Balance Notification System (SQLite)

CREATE TABLE users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  full_name TEXT NOT NULL,
  phone_number TEXT NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'customer',
  notify_preference TEXT NOT NULL DEFAULT 'sms',
  status TEXT NOT NULL DEFAULT 'active'
);

CREATE TABLE accounts (
  account_id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INT NOT NULL,
  account_number TEXT NOT NULL UNIQUE,
  balance REAL NOT NULL DEFAULT 0.00,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE transactions (
  transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
  transaction_ref TEXT NOT NULL UNIQUE,
  account_id INT NOT NULL,
  type TEXT NOT NULL,
  direction TEXT NOT NULL,
  amount REAL NOT NULL,
  balance_after REAL NOT NULL,
  counterparty_account TEXT NULL,
  created_at TEXT NOT NULL,
  FOREIGN KEY (account_id) REFERENCES accounts(account_id)
);

CREATE TABLE notifications (
  notification_id INTEGER PRIMARY KEY AUTOINCREMENT,
  transaction_id INT NOT NULL UNIQUE,
  message TEXT NOT NULL,
  channel TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  retry_count INT NOT NULL DEFAULT 0,
  sent_at TEXT NULL,
  FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
);

CREATE TABLE complaints (
  complaint_id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INT NOT NULL,
  transaction_id INT NULL,
  issue_type TEXT NOT NULL,
  description TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  admin_response TEXT NULL,
  created_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
);

CREATE TABLE audit_logs (
  log_id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INT NULL,
  action TEXT NOT NULL,
  details TEXT NULL,
  created_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

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
