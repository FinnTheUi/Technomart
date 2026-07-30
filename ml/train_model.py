import pandas as pd
from sqlalchemy import create_engine
from sklearn.linear_model import LogisticRegression
import joblib
import os

def train():
    print("Connecting to database...")
    engine = create_engine('mysql+pymysql://root:@127.0.0.1/rentelligence')
    
    query = "SELECT id, tenant_id, amount, status, created_at FROM payments ORDER BY tenant_id, created_at ASC"
    df = pd.read_sql(query, engine)
    
    dataset = []
    tenant_stats = {}
    
    print(f"Processing {len(df)} payment records...")
    for index, row in df.iterrows():
        t_id = row['tenant_id']
        if t_id not in tenant_stats:
            tenant_stats[t_id] = {'paid': 0, 'overdue': 0}
            
        past_paid = tenant_stats[t_id]['paid']
        past_overdue = tenant_stats[t_id]['overdue']
        total_past = past_paid + past_overdue
        overdue_ratio = past_overdue / total_past if total_past > 0 else 0.0
        
        is_overdue = 1 if row['status'] == 'overdue' else 0
        
        if row['status'] in ['paid', 'overdue']:
            dataset.append({
                'past_paid': past_paid,
                'past_overdue': past_overdue,
                'overdue_ratio': overdue_ratio,
                'amount': float(row['amount']),
                'is_overdue': is_overdue
            })
            
            if row['status'] == 'paid':
                tenant_stats[t_id]['paid'] += 1
            elif row['status'] == 'overdue':
                tenant_stats[t_id]['overdue'] += 1
                
    train_df = pd.DataFrame(dataset)
    
    if len(train_df) > 10:
        X = train_df[['past_paid', 'past_overdue', 'overdue_ratio', 'amount']]
        y = train_df['is_overdue']
        
        print("Training Logistic Regression model...")
        model = LogisticRegression(class_weight='balanced')
        model.fit(X, y)
        
        os.makedirs('ml', exist_ok=True)
        joblib.dump(model, 'ml/risk_model.pkl')
        print("Model trained and saved successfully to ml/risk_model.pkl")
    else:
        print("Not enough data to train. Need at least 10 historical records.")

if __name__ == "__main__":
    train()
