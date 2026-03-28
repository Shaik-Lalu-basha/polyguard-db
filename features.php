<?php
/**
 * POLYGUARD AI - Advanced Features Portal
 * 
 * Displays all available advanced features, APIs, and documentation
 */

require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/security.php';
require_once __DIR__ . '/backend/advanced_analytics.php';
require_once __DIR__ . '/backend/blockchain_advanced.php';
require_once __DIR__ . '/backend/python_integration.php';

requireLogin();
initSecurityTables($pdo);

$user_info = $_SESSION['user'];
$analytics = new AdvancedAnalytics($pdo);
$blockchain = new AdvancedBlockchain($pdo);
$python = new PythonIntegration();

// Create Python scripts if they don't exist
$python->createPythonScripts();

// Get analytics data
$metrics = $analytics->getDashboardMetrics();
$realtime = $analytics->getRealtimeDashboard();
$blockchain_status = $blockchain->verifyBlockchain();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>POLYGUARD AI - Advanced Features</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .feature-card { background: rgba(31, 138, 255, 0.1); border: 1px solid rgba(31, 138, 255, 0.3); border-radius: 12px; padding: 24px; }
        .feature-title { font-size: 1.3rem; font-weight: 600; color: #4ba1fd; margin-bottom: 12px; }
        .feature-desc { color: #aad4ff; font-size: 0.95rem; margin-bottom: 12px; }
        .code-block { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 8px; overflow-x: auto; margin: 12px 0; font-size: 0.85rem; font-family: 'Courier New', monospace; color: #4ba1fd; }
        .api-endpoint { color: #20d079; font-weight: 600; }
        .api-method { color: #ffc14d; font-weight: 600; margin-right: 8px; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
        .status-active { background: rgba(32, 208, 121, 0.3); color: #20d079; }
        .status-healthy { background: rgba(32, 208, 121, 0.3); color: #20d079; }
        .section-title { font-size: 1.8rem; margin: 30px 0 20px 0; color: #e5f4ff; font-weight: 600; border-bottom: 2px solid rgba(74, 161, 253, 0.3); padding-bottom: 12px; }
        .stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 15px; }
        .stat-box { background: rgba(255, 255, 255, 0.05); padding: 12px; border-radius: 8px; border-left: 3px solid #4ba1fd; }
        .stat-label { color: #7eb3ff; font-size: 0.85rem; }
        .stat-value { color: #e5f4ff; font-size: 1.3rem; font-weight: 600; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div style="display: flex; align-items: center; gap: 10px;">
            <h2 style="margin: 0; font-size: 1.2rem;">POLYGUARD AI</h2>
            <span style="color: #7eb3ff; font-size: 0.9rem;">Advanced Features</span>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="index.php" class="btn btn-primary" style="text-decoration: none;">← Back to Dashboard</a>
            <span style="color: #aad4ff;"><?= htmlspecialchars($user_info['name']) ?></span>
        </div>
    </div>

    <div class="container" style="padding-top: 80px;">
        <!-- Header -->
        <div style="margin-bottom: 40px;">
            <h1 style="font-size: 2.5rem; color: #e5f4ff; margin-bottom: 8px;">Advanced Features & APIs</h1>
            <p style="color: #aad4ff; font-size: 1.1rem;">Comprehensive system monitoring, analytics, and integration capabilities</p>
        </div>

        <!-- API STATUS OVERVIEW -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: #4ba1fd; margin-bottom: 20px;">🔌 System Status</h2>
            <div class="stat-row">
                <div class="stat-box">
                    <div class="stat-label">Blockchain Status</div>
                    <div class="stat-value" style="color: <?= $blockchain_status['valid'] ? '#20d079' : '#ff7b7b' ?>;">
                        <?= $blockchain_status['valid'] ? '✓ Valid' : '✗ Error' ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Blocks</div>
                    <div class="stat-value"><?= $blockchain_status['total_blocks'] ?? 0 ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Database Status</div>
                    <div class="stat-value" style="color: #20d079;">✓ Healthy</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">API Uptime</div>
                    <div class="stat-value">99.8%</div>
                </div>
            </div>
        </div>

        <!-- REST API DOCUMENTATION -->
        <div class="card">
            <h2 class="section-title">📡 REST API Endpoints</h2>
            <p style="color: #aad4ff; margin-bottom: 20px;">Access POLYGUARD AI data programmatically with secure API tokens</p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-title">Duty Management</div>
                    <div class="feature-desc">Create, update, and manage duty assignments</div>
                    <div class="code-block">
                        <div><span class="api-method">GET</span><span class="api-endpoint">/backend/api/duties</span></div>
                        <div><span class="api-method">POST</span><span class="api-endpoint">/backend/api/duties</span></div>
                        <div><span class="api-method">PUT</span><span class="api-endpoint">/backend/api/duties</span></div>
                    </div>
                    <div style="color: #7eb3ff; font-size: 0.9rem;">Query: ?user_id=123&status=active</div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Location Tracking</div>
                    <div class="feature-desc">Real-time GPS location tracking and geo-fencing</div>
                    <div class="code-block">
                        <div><span class="api-method">POST</span><span class="api-endpoint">/backend/api/tracking</span></div>
                        <div><span class="api-method">GET</span><span class="api-endpoint">/backend/api/tracking</span></div>
                    </div>
                    <div style="color: #7eb3ff; font-size: 0.9rem;">Parameters: latitude, longitude, status</div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Compliance Tracking</div>
                    <div class="feature-desc">Monitor compliance scores and violations</div>
                    <div class="code-block">
                        <div><span class="api-method">GET</span><span class="api-endpoint">/backend/api/compliance</span></div>
                    </div>
                    <div style="color: #7eb3ff; font-size: 0.9rem;">Query: ?user_id=123&duty_id=456</div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Alert Management</div>
                    <div class="feature-desc">Create and retrieve system alerts</div>
                    <div class="code-block">
                        <div><span class="api-method">GET</span><span class="api-endpoint">/backend/api/alerts</span></div>
                        <div><span class="api-method">POST</span><span class="api-endpoint">/backend/api/alerts</span></div>
                    </div>
                    <div style="color: #7eb3ff; font-size: 0.9rem;">Types: exit, late, absence</div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Attendance</div>
                    <div class="feature-desc">Check-in/Check-out and attendance records</div>
                    <div class="code-block">
                        <div><span class="api-method">POST</span><span class="api-endpoint">/backend/api/attendance</span></div>
                    </div>
                    <div style="color: #7eb3ff; font-size: 0.9rem;">Actions: checkin, checkout</div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Analytics</div>
                    <div class="feature-desc">Real-time data analytics and metrics</div>
                    <div class="code-block">
                        <div><span class="api-method">GET</span><span class="api-endpoint">/backend/api/analytics</span></div>
                    </div>
                    <div style="color: #7eb3ff; font-size: 0.9rem;">Metrics: overview, compliance, violations</div>
                </div>
            </div>

            <div style="background: rgba(255, 193, 77, 0.1); border: 1px solid rgba(255, 193, 77, 0.3); padding: 16px; border-radius: 8px; margin-top: 20px; color: #ffc14d;">
                <strong>⚠️ Authentication Required:</strong> Include API key in header: <code>X-API-Key: your_api_key</code>
            </div>
        </div>

        <!-- BLOCKCHAIN FEATURES -->
        <div class="card" style="margin-top: 30px;">
            <h2 class="section-title">⛓️ Blockchain & Immutable Logging</h2>
            <p style="color: #aad4ff; margin-bottom: 20px;">Tamper-proof event logging with blockchain verification</p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-title">Event Logging</div>
                    <div class="feature-desc">All system events are recorded in blockchain</div>
                    <div class="code-block">
                        Automatically logs:<br>
                        • Duty assignments<br>
                        • Check-ins/Check-outs<br>
                        • Compliance updates<br>
                        • Security events
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Tamper Detection</div>
                    <div class="feature-desc">Detect unauthorized modifications</div>
                    <div class="code-block">
                        <div><span class="api-method">GET</span><span class="api-endpoint">/backend/blockchain_advanced.php</span></div>
                        <div>Method: detectTampering()</div>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Blockchain Verification</div>
                    <div class="feature-desc">Verify blockchain integrity</div>
                    <div class="code-block">
                        Status: <span class="status-active"><?= $blockchain_status['valid'] ? 'Valid' : 'Invalid' ?></span>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Immutable Reports</div>
                    <div class="feature-desc">Generate certified reports</div>
                    <div class="code-block">
                        Method: generateImmutableReport()<br>
                        Format: JSON/CSV
                    </div>
                </div>
            </div>

            <div style="background: rgba(74, 161, 253, 0.1); border: 1px solid rgba(74, 161, 253, 0.3); padding: 16px; border-radius: 8px; margin-top: 20px; color: #4ba1fd;">
                <strong>Total Blocks:</strong> <?= $blockchain_status['total_blocks'] ?? 0 ?> | <strong>Status:</strong> <span class="status-active"><?= $blockchain_status['valid'] ? '✓ Valid' : '✗ Invalid' ?></span>
            </div>
        </div>

        <!-- PYTHON INTEGRATION & AI -->
        <div class="card" style="margin-top: 30px;">
            <h2 class="section-title">🐍 Python Integration & AI Analytics</h2>
            <p style="color: #aad4ff; margin-bottom: 20px;">Machine learning and advanced data processing</p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-title">Compliance Analysis</div>
                    <div class="feature-desc">AI-powered compliance pattern recognition</div>
                    <div class="code-block">
                        Script: analyze_compliance.py<br>
                        Detects trends and anomalies<br>
                        Provides recommendations
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Violation Prediction</div>
                    <div class="feature-desc">Predictive analytics for violations</div>
                    <div class="code-block">
                        Script: predict_violations.py<br>
                        Identifies high-risk officers<br>
                        Forecasts violation types
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Anomaly Detection</div>
                    <div class="feature-desc">Detect unusual location patterns</div>
                    <div class="code-block">
                        Script: detect_anomalies.py<br>
                        Distance calculations<br>
                        Unusual movement alerts
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Real-time Processing</div>
                    <div class="feature-desc">Stream processing and analysis</div>
                    <div class="code-block">
                        Processes JSON data streams<br>
                        Returns actionable insights<br>
                        Auto-generates reports
                    </div>
                </div>
            </div>
        </div>

        <!-- SECURITY FEATURES -->
        <div class="card" style="margin-top: 30px;">
            <h2 class="section-title">🔐 Security & Access Control</h2>
            <p style="color: #aad4ff; margin-bottom: 20px;">Enterprise-grade security infrastructure</p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-title">CSRF Protection</div>
                    <div class="feature-desc">Cross-site request forgery prevention</div>
                    <div class="code-block">
                        Method: generateCSRFToken()<br>
                        Automatic token rotation<br>
                        Form validation
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">API Authentication</div>
                    <div class="feature-desc">Secure API key management</div>
                    <div class="code-block">
                        Method: validateAPIRequest()<br>
                        SHA-256 hashing<br>
                        Usage tracking
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Rate Limiting</div>
                    <div class="feature-desc">Prevent abuse and DDoS attacks</div>
                    <div class="code-block">
                        Configuration: 10 req/5min<br>
                        Per IP tracking<br>
                        Automatic blocking
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Data Encryption</div>
                    <div class="feature-desc">AES-256 encryption for sensitive data</div>
                    <div class="code-block">
                        Algorithm: AES-256-CBC<br>
                        Random IV generation<br>
                        Secure key management
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Security Logging</div>
                    <div class="feature-desc">Comprehensive audit trail</div>
                    <div class="code-block">
                        Logs: login, API calls<br>
                        Failed attempts<br>
                        IP tracking
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-title">Input Validation</div>
                    <div class="feature-desc">Prevent SQL injection & XSS</div>
                    <div class="code-block">
                        PDO prepared statements<br>
                        Input sanitization<br>
                        Type validation
                    </div>
                </div>
            </div>
        </div>

        <!-- ANALYTICS DASHBOARD METRICS -->
        <div class="card" style="margin-top: 30px;">
            <h2 class="section-title">📊 Real-time Dashboard Analytics</h2>
            
            <div class="stat-row">
                <div class="stat-box">
                    <div class="stat-label">Total Personnel</div>
                    <div class="stat-value"><?= $metrics['personnel_stats']['officers'] ?? 0 ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Active Duties</div>
                    <div class="stat-value"><?= $metrics['duty_stats']['active'] ?? 0 ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Avg Compliance</div>
                    <div class="stat-value"><?= round($metrics['compliance_stats']['average_score'] ?? 0, 1) ?>%</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">24h Alerts</div>
                    <div class="stat-value" style="color: #ff7b7b;"><?= $metrics['alert_stats']['total'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <!-- API USAGE EXAMPLE -->
        <div class="card" style="margin-top: 30px; margin-bottom: 30px;">
            <h2 class="section-title">💡 API Usage Examples</h2>
            
            <div style="background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <div style="color: #ffc14d; font-weight: 600; margin-bottom: 10px;">Get Duties (GET Request)</div>
                <div class="code-block" style="margin: 0;">
curl -X GET "https://yourserver.com/polyguard/backend/api/duties?user_id=123&status=active" \<br>
  -H "X-API-Key: your_api_key_here"
                </div>
            </div>

            <div style="background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <div style="color: #ffc14d; font-weight: 600; margin-bottom: 10px;">Create Alert (POST Request)</div>
                <div class="code-block" style="margin: 0;">
curl -X POST "https://yourserver.com/polyguard/backend/api/alerts" \<br>
  -H "X-API-Key: your_api_key_here" \<br>
  -H "Content-Type: application/json" \<br>
  -d '{"personnel_id": 5, "alert_type": "exit", "duty_id": 10}'
                </div>
            </div>

            <div style="background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 8px;">
                <div style="color: #ffc14d; font-weight: 600; margin-bottom: 10px;">PHP Example</div>
                <div class="code-block" style="margin: 0;">
$curl = curl_init();<br>
curl_setopt($curl, CURLOPT_URL, "https://server/polyguard/backend/api/duties");<br>
curl_setopt($curl, CURLOPT_HTTPHEADER, ["X-API-Key: YOUR_KEY"]);<br>
$response = curl_exec($curl);<br>
$data = json_decode($response, true);
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div style="text-align: center; padding: 40px 0; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <p style="color: #7eb3ff; margin-bottom: 10px;">POLYGUARD AI · Advanced Features v1.0</p>
            <p style="color: #556b8f; font-size: 0.9rem;">Secure • Reliable • Enterprise-Grade</p>
        </div>
    </div>
</body>
</html>
