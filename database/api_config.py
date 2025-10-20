#!/usr/bin/env python3
"""
API Configuration for External Agricultural Price Sources
Add your API keys here to enable real-time price data
"""

import os

# API Configuration
API_CONFIG = {
    # Tradefeeds API - Real-time agricultural commodities prices
    "tradefeeds": {
        "api_key": os.getenv("TRADEFEEDS_API_KEY", ""),
        "base_url": "https://api.tradefeeds.com",
        "enabled": bool(os.getenv("TRADEFEEDS_API_KEY", "")),
        "rate_limit": 1000,  # requests per hour
        "cost_per_request": 0.001  # USD
    },
    
    # APIFarmer - Agriculture commodity prices
    "apifarmer": {
        "api_key": os.getenv("APIFARMER_API_KEY", ""),
        "base_url": "https://api.apifarmer.com/api/v0",
        "enabled": bool(os.getenv("APIFARMER_API_KEY", "")),
        "rate_limit": 500,  # requests per hour
        "cost_per_request": 0.002  # USD
    },
    
    # Commodities-API - Global commodities data
    "commodities_api": {
        "api_key": os.getenv("COMMODITIES_API_KEY", ""),
        "base_url": "https://commodities-api.com/api",
        "enabled": bool(os.getenv("COMMODITIES_API_KEY", "")),
        "rate_limit": 1000,  # requests per month (free tier)
        "cost_per_request": 0.0005  # USD
    },
    
    # WFP Rwanda - Local market data
    "wfp_rwanda": {
        "api_key": "",  # WFP data is typically open/public
        "base_url": "https://dataviz.vam.wfp.org",
        "enabled": True,  # Free public data
        "rate_limit": 100,  # requests per hour
        "cost_per_request": 0  # Free
    }
}

# Crop mapping for different APIs
CROP_MAPPING = {
    "tradefeeds": {
        "rice": "rice",
        "maize": "corn",
        "wheat": "wheat",
        "potato": "potato",
        "tomato": "tomato",
        "banana": "banana",
        "bean": "beans",
        "cassava": "cassava"
    },
    "commodities_api": {
        "rice": "RICE",
        "maize": "CORN",
        "wheat": "WHEAT",
        "potato": "POTATO",
        "sugar": "SUGAR",
        "coffee": "COFFEE",
        "soybean": "SOYBEAN",
        "cotton": "COTTON"
    },
    "apifarmer": {
        "rice": "rice",
        "maize": "maize",
        "wheat": "wheat",
        "potato": "potato",
        "tomato": "tomato",
        "banana": "banana",
        "bean": "beans",
        "cassava": "cassava"
    }
}

def get_enabled_apis():
    """Get list of enabled APIs"""
    return [name for name, config in API_CONFIG.items() if config["enabled"]]

def get_api_config(api_name):
    """Get configuration for specific API"""
    return API_CONFIG.get(api_name, {})

def get_crop_symbol(api_name, crop_name):
    """Get the correct symbol for a crop in a specific API"""
    mapping = CROP_MAPPING.get(api_name, {})
    return mapping.get(crop_name.lower(), crop_name.upper())

# Example usage
if __name__ == "__main__":
    print("Available APIs:", get_enabled_apis())
    print("Tradefeeds config:", get_api_config("tradefeeds"))
    print("Rice symbol for commodities_api:", get_crop_symbol("commodities_api", "rice"))
