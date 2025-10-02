# Adaptive AI-Powered Learning Assessment Extension

This document describes the extensions made to the Concerto platform to add adaptive AI-powered learning assessment capabilities with fundamental analysis and personalized practice recommendations.

## Overview

The extension adds the following key features to Concerto:

1. **Fundamentals Mapping** - Categorizes questions into 4 fundamental learning types
2. **Personalized Practice Mode** - Generates targeted practice sessions based on weaknesses
3. **Diagnostic Dashboards** - Provides insights for students, teachers, and parents
4. **User Journey Demo** - Complete flow from test to practice recommendations
5. **AI Layer** - ML-based prediction of fundamental weaknesses

## Architecture

### New Entities

#### TestNode Extension
- Added `fundamental_type` field to categorize questions into:
  - `listening` - Audio comprehension and following instructions
  - `grasping` - Understanding concepts and making connections
  - `retention` - Memory and recall techniques
  - `application` - Applying knowledge to new situations

#### FundamentalPerformance
- Tracks performance metrics for each fundamental type per test session
- Fields: score, total_questions, correct_answers, average_response_time, weakness_pattern

#### PracticeSession
- Manages targeted practice sessions based on fundamental weaknesses
- Fields: type, target_fundamental, difficulty, status, recommendations

### New Services

#### FundamentalAnalysisService
- Analyzes test sessions and creates performance records
- Generates practice recommendations based on weaknesses
- Creates targeted practice sessions

#### AIPredictionService
- Uses ML techniques to predict fundamental weaknesses
- Analyzes response patterns and timing data
- Provides confidence scores and risk factors

### New API Endpoints

#### Fundamental Analysis
- `POST /api/fundamental/session/{hash}/analyze` - Analyze test session
- `GET /api/fundamental/session/{hash}/recommendations` - Get recommendations
- `POST /api/fundamental/session/{hash}/practice/create` - Create practice session

#### Practice Sessions
- `GET /api/practice/session/{hash}/list` - List practice sessions
- `POST /api/practice/session/{id}/start` - Start practice session
- `POST /api/practice/session/{id}/submit` - Submit practice answers
- `GET /api/practice/session/{id}/progress` - Get practice progress

#### Dashboards
- `GET /api/dashboard/teacher/class/{id}` - Teacher dashboard
- `GET /api/dashboard/parent/student/{hash}` - Parent dashboard
- `GET /api/dashboard/analytics/fundamental-trends` - Analytics trends

## Installation & Setup

### 1. Database Migration

Run the migration script to add new tables and columns:

```bash
mysql -u username -p database_name < migrations/add_fundamental_analysis.sql
```

### 2. Clear Cache

Clear Symfony cache to register new entities:

```bash
php bin/console cache:clear
```

### 3. Update Doctrine Schema

Generate and run Doctrine migrations:

```bash
php bin/console doctrine:schema:update --dump-sql
php bin/console doctrine:schema:update --force
```

### 4. Test the Demo

Open the demo page in your browser:

```
http://your-concerto-domain/demo.html
```

## Usage Guide

### 1. Setting Up Fundamental Types

When creating test nodes, assign fundamental types:

```php
$testNode = new TestNode();
$testNode->setFundamentalType('listening'); // or 'grasping', 'retention', 'application'
```

### 2. Analyzing Test Sessions

After a test session completes, analyze it:

```bash
curl -X POST http://your-domain/api/fundamental/session/{session_hash}/analyze
```

### 3. Getting Recommendations

Retrieve practice recommendations:

```bash
curl -X GET http://your-domain/api/fundamental/session/{session_hash}/recommendations
```

### 4. Creating Practice Sessions

Create a targeted practice session:

```bash
curl -X POST http://your-domain/api/fundamental/session/{session_hash}/practice/create \
  -H "Content-Type: application/json" \
  -d '{"fundamental": "application", "type": "targeted"}'
```

### 5. Viewing Dashboards

Access different dashboard views:

```bash
# Student dashboard
curl -X GET http://your-domain/api/dashboard/student/{session_hash}

# Teacher dashboard
curl -X GET http://your-domain/api/dashboard/teacher/class/{class_id}

# Parent dashboard
curl -X GET http://your-domain/api/dashboard/parent/student/{session_hash}
```

## User Journey Flow

### 1. Student Takes Test
- Student completes adaptive test with questions tagged by fundamental type
- System tracks responses, timing, and accuracy

### 2. AI Analysis
- System analyzes performance across all fundamental types
- AI predicts weaknesses and generates recommendations

### 3. Practice Recommendations
- Student sees breakdown of strengths and weaknesses
- System suggests targeted practice sessions

### 4. Practice Session
- Student starts practice session focused on weakest fundamentals
- System provides immediate feedback and progress tracking

