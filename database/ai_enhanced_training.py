import argparse
import joblib
import pathlib
from typing import List

from sklearn.pipeline import Pipeline
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report

MODEL_PATH = pathlib.Path(__file__).parent / "intent_classifier.pkl"

# Enhanced dataset with diverse agricultural queries
SEED_TEXTS = [
	# Price queries
	"what is the price of tomatoes",
	"how much does maize cost",
	"rice price today",
	"potato market price",
	"banana cost per kg",
	"onion price in market",
	"carrot price today",
	"cabbage market rate",
	"bean price per bag",
	"cassava price",
	"what are current crop prices",
	"show me today's prices",
	"price list for vegetables",
	"grain prices this week",
	"fruit prices in market",
	
	# Market trends
	"show market trends",
	"market analysis for crops",
	"price trends for tomatoes",
	"market forecast",
	"commodity market status",
	"agricultural market report",
	"price movement analysis",
	"market volatility",
	"seasonal price patterns",
	"market outlook",
	
	# Advice and recommendations
	"what should i plant this season",
	"best crops to grow now",
	"farming advice",
	"crop selection help",
	"seasonal farming tips",
	"agricultural guidance",
	"farming recommendations",
	"crop planning advice",
	"when to plant tomatoes",
	"farming best practices",
	
	# Platform information
	"how your platform works",
	"how to use this app",
	"platform features",
	"how to buy crops",
	"how to sell crops",
	"registration process",
	"user guide",
	"platform tutorial",
	"how to place order",
	"buying process",
	
	# Safety and security
	"is payment safe",
	"secure payment methods",
	"data protection",
	"privacy policy",
	"security measures",
	"payment security",
	"user safety",
	"fraud protection",
	"secure transactions",
	"trust and safety",
	
	# Demand forecasting
	"demand forecast for beans",
	"crop demand analysis",
	"market demand trends",
	"future demand prediction",
	"demand supply analysis",
	"market demand forecast",
	"crop demand outlook",
	"demand planning",
	"market demand report",
	"demand forecasting",
	
	# Weather information
	"weather today",
	"weather forecast",
	"rainfall prediction",
	"weather for farming",
	"seasonal weather",
	"weather impact on crops",
	"farming weather",
	"agricultural weather",
	"weather conditions",
	"climate information",
	
	# Greetings and general
	"hi",
	"hello",
	"good morning",
	"good afternoon",
	"good evening",
	"thank you",
	"help me",
	"support",
	"contact us",
	"customer service",
]

SEED_LABELS = [
	# Price queries
	"price", "price", "price", "price", "price", "price", "price", "price", "price", "price",
	"price", "price", "price", "price", "price",
	
	# Market trends
	"market", "market", "market", "market", "market", "market", "market", "market", "market", "market",
	
	# Advice and recommendations
	"advice", "advice", "advice", "advice", "advice", "advice", "advice", "advice", "advice", "advice",
	
	# Platform information
	"how_platform", "how_platform", "how_platform", "how_platform", "how_platform", "how_platform", 
	"how_platform", "how_platform", "how_platform", "how_platform",
	
	# Safety and security
	"safety", "safety", "safety", "safety", "safety", "safety", "safety", "safety", "safety", "safety",
	
	# Demand forecasting
	"demand", "demand", "demand", "demand", "demand", "demand", "demand", "demand", "demand", "demand",
	
	# Weather information
	"weather", "weather", "weather", "weather", "weather", "weather", "weather", "weather", "weather", "weather",
	
	# Greetings and general
	"greeting", "greeting", "greeting", "greeting", "greeting", "greeting", "greeting", "greeting", "greeting", "greeting",
]


def train(texts: List[str], labels: List[str]):
	X_train, X_test, y_train, y_test = train_test_split(texts, labels, test_size=0.2, random_state=42)
	clf = Pipeline([
		("tfidf", TfidfVectorizer(ngram_range=(1,2), min_df=1)),
		("lr", LogisticRegression(max_iter=1000)),
	])
	clf.fit(X_train, y_train)
	y_pred = clf.predict(X_test)
	print(classification_report(y_test, y_pred))
	joblib.dump(clf, MODEL_PATH)
	print(f"Saved model to {MODEL_PATH}")


def predict(text: str):
    if not MODEL_PATH.exists():
        # Auto-train if model is missing
        try:
            train(SEED_TEXTS, SEED_LABELS)
        except Exception:
            print("Model not found; run without --predict to train first.")
            return
    clf = joblib.load(MODEL_PATH)
    y = clf.predict([text])[0]
    print(y)


if __name__ == "__main__":
	parser = argparse.ArgumentParser()
	parser.add_argument("--predict", type=str, default=None)
	args = parser.parse_args()

	if args.predict:
		predict(args.predict)
	else:
		train(SEED_TEXTS, SEED_LABELS)
