from fastapi import FastAPI
from pydantic import BaseModel
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
import os
import datetime as dt
import mysql.connector
from mysql.connector import Error
from typing import Optional

DB_CONFIG = {
	"host": os.getenv("DB_HOST", "localhost"),
	"user": os.getenv("DB_USER", "root"),
	"password": os.getenv("DB_PASS", ""),
	"database": os.getenv("DB_NAME", "farmbridge"),
}

app = FastAPI(title="FarmBridge AI Service", version="0.1.0")
app.add_middleware(
	CORSMiddleware,
	allow_origins=["*"],
	allow_credentials=True,
	allow_methods=["*"],
	allow_headers=["*"],
)

class PriceRequest(BaseModel):
	crop: str
	date: dt.date
	location: Optional[str] = None

class IntentRequest(BaseModel):
	message: str

# Simple DB helper

def query_single(sql: str, params: tuple = ()):  # returns first row or None
	try:
		conn = mysql.connector.connect(**DB_CONFIG)
		cur = conn.cursor(dictionary=True)
		cur.execute(sql, params)
		row = cur.fetchone()
		cur.close()
		conn.close()
		return row
	except Error:
		return None


def query_many(sql: str, params: tuple = ()):  # returns list of rows
	try:
		conn = mysql.connector.connect(**DB_CONFIG)
		cur = conn.cursor(dictionary=True)
		cur.execute(sql, params)
		rows = cur.fetchall()
		cur.close()
		conn.close()
		return rows
	except Error:
		return []


@app.post("/predict_price")
def predict_price(req: PriceRequest):
	crop = req.crop.strip().lower()
	target_date = req.date
	location = (req.location or "").strip().lower()

	# 1) Use internal averages as baseline features
	rows = query_many(
		"""
		SELECT price, date FROM market_prices
		WHERE LOWER(commodity) LIKE %s
		AND date >= DATE_SUB(%s, INTERVAL 90 DAY)
		ORDER BY date DESC
		""",
		(f"%{crop}%", target_date),
	)

	prices = [float(r["price"]) for r in rows if r.get("price") is not None]

	# 2) Seasonal factor (very simple heuristics)
	month = target_date.month
	seasonal_factor = 1.0
	if crop in {"tomato", "onion", "cabbage", "carrot"}:
		if month in {1, 2, 9, 10, 11, 12}:  # rainy peaks
			seasonal_factor = 1.05
		elif month in {6, 7, 8}:  # dry season pressure
			seasonal_factor = 1.08
	elif crop in {"maize", "bean", "rice"}:
		if month in {3, 4, 5, 9, 10, 11}:  # planting/harvest cycles
			seasonal_factor = 0.98

	# 3) Compute recent vs prior 30d averages if available
	def avg(vals):
		return sum(vals) / len(vals) if vals else 0.0

	if prices:
		recent = avg(prices[:min(30, len(prices))])
		prior = avg(prices[min(30, len(prices)):min(60, len(prices))])
		if prior > 0:
			growth = max(min((recent - prior) / prior, 0.25), -0.25)
			forecast = recent * (1.0 + growth) * seasonal_factor
		else:
			forecast = recent * seasonal_factor
	else:
		# Fallback baseline by commodity
		baselines = {
			"maize": 450,
			"tomato": 1200,
			"potato": 800,
			"banana": 600,
			"rice": 1800,
			"bean": 2200,
			"cassava": 300,
			"onion": 900,
			"cabbage": 400,
			"carrot": 600,
		}
		forecast = baselines.get(crop, 1000) * seasonal_factor

	return {
		"crop": crop,
		"date": target_date.isoformat(),
		"location": location or None,
		"price": round(float(forecast), 2),
		"basis": "internal market averages + seasonal heuristic",
	}

# Intent prediction endpoint: placeholder until training script writes a model
try:
	import joblib  # type: ignore
	import pathlib
	MODEL_PATH = pathlib.Path(__file__).parent / "intent_classifier.pkl"
	_intent_model = joblib.load(MODEL_PATH) if MODEL_PATH.exists() else None
except Exception:
	_intent_model = None


@app.post("/predict_intent")
def predict_intent(req: IntentRequest):
	text = req.message.strip()
	if not text:
		return {"label": None, "confidence": 0.0}
	if _intent_model is None:
		# simple rule baseline while model missing
		label = "price" if ("price" in text.lower() or "cost" in text.lower()) else "general"
		return {"label": label, "confidence": 0.5, "note": "model not loaded; rule baseline"}
	try:
		label = _intent_model.predict([text])[0]
		proba = getattr(_intent_model, "predict_proba", None)
		conf = float(max(proba([text])[0])) if callable(proba) else 0.0
		return {"label": str(label), "confidence": conf}
	except Exception:
		return {"label": None, "confidence": 0.0}


if __name__ == "__main__":
	uvicorn.run(app, host=os.getenv("AI_HOST", "127.0.0.1"), port=int(os.getenv("AI_PORT", 8000)))
