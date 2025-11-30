# -------------------------
# 0. Install dependencies
# -------------------------
!pip install pandas openpyxl scikit-learn xgboost joblib matplotlib seaborn

# -------------------------
# 1. Imports
# -------------------------
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from sklearn.preprocessing import LabelEncoder, MultiLabelBinarizer
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, confusion_matrix
from sklearn.ensemble import RandomForestClassifier
from sklearn.svm import SVC
from xgboost import XGBClassifier
from sklearn.calibration import CalibratedClassifierCV
from joblib import dump
from collections import Counter

# -------------------------
# 2. Upload Excel file
# -------------------------
from google.colab import files
uploaded = files.upload()  # manually select your Excel file

excel_file = list(uploaded.keys())[0]
df = pd.read_excel(excel_file)
print(f"✅ Loaded Excel: {excel_file}")
print("Columns:", df.columns.tolist())

# -------------------------
# 3. Target & Preprocessing
# -------------------------
target_col = "risk_category"
if target_col not in df.columns:
    raise ValueError("❌ 'risk_category' column not found in Excel.")

# Encode target
le = LabelEncoder()
y = le.fit_transform(df[target_col].astype(str))
print("Classes:", list(le.classes_))
print("Class distribution:", Counter(y))

# -------------------------
# 4. Encode categorical columns
# -------------------------
cat_cols = ["Sex", "Civil Status", "School Status", 
            "Have you ever been pregnant?", "Have you impregnated someone?"]

df_cat = pd.get_dummies(df[cat_cols].fillna("NA").astype(str), prefix=cat_cols)

# -------------------------
# 5. Encode multi-value "Current Problems"
# -------------------------
def split_problems(x):
    if pd.isna(x):
        return []
    # Assume comma-separated values
    return [s.strip() for s in str(x).split(",")]

mlb_problems = MultiLabelBinarizer()
X_problems = mlb_problems.fit_transform(df['Current Problems'].apply(split_problems))
df_problems = pd.DataFrame(X_problems, columns=[f"prob__{c}" for c in mlb_problems.classes_])

# -------------------------
# 6. Combine features
# -------------------------
X = pd.concat([df_cat, df_problems], axis=1)
print(f"✅ Features: {X.shape[1]}, Samples: {len(df)}")

# -------------------------
# 7. Train/test split
# -------------------------
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, stratify=y, random_state=42
)

# -------------------------
# 8. Initialize models
# -------------------------
BASE_MODELS = {
    "Random Forest": RandomForestClassifier(n_estimators=200, class_weight="balanced", random_state=42),
    "XGBoost": XGBClassifier(eval_metric="mlogloss", n_estimators=300, learning_rate=0.1, max_depth=6, 
                             subsample=0.8, colsample_bytree=0.8, random_state=42),
    "SVM": SVC(kernel="linear", probability=True, class_weight="balanced", random_state=42)
}

# -------------------------
# 9. Train base models first
# -------------------------
for name, model in BASE_MODELS.items():
    print(f"\n🚀 Training base {name} ...")
    model.fit(X_train, y_train)
    BASE_MODELS[name] = model  # save trained model

# -------------------------
# 10. Calibrate models (safe with small classes)
# -------------------------
MODELS = {}
for name, model in BASE_MODELS.items():
    MODELS[name] = CalibratedClassifierCV(model, cv='prefit', method='sigmoid')
    MODELS[name].fit(X_train, y_train)

# -------------------------
# 11. Evaluate
# -------------------------
results = []
confidence_threshold = 0.8

for name, model in MODELS.items():
    print(f"\n🚀 Evaluating {name} ...")
    y_pred = model.predict(X_test)
    y_probs = model.predict_proba(X_test)
    max_conf = y_probs.max(axis=1)
    
    flagged = max_conf < confidence_threshold
    
    results.append({
        "Model": name,
        "Accuracy": round(accuracy_score(y_test, y_pred), 3),
        "Precision": round(precision_score(y_test, y_pred, average="macro", zero_division=0),3),
        "Recall": round(recall_score(y_test, y_pred, average="macro", zero_division=0),3),
        "F1-Score": round(f1_score(y_test, y_pred, average="macro", zero_division=0),3),
        "Low Confidence Count": flagged.sum()
    })
    
    # Confusion matrix
    cm = confusion_matrix(y_test, y_pred)
    plt.figure(figsize=(6,5))
    sns.heatmap(cm, annot=True, fmt="d", cmap="Blues",
                xticklabels=le.classes_,
                yticklabels=le.classes_)
    plt.title(f"Confusion Matrix - {name}")
    plt.xlabel("Predicted")
    plt.ylabel("Actual")
    plt.tight_layout()
    plt.savefig(f"confusion_matrix_{name.replace(' ','_').lower()}.png")
    plt.close()
    
    # Save low-confidence predictions
    df_conf = pd.DataFrame({
        "Predicted Risk": le.inverse_transform(y_pred),
        "Confidence": max_conf,
        "Low Confidence": flagged
    })
    df_conf.to_csv(f"{name.replace(' ','_').lower()}_predictions_with_confidence.csv", index=False)

# -------------------------
# 12. Save models & artifacts
# -------------------------
for name, model in MODELS.items():
    dump(model, f"{name.replace(' ','_').lower()}_model.joblib")
dump(X.columns.tolist(), "feature_columns.joblib")
dump(le, "label_encoder.joblib")

# -------------------------
# 13. Summary
# -------------------------
df_results = pd.DataFrame(results)
print("\n📊 Classifier Results:")
print(df_results)
print("\n✅ Training complete. Models saved!")
