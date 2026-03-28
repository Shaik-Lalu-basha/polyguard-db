# POLYGUARD AI - Quick Reference Guide

## 🚀 Getting Started

### 1. Access the System
- **URL:** `http://localhost/polyguard`
- **Default Credentials:**
  - Admin: `admin` / `admin123`
  - Control: `control` / `control123`
  - Police: `police1` / `police123`

### 2. Main Dashboard Features
- Click **⭐ Advanced Features** to access all APIs and tools
- Home button 🏠 available in all dashboards
- Real-time metrics and statistics
- Role-based access control

---

## 🔌 API Quick Start

### Base URL
```
http://localhost/polyguard/backend/api/
```

### Authentication
```
Header: X-API-Key: your_api_key_here
```

### Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/duties` | GET | List duties |
| `/duties` | POST | Create duty |
| `/tracking` | POST | Log location |
| `/compliance` | GET | Get compliance data |
| `/alerts` | GET/POST | Manage alerts |
| `/attendance` | POST | Check-in/out |
| `/analytics` | GET | Get metrics |

### Example cURL Request
```bash
curl -X GET "http://localhost/polyguard/backend/api/duties" \
  -H "X-API-Key: YOUR_KEY"
```

---

## 🛡️ Security Features

### Rate Limiting
- **Limit:** 10 requests per 5 minutes
- **Per:** IP address
- **Error:** HTTP 429 Too Many Requests

### CSRF Protection
- Automatic on all forms
- Token validation required
- Session-based management

### Data Encryption
- Algorithm: AES-256-CBC
- Automatic encryption/decryption
- Secure key management

---

## ⛓️ Blockchain

### Access Blockchain Features
```php
$blockchain = new AdvancedBlockchain($pdo);

// Verify integrity
$result = $blockchain->verifyBlockchain();

// Detect tampering
$tampering = $blockchain->detectTampering();

// Generate report
$report = $blockchain->generateImmutableReport();
```

### Automatic Logging
All these events are logged:
- Duty assignments
- Check-in/Check-out events
- Compliance updates
- Alert triggers
- Security events

---

## 🐍 Python & AI

### Automatic Script Generation
Scripts are created in `/python_scripts/`:
1. `analyze_compliance.py`
2. `predict_violations.py`
3. `detect_anomalies.py`

### Usage in PHP
```php
$python = new PythonIntegration();

// Analyze compliance
$analysis = $python->analyzeCompliancePatterns($pdo);

// Predict violations
$predictions = $python->predictViolations($pdo);

// Detect anomalies
$anomalies = $python->detectAnomalies($pdo);
```

---

## 📊 Analytics

### Available Reports
```php
$analytics = new AdvancedAnalytics($pdo);

// Compliance report
$compliance = $analytics->generateComplianceReport('2026-01-01', '2026-03-28');

// Duty report
$duties = $analytics->generateDutyReport('2026-01-01', '2026-03-28');

// Executive report
$executive = $analytics->generateExecutiveReport();

// Real-time dashboard
$realtime = $analytics->getRealtimeDashboard();

// Export to CSV
$csv = $analytics->exportToCSV('compliance');
```

---

## 🔐 Security Best Practices

1. **Keep API Keys Secure** - Don't expose in code
2. **Use HTTPS** - In production
3. **Rotate API Keys** - Regularly
4. **Monitor Logs** - Check security_logs table
5. **Update Passwords** - Regular changes
6. **Enable Blockchain** - For audit trails
7. **Rate Limit** - Monitor for suspicious activity

---

## 📁 Key Files

### Backend
- `backend/api.php` - REST API
- `backend/security.php` - Security middleware
- `backend/blockchain_advanced.php` - Blockchain
- `backend/advanced_analytics.php` - Analytics
- `backend/python_integration.php` - Python handler

### Dashboards
- `index.php` - Main dashboard
- `admin/dashboard.php` - Admin portal
- `control/dashboard.php` - Control room
- `police/dashboard.php` - Officer portal
- `features.php` - Features portal

### Documentation
- `FEATURES_DOCUMENTATION.md` - Full documentation
- `IMPLEMENTATION_SUMMARY.md` - Implementation details
- `QUICK_REFERENCE.md` - This file

---

## 🐛 Troubleshooting

### API Returns 401
- Check API key is correct
- Verify API key is active in database
- Confirm header format: `X-API-Key: key`

### Rate Limit Hit
- Wait 5 minutes or
- Contact admin to reset limit

### Python Scripts Not Found
- Run: `$python->createPythonScripts()`
- Verify Python 3 is installed
- Check file permissions (755)

### Database Connection Error
- Verify MySQL is running
- Check credentials in `backend/db.php`
- Ensure database exists

---

## 📞 Support

### Documentation
- **Full Guide:** `FEATURES_DOCUMENTATION.md`
- **Implementation:** `IMPLEMENTATION_SUMMARY.md`
- **Features Portal:** `features.php`

### Database
- All tables auto-created on first access
- Security tables created automatically
- Python output directory created as needed

### Logging
- Security logs in `security_logs` table
- Blockchain logs in `blockchain_events` table
- API activity tracked in history

---

## ⚡ Performance Tips

1. Use index.php for quick access to all features
2. Features button (⭐) provides complete documentation
3. API rate limiting protects system
4. Blockchain verification is automatic
5. Python scripts run async when possible
6. Analytics cache updates automatically

---

## 🎯 Common Tasks

### Create API Key
1. Go to Admin Dashboard
2. API Management section
3. Generate new key
4. Copy key for API requests

### View Compliance Report
1. Go to Features Portal
2. Analytics section
3. Select date range
4. Export as CSV if needed

### Check Blockchain Status
1. Go to Features Portal
2. Blockchain section
3. View verification status
4. Download immutable report

### Monitor Alerts
1. Go to Control Room
2. View latest alerts
3. Check violation types
4. Review officer risk levels

---

## 🔒 Security Checklist

- [ ] Database credentials configured
- [ ] API keys generated
- [ ] Python installed on server
- [ ] File permissions set (755)
- [ ] HTTPS enabled (production)
- [ ] Security logs monitored
- [ ] Backup strategy in place
- [ ] Update passwords regularly

---

## 📈 System Status

### Default Endpoints Status
- ✅ API Server
- ✅ Database
- ✅ Blockchain
- ✅ Analytics
- ✅ Python Integration
- ✅ Security Middleware

### Monitoring
- Real-time metrics in Features Portal
- System health dashboard
- API performance monitoring
- Database connection status

---

## 🎓 Learning Resources

1. **Features Portal** - Visual API documentation
2. **Full Documentation** - `FEATURES_DOCUMENTATION.md`
3. **Implementation Guide** - `IMPLEMENTATION_SUMMARY.md`
4. **This Guide** - Quick reference

---

**Last Updated:** March 28, 2026  
**Version:** 1.0  
**Status:** ✅ Ready for Production
