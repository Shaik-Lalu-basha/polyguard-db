-- POLYGUARD AI Extended Database Schema
-- Tables for duty reports, location history, and officer tracking

USE polyguard_ai;

-- Location History & Entry/Exit Tracking
CREATE TABLE IF NOT EXISTS location_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  duty_id INT,
  latitude DOUBLE NOT NULL,
  longitude DOUBLE NOT NULL,
  status ENUM('inside','outside','arrival','departure') NOT NULL,
  distance_from_duty FLOAT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (duty_id) REFERENCES duty_assignments(duty_id) ON DELETE SET NULL,
  INDEX (personnel_id, timestamp),
  INDEX (duty_id, timestamp)
);

-- Duty Reports with GPS Images
CREATE TABLE IF NOT EXISTS duty_reports (
  report_id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  duty_id INT NOT NULL,
  report_type ENUM('arrival','departure','incident','checkpoint') NOT NULL,
  latitude DOUBLE NOT NULL,
  longitude DOUBLE NOT NULL,
  location_name VARCHAR(255),
  description TEXT,
  image_path VARCHAR(500),
  image_base64 LONGTEXT,
  status ENUM('pending','reviewed','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_by INT,
  reviewed_at TIMESTAMP NULL,
  review_comments TEXT,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (duty_id) REFERENCES duty_assignments(duty_id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX (personnel_id, created_at),
  INDEX (duty_id, created_at),
  INDEX (status, created_at)
);

-- Entry/Exit Events with Timing
CREATE TABLE IF NOT EXISTS entry_exit_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  duty_id INT NOT NULL,
  event_type ENUM('entry','exit','attempt_exit','re_entry') NOT NULL,
  latitude DOUBLE NOT NULL,
  longitude DOUBLE NOT NULL,
  accuracy FLOAT,
  distance_from_zone FLOAT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  event_time TIME,
  is_violation BOOLEAN DEFAULT FALSE,
  violation_reason VARCHAR(255),
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (duty_id) REFERENCES duty_assignments(duty_id) ON DELETE CASCADE,
  INDEX (personnel_id, timestamp),
  INDEX (duty_id, timestamp),
  INDEX (event_type, timestamp)
);

-- Daily Duty Summary
CREATE TABLE IF NOT EXISTS duty_summary (
  summary_id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  duty_id INT NOT NULL,
  duty_date DATE NOT NULL,
  arrival_time TIMESTAMP NULL,
  departure_time TIMESTAMP NULL,
  total_duration INT DEFAULT 0,
  entry_count INT DEFAULT 0,
  exit_count INT DEFAULT 0,
  violation_count INT DEFAULT 0,
  arrival_report_id INT,
  departure_report_id INT,
  status ENUM('not_started','in_progress','completed','abandoned') DEFAULT 'not_started',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (duty_id) REFERENCES duty_assignments(duty_id) ON DELETE CASCADE,
  FOREIGN KEY (arrival_report_id) REFERENCES duty_reports(report_id) ON DELETE SET NULL,
  FOREIGN KEY (departure_report_id) REFERENCES duty_reports(report_id) ON DELETE SET NULL,
  UNIQUE KEY (personnel_id, duty_id, duty_date),
  INDEX (duty_date, status)
);

-- Location Alerts for Violations
CREATE TABLE IF NOT EXISTS location_violations (
  violation_id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  duty_id INT NOT NULL,
  violation_type ENUM('outside_zone','unauthorized_location','rapid_movement','missed_checkin') NOT NULL,
  latitude DOUBLE,
  longitude DOUBLE,
  details TEXT,
  severity ENUM('low','medium','high','critical') DEFAULT 'medium',
  resolved BOOLEAN DEFAULT FALSE,
  resolved_at TIMESTAMP NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (duty_id) REFERENCES duty_assignments(duty_id) ON DELETE CASCADE,
  INDEX (personnel_id, timestamp),
  INDEX (resolved, timestamp)
);

-- Add columns to existing tables if they don't exist
ALTER TABLE duty_assignments ADD COLUMN arrival_time TIMESTAMP NULL AFTER end_time;
ALTER TABLE duty_assignments ADD COLUMN departure_time TIMESTAMP NULL AFTER arrival_time;
ALTER TABLE duty_assignments ADD COLUMN arrival_report_id INT NULL AFTER departure_time;

-- Add index for performance
CREATE INDEX idx_duty_personnel_status ON duty_assignments(personnel_id, status);
CREATE INDEX idx_location_tracking_latest ON location_tracking(personnel_id, timestamp DESC);
