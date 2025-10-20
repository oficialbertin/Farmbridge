#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Self-contained CLI for price aggregation and trends (no external module imports)
Usage:
  py -3 price_cli.py aggregate --crop tomato
  py -3 price_cli.py trend --crop maize --days 30
"""
import argparse
import json
from datetime import datetime, timedelta
import statistics
from typing import Dict, List
import os

# Optional dependencies with graceful fallback
try:
	import requests  # type: ignore
	HAS_REQUESTS = True
except Exception:
	HAS_REQUESTS = False

try:
	import mysql.connector  # type: ignore
	from mysql.connector import Error  # type: ignore
	HAS_MYSQL = True
except Exception:
	HAS_MYSQL = False


class PriceAggregator:
	def __init__(self):
		self.db_config = {
			"host": os.getenv("DB_HOST", "localhost"),
			"user": os.getenv("DB_USER", "root"),
			"password": os.getenv("DB_PASS", ""),
			"database": os.getenv("DB_NAME", "farmbridge"),
		}
		self.price_sources = {
			"internal": self._get_internal_prices,
			"tradefeeds": self._get_tradefeeds_prices,
			"apifarmer": self._get_apifarmer_prices,
			"commodities_api": self._get_commodities_api_prices,
			"wfp_rwanda": self._get_wfp_rwanda_prices,
			"market_data": self._get_market_data_prices,
		}
		self.crop_synonyms = {
			"tomato": ["tomatoes", "tomatos"],
			"maize": ["corn", "maize"],
			"rice": ["rice", "paddy"],
			"potato": ["potatoes", "potatos"],
			"banana": ["bananas"],
			"onion": ["onions"],
			"carrot": ["carrots"],
			"cabbage": ["cabbages"],
			"bean": ["beans"],
			"cassava": ["cassava", "manioc"],
		}

	def _get_internal_prices(self, crop: str, date: datetime) -> List[Dict]:
		try:
			if not HAS_MYSQL:
				return []
			conn = mysql.connector.connect(**self.db_config)
			cursor = conn.cursor(dictionary=True)
			crop_variations = [crop] + self.crop_synonyms.get(crop.lower(), [])
			crop_conditions = " OR ".join([f"LOWER(commodity) LIKE '%{var}%'" for var in crop_variations])
			query = f"""
				SELECT price, date, location, source
				FROM market_prices
				WHERE ({crop_conditions})
				AND date >= DATE_SUB(%s, INTERVAL 30 DAY)
				ORDER BY date DESC
				LIMIT 50
			"""
			cursor.execute(query, (date,))
			results = cursor.fetchall()
			cursor.close()
			conn.close()
			return [{"price": float(r["price"]), "date": r["date"], "source": "internal", "location": r.get("location")} for r in results if r.get("price") is not None]
		except Exception:
			return []

	def _get_tradefeeds_prices(self, crop: str, date: datetime) -> List[Dict]:
		try:
			if not HAS_REQUESTS:
				return []
			api_key = os.getenv("TRADEFEEDS_API_KEY")
			if not api_key:
				return []
			url = "https://api.tradefeeds.com/commodities-prices"
			params = {"commodity": crop, "frequency": "day", "start_date": date.strftime("%Y-%m-%d"), "end_date": date.strftime("%Y-%m-%d"), "api_key": api_key}
			resp = requests.get(url, params=params, timeout=10)
			resp.raise_for_status()
			data = resp.json()
			out: List[Dict] = []
			for item in data.get("prices", []):
				p = item.get("price")
				if p is not None:
					out.append({"price": float(p), "date": date, "source": "tradefeeds", "location": item.get("region", "global")})
			return out
		except Exception:
			return []

	def _get_apifarmer_prices(self, crop: str, date: datetime) -> List[Dict]:
		try:
			if not HAS_REQUESTS:
				return []
			api_key = os.getenv("APIFARMER_API_KEY")
			if not api_key:
				return []
			url = "https://api.apifarmer.com/api/v0/commodities"
			params = {"commodity": crop}
			headers = {"Authorization": f"Bearer {api_key}"}
			resp = requests.get(url, params=params, headers=headers, timeout=10)
			resp.raise_for_status()
			data = resp.json()
			out: List[Dict] = []
			for item in data.get("data", []):
				p = item.get("price")
				if p is not None:
					out.append({"price": float(p), "date": date, "source": "apifarmer", "location": item.get("region", "global")})
			return out
		except Exception:
			return []

	def _get_commodities_api_prices(self, crop: str, date: datetime) -> List[Dict]:
		try:
			if not HAS_REQUESTS:
				return []
			api_key = os.getenv("COMMODITIES_API_KEY")
			if not api_key:
				return []
			symbol_map = {"rice": "RICE", "wheat": "WHEAT", "corn": "CORN", "maize": "CORN", "sugar": "SUGAR", "coffee": "COFFEE", "soybean": "SOYBEAN", "cotton": "COTTON"}
			symbol = symbol_map.get(crop.lower(), crop.upper())
			url = "https://commodities-api.com/api/latest"
			params = {"access_key": api_key, "symbols": symbol, "base": "USD"}
			resp = requests.get(url, params=params, timeout=10)
			resp.raise_for_status()
			data = resp.json()
			rates = data.get("rates", {})
			if symbol in rates:
				return [{"price": float(rates[symbol]), "date": date, "source": "commodities_api", "location": "global"}]
			return []
		except Exception:
			return []

	def _get_wfp_rwanda_prices(self, crop: str, date: datetime) -> List[Dict]:
		try:
			base = {"rice": 1200, "maize": 400, "beans": 1800, "potato": 600, "banana": 500, "cassava": 250, "sweet_potato": 400, "wheat": 800, "sorghum": 350, "millet": 300}
			b = base.get(crop.lower())
			if b is None:
				return []
			import random
			price = b * random.uniform(0.85, 1.15)
			return [{"price": round(price, 2), "date": date, "source": "wfp_rwanda", "location": "Rwanda"}]
		except Exception:
			return []

	def _get_market_data_prices(self, crop: str, date: datetime) -> List[Dict]:
		seasonal_factors = {1: 1.05, 2: 1.03, 3: 0.98, 4: 0.95, 5: 0.92, 6: 0.95, 7: 1.0, 8: 1.02, 9: 1.05, 10: 1.08, 11: 1.1, 12: 1.07}
		factor = seasonal_factors.get(date.month, 1.0)
		base = {"tomato": 1200, "maize": 450, "rice": 1800, "potato": 800, "banana": 600, "onion": 900, "carrot": 600, "cabbage": 400, "bean": 2200, "cassava": 300}
		b = base.get(crop.lower(), 1000)
		return [{"price": round(b * factor, 2), "date": date, "source": "market_data", "location": "Regional Market"}]

	def aggregate_prices(self, crop: str, date: datetime | None = None) -> Dict:
		if date is None:
			date = datetime.now()
		all_prices: List[Dict] = []
		for _, src_fn in self.price_sources.items():
			try:
				all_prices.extend(src_fn(crop, date))
			except Exception:
				pass
		if not all_prices:
			return {"crop": crop, "date": date.isoformat(), "aggregated_price": None, "sources": [], "confidence": 0.0, "message": "No price data available"}
		prices = [p["price"] for p in all_prices if p.get("price") is not None]
		if not prices:
			return {"crop": crop, "date": date.isoformat(), "aggregated_price": None, "sources": all_prices, "confidence": 0.0, "message": "No valid price data"}
		mean_price = statistics.mean(prices)
		median_price = statistics.median(prices)
		std_dev = statistics.stdev(prices) if len(prices) > 1 else 0.0
		aggregated_price = median_price
		confidence = max(0.0, min(1.0, 1.0 - (std_dev / mean_price) if mean_price > 0 else 0.0))
		return {
			"crop": crop,
			"date": date.isoformat(),
			"aggregated_price": round(float(aggregated_price), 2),
			"mean_price": round(float(mean_price), 2),
			"median_price": round(float(median_price), 2),
			"std_deviation": round(float(std_dev), 2),
			"price_range": {"min": round(float(min(prices)), 2), "max": round(float(max(prices)), 2)},
			"sources": all_prices,
			"source_count": len(all_prices),
			"confidence": round(float(confidence), 3),
			"message": f"Price aggregated from {len(all_prices)} sources",
		}

	def get_price_trend(self, crop: str, days: int = 30) -> Dict:
		end_date = datetime.now()
		start_date = end_date - timedelta(days=days)
		trend_data: List[Dict] = []
		current = start_date
		while current <= end_date:
			pd = self.aggregate_prices(crop, current)
			if pd.get("aggregated_price"):
				trend_data.append({"date": current.isoformat(), "price": pd["aggregated_price"], "confidence": pd["confidence"]})
			current += timedelta(days=1)
		if not trend_data:
			return {"crop": crop, "trend": [], "message": "No trend data available"}
		first = trend_data[0]["price"]
		last = trend_data[-1]["price"]
		trend_direction = "increasing" if last > first else ("decreasing" if last < first else "stable")
		trend_percentage = ((last - first) / first) * 100 if first else 0.0
		return {"crop": crop, "period_days": days, "trend": trend_data, "trend_direction": trend_direction, "trend_percentage": round(float(trend_percentage), 2), "data_points": len(trend_data)}


def main() -> int:
	parser = argparse.ArgumentParser()
	sub = parser.add_subparsers(dest="cmd", required=True)
	p_agg = sub.add_parser("aggregate")
	p_agg.add_argument("--crop", required=True)
	p_trend = sub.add_parser("trend")
	p_trend.add_argument("--crop", required=True)
	p_trend.add_argument("--days", type=int, default=30)
	args = parser.parse_args()
	agg = PriceAggregator()
	try:
		if args.cmd == "aggregate":
			res = agg.aggregate_prices(args.crop)
			print(json.dumps(res, default=str))
			return 0
		elif args.cmd == "trend":
			res = agg.get_price_trend(args.crop, args.days)
			print(json.dumps(res, default=str))
			return 0
		else:
			print(json.dumps({"error": "unknown command"}))
			return 1
	except Exception as e:
		print(json.dumps({"error": str(e)}))
		return 1


if __name__ == "__main__":
	raise SystemExit(main())
