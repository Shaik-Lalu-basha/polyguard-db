# POLYGUARD AI - Complete Advanced Features Documentation

**Version:** 1.0  
**Last Updated:** March 28, 2026  
**Project:** Smart Bandobusth Duty Monitoring System

---

## 📋 Table of Contents

1. [System Overview](#system-overview)
2. [Security Features](#security-features)
3. [REST API Documentation](#rest-api-documentation)
4. [Blockchain & Immutable Logging](#blockchain--immutable-logging)
5. [Python Integration & AI](#python-integration--ai)
6. [Advanced Analytics](#advanced-analytics)
7. [Database Schema](#database-schema)
8. [Installation & Setup](#installation--setup)
9. [API Usage Examples](#api-usage-examples)
10. [Troubleshooting](#troubleshooting)

---

## System Overview

### Architecture

```
┌─────────────────────────────────────────────────────┐
│           POLYGUARD AI System Architecture          │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │         Web Interface (PHP/HTML/CSS)         │  │
│  │  ├─ index.php (Main Dashboard)               │  │
│  │  ├─ admin/dashboard.php (Admin Portal)       │  │
│  │  ├─ control/dashboard.php (Command Center)   │  │
│  │  ├─ police/dashboard.php (Officer Portal)    │  │
│  │  └─ features.php (Advanced Features)         │  │
│  └──────────────────────────────────────────────┘  │
│                      ↓                              │
│  ┌──────────────────────────────────────────────┐  │
│  │        Backend APIs & Middleware             │  │
│  │  ├─ api.php (REST API Endpoints)             │  │
│  │  ├─ security.php (Security Middleware)       │  │
│  │  ├─ auth.php (Authentication)                │  │
│  │  ├─ db.php (Database Connection)             │  │
│  │  ├─ blockchain_advanced.php (Immutable Log)  │  │
│  │  ├─ advanced_analytics.php (Analytics)       │  │
│  │  └─ python_integration.php (AI Processing)   │  │
│  └──────────────────────────────────────────────┘  │
│                      ↓                              │
│  ┌──────────────────────────────────────────────┐  │
│  │     Data Processing & External Systems       │  │
│  │  ├─ Python Scripts (Machine Learning)        │  │
│  │  ├─ Blockchain Chain (Immutable Log)         │  │
│  │  └─ MySQL Database (Persistent Storage)      │  │
│  └──────────────────────────────────────────────┘  │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Key Components

| Component | Purpose | Technology |
|-----------|---------|-----------|
| **Web UI** | User Interface & Dashboards | PHP, HTML5, CSS3, JavaScript |
| **API Layer** | REST API for External Integration | PHP (OOP) |
| **Authentication** | User Login & Session Management | PHP Sessions, SHA-256 |
| **Security** | CSRF, Rate Limiting, Encryption | AES-256, CSRF Tokens |
| **Blockchain** | Immutable Event Logging | SHA-256 Hash Chain |
| **Analytics** | Real-time Data Analysis | PHP Analytics |
| **AI/ML** | Predictive Analytics | Python, Machine Learning |
| **Database** | Data Storage & Retrieval | MySQL 8.0+, PDO |

---

## Security Features

### 1. **CSRF Token Protection**

```php
// Generate CSRF Token
$token = SecurityMiddleware::generateCSRFToken();

// Verify CSRF Token
SecurityMiddleware::verifyCSRFToken($_POST['csrf_token']);
```

**Features:**
- Automatic token generation per session
- Token rotation on each request
- Form validation
- Prevents Cross-Site Request Forgery attacks

### 2. **API Authentication**

```php
// Validate API Request
$api_key = $_SERVER['HTTP_X_API_KEY'];
$user = SecurityMiddleware::validateAPIRequest($api_key, $pdo);
```

**Features:**
- SHA-256 API Key Generation
- Per-key usage tracking
- Automatic key rotation capability
- Scoped access control

### 3. **Rate Limiting**

```php
// Check Rate Limit (10 requests per 5 minutes)
SecurityMiddleware::checkRateLimit($identifier);
```

**Configuration:**
- Max Attempts: 10
- Time Window: 300 seconds (5 minutes)
- Per IP tracking
- Automatic blocking with HTTP 429 response

### 4. **Data Encryption**

```php
// Encrypt sensitive data
$encrypted = SecurityMiddleware::encryptData($data, $key);

// Decrypt data
$decrypted = SecurityMiddleware::decryptData($encrypted, $key);
```

**Specifications:**
- Algorithm: AES-256-CBC
- IV: Random 16 bytes
- Base64 Encoding for transport

### 5. **Input Validation & Sanitization**

```php
// Sanitize user input
$clean_data = SecurityMiddleware::sanitizeInput($_POST['data']);

// Validate email
SecurityMiddleware::validateEmail($email);
```

**Methods:**
- HTML Entity Encoding
- Type Validation
- Email Format Validation
- SQL Injection Prevention (PDO Prepared Statements)
- XSS Prevention (HTMLSpecialChars)

### 6. **Security Logging**

```php
// Log security event
SecurityMiddleware::logSecurityEvent('LOGIN_SUCCESS', $user_id, 
    ['ip' => $_SERVER['REMOTE_ADDR']], $pdo);
```

**Logged Events:**
- User Login/Logout
- Failed Login Attempts
- API Calls
- Data Modifications
- Admin Actions
- System Access

---

## REST API Documentation

### API Endpoints Overview

| Endpoint | Method | Purpose | Auth Required |
|----------|--------|---------|---|
| `/backend/api/duties` | GET, POST, PUT, DELETE | Manage duty assignments | Yes |
| `/backend/api/tracking` | GET, POST | Location tracking | Yes |
| `/backend/api/compliance` | GET | Compliance metrics | Yes |
| `/backend/api/alerts` | GET, POST | Alert management | Yes |
| `/backend/api/attendance` | POST | Check-in/Check-out | Yes |
| `/backend/api/analytics` | GET | Real-time analytics | Yes |

### Duty Management API

#### Get Duties

```
GET /backend/api/duties?user_id=123&status=active
X-API-Key: your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "duty_id": 1,
      "personnel_id": 123,
      "location_name": "Central Station",
      "latitude": 13.0827,
      "longitude": 80.2707,
      "radius": 30,
      "start_time": "08:00:00",
      "end_time": "16:00:00",
      "status": "active",
      "name": "Officer John",
      "rank": "SI"
    }
  ]
}
```

#### Create Duty

```
POST /backend/api/duties
X-API-Key: your_api_key_here
Content-Type: application/json

{
  "personnel_id": 123,
  "location_name": "Central Station",
  "latitude": 13.0827,
  "longitude": 80.2707,
  "radius": 30,
  "start_time": "08:00:00",
  "end_time": "16:00:00"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Duty created",
  "duty_id": 1
}
```

### Location Tracking API

#### Post Location

```
POST /backend/api/tracking
X-API-Key: your_api_key_here
Content-Type: application/json

{
  "personnel_id": 123,
  "latitude": 13.0827,
  "longitude": 80.2707,
  "status": "inside"
}
```

#### Get Latest Location

```
GET /backend/api/tracking?user_id=123
X-API-Key: your_api_key_here
```

### Attendance API

#### Check-in

```
POST /backend/api/attendance
X-API-Key: your_api_key_here
Content-Type: application/json

{
  "action": "checkin",
  "personnel_id": 123,
  "duty_id": 1
}
```

#### Check-out

```
POST /backend/api/attendance
X-API-Key: your_api_key_here
Content-Type: application/json

{
  "action": "checkout",
  "personnel_id": 123,
  "duty_id": 1
}
```

### Alerts API

#### Get Alerts

```
GET /backend/api/alerts?limit=10
X-API-Key: your_api_key_here
```

#### Create Alert

```
POST /backend/api/alerts
X-API-Key: your_api_key_here
Content-Type: application/json

{
  "personnel_id": 123,
  "alert_type": "exit",
  "duty_id": 1
}
```

### Analytics API

#### Get Analytics

```
GET /backend/api/analytics?metric=compliance_rate
X-API-Key: your_api_key_here
```

**Metrics:**
- `overview` - General system overview
- `compliance_rate` - Average compliance score
- `duty_completion` - Duty completion statistics
- `violations` - Violation breakdown by type

---

## Blockchain & Immutable Logging

### Overview

All critical system events are recorded in an immutable blockchain. This ensures data integrity and provides tamper detection.

### Features

#### 1. Event Logging

```php
$blockchain = new AdvancedBlockchain($pdo);

$blockchain->logEvent(
    'DUTY_ASSIGNED',
    $user_id,
    'Assigned duty to Officer John',
    ['duty_id' => 1, 'officer_id' => 123]
);
```

**Logged Events:**
- Duty Assignments
- Check-in/Check-out Events
- Compliance Updates
- Alert Triggers
- Security Events
- System Configuration Changes

#### 2. Blockchain Verification

```php
$verification = $blockchain->verifyBlockchain();

/*
Output:
{
  "valid": true,
  "total_blocks": 1247,
  "errors": []
}
*/
```

#### 3. Tamper Detection

```php
$tampering = $blockchain->detectTampering(1, 100);

/*
Output:
{
  "tampering_detected": false,
  "inconsistencies": [],
  "blocks_checked": 100
}
*/
```

#### 4. Immutable Reports

```php
$report = $blockchain->generateImmutableReport('2026-01-01', '2026-03-28');

/*
Output:
{
  "summary": {
    "total_blocks": 5000,
    "total_events": 15000,
    "event_types": 12,
    "earliest_event": "2026-01-01 08:00:00",
    "latest_event": "2026-03-28 17:45:00"
  },
  "verification_status": { "valid": true }
}
*/
```

#### 5. Blockchain Export

```php
$export = $blockchain->exportBlockchain();

// Returns JSON with all blocks for archival/compliance
```

---

## Python Integration & AI

### Overview

Advanced Python scripts provide machine learning and AI-powered analytics.

### Available Scripts

#### 1. **analyze_compliance.py**

Analyzes compliance patterns and detects anomalies.

```python
# Automatically runs:
# - Trend analysis
# - Pattern recognition
# - Anomaly detection
# - Scoring
```

**Output:**
```json
{
  "average_score": 78.5,
  "std_deviation": 12.3,
  "patterns": [
    {
      "type": "COMPLIANCE_TREND",
      "direction": "improving",
      "confidence": 0.85
    }
  ],
  "anomalies": [
    {
      "type": "LOW_COMPLIANCE",
      "count": 3,
      "severity": "MEDIUM"
    }
  ]
}
```

#### 2. **predict_violations.py**

Predicts future violations using machine learning.

```python
# Performs:
# - Historical pattern analysis
# - Officer risk assessment
# - Violation type prediction
```

**Output:**
```json
{
  "predictions": [
    {
      "type": "COMMON_VIOLATION",
      "violation_type": "exit",
      "frequency": 47,
      "probability": 0.38
    }
  ],
  "high_risk_officers": [
    {
      "officer_id": 5,
      "violation_count": 12
    }
  ]
}
```

#### 3. **detect_anomalies.py**

Detects unusual location patterns using distance calculations.

```python
# Detects:
# - Unusual movements (> 100km between points)
# - Frequent status changes
# - Suspicious patterns
```

**Output:**
```json
{
  "anomalies_detected": [
    {
      "type": "UNUSUAL_MOVEMENT",
      "distance_km": 245.3,
      "severity": "HIGH"
    }
  ],
  "summary": {
    "total_anomalies": 2,
    "risk_level": "HIGH"
  }
}
```

### PHP Integration

```php
use PythonIntegration;

$python = new PythonIntegration();

// Analyze Compliance
$analysis = $python->analyzeCompliancePatterns($pdo);

// Predict Violations
$predictions = $python->predictViolations($pdo);

// Detect Anomalies
$anomalies = $python->detectAnomalies($pdo);

// Create Python scripts
$python->createPythonScripts();
```

---

## Advanced Analytics

### Dashboard Metrics

```php
$analytics = new AdvancedAnalytics($pdo);

// Get all metrics
$metrics = $analytics->getDashboardMetrics();

// Includes:
// - Personnel Statistics
// - Duty Statistics
// - Compliance Analysis
// - Alert Statistics
// - Attendance Records
// - Performance Metrics
```

### Reports

#### Compliance Report

```php
$report = $analytics->generateComplianceReport('2026-01-01', '2026-03-28');

/*
Returns officer-wise compliance with:
- Total duties
- Completed duties
- Average compliance score
- Violations count
- Attendance days
*/
```

#### Duty Report

```php
$report = $analytics->generateDutyReport('2026-01-01', '2026-03-28');

/*
Returns duty-wise details:
- Officer name
- Location
- Check-in/Check-out times
- Total seconds worked
- Compliance score
*/
```

#### Executive Report

```php
$executive = $analytics->generateExecutiveReport();

/*
Comprehensive report with:
- Summary statistics
- Compliance analysis
- Duty performance
- AI recommendations
*/
```

### Real-time Dashboard

```php
$realtime = $analytics->getRealtimeDashboard();

/*
Contains:
- Active duties
- Ongoing violations
- Officers online
- Recent events
*/
```

### Export to CSV

```php
$csv = $analytics->exportToCSV('compliance');

// Get CSV formatted data for spreadsheet applications
```

---

## Database Schema

### Tables

#### users
Stores user accounts and authentication

```sql
CREATE TABLE users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(150) NOT NULL,
  rank VARCHAR(50),
  mobile VARCHAR(20),
  role ENUM('admin','control','police'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### duty_assignments
Stores duty assignments for officers

```sql
CREATE TABLE duty_assignments (
  duty_id INT PRIMARY KEY AUTO_INCREMENT,
  personnel_id INT NOT NULL,
  location_name VARCHAR(150),
  latitude DOUBLE,
  longitude DOUBLE,
  radius INT DEFAULT 30,
  start_time TIME,
  end_time TIME,
  status ENUM('active','completed','cancelled'),
  created_at TIMESTAMP
);
```

#### location_tracking
Real-time location data for officers

```sql
CREATE TABLE location_tracking (
  id INT PRIMARY KEY AUTO_INCREMENT,
  personnel_id INT NOT NULL,
  latitude DOUBLE,
  longitude DOUBLE,
  status ENUM('inside','outside'),
  timestamp TIMESTAMP
);
```

#### alerts
System alerts for violations

```sql
CREATE TABLE alerts (
  alert_id INT PRIMARY KEY AUTO_INCREMENT,
  personnel_id INT NOT NULL,
  alert_type ENUM('exit','late','absence'),
  alert_time TIMESTAMP,
  status ENUM('sent','acknowledged'),
  duty_id INT
);
```

#### attendance
Attendance tracking (check-in/check-out)

```sql
CREATE TABLE attendance (
  id INT PRIMARY KEY AUTO_INCREMENT,
  personnel_id INT NOT NULL,
  duty_id INT NOT NULL,
  checkin_time TIMESTAMP NULL,
  checkout_time TIMESTAMP NULL,
  total_seconds INT
);
```

#### compliance
Compliance scoring

```sql
CREATE TABLE compliance (
  id INT PRIMARY KEY AUTO_INCREMENT,
  personnel_id INT NOT NULL,
  duty_id INT NOT NULL,
  violation_count INT DEFAULT 0,
  compliance_score INT DEFAULT 0,
  updated_at TIMESTAMP
);
```

#### blockchain_blocks
Immutable blockchain blocks

```sql
CREATE TABLE blockchain_blocks (
  block_id INT PRIMARY KEY AUTO_INCREMENT,
  block_number INT UNIQUE,
  previous_hash VARCHAR(255),
  data_hash VARCHAR(255),
  timestamp TIMESTAMP,
  verified TINYINT DEFAULT 1
);
```

#### blockchain_events
Events recorded in blockchain

```sql
CREATE TABLE blockchain_events (
  event_id INT PRIMARY KEY AUTO_INCREMENT,
  block_id INT,
  event_type VARCHAR(100),
  user_id INT,
  action VARCHAR(255),
  data JSON,
  event_hash VARCHAR(255),
  timestamp TIMESTAMP
);
```

#### security_logs
Security and audit trail

```sql
CREATE TABLE security_logs (
  log_id INT PRIMARY KEY AUTO_INCREMENT,
  event_type VARCHAR(50),
  user_id INT,
  ip_address VARCHAR(45),
  user_agent TEXT,
  details JSON,
  created_at TIMESTAMP
);
```

#### api_keys
API authentication keys

```sql
CREATE TABLE api_keys (
  api_key_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  api_key VARCHAR(255) UNIQUE,
  scope VARCHAR(100),
  is_active TINYINT DEFAULT 1,
  last_used TIMESTAMP NULL,
  created_at TIMESTAMP
);
```

---

## Installation & Setup

### Prerequisites

- Apache 2.4+
- PHP 7.4+
- MySQL 8.0+
- Python 3.6+ (for AI features)
- cURL enabled in PHP

### Step 1: Clone/Copy Files

```bash
cd /path/to/htdocs
cp -r polyguard /var/www/html/
```

### Step 2: Create Database

```bash
mysql -u root -p < database/schema.sql
```

### Step 3: Configure Database

Edit `backend/db.php`:

```php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'polyguard_ai';
$DB_USER = 'root';
$DB_PASS = '';
```

### Step 4: Set Permissions

```bash
chmod -R 755 /path/to/polyguard
chmod -R 777 /path/to/polyguard/python_output
chmod -R 777 /path/to/polyguard/python_scripts
```

### Step 5: Test Installation

Navigate to: `http://localhost/polyguard`

**Default Credentials:**
- Admin: `admin` / `admin123`
- Control: `control` / `control123`
- Police: `police1` / `police123`

---

## API Usage Examples

### cURL Examples

#### Get Duties

```bash
curl -X GET "http://localhost/polyguard/backend/api/duties?user_id=1" \
  -H "X-API-Key: YOUR_API_KEY"
```

#### Create Duty

```bash
curl -X POST "http://localhost/polyguard/backend/api/duties" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "personnel_id": 3,
    "location_name": "Police Station",
    "latitude": 13.0827,
    "longitude": 80.2707,
    "start_time": "08:00:00",
    "end_time": "16:00:00"
  }'
```

### PHP Examples

```php
<?php
// Using PHP cURL

$api_key = 'YOUR_API_KEY';

// Get Duties
$ch = curl_init('http://localhost/polyguard/backend/api/duties?user_id=1');
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $api_key"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
?>
```

### JavaScript/AJAX Examples

```javascript
// Using Fetch API

const apiKey = 'YOUR_API_KEY';

fetch('/polyguard/backend/api/duties?user_id=1', {
  method: 'GET',
  headers: {
    'X-API-Key': apiKey,
    'Content-Type': 'application/json'
  }
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

---

## Troubleshooting

### Issue: API Returns 401 Unauthorized

**Solution:**
1. Verify API key is correct
2. Check API key is active in database
3. Ensure header is: `X-API-Key: your_key`

### Issue: Python Scripts Not Executing

**Solution:**
1. Verify Python 3 is installed: `python3 --version`
2. Check file permissions: `chmod 755 python_scripts/*`
3. Verify Python path in `python_integration.php`

### Issue: Blockchain Verification Fails

**Solution:**
1. Run: `$blockchain->verifyBlockchain()`
2. Check for tampering: `$blockchain->detectTampering()`
3. Verify database integrity

### Issue: Rate Limit Exceeded

**Solution:**
- Wait 5 minutes for the limit to reset
- Configure limit in `security.php` if needed

### Issue: Database Connection Error

**Solution:**
1. Verify MySQL is running
2. Check credentials in `backend/db.php`
3. Verify database exists: `show databases;`

---

## Support & Documentation

For more information:
- GitHub: [POLYGUARD AI Repository]
- Documentation: `/docs/`
- Issues: Submit via admin panel

---

**End of Documentation**

*POLYGUARD AI © 2026 - All Rights Reserved*
