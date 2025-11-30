# train_classifiers.py  (CONFIDENCE-BOOSTED VERSION)
import json
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from sqlalchemy import create_engine
from sklearn.preprocessing import LabelEncoder, MultiLabelBinarizer
from sklearn.model_selection import train_test_split
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score, confusion_matrix
)
from sklearn.ensemble import RandomForestClassifier
from sklearn.svm import SVC
from xgboost import XGBClassifier
from imblearn.combine import SMOTETomek
from sklearn.calibration import CalibratedClassifierCV
from joblib import dump
from collections import Counter

# -------------------------
# 1. Database Config
# -------------------------
DB_USER = "root"
DB_PASS = ""
DB_HOST = "localhost"
DB_NAME = "capstone"

engine = create_engine(f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}/{DB_NAME}")

# -------------------------
# 2. Load Data
# -------------------------
sql = """
SELECT 
    assessment_id,
    sex,
    civil_status,
    school_status,
    current_problems,
    desired_service,
    pregnant,
    impregnated_someone,
    school_id,
    risk_category
FROM assessment
WHERE risk_category IS NOT NULL;
"""
df = pd.read_sql(sql, engine)

if df.empty:
    raise ValueError("❌ No labeled data found. Import assessments with risk_category first.")

# -------------------------
# 3. Parse JSON Fields
# -------------------------
def parse_json_list(x):
    try:
        return json.loads(x) if x else []
    except:
        return []

df['problems_list'] = df['current_problems'].apply(parse_json_list)
df['services_list'] = df['desired_service'].apply(parse_json_list)

mlb_problems = MultiLabelBinarizer()
X_problems = mlb_problems.fit_transform(df['problems_list'])
df_problems = pd.DataFrame(X_problems, columns=[f"prob__{c}" for c in mlb_problems.classes_], index=df.index)

mlb_services = MultiLabelBinarizer()
X_services = mlb_services.fit_transform(df['services_list'])
df_services = pd.DataFrame(X_services, columns=[f"serv__{c}" for c in mlb_services.classes_], index=df.index)

# -------------------------
# 4. Encode Categorical
# -------------------------
cat_cols = ["sex", "civil_status", "school_status", "pregnant", "impregnated_someone"]

df_cat = pd.get_dummies(df[cat_cols].fillna("NA").astype(str), prefix=cat_cols)
df_school = pd.get_dummies(df["school_id"].fillna(-1).astype(str), prefix="school")

# -------------------------
# 5. Combine Features
# -------------------------
X = pd.concat([df_cat, df_problems, df_services, df_school], axis=1).fillna(0)
feature_columns = X.columns.tolist()

le = LabelEncoder()
y = le.fit_transform(df["risk_category"].astype(str))

print(f"✅ Features used: {len(feature_columns)}")
print("Classes:", list(le.classes_))
print("Class distribution (before SMOTE):", Counter(y))

# -------------------------
# 6. Train/Test Split
# -------------------------
try:
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, stratify=y, random_state=42
    )
except ValueError:
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42
    )

# -------------------------
# 7. SMOTE + Tomek Links
# -------------------------
resampler = SMOTETomek(random_state=42)
X_train_res, y_train_res = resampler.fit_resample(X_train, y_train)
print("Class distribution after SMOTE:", Counter(y_train_res))

# -------------------------
# 8. Initialize MODELS
# -------------------------
BASE_MODELS = {
    "Random Forest": RandomForestClassifier(
        n_estimators=200,
        class_weight="balanced",
        random_state=42
    ),
    "XGBoost": XGBClassifier(
        eval_metric="mlogloss",
        random_state=42,
        n_estimators=300,
        learning_rate=0.1,
        max_depth=6,
        subsample=0.8,
        colsample_bytree=0.8
    ),
    "SVM": SVC(
        kernel="linear",
        probability=True,
        class_weight="balanced",
        random_state=42
    )
}

# -------------------------
# 9. Apply CALIBRATION (Boosts confidence!)
# -------------------------
MODELS = {
    name: CalibratedClassifierCV(model, cv=5, method="isotonic")
    for name, model in BASE_MODELS.items()
}

# -------------------------
# 10. Train + Evaluate
# -------------------------
results = []

for name, model in MODELS.items():
    print(f"\n🚀 Training (Calibrated) {name} ...")
    model.fit(X_train_res, y_train_res)
    y_pred = model.predict(X_test)

    acc = accuracy_score(y_test, y_pred)
    prec = precision_score(y_test, y_pred, average="macro", zero_division=0)
    rec = recall_score(y_test, y_pred, average="macro", zero_division=0)
    f1 = f1_score(y_test, y_pred, average="macro", zero_division=0)

    results.append({
        "Model": name,
        "Accuracy": round(acc, 3),
        "Precision": round(prec, 3),
        "Recall": round(rec, 3),
        "F1-Score": round(f1, 3)
    })

    # Confusion matrix
    cm = confusion_matrix(y_test, y_pred)
    plt.figure(figsize=(6, 5))
    sns.heatmap(cm, annot=True, fmt="d", cmap="Blues",
                xticklabels=le.classes_,
                yticklabels=le.classes_)
    plt.title(f"Confusion Matrix - {name}")
    plt.xlabel("Predicted")
    plt.ylabel("Actual")
    plt.tight_layout()
    plt.savefig(f"confusion_matrix_{name.replace(' ', '_').lower()}.png")
    plt.close()

df_results = pd.DataFrame(results)
print("\n📊 Calibrated Classifier Comparison:")
print(df_results)

# -------------------------
# 11. Save MODELS
# -------------------------
dump(MODELS["Random Forest"], "deepforest_model_rf.joblib")
dump(MODELS["XGBoost"], "xgb_model.joblib")  # 🔥 This is what API will use
dump(MODELS["SVM"], "svm_model.joblib")
dump(mlb_problems, "mlb_problems.joblib")
dump(mlb_services, "mlb_services.joblib")
dump(le, "label_encoder.joblib")

with open("feature_columns.json", "w") as f:
    json.dump(feature_columns, f)

print("\n✅ TRAINING COMPLETE")
print("📌 Models saved (calibrated)")
print(f"📌 Training samples: {len(df)}")
