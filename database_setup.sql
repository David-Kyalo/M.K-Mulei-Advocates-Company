-- Database setup for M.K Mulei & Company Advocates consultation requests
-- Run this SQL script to create the database and table

-- Create database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS law_firm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE law_firm_db;

-- Create consultation_requests table
CREATE TABLE IF NOT EXISTS consultation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    practice_area VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    appointment_datetime DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT NULL,
    INDEX idx_email (email),
    INDEX idx_appointment_datetime (appointment_datetime),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Create a view for easy querying
CREATE OR REPLACE VIEW recent_consultations AS
SELECT 
    id,
    name,
    email,
    phone,
    practice_area,
    appointment_datetime,
    status,
    created_at
FROM consultation_requests
ORDER BY created_at DESC;

