-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS energy_db;
USE energy_db;

-- Table for Individual Unit appliances
CREATE TABLE IF NOT EXISTS appliances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    watts DECIMAL(10, 2) NOT NULL,
    hours DECIMAL(5, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for Residential Building appliances (multi-tenant)
CREATE TABLE IF NOT EXISTS building_appliances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_name VARCHAR(255) NOT NULL,
    apartment_number VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    watts DECIMAL(10, 2) NOT NULL,
    hours DECIMAL(5, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table intended for storing real-time alerts in the Live Operations view 
-- (Required only if you transition from the simulated API data to saving physical IoT alerts to DB)
CREATE TABLE IF NOT EXISTS live_grid_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tower_name VARCHAR(100) NOT NULL,
    event_description text NOT NULL,
    kw_change DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
