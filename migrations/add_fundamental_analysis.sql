-- Migration script to add fundamental analysis features to Concerto Platform
-- Run this script to add the new tables and columns for the adaptive learning features

-- Add fundamental_type column to TestNode table
ALTER TABLE TestNode ADD COLUMN fundamental_type VARCHAR(20) NULL;

-- Create FundamentalPerformance table
CREATE TABLE IF NOT EXISTS FundamentalPerformance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created DATETIME NOT NULL,
    updated DATETIME NOT NULL,
    test_session_id INT NOT NULL,
    fundamental_type VARCHAR(20) NOT NULL,
    score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    total_questions INT NOT NULL DEFAULT 0,
    correct_answers INT NOT NULL DEFAULT 0,
    average_response_time DECIMAL(8,2) NULL,
    weakness_pattern TEXT NULL,
    INDEX idx_test_session (test_session_id),
    INDEX idx_fundamental_type (fundamental_type),
    INDEX idx_created (created),
    FOREIGN KEY (test_session_id) REFERENCES TestSession(id) ON DELETE CASCADE
);

-- Create PracticeSession table
CREATE TABLE IF NOT EXISTS PracticeSession (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created DATETIME NOT NULL,
    updated DATETIME NOT NULL,
    source_test_session_id INT NOT NULL,
    type VARCHAR(20) NOT NULL,
    target_fundamental VARCHAR(20) NULL,
    difficulty VARCHAR(20) NULL,
    status INT NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 0,
    completed_questions INT NOT NULL DEFAULT 0,
    score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    recommendations TEXT NULL,
    INDEX idx_source_session (source_test_session_id),
    INDEX idx_type (type),
    INDEX idx_target_fundamental (target_fundamental),
    INDEX idx_status (status),
    INDEX idx_created (created),
    FOREIGN KEY (source_test_session_id) REFERENCES TestSession(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_testnode_fundamental_type ON TestNode(fundamental_type);
CREATE INDEX idx_fundamental_performance_score ON FundamentalPerformance(score);
CREATE INDEX idx_practice_session_score ON PracticeSession(score);

-- Insert sample data for testing (optional)
-- You can uncomment these lines to add sample data for testing

/*
-- Sample fundamental types for TestNodes
UPDATE TestNode SET fundamental_type = 'listening' WHERE id % 4 = 0;
UPDATE TestNode SET fundamental_type = 'grasping' WHERE id % 4 = 1;
UPDATE TestNode SET fundamental_type = 'retention' WHERE id % 4 = 2;
UPDATE TestNode SET fundamental_type = 'application' WHERE id % 4 = 3;

-- Sample FundamentalPerformance data
INSERT INTO FundamentalPerformance (created, updated, test_session_id, fundamental_type, score, total_questions, correct_answers, average_response_time, weakness_pattern)
SELECT 
    NOW() - INTERVAL FLOOR(RAND() * 30) DAY,
    NOW() - INTERVAL FLOOR(RAND() * 30) DAY,
    ts.id,
    CASE (ts.id % 4)
        WHEN 0 THEN 'listening'
        WHEN 1 THEN 'grasping'
        WHEN 2 THEN 'retention'
        WHEN 3 THEN 'application'
    END,
    ROUND(40 + RAND() * 50, 2),
    FLOOR(5 + RAND() * 10),
    FLOOR(2 + RAND() * 8),
    ROUND(10 + RAND() * 20, 2),
    CASE 
        WHEN RAND() > 0.7 THEN 'low_accuracy,slow_response'
        WHEN RAND() > 0.5 THEN 'low_accuracy'
        WHEN RAND() > 0.3 THEN 'slow_response'
        ELSE NULL
    END
FROM TestSession ts
WHERE ts.id <= 10;
*/
