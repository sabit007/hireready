<?php
// ============================================================
//  HireReady — Topics Migration Script
//  Creates the new topics tables and seeds the database
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hireready_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

echo "<pre>";
echo "Starting Migration...\n\n";

$tables = [
    "topics" => "CREATE TABLE IF NOT EXISTS topics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(100) NOT NULL,
        name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB",

    "quiz_topics" => "CREATE TABLE IF NOT EXISTS quiz_topics (
        quiz_id INT NOT NULL,
        topic_id INT NOT NULL,
        PRIMARY KEY (quiz_id, topic_id),
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
        FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "question_topics" => "CREATE TABLE IF NOT EXISTS question_topics (
        question_id INT NOT NULL,
        topic_id INT NOT NULL,
        PRIMARY KEY (question_id, topic_id),
        FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
        FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "course_topics" => "CREATE TABLE IF NOT EXISTS course_topics (
        course_id INT NOT NULL,
        topic_id INT NOT NULL,
        PRIMARY KEY (course_id, topic_id),
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "user_question_results" => "CREATE TABLE IF NOT EXISTS user_question_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        quiz_id INT NOT NULL,
        question_id INT NOT NULL,
        is_correct TINYINT(1) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "✅ Table '$name' created/verified.\n";
    } else {
        echo "❌ Error creating '$name': " . $conn->error . "\n";
    }
}

// Seed Topics
$seedData = [
    "Programming" => [
        "Programming Fundamentals", "OOP", "Data Structures", "Algorithms", 
        "Python", "Java", "PHP", "JavaScript", "TypeScript"
    ],
    "Web Development" => [
        "HTML", "CSS", "React", "Vue", "Angular", "Node.js", 
        "Express.js", "Laravel", "REST APIs", "Authentication"
    ],
    "Databases" => [
        "MySQL", "PostgreSQL", "MongoDB", "Database Design", "SQL Queries"
    ],
    "Cloud & DevOps" => [
        "Linux", "Git", "GitHub", "Docker", "Kubernetes", "AWS", "Azure", "CI/CD"
    ],
    "Cybersecurity" => [
        "Network Security", "Cryptography", "Ethical Hacking", "Penetration Testing", "OWASP"
    ],
    "Data & AI" => [
        "Data Analysis", "Machine Learning", "Deep Learning", "Pandas", "NumPy", "TensorFlow"
    ],
    "Soft Skills" => [
        "Problem Solving", "Communication", "Teamwork", "Debugging"
    ]
];

$stmt = $conn->prepare("INSERT IGNORE INTO topics (category, name) VALUES (?, ?)");

$seededCount = 0;
foreach ($seedData as $category => $topics) {
    foreach ($topics as $name) {
        $stmt->bind_param("ss", $category, $name);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $seededCount++;
        }
    }
}

echo "\n✅ Successfully seeded $seededCount new topics.\n";
echo "Migration Complete!</pre>";
$conn->close();
?>
