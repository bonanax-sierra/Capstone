# train_multi_classifier.py
import json
import pandas as pd
from sqlalchemy import create_engine
from sklearn.preprocessing import LabelEncoder, MultiLabelBinarizer, StandardScaler
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score
from sklearn.ensemble import RandomForestClassifier
from sklearn.neural_network import MLPClassifier
from xgboost import XGBClassifier
from imblearn.over_sampling import SMOTE
from joblib import dump
from collections import Counter
import numpy as np

# -------------------------
# 1. Database Config
# -------------------------
DB_USER = "root"
DB_PASS = ""  # Update your password
DB_HOST = "localhost"
DB_NAME = "capstone1"

DB_URI = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}/{DB_NAME}"
engine = create_engine(DB_URI)

# -------------------------
# 2. Load Data
# -------------------------
sql = """
SELECT 
    assessment_id,
    age,
    gender,
    dob,
    civil_status,
    employment_status,
    school_status,
    grade_level,
    school_id,
    problems,
    desired_service,
    pregnant,
    pregnant_age,
    mobile_use,
    risk_category
FROM assessment
WHERE risk_category IS NOT NULL;
"""
df = pd.read_sql(sql, engine)

# -------------------------
# 3. Parse problems
# -------------------------
def parse_problems(x):
    try:
        return json.loads(x) if x else []
    except:
        return []

df['problems_list'] = df['problems'].apply(parse_problems)
mlb = MultiLabelBinarizer()
X_problems = mlb.fit_transform(df['problems_list'])
df_problems = pd.DataFrame(
    X_problems,
    columns=[f"prob__{c}" for c in mlb.classes_],
    index=df.index
)

# -------------------------
# 4. Feature Engineering
# -------------------------
cat_cols = [
    "civil_status", "employment_status", "school_status",
    "grade_level", "desired_service", "pregnant", "pregnant_age",
    "mobile_use", "gender"
]
df_cat = pd.get_dummies(df[cat_cols].fillna("NA").astype(str), prefix=cat_cols)
df_num = df[["age"]].fillna(df["age"].median())
df_school = pd.get_dummies(df["school_id"].fillna(-1).astype(str), prefix="school")

X = pd.concat([df_num, df_cat, df_problems, df_school], axis=1).fillna(0)
feature_columns = X.columns.tolist()

le = LabelEncoder()
y = le.fit_transform(df["risk_category"].astype(str))

print(f"✅ Using features ({len(feature_columns)}): {feature_columns}")
print("✅ Target classes:", list(le.classes_))
print("Class distribution:", Counter(y))

# -------------------------
# 5. Train/Test Split
# -------------------------
try:
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, stratify=y, random_state=42
    )
except ValueError:
    print("⚠ Stratified split failed, using non-stratified split.")
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42
    )

# -------------------------
# 6. Handle Class Imbalance
# -------------------------
smote = SMOTE(random_state=42)
X_train_res, y_train_res = smote.fit_resample(X_train, y_train)
print("Class distribution after SMOTE:", Counter(y_train_res))

# Scale numeric features for ANN
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train_res)
X_test_scaled = scaler.transform(X_test)

# -------------------------
# 7. Initialize Classifiers
# -------------------------
models = {
    "Random Forest": RandomForestClassifier(n_estimators=200, class_weight="balanced", random_state=42),
    "ANN": MLPClassifier(hidden_layer_sizes=(50,50), max_iter=500, random_state=42),
    "XGBoost": XGBClassifier(use_label_encoder=False, eval_metric='mlogloss', random_state=42)
}

# -------------------------
# 8. Train and Evaluate
# -------------------------
results = []

for name, model in models.items():
    if name == "ANN":
        model.fit(X_train_scaled, y_train_res)
        y_pred = model.predict(X_test_scaled)
    else:
        model.fit(X_train_res, y_train_res)
        y_pred = model.predict(X_test)
    
    acc = accuracy_score(y_test, y_pred)
    prec = precision_score(y_test, y_pred, average='macro', zero_division=0)
    rec = recall_score(y_test, y_pred, average='macro', zero_division=0)
    f1 = f1_score(y_test, y_pred, average='macro', zero_division=0)

    results.append({
        "Model": name,
        "Accuracy": round(acc,3),
        "Precision": round(prec,3),
        "Recall": round(rec,3),
        "F1-Score": round(f1,3)
    })

df_results = pd.DataFrame(results)
print("\n📊 Multi-Classifier Comparison:")
print(df_results)

# -------------------------
# 9. Save Models
# -------------------------
dump(models["Random Forest"], "rf_model.joblib")
dump(models["ANN"], "ann_model.joblib")
dump(models["XGBoost"], "xgb_model.joblib")
dump(mlb, "mlb_problems.joblib")
dump(le, "label_encoder.joblib")
dump(scaler, "scaler.joblib")
with open("feature_columns.json", "w") as f:
    json.dump(feature_columns, f)

print("\n✅ Training complete. Models saved.")
