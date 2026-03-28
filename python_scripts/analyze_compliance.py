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