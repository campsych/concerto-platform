#!/bin/bash

# Startup script for AI Prediction Service
# This script sets up and starts the AI service for the Concerto platform

echo "Starting AI Prediction Service for Concerto Platform..."

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "Error: Python 3 is not installed. Please install Python 3.7 or higher."
    exit 1
fi

# Check if pip is installed
if ! command -v pip3 &> /dev/null; then
    echo "Error: pip3 is not installed. Please install pip3."
    exit 1
fi

# Create virtual environment if it doesn't exist
if [ ! -d "venv" ]; then
    echo "Creating virtual environment..."
    python3 -m venv venv
fi

# Activate virtual environment
echo "Activating virtual environment..."
source venv/bin/activate

# Install dependencies
echo "Installing dependencies..."
pip install -r requirements.txt

# Create models directory
mkdir -p models

# Set environment variables
export AI_SERVICE_HOST=${AI_SERVICE_HOST:-"0.0.0.0"}
export AI_SERVICE_PORT=${AI_SERVICE_PORT:-5000}
export AI_SERVICE_DEBUG=${AI_SERVICE_DEBUG:-"False"}

echo "Configuration:"
echo "  Host: $AI_SERVICE_HOST"
echo "  Port: $AI_SERVICE_PORT"
echo "  Debug: $AI_SERVICE_DEBUG"

# Start the service
echo "Starting AI Prediction Service..."
python app.py
