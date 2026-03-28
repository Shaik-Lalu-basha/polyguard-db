-- POLYGUARD AI database schema
CREATE DATABASE IF NOT EXISTS polyguard_ai;
USE polyguard_ai;

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(150) NOT NULL,
  rank VARCHAR(50),
  mobile VARCHAR(20),
  role ENUM('admin','control','police') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS duty_assignments (
  duty_id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  location_name VARCHAR(150) NOT NULL,
  latitude DOUBLE NOT NULL,
  longitude DOUBLE NOT NULL,
  radius INT NOT NULL DEFAULT 30,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  status ENUM('active','completed','cancelled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS location_tracking (
  id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  latitude DOUBLE NOT NULL,
  longitude DOUBLE NOT NULL,
  status ENUM('inside','outside') NOT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alerts (
  alert_id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  alert_type ENUM('exit','late','absence') NOT NULL,
  alert_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('sent','acknowledged') DEFAULT 'sent',
  duty_id INT,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (duty_id) REFERENCES duty_assignments(duty_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  duty_id INT NOT NULL,
  checkin_time TIMESTAMP NULL,
  checkout_time TIMESTAMP NULL,
  total_seconds INT DEFAULT 0,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (duty_id) REFERENCES duty_assignments(duty_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS compliance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  duty_id INT NOT NULL,
  violation_count INT DEFAULT 0,
  compliance_score INT DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (duty_id) REFERENCES duty_assignments(duty_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS blockchain_logs (
  block_id INT AUTO_INCREMENT PRIMARY KEY,
  data_hash VARCHAR(128) NOT NULL,
  previous_hash VARCHAR(128),
  entry TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vip_persons (
  vip_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  designation VARCHAR(100),
  contact_number VARCHAR(20),
  emergency_contact VARCHAR(20),
  address TEXT,
  priority_level ENUM('high','medium','low') DEFAULT 'medium',
  security_clearance VARCHAR(50),
  photo_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vip_assignments (
  assignment_id INT AUTO_INCREMENT PRIMARY KEY,
  vip_id INT NOT NULL,
  personnel_id INT NOT NULL,
  assignment_type ENUM('personal_security','residence_security','travel_security','event_security') DEFAULT 'personal_security',
  location_name VARCHAR(150),
  latitude DOUBLE,
  longitude DOUBLE,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  status ENUM('scheduled','active','completed','cancelled') DEFAULT 'scheduled',
  special_instructions TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vip_id) REFERENCES vip_persons(vip_id) ON DELETE CASCADE,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS duty_roster (
  roster_id INT AUTO_INCREMENT PRIMARY KEY,
  personnel_id INT NOT NULL,
  shift_date DATE NOT NULL,
  shift_type ENUM('morning','afternoon','night','special') DEFAULT 'morning',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  location_name VARCHAR(150),
  latitude DOUBLE,
  longitude DOUBLE,
  status ENUM('scheduled','active','completed','absent') DEFAULT 'scheduled',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (personnel_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_shift (personnel_id, shift_date, shift_type)
);

-- Default admin+control/police user
INSERT IGNORE INTO users (username, password, name, rank, mobile, role) VALUES
('admin', SHA2('admin123', 256), 'System Administrator', 'DCP', '9999999999', 'admin'),
('control', SHA2('control123',256), 'Control Room', 'Control', '8888888888', 'control'),
('police1', SHA2('police123',256), 'Officer John', 'SI', '7777777777', 'police');
