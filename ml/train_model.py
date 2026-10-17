"""
Train the late-payment risk model.

Training data is exported by Laravel (`php artisan ml:train`) as a CSV with columns:
    past_paid, past_overdue, overdue_ratio, amount, is_overdue

Usage:
    python ml/train_model.py --input storage/app/ml/training.csv --output ml/risk_model.pkl --min-rows 30

Prints a single JSON line with the outcome so the caller can log it.
Exit codes: 0 = trained, 2 = not enough / unusable data (existing model kept), 1 = error.
"""
import argparse
import json
import os
import sys

import joblib
import pandas as pd
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, confusion_matrix, f1_score, precision_score, recall_score, roc_auc_score
from sklearn.model_selection import train_test_split

FEATURES = ["past_paid", "past_overdue", "overdue_ratio", "amount"]
LABEL = "is_overdue"


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True)
    parser.add_argument("--output", default="ml/risk_model.pkl")
    parser.add_argument("--min-rows", type=int, default=30)
    parser.add_argument("--metrics-output")
    args = parser.parse_args()

    df = pd.read_csv(args.input)

    if len(df) < args.min_rows:
        print(json.dumps({"trained": False, "reason": f"only {len(df)} rows (need {args.min_rows})"}))
        return 2

    if df[LABEL].nunique() < 2:
        print(json.dumps({"trained": False, "reason": "training data has only one class (no late or no on-time bills)"}))
        return 2

    X, y = df[FEATURES], df[LABEL]

    metrics = {"rows": int(len(df)), "late_rows": int(y.sum()), "on_time_rows": int((y == 0).sum())}
    # Hold out 20% for an honest accuracy estimate when each class has enough samples.
    if y.value_counts().min() >= 5:
        X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, stratify=y, random_state=42)
        evaluation = LogisticRegression(class_weight="balanced", max_iter=1000).fit(X_train, y_train)
        predicted = evaluation.predict(X_test)
        matrix = confusion_matrix(y_test, predicted, labels=[0, 1])
        metrics |= {
            "accuracy": round(float(accuracy_score(y_test, evaluation.predict(X_test))), 4),
            "roc_auc": round(float(roc_auc_score(y_test, evaluation.predict_proba(X_test)[:, 1])), 4),
            "precision": round(float(precision_score(y_test, predicted, zero_division=0)), 4),
            "recall": round(float(recall_score(y_test, predicted, zero_division=0)), 4),
            "f1": round(float(f1_score(y_test, predicted, zero_division=0)), 4),
            "confusion_matrix": matrix.tolist(),
        }

    # Final model is fitted on all available data.
    model = LogisticRegression(class_weight="balanced", max_iter=1000).fit(X, y)

    # Write atomically so a running predictor never reads a half-written file.
    os.makedirs(os.path.dirname(args.output) or ".", exist_ok=True)
    tmp_path = args.output + ".tmp"
    joblib.dump(model, tmp_path)
    os.replace(tmp_path, args.output)

    result = {"trained": True, "trained_at": pd.Timestamp.now(tz="UTC").isoformat(), **metrics}
    if args.metrics_output:
        os.makedirs(os.path.dirname(args.metrics_output) or ".", exist_ok=True)
        metrics_tmp = args.metrics_output + ".tmp"
        with open(metrics_tmp, "w", encoding="utf-8") as handle:
            json.dump(result, handle)
        os.replace(metrics_tmp, args.metrics_output)
    print(json.dumps(result))
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as exc:  # noqa: BLE001 - report any failure to the caller
        print(json.dumps({"trained": False, "error": str(exc)}))
        sys.exit(1)
