#!/usr/bin/env python3
"""
Flask API wrapper for the AI Prediction Service
Provides REST API endpoints for the Concerto platform
"""

from flask import Flask, request, jsonify
from prediction_service import FundamentalPredictionService
import logging
import os

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Initialize the prediction service
prediction_service = FundamentalPredictionService()

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'AI Prediction Service',
        'version': '1.0.0'
    })

@app.route('/predict', methods=['POST'])
def predict_weaknesses():
    """
    Predict fundamental weaknesses for a test session
    
    Expected JSON payload:
    {
        "session_data": {
            "total_questions": 20,
            "session_duration": 1200,
            "total_response_time": 800,
            "listening": {
                "total_questions": 5,
                "correct_answers": 4,
                "response_times": [10, 12, 8, 15, 11]
            },
            ...
        }
    }
    """
    try:
        data = request.get_json()
        
        if not data or 'session_data' not in data:
            return jsonify({'error': 'session_data is required'}), 400
        
        session_data = data['session_data']
        
        # Make predictions
        predictions = prediction_service.predict_all_fundamentals(session_data)
        
        # Generate recommendations
        recommendations = prediction_service.generate_recommendations(predictions)
        
        return jsonify({
            'predictions': predictions,
            'recommendations': recommendations,
            'status': 'success'
        })
        
    except Exception as e:
        logger.error(f"Error in predict_weaknesses: {e}")
        return jsonify({'error': str(e)}), 500

@app.route('/predict/<fundamental>', methods=['POST'])
def predict_single_fundamental(fundamental):
    """
    Predict weakness for a single fundamental type
    
    Expected JSON payload:
    {
        "session_data": { ... }
    }
    """
    try:
        data = request.get_json()
        
        if not data or 'session_data' not in data:
            return jsonify({'error': 'session_data is required'}), 400
        
        session_data = data['session_data']
        
        # Make prediction for specific fundamental
        prediction = prediction_service.predict_weakness(session_data, fundamental)
        
        return jsonify({
            'prediction': prediction,
            'status': 'success'
        })
        
    except ValueError as e:
        return jsonify({'error': str(e)}), 400
    except Exception as e:
        logger.error(f"Error in predict_single_fundamental: {e}")
        return jsonify({'error': str(e)}), 500

@app.route('/train', methods=['POST'])
def train_model():
    """
    Train model with new data
    
    Expected JSON payload:
    {
        "fundamental": "listening",
        "training_data": [
            {
                "session_data": { ... },
                "is_weak": true
            },
            ...
        ]
    }
    """
    try:
        data = request.get_json()
        
        if not data or 'fundamental' not in data or 'training_data' not in data:
            return jsonify({'error': 'fundamental and training_data are required'}), 400
        
        fundamental = data['fundamental']
        training_data = data['training_data']
        
        # Train the model
        prediction_service.train_model(training_data, fundamental)
        
        return jsonify({
            'message': f'Model trained successfully for {fundamental}',
            'training_samples': len(training_data),
            'status': 'success'
        })
        
    except Exception as e:
        logger.error(f"Error in train_model: {e}")
        return jsonify({'error': str(e)}), 500

@app.route('/recommendations', methods=['POST'])
def get_recommendations():
    """
    Generate practice recommendations based on predictions
    
    Expected JSON payload:
    {
        "predictions": [
            {
                "fundamental": "listening",
                "weakness_score": 75.0,
                "confidence": 85.0,
                "risk_factors": ["low_accuracy"]
            },
            ...
        ]
    }
    """
    try:
        data = request.get_json()
        
        if not data or 'predictions' not in data:
            return jsonify({'error': 'predictions are required'}), 400
        
        predictions = data['predictions']
        
        # Generate recommendations
        recommendations = prediction_service.generate_recommendations(predictions)
        
        return jsonify({
            'recommendations': recommendations,
            'status': 'success'
        })
        
    except Exception as e:
        logger.error(f"Error in get_recommendations: {e}")
        return jsonify({'error': str(e)}), 500

@app.route('/models/status', methods=['GET'])
def get_models_status():
    """Get status of all models"""
    try:
        status = {}
        
        for fundamental in prediction_service.fundamental_types:
            model = prediction_service.models.get(fundamental)
            scaler = prediction_service.scalers.get(fundamental)
            
            status[fundamental] = {
                'model_loaded': model is not None,
                'scaler_loaded': scaler is not None,
                'model_type': type(model).__name__ if model else None
            }
        
        return jsonify({
            'models': status,
            'status': 'success'
        })
        
    except Exception as e:
        logger.error(f"Error in get_models_status: {e}")
        return jsonify({'error': str(e)}), 500

@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(405)
def method_not_allowed(error):
    return jsonify({'error': 'Method not allowed'}), 405

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    # Get configuration from environment variables
    host = os.getenv('AI_SERVICE_HOST', '0.0.0.0')
    port = int(os.getenv('AI_SERVICE_PORT', 5000))
    debug = os.getenv('AI_SERVICE_DEBUG', 'False').lower() == 'true'
    
    logger.info(f"Starting AI Prediction Service on {host}:{port}")
    logger.info(f"Debug mode: {debug}")
    
    app.run(host=host, port=port, debug=debug)
