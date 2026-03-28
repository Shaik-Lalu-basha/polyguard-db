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