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