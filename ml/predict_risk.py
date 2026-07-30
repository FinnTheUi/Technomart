import sys
import json
import joblib
import pandas as pd

def predict(input_json):
    try:
        # Load the trained model
        model = joblib.load('ml/risk_model.pkl')
        
        # Parse the input JSON (expected to be a list of dictionaries)
        data = json.loads(input_json)
        
        if not data:
            return json.dumps([])
            
        df = pd.DataFrame(data)
        
        # Extract features (must match the training features)
        # Expected keys: 'id', 'past_paid', 'past_overdue', 'overdue_ratio', 'amount'
        X = df[['past_paid', 'past_overdue', 'overdue_ratio', 'amount']]
        
        # Predict probability of class 1 (is_overdue)
        probabilities = model.predict_proba(X)[:, 1]
        
        # Format output
        results = []
        for i, row in df.iterrows():
            results.append({
                'id': row['id'],
                'risk_probability': float(probabilities[i])
            })
            
        return json.dumps(results)
    except Exception as e:
        # Return empty list on error
        print(f"Error: {e}", file=sys.stderr)
        return json.dumps([])

if __name__ == "__main__":
    if len(sys.argv) > 1:
        print(predict(sys.argv[1]))
    else:
        print(json.dumps([]))
