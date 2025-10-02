#!/usr/bin/env python3
"""
AI Prediction Service for Concerto Platform
Lightweight ML service for predicting fundamental weaknesses
"""

import json
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report
import joblib
import os
from typing import Dict, List, Tuple, Any
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class FundamentalPredictionService:
    """
    AI service for predicting fundamental learning weaknesses
    """
    
    def __init__(self, model_path: str = "models/"):
        self.model_path = model_path
        self.models = {}
        self.scalers = {}
        self.fundamental_types = ['listening', 'grasping', 'retention', 'application']
        
        # Create models directory if it doesn't exist
        os.makedirs(model_path, exist_ok=True)
        
        # Load or initialize models
        self._load_models()
    
    def _load_models(self):
        """Load pre-trained models or initialize new ones"""
        for fundamental in self.fundamental_types:
            model_file = os.path.join(self.model_path, f"{fundamental}_model.pkl")
            scaler_file = os.path.join(self.model_path, f"{fundamental}_scaler.pkl")
            
            if os.path.exists(model_file) and os.path.exists(scaler_file):
                self.models[fundamental] = joblib.load(model_file)
                self.scalers[fundamental] = joblib.load(scaler_file)
                logger.info(f"Loaded model for {fundamental}")
            else:
                # Initialize with default model
                self.models[fundamental] = RandomForestClassifier(
                    n_estimators=100,
                    random_state=42,
                    max_depth=10
                )
                self.scalers[fundamental] = StandardScaler()
                logger.info(f"Initialized new model for {fundamental}")
    
    def _save_models(self):
        """Save trained models to disk"""
        for fundamental in self.fundamental_types:
            model_file = os.path.join(self.model_path, f"{fundamental}_model.pkl")
            scaler_file = os.path.join(self.model_path, f"{fundamental}_scaler.pkl")
            
            joblib.dump(self.models[fundamental], model_file)
            joblib.dump(self.scalers[fundamental], scaler_file)
            logger.info(f"Saved model for {fundamental}")
    
    def extract_features(self, session_data: Dict[str, Any]) -> np.ndarray:
        """
        Extract features from test session data
        
        Args:
            session_data: Dictionary containing session information
            
        Returns:
            Feature vector as numpy array
        """
        features = []
        
        # Basic session features
        features.append(session_data.get('total_questions', 0))
        features.append(session_data.get('session_duration', 0))
        features.append(session_data.get('total_response_time', 0))
        
        # Fundamental-specific features
        for fundamental in self.fundamental_types:
            fundamental_data = session_data.get(fundamental, {})
            
            # Accuracy features
            total_questions = fundamental_data.get('total_questions', 0)
            correct_answers = fundamental_data.get('correct_answers', 0)
            accuracy = (correct_answers / total_questions * 100) if total_questions > 0 else 0
            features.append(accuracy)
            
            # Response time features
            response_times = fundamental_data.get('response_times', [])
            avg_response_time = np.mean(response_times) if response_times else 0
            std_response_time = np.std(response_times) if response_times else 0
            features.extend([avg_response_time, std_response_time])
            
            # Question count
            features.append(total_questions)
        
        # Derived features
        features.append(session_data.get('session_duration', 0) / max(session_data.get('total_questions', 1), 1))  # Time per question
        features.append(len([f for f in self.fundamental_types if session_data.get(f, {}).get('total_questions', 0) > 0]))  # Fundamentals attempted
        
        return np.array(features).reshape(1, -1)
    
    def predict_weakness(self, session_data: Dict[str, Any], fundamental: str) -> Dict[str, Any]:
        """
        Predict weakness for a specific fundamental
        
        Args:
            session_data: Test session data
            fundamental: Fundamental type to predict
            
        Returns:
            Prediction results with confidence and risk factors
        """
        if fundamental not in self.fundamental_types:
            raise ValueError(f"Invalid fundamental type: {fundamental}")
        
        # Extract features
        features = self.extract_features(session_data)
        
        # Scale features
        features_scaled = self.scalers[fundamental].transform(features)
        
        # Make prediction
        model = self.models[fundamental]
        
        # Get prediction probability
        if hasattr(model, 'predict_proba'):
            proba = model.predict_proba(features_scaled)[0]
            # Assuming binary classification: [not_weak, weak]
            weakness_probability = proba[1] if len(proba) > 1 else proba[0]
        else:
            # Fallback for models without probability prediction
            prediction = model.predict(features_scaled)[0]
            weakness_probability = float(prediction)
        
        # Calculate confidence based on feature quality
        confidence = self._calculate_confidence(session_data, fundamental)
        
        # Identify risk factors
        risk_factors = self._identify_risk_factors(session_data, fundamental)
        
        return {
            'fundamental': fundamental,
            'weakness_score': float(weakness_probability * 100),
            'confidence': confidence,
            'risk_factors': risk_factors,
            'prediction': 'weak' if weakness_probability > 0.5 else 'strong'
        }
    
    def predict_all_fundamentals(self, session_data: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        Predict weaknesses for all fundamental types
        
        Args:
            session_data: Test session data
            
        Returns:
            List of predictions for each fundamental
        """
        predictions = []
        
        for fundamental in self.fundamental_types:
            try:
                prediction = self.predict_weakness(session_data, fundamental)
                predictions.append(prediction)
            except Exception as e:
                logger.error(f"Error predicting {fundamental}: {e}")
                # Add fallback prediction
                predictions.append({
                    'fundamental': fundamental,
                    'weakness_score': 50.0,
                    'confidence': 30.0,
                    'risk_factors': ['prediction_error'],
                    'prediction': 'unknown'
                })
        
        return predictions
    
    def _calculate_confidence(self, session_data: Dict[str, Any], fundamental: str) -> float:
        """Calculate confidence in prediction based on data quality"""
        confidence = 50.0  # Base confidence
        
        fundamental_data = session_data.get(fundamental, {})
        
        # More questions = higher confidence
        question_count = fundamental_data.get('total_questions', 0)
        if question_count >= 5:
            confidence += 30
        elif question_count >= 3:
            confidence += 20
        elif question_count >= 1:
            confidence += 10
        
        # Longer session = higher confidence
        session_duration = session_data.get('session_duration', 0)
        if session_duration > 600:  # 10 minutes
            confidence += 20
        
        # Multiple fundamentals attempted = higher confidence
        fundamentals_attempted = len([f for f in self.fundamental_types 
                                    if session_data.get(f, {}).get('total_questions', 0) > 0])
        if fundamentals_attempted >= 3:
            confidence += 10
        
        return min(100.0, confidence)
    
    def _identify_risk_factors(self, session_data: Dict[str, Any], fundamental: str) -> List[str]:
        """Identify risk factors for weakness"""
        risk_factors = []
        fundamental_data = session_data.get(fundamental, {})
        
        # Check accuracy
        total_questions = fundamental_data.get('total_questions', 0)
        correct_answers = fundamental_data.get('correct_answers', 0)
        if total_questions > 0:
            accuracy = (correct_answers / total_questions) * 100
            if accuracy < 50:
                risk_factors.append('low_accuracy')
        
        # Check response time
        response_times = fundamental_data.get('response_times', [])
        if response_times:
            avg_response_time = np.mean(response_times)
            if avg_response_time > 20:
                risk_factors.append('slow_response')
        
        # Check data sufficiency
        if total_questions < 2:
            risk_factors.append('insufficient_data')
        
        # Check session duration
        session_duration = session_data.get('session_duration', 0)
        if session_duration > 1800:  # 30 minutes
            risk_factors.append('extended_session_time')
        
        return risk_factors
    
    def train_model(self, training_data: List[Dict[str, Any]], fundamental: str):
        """
        Train model with new data
        
        Args:
            training_data: List of training examples
            fundamental: Fundamental type to train
        """
        if not training_data:
            logger.warning(f"No training data provided for {fundamental}")
            return
        
        # Prepare features and labels
        X = []
        y = []
        
        for example in training_data:
            features = self.extract_features(example['session_data'])
            X.append(features.flatten())
            y.append(1 if example['is_weak'] else 0)
        
        X = np.array(X)
        y = np.array(y)
        
        if len(np.unique(y)) < 2:
            logger.warning(f"Insufficient class diversity for {fundamental}")
            return
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42, stratify=y
        )
        
        # Scale features
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        X_test_scaled = scaler.transform(X_test)
        
        # Train model
        model = RandomForestClassifier(
            n_estimators=100,
            random_state=42,
            max_depth=10
        )
        model.fit(X_train_scaled, y_train)
        
        # Evaluate
        y_pred = model.predict(X_test_scaled)
        accuracy = accuracy_score(y_test, y_pred)
        
        logger.info(f"Model accuracy for {fundamental}: {accuracy:.3f}")
        
        # Save model
        self.models[fundamental] = model
        self.scalers[fundamental] = scaler
        self._save_models()
    
    def generate_recommendations(self, predictions: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Generate practice recommendations based on predictions
        
        Args:
            predictions: List of fundamental predictions
            
        Returns:
            List of recommendations
        """
        recommendations = []
        
        # Sort by weakness score
        sorted_predictions = sorted(predictions, key=lambda x: x['weakness_score'], reverse=True)
        
        for prediction in sorted_predictions:
            if prediction['weakness_score'] > 60 and prediction['confidence'] > 50:
                priority = 'high' if prediction['weakness_score'] > 80 else 'medium'
                
                recommendation = {
                    'fundamental': prediction['fundamental'],
                    'priority': priority,
                    'type': 'targeted',
                    'reason': self._get_recommendation_reason(prediction),
                    'suggested_questions': self._get_suggested_questions(prediction),
                    'confidence': prediction['confidence']
                }
                recommendations.append(recommendation)
        
        return recommendations
    
    def _get_recommendation_reason(self, prediction: Dict[str, Any]) -> str:
        """Get recommendation reason based on prediction"""
        fundamental = prediction['fundamental']
        risk_factors = prediction['risk_factors']
        
        reasons = {
            'listening': 'Focus on audio comprehension and following instructions',
            'grasping': 'Practice understanding concepts and making connections',
            'retention': 'Work on memory and recall techniques',
            'application': 'Practice applying knowledge to new situations'
        }
        
        base_reason = reasons.get(fundamental, 'Practice this fundamental skill')
        
        if 'slow_response' in risk_factors:
            base_reason += ' with emphasis on speed'
        
        if 'low_accuracy' in risk_factors:
            base_reason += ' - start with easier questions'
        
        return base_reason
    
    def _get_suggested_questions(self, prediction: Dict[str, Any]) -> int:
        """Get suggested number of practice questions"""
        weakness_score = prediction['weakness_score']
        
        if weakness_score > 80:
            return 15  # More practice for very weak areas
        elif weakness_score > 60:
            return 10  # Moderate practice
        else:
            return 5   # Light practice

def main():
    """Main function for testing the service"""
    service = FundamentalPredictionService()
    
    # Example session data
    session_data = {
        'total_questions': 20,
        'session_duration': 1200,  # 20 minutes
        'total_response_time': 800,
        'listening': {
            'total_questions': 5,
            'correct_answers': 4,
            'response_times': [10, 12, 8, 15, 11]
        },
        'grasping': {
            'total_questions': 5,
            'correct_answers': 3,
            'response_times': [15, 18, 12, 20, 16]
        },
        'retention': {
            'total_questions': 5,
            'correct_answers': 2,
            'response_times': [25, 30, 22, 28, 26]
        },
        'application': {
            'total_questions': 5,
            'correct_answers': 1,
            'response_times': [30, 35, 28, 32, 29]
        }
    }
    
    # Make predictions
    predictions = service.predict_all_fundamentals(session_data)
    
    print("Fundamental Predictions:")
    for prediction in predictions:
        print(f"- {prediction['fundamental']}: {prediction['weakness_score']:.1f}% weakness "
              f"(confidence: {prediction['confidence']:.1f}%)")
        print(f"  Risk factors: {', '.join(prediction['risk_factors'])}")
    
    # Generate recommendations
    recommendations = service.generate_recommendations(predictions)
    
    print("\nRecommendations:")
    for rec in recommendations:
        print(f"- {rec['fundamental']} ({rec['priority']} priority): {rec['reason']}")
        print(f"  Suggested: {rec['suggested_questions']} questions")

if __name__ == "__main__":
    main()
