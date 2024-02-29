-- Create database
CREATE DATABASE IF NOT EXISTS ussd;

-- Use the database
USE ussd;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    id_number VARCHAR(20) NOT NULL,
    location VARCHAR(255) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    requestcodeid VARCHAR(250) NOT NULL,
    subscription VARCHAR(150) NOT NULL,
);
