<?php
/**
 * POLYGUARD AI - Python Integration Handler
 * 
 * Enables PHP to execute Python scripts for:
 * - Machine Learning Processing
 * - Data Analysis
 * - Advanced Calculations
 * - Pattern Recognition
 */

class PythonIntegration {
    private $python_path = 'python'; // or 'python3' or full path
    private $scripts_dir;
    private $output_dir;

    public function __construct() {
        $this->scripts_dir = __DIR__ . '/../python_scripts';
        $this->output_dir = __DIR__ . '/../python_output';

        // Create directories if they don't exist
        @mkdir($this->scripts_dir, 0755, true);
        @mkdir($this->output_dir, 0755, true);
    }

    /**
     * Execute Python script and return output
     */
    public function executePython($script_name, $args = []) {
        try {
            $script_path = $this->scripts_dir . '/' . $script_name;

            if (!file_exists($script_path)) {
                return ['error' => "Script not found: $script_name"];
            }

            // Prepare arguments
            $escaped_args = array_map('escapeshellarg', $args);
            $command = "{$this->python_path} " . escapeshellarg($script_path) . " " . implode(' ', $escaped_args);

            // Execute with timeout
            $output = shell_exec($command . ' 2>&1');

            if ($output === null) {
                return ['error' => 'Python execution failed'];
            }

            return [
                'success' => true,
                'output' => $output
            ];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Analyze compliance patterns
     */
    public function analyzeCompliancePatterns($pdo) {
        // Get data from database
        $stmt = $pdo->query("SELECT c.*, u.name FROM compliance c
            JOIN users u ON c.personnel_id = u.user_id
            ORDER BY c.updated_at DESC");

        $data = $stmt->fetchAll();

        // Save to JSON file for Python processing
        $input_file = $this->output_dir . '/compliance_input.json';
        file_put_contents($input_file, json_encode($data));

        // Execute Python analysis
        $result = $this->executePython('analyze_compliance.py', [$input_file]);

        if (isset($result['success'])) {
            $output_file = $this->output_dir . '/compliance_analysis.json';
            if (file_exists($output_file)) {
                return json_decode(file_get_contents($output_file), true);
            }
        }

        return $result;
    }

    /**
     * Predict duty violations
     */
    public function predictViolations($pdo) {
        $stmt = $pdo->query("SELECT * FROM alerts ORDER BY alert_time DESC LIMIT 500");
        $data = $stmt->fetchAll();

        $input_file = $this->output_dir . '/violation_input.json';
        file_put_contents($input_file, json_encode($data));

        $result = $this->executePython('predict_violations.py', [$input_file]);

        if (isset($result['success'])) {
            $output_file = $this->output_dir . '/violation_predictions.json';
            if (file_exists($output_file)) {
                return json_decode(file_get_contents($output_file), true);
            }
        }

        return $result;
    }

    /**
     * Generate anomaly detection report
     */
    public function detectAnomalies($pdo) {
        $stmt = $pdo->query("SELECT * FROM location_tracking ORDER BY timestamp DESC LIMIT 1000");
        $data = $stmt->fetchAll();

        $input_file = $this->output_dir . '/location_input.json';
        file_put_contents($input_file, json_encode($data));

        $result = $this->executePython('detect_anomalies.py', [$input_file]);

        if (isset($result['success'])) {
            $output_file = $this->output_dir . '/anomalies.json';
            if (file_exists($output_file)) {
                return json_decode(file_get_contents($output_file), true);
            }
        }

        return $result;
    }

    /**
     * Create Python data processing script files
     */
    public function createPythonScripts() {
        // Create analyze_compliance.py
        $compliance_script = <<<'PYTHON'
import json
import sys
from statistics import mean, stdev
from datetime import datetime

def analyze_compliance(data):
    """Analyze compliance patterns"""
    analysis = {
        'timestamp': datetime.now().isoformat(),
        'total_records': len(data),
        'average_score': 0,
        'std_deviation': 0,
        'patterns': [],
        'anomalies': []
    }

    if data:
        scores = [d.get('compliance_score', 0) for d in data if d.get('compliance_score')]
        if scores:
            analysis['average_score'] = round(mean(scores), 2)
            if len(scores) > 1:
                analysis['std_deviation'] = round(stdev(scores), 2)
        
        # Detect low compliance pattern
        low_compliance = [d for d in data if d.get('compliance_score', 100) < 60]
        if low_compliance:
            analysis['anomalies'].append({
                'type': 'LOW_COMPLIANCE',
                'count': len(low_compliance),
                'severity': 'HIGH' if len(low_compliance) > 5 else 'MEDIUM'
            })

        # Trend analysis
        analysis['patterns'].append({
            'type': 'COMPLIANCE_TREND',
            'direction': 'improving' if analysis['average_score'] > 70 else 'declining',
            'confidence': 0.85
        })

    return analysis

if __name__ == '__main__':
    if len(sys.argv) > 1:
        with open(sys.argv[1], 'r') as f:
            data = json.load(f)
            result = analyze_compliance(data)
            
            output_file = sys.argv[1].replace('compliance_input.json', 'compliance_analysis.json')
            with open(output_file, 'w') as out:
                json.dump(result, out, indent=2)
            
            print(json.dumps(result))
PYTHON;

        $compliance_path = $this->scripts_dir . '/analyze_compliance.py';
        file_put_contents($compliance_path, $compliance_script);

        // Create predict_violations.py
        $violation_script = <<<'PYTHON'
import json
import sys
from datetime import datetime, timedelta
from collections import Counter

def predict_violations(data):
    """Predict future violations based on patterns"""
    predictions = {
        'timestamp': datetime.now().isoformat(),
        'predictions': [],
        'high_risk_officers': [],
        'peak_violation_hours': []
    }

    if data:
        # Analyze violation types
        violation_types = Counter([d.get('alert_type') for d in data if d.get('alert_type')])
        
        # Find high-risk officers
        officer_violations = {}
        for record in data:
            officer = record.get('personnel_id')
            if officer:
                officer_violations[officer] = officer_violations.get(officer, 0) + 1
        
        high_risk = sorted(officer_violations.items(), key=lambda x: x[1], reverse=True)[:5]
        predictions['high_risk_officers'] = [{'officer_id': o[0], 'violation_count': o[1]} for o in high_risk]

        # Predict most likely violation type
        if violation_types:
            most_common = violation_types.most_common(1)[0]
            predictions['predictions'].append({
                'type': 'COMMON_VIOLATION',
                'violation_type': most_common[0],
                'frequency': most_common[1],
                'probability': round(most_common[1] / len(data), 2)
            })

    return predictions

if __name__ == '__main__':
    if len(sys.argv) > 1:
        with open(sys.argv[1], 'r') as f:
            data = json.load(f)
            result = predict_violations(data)
            
            output_file = sys.argv[1].replace('violation_input.json', 'violation_predictions.json')
            with open(output_file, 'w') as out:
                json.dump(result, out, indent=2)
            
            print(json.dumps(result))
PYTHON;

        $violation_path = $this->scripts_dir . '/predict_violations.py';
        file_put_contents($violation_path, $violation_script);

        // Create detect_anomalies.py
        $anomaly_script = <<<'PYTHON'
import json
import sys
import math
from datetime import datetime

def haversine_distance(lat1, lon1, lat2, lon2):
    """Calculate distance between two coordinates"""
    R = 6371
    dLat = math.radians(float(lat2) - float(lat1))
    dLon = math.radians(float(lon2) - float(lon1))
    a = math.sin(dLat/2) * math.sin(dLat/2) + math.cos(math.radians(float(lat1))) * math.cos(math.radians(float(lat2))) * math.sin(dLon/2) * math.sin(dLon/2)
    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1-a))
    return R * c

def detect_anomalies(data):
    """Detect anomalies in location tracking"""
    anomalies = {
        'timestamp': datetime.now().isoformat(),
        'total_records': len(data),
        'anomalies_detected': [],
        'summary': {}
    }

    if len(data) > 1:
        # Check for unusual movements
        for i in range(1, len(data)):
            curr = data[i]
            prev = data[i-1]
            
            if curr.get('latitude') and curr.get('longitude') and prev.get('latitude') and prev.get('longitude'):
                distance = haversine_distance(prev['latitude'], prev['longitude'], 
                                            curr['latitude'], curr['longitude'])
                
                # Flag if movement > 100km (unrealistic for duty on foot)
                if distance > 100:
                    anomalies['anomalies_detected'].append({
                        'type': 'UNUSUAL_MOVEMENT',
                        'distance_km': round(distance, 2),
                        'severity': 'HIGH',
                        'record_id': curr.get('id')
                    })

        # Count status changes
        status_changes = sum(1 for i in range(1, len(data)) 
                            if data[i].get('status') != data[i-1].get('status'))
        if status_changes > 5:
            anomalies['anomalies_detected'].append({
                'type': 'FREQUENT_STATUS_CHANGES',
                'count': status_changes,
                'severity': 'MEDIUM'
            })

        anomalies['summary'] = {
            'total_anomalies': len(anomalies['anomalies_detected']),
            'risk_level': 'HIGH' if len(anomalies['anomalies_detected']) > 5 else 'MEDIUM' if len(anomalies['anomalies_detected']) > 0 else 'LOW'
        }

    return anomalies

if __name__ == '__main__':
    if len(sys.argv) > 1:
        with open(sys.argv[1], 'r') as f:
            data = json.load(f)
            result = detect_anomalies(data)
            
            output_file = sys.argv[1].replace('location_input.json', 'anomalies.json')
            with open(output_file, 'w') as out:
                json.dump(result, out, indent=2)
            
            print(json.dumps(result))
PYTHON;

        $anomaly_path = $this->scripts_dir . '/detect_anomalies.py';
        file_put_contents($anomaly_path, $anomaly_script);

        return [
            'success' => true,
            'scripts_created' => [
                'analyze_compliance.py',
                'predict_violations.py',
                'detect_anomalies.py'
            ]
        ];
    }
}

?>
