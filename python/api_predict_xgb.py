# api_predict_xgb.py
from flask import Flask, request, jsonify
import pandas as pd
from joblib import load
import json
import numpy as np
import os

app = Flask(__name__)

# -------------------------
# Set base directory
# -------------------------
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# -------------------------
# Load artifacts
# -------------------------
xgb_model = load(os.path.join(BASE_DIR, "xgb_model.joblib"))
mlb_problems = load(os.path.join(BASE_DIR, "mlb_problems.joblib"))
mlb_services = load(os.path.join(BASE_DIR, "mlb_services.joblib"))
le = load(os.path.join(BASE_DIR, "label_encoder.joblib"))

with open(os.path.join(BASE_DIR, "feature_columns.json")) as f:
    feature_columns = json.load(f)


def preprocess_batch(records):
    all_features = []

    for data in records:
        # 1. Problems
        problems_list = data.get("problems", [])
        X_problems = pd.DataFrame(
            mlb_problems.transform([problems_list]),
            columns=[f"prob__{c}" for c in mlb_problems.classes_]
        )

        # 2. Services
        services_list = data.get("services", [])
        X_services = pd.DataFrame(
            mlb_services.transform([services_list]),
            columns=[f"serv__{c}" for c in mlb_services.classes_]
        )

        # 3. Categorical
        cat_cols = ["sex", "civil_status", "school_status", "pregnant", "impregnated_someone"]
        df_cat = pd.get_dummies(pd.DataFrame([data], columns=cat_cols).fillna("NA").astype(str))

        # 4. School features
        school_col = f"school_{data.get('school_id', -1)}"
        df_school = pd.DataFrame([{school_col: 1}])

        # Merge and align columns
        X_row = pd.concat([df_cat, X_problems, X_services, df_school], axis=1)
        X_row = X_row.reindex(columns=feature_columns, fill_value=0)
        all_features.append(X_row)

    return pd.concat(all_features, ignore_index=True)


@app.route("/predict_risk", methods=["POST"])
def predict_risk():
    records = request.json
    if isinstance(records, dict):
        records = [records]

    X = preprocess_batch(records)

    preds = xgb_model.predict(X)
    probs = xgb_model.predict_proba(X)

    # Use classes from label encoder
    model_classes = le.classes_

    results = []
    for i, record in enumerate(records):
        prob_dict = {model_classes[j]: float(probs[i][j]) for j in range(len(probs[i]))}
        sorted_prob_dict = dict(sorted(prob_dict.items(), key=lambda x: x[1], reverse=True))

        results.append({
            "input": record,
            "predicted": str(preds[i]),
            "xgboost": sorted_prob_dict
        })

    return jsonify(results)


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port)