### 5. Dashboard Views
- **Student**: Personal performance breakdown and practice suggestions
- **Teacher**: Class-level heatmap and intervention recommendations
- **Parent**: Simplified report with improvement tips

## API Examples

### Analyze Test Session

```json
POST /api/fundamental/session/abc123/analyze

Response:
{
  "session_id": 123,
  "performances": [
    {
      "fundamental": "listening",
      "score": 85.0,
      "total_questions": 5,
      "correct_answers": 4,
      "average_response_time": 12.5,
      "weakness_pattern": null
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
      "reason": "Focus on applying knowledge to new situations - start with easier questions",
      "suggestedQuestions": 15
    }
  ]
}
```

### Create Practice Session

```json
POST /api/fundamental/session/abc123/practice/create
{
  "fundamental": "application",
  "type": "targeted"
}

Response:
{
  "practice_session_id": 456,
  "type": "targeted",
  "target_fundamental": "application",
  "total_questions": 15,
  "recommendations": "Focus on applying knowledge to new situations - start with easier questions"
}
```

### Teacher Dashboard

```json
GET /api/dashboard/teacher/class/class123

Response:
{
  "class_id": "class123",
  "total_students": 25,
  "fundamental_heatmap": {
    "listening": {
      "excellent": 15,
      "good": 8,
      "needs_improvement": 2,
      "struggling": 0
    },
    "application": {
      "excellent": 5,
      "good": 8,
      "needs_improvement": 7,
      "struggling": 5
    }
  },
  "class_performance": {
    "average_score": 72.5,
    "completion_rate": 96.0,
    "total_sessions": 25,
    "completed_sessions": 24
  },
  "recommendations": [
    {
      "fundamental": "application",
      "priority": "high",
      "message": "Over 50% of students struggle with application. Consider additional instruction and practice materials."
    }
  ]
}
```

## Configuration

### Fundamental Types

The system supports four fundamental types defined in `FundamentalPerformance`:

```php
const FUNDAMENTAL_LISTENING = 'listening';
const FUNDAMENTAL_GRASPING = 'grasping';
const FUNDAMENTAL_RETENTION = 'retention';
const FUNDAMENTAL_APPLICATION = 'application';
```

### Practice Session Types

```php
const TYPE_TARGETED = 'targeted';    // Focus on specific fundamental
const TYPE_MIXED = 'mixed';          // Mix of fundamentals
const TYPE_DIFFICULTY = 'difficulty'; // Focus on difficulty level
```

### AI Prediction Thresholds

Adjust these in `AIPredictionService`:

```php
// Weakness score thresholds
$highRiskThreshold = 70;    // High risk of weakness
$mediumRiskThreshold = 50;  // Medium risk
$lowRiskThreshold = 30;     // Low risk

// Confidence thresholds
$highConfidence = 80;       // High confidence in prediction
$mediumConfidence = 60;     // Medium confidence
```

## Customization

### Adding New Fundamental Types

1. Update the constants in `FundamentalPerformance.php`
2. Add the new type to `getFundamentalTypes()` method
3. Update the analysis logic in `FundamentalAnalysisService`
4. Modify the AI prediction model in `AIPredictionService`

### Customizing Practice Recommendations

Modify the `generatePracticeRecommendations()` method in `FundamentalAnalysisService` to adjust:

- Recommendation priorities
- Question counts
- Practice types
- Reasoning messages

### Extending AI Predictions

Enhance the `AIPredictionService` by:

- Adding more sophisticated ML models
- Incorporating additional features
- Improving confidence calculations
- Adding ensemble methods

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Ensure migration script ran successfully
   - Check database permissions
   - Verify table creation

2. **API Endpoints Not Found**
   - Clear Symfony cache: `php bin/console cache:clear`
   - Check routing configuration
   - Verify controller registration

3. **Performance Issues**
   - Add database indexes for large datasets
   - Implement caching for frequent queries
   - Optimize AI prediction algorithms

### Debug Mode

Enable debug mode in Symfony to see detailed error messages:

```yaml
# app/config/config_dev.yml
framework:
    debug: true
```

## Future Enhancements

### Planned Features

1. **Advanced ML Models**
   - Deep learning for pattern recognition
   - Ensemble methods for better predictions
   - Real-time model updates

2. **Enhanced Analytics**
   - Longitudinal performance tracking
   - Predictive analytics for learning outcomes
   - Comparative analysis across cohorts

3. **Gamification**
   - Achievement badges for fundamental mastery
   - Progress tracking and milestones
   - Social learning features

4. **Integration Features**
   - LMS integration (Moodle, Canvas, etc.)
   - Third-party assessment tools
   - Learning management systems

## Support

For technical support or questions about this extension:

1. Check the demo page: `/demo.html`
2. Review API documentation above
3. Examine the source code in the new service classes
4. Test with the provided API examples

## License

This extension follows the same Apache 2.0 license as the original Concerto platform.
