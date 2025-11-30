# api_predict_multi.py
from flask import Flask, request, jsonify
import pandas as pd
from joblib import load
import json

app = Flask(__name__)

# -------------------------
# Load all artifacts
# -------------------------
rf_model = load("deepforest_model_rf.joblib")      # Random Forest
ann_model = load("ann_model.joblib")    # ANN
xgb_model = load("xgb_model.joblib")    # XGBoost

mlb = load("mlb_problems.joblib")
le = load("label_encoder.joblib")
with open("feature_columns.json") as f:
    feature_columns = json.load(f)

# -------------------------
# API Endpoint
# -------------------------
@app.route("/predict_risk", methods=["POST"])
def predict_risk():
    data = request.json

    # 1. Prepare problems features
    problems_list = data.get("problems", [])
    X_problems = pd.DataFrame(
        mlb.transform([problems_list]), 
        columns=[f"prob__{c}" for c in mlb.classes_]
    )

    # 2. Prepare categorical features
    cat_cols = [
        "civil_status", "employment_status", "school_status", "grade_level",
        "desired_service", "pregnant", "pregnant_age", "mobile_use", "gender"
    ]
    df_cat = pd.get_dummies(
        pd.DataFrame([data], columns=cat_cols).fillna("NA").astype(str)
    )

    # 3. Numeric features
    df_num = pd.DataFrame([{"age": data.get("age", 0)}])

    # 4. School
    school_col = f"school_{data.get('school_id', 0)}"
    df_school = pd.DataFrame([{school_col: 1}])

    # 5. Merge all features
    X = pd.concat([df_num, df_cat, X_problems, df_school], axis=1)\
           .reindex(columns=feature_columns, fill_value=0)

    # -------------------------
    # 6. Predict using all classifiers
    # -------------------------
    pred_rf = le.inverse_transform([rf_model.predict(X)[0]])[0]
    pred_ann = le.inverse_transform([ann_model.predict(X)[0]])[0]
    pred_xgb = le.inverse_transform([xgb_model.predict(X)[0]])[0]

    return jsonify({
        "random_forest": pred_rf,
        "ann": pred_ann,
        "xgboost": pred_xgb
    })

# -------------------------
# Run server
# -------------------------
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
