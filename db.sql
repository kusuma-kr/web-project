
CREATE DATABASE IF NOT EXISTS spendwise;
USE spendwise;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('income','expense') NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO transactions
(type,title,category,amount,transaction_date,notes)
VALUES
('income','Pocket Money','Pocket Money',5000,'2026-11-01','Monthly allowance'),
('income','Freelance Work','Part-time Job',1500,'2026-11-03','Project income'),
('expense','College Lunch','Food',800,'2026-11-05','Lunch'),
('expense','Bus Pass','Transport',600,'2026-11-06','Travel'),
('expense','Earphones','Shopping',1200,'2026-11-08','Accessories');
