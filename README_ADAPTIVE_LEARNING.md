# 🧠 Adaptive AI-Powered Learning Assessment Extension

This project extends the [Concerto Platform](https://github.com/campsych/concerto-platform) with advanced adaptive learning capabilities, including fundamental analysis, personalized practice recommendations, and AI-powered weakness prediction.

## 🚀 Quick Start

### 1. Clone and Setup

```bash
# Clone the repository
git clone https://github.com/campsych/concerto-platform.git
cd concerto-platform

# Run the database migration
mysql -u username -p database_name < migrations/add_fundamental_analysis.sql

# Clear Symfony cache
php bin/console cache:clear
```

### 2. Start the AI Service

```bash
# Navigate to AI service directory
cd ai_service

# Start the AI service
./start_ai_service.sh
```

### 3. View the Demo

Open your browser and navigate to:
```
http://your-concerto-domain/demo.html
```

## 🎯 Features

### 1. Fundamentals Mapping
- **4 Learning Categories**: Listening, Grasping, Retention, Application
- **Question Tagging**: Each test question is categorized by fundamental type
- **Performance Tracking**: Detailed metrics for each fundamental area

### 2. Personalized Practice Mode
- **Weakness-Based Recommendations**: AI identifies areas needing improvement
- **Targeted Practice Sessions**: Customized questions based on fundamental weaknesses
- **Progress Tracking**: Real-time monitoring of practice session progress

### 3. Diagnostic Dashboards
- **Student View**: Personal performance breakdown with practice suggestions
- **Teacher View**: Class-level heatmap showing fundamental performance across students
- **Parent View**: Simplified reports with improvement tips and strengths/weaknesses

### 4. AI-Powered Predictions
- **ML-Based Analysis**: Uses machine learning to predict fundamental weaknesses
- **Risk Factor Identification**: Identifies specific patterns that indicate learning difficulties
- **Confidence Scoring**: Provides confidence levels for predictions

### 5. Complete User Journey
- **Seamless Flow**: From test completion → analysis → recommendations → practice
- **Interactive Demo**: Full demonstration of the learning assessment workflow

## 🏗️ Architecture

### Backend Extensions (PHP/Symfony)

#### New Entities
- `FundamentalPerformance`: Tracks performance metrics per fundamental type
- `PracticeSession`: Manages targeted practice sessions
- `TestNode` (extended): Added `fundamental_type` field

#### New Services
- `FundamentalAnalysisService`: Analyzes test sessions and generates recommendations
- `AIPredictionService`: Provides ML-based weakness predictions

#### New API Endpoints
- `/api/fundamental/*`: Fundamental analysis and recommendations
- `/api/practice/*`: Practice session management
- `/api/dashboard/*`: Multi-user dashboard views

### AI Service (Python/Flask)

#### Core Components
- `FundamentalPredictionService`: ML model for weakness prediction
- `Flask API`: REST endpoints for AI predictions
- `Random Forest Models`: Trained models for each fundamental type

#### Features
- Real-time prediction of fundamental weaknesses
- Confidence scoring and risk factor identification
- Practice recommendation generation

## 📊 API Usage Examples

### Analyze Test Session

```bash
curl -X POST http://localhost/api/fundamental/session/abc123/analyze
```

**Response:**
```json
{
  "session_id": 123,
  "performances": [
    {
      "fundamental": "listening",
      "score": 85.0,
      "total_questions": 5,
      "correct_answers": 4,
      "average_response_time": 12.5
    },
    {
      "fundamental": "application",
      "score": 38.0,
      "total_questions": 5,
      "correct_answers": 2,
      "average_response_time": 25.3,
      "weakness_pattern": "low_accuracy,slow_response"
    }
  ],
  "recommendations": [
    {
      "fundamental": "application",
      "priority": "high",
      "type": "targeted",
      "reason": "Focus on applying knowledge to new situations",
      "suggestedQuestions": 15
    }
  ]
}
```

### Create Practice Session

```bash
curl -X POST http://localhost/api/fundamental/session/abc123/practice/create \
  -H "Content-Type: application/json" \
  -d '{"fundamental": "application", "type": "targeted"}'
```

### Get Teacher Dashboard

```bash
curl -X GET http://localhost/api/dashboard/teacher/class/class123
```

## 🎮 Demo Walkthrough

The interactive demo (`/demo.html`) showcases the complete user journey:

1. **Test Completion**: Student finishes adaptive test with fundamental-tagged questions
2. **AI Analysis**: System analyzes performance across all fundamental types
3. **Recommendations**: AI generates personalized practice recommendations
4. **Practice Session**: Student engages in targeted practice for weak areas
5. **Dashboard Views**: Different stakeholders see relevant insights

### Demo Features
- **Interactive UI**: Click through different steps of the learning process
- **Real-time Updates**: See how recommendations change based on performance
- **Multi-user Views**: Experience student, teacher, and parent perspectives
- **AI Predictions**: View ML-based weakness predictions and confidence scores

## 🔧 Configuration

### Fundamental Types
The system supports four fundamental learning types:

```php
const FUNDAMENTAL_LISTENING = 'listening';    // Audio comprehension
const FUNDAMENTAL_GRASPING = 'grasping';      // Concept understanding  
const FUNDAMENTAL_RETENTION = 'retention';    // Memory and recall
const FUNDAMENTAL_APPLICATION = 'application'; // Knowledge application
```

### AI Service Configuration

Environment variables for the AI service:

```bash
export AI_SERVICE_HOST=0.0.0.0
export AI_SERVICE_PORT=5000
export AI_SERVICE_DEBUG=False
```

### Database Configuration

The migration script adds:
- `fundamental_type` column to `TestNode` table
- `FundamentalPerformance` table for tracking metrics
- `PracticeSession` table for practice management
- Indexes for optimal performance

## 🧪 Testing

### Test the AI Service

```bash
# Start the AI service
cd ai_service
./start_ai_service.sh

# Test the health endpoint
curl http://localhost:5000/health

# Test prediction endpoint
curl -X POST http://localhost:5000/predict \
  -H "Content-Type: application/json" \
  -d '{
    "session_data": {
      "total_questions": 20,
      "session_duration": 1200,
      "listening": {
        "total_questions": 5,
        "correct_answers": 4,
        "response_times": [10, 12, 8, 15, 11]
      }
    }
  }'
```

### Test the Concerto Extensions

```bash
# Test fundamental analysis
curl -X POST http://your-concerto-domain/api/fundamental/session/{hash}/analyze

# Test practice session creation
curl -X POST http://your-concerto-domain/api/fundamental/session/{hash}/practice/create \
  -H "Content-Type: application/json" \
  -d '{"fundamental": "listening", "type": "targeted"}'
```

## 📈 Performance Considerations

### Database Optimization
- Added indexes on frequently queried columns
- Optimized queries for large datasets
- Efficient aggregation for dashboard views

### AI Service Optimization
- Model caching and persistence
- Batch processing for multiple predictions
- Efficient feature extraction

### Caching Strategy
- Cache fundamental performance data
- Cache AI predictions for similar sessions
- Cache dashboard aggregations

## 🔮 Future Enhancements

### Planned Features
1. **Advanced ML Models**: Deep learning and ensemble methods
2. **Real-time Analytics**: Live performance tracking
3. **Gamification**: Achievement badges and progress milestones
4. **LMS Integration**: Connect with Moodle, Canvas, etc.
5. **Mobile App**: Native mobile experience

### AI Improvements
1. **Deep Learning**: Neural networks for pattern recognition
2. **Ensemble Methods**: Multiple models for better accuracy
3. **Online Learning**: Models that improve with new data
4. **Feature Engineering**: More sophisticated feature extraction

## 🤝 Contributing

### Development Setup

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Make your changes**
4. **Add tests** for new functionality
5. **Submit a pull request**

### Code Style

- Follow PSR-12 for PHP code
- Follow PEP 8 for Python code
- Add comprehensive documentation
- Include unit tests for new features

## 📝 License

This project extends the Concerto Platform and follows the same Apache 2.0 license.

## 🆘 Support

### Getting Help

1. **Check the demo**: `/demo.html` for interactive examples
2. **Review the API docs**: See examples above
3. **Check the logs**: Both PHP and Python services log detailed information
4. **Test with sample data**: Use the provided test endpoints

### Common Issues

1. **Database Connection**: Ensure migration script ran successfully
2. **AI Service Not Starting**: Check Python dependencies and virtual environment
3. **API Endpoints Not Found**: Clear Symfony cache and check routing
4. **Performance Issues**: Check database indexes and caching

### Debug Mode

Enable debug mode for detailed error messages:

```bash
# PHP/Symfony
export APP_ENV=dev

# Python AI Service
export AI_SERVICE_DEBUG=True
```

## 🎉 Acknowledgments

- Built on the excellent [Concerto Platform](https://github.com/campsych/concerto-platform)
- Uses scikit-learn for machine learning capabilities
- Inspired by modern adaptive learning research
- Designed for educational technology best practices

---

**Ready to revolutionize learning assessment?** 🚀

Start with the demo at `/demo.html` and explore the full capabilities of this adaptive learning extension!
