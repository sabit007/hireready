<?php
// ============================================================
//  HireReady — One-Time Setup Script
//  1. Creates the database and tables
//  2. Inserts a test admin user
//  3. Run this ONCE via: http://localhost/hireready/setup.php
//  4. DELETE this file after setup is complete!
// ============================================================

$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'hireready_db';

echo "<pre style='font-family:Consolas,monospace;font-size:14px;padding:2rem;'>";
echo "═══════════════════════════════════════\n";
echo "  HireReady Database Setup\n";
echo "═══════════════════════════════════════\n\n";

// Connect without database first
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n   Make sure XAMPP MySQL is running!\n");
}
echo "✅ Connected to MySQL\n";

// Create database
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "✅ Database '$dbName' created/verified\n";

$conn->select_db($dbName);
$conn->set_charset('utf8mb4');

// Create tables
$tables = [
    "admins" => "CREATE TABLE IF NOT EXISTS admins (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        company_name    VARCHAR(255)   NOT NULL,
        company_address VARCHAR(500)   DEFAULT '',
        rep_name        VARCHAR(255)   NOT NULL,
        rep_role        VARCHAR(255)   DEFAULT '',
        email           VARCHAR(255)   NOT NULL UNIQUE,
        phone           VARCHAR(50)    DEFAULT '',
        password_hash   VARCHAR(255)   NOT NULL,
        is_approved     TINYINT(1)     DEFAULT 0,
        last_login      DATETIME       DEFAULT NULL,
        created_at      DATETIME       DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "jobs" => "CREATE TABLE IF NOT EXISTS jobs (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        admin_id    INT            NOT NULL,
        title       VARCHAR(255)   NOT NULL,
        job_type    VARCHAR(50)    NOT NULL,
        location    VARCHAR(50)    NOT NULL,
        salary      VARCHAR(100)   DEFAULT '',
        skills      TEXT           DEFAULT NULL,
        description TEXT           DEFAULT NULL,
        status      ENUM('active','closed') DEFAULT 'active',
        created_at  DATETIME       DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "quizzes" => "CREATE TABLE IF NOT EXISTS quizzes (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        admin_id    INT            NOT NULL,
        job_id      INT            NOT NULL,
        title       VARCHAR(255)   NOT NULL,
        topics      TEXT           DEFAULT NULL,
        pass_mark   INT            DEFAULT 70,
        time_limit  INT            DEFAULT 30,
        status      ENUM('active','closed') DEFAULT 'active',
        created_at  DATETIME       DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id)   REFERENCES jobs(id)   ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "quiz_questions" => "CREATE TABLE IF NOT EXISTS quiz_questions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id         INT            NOT NULL,
        question_text   TEXT           NOT NULL,
        question_type   VARCHAR(20)    DEFAULT 'MCQ',
        mark            INT            DEFAULT 1,
        options_json    TEXT           DEFAULT NULL,
        correct_answer  VARCHAR(255)   DEFAULT '',
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "users" => "CREATE TABLE IF NOT EXISTS users (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        full_name       VARCHAR(255)   NOT NULL,
        email           VARCHAR(255)   NOT NULL UNIQUE,
        phone           VARCHAR(50)    DEFAULT '',
        password_hash   VARCHAR(255)   NOT NULL,
        field           VARCHAR(255)   DEFAULT '',
        survey_done     TINYINT(1)     DEFAULT 0,
        cv_path         VARCHAR(500)   DEFAULT NULL,
        created_at      DATETIME       DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "applicants" => "CREATE TABLE IF NOT EXISTS applicants (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        job_id      INT            NOT NULL,
        quiz_id     INT            DEFAULT NULL,
        user_id     INT            DEFAULT NULL,
        name        VARCHAR(255)   NOT NULL,
        email       VARCHAR(255)   NOT NULL,
        quiz_passed TINYINT(1)     DEFAULT 0,
        quiz_score  INT            DEFAULT 0,
        total_marks INT            DEFAULT 0,
        answers_json TEXT          DEFAULT NULL,
        cv_path     VARCHAR(500)   DEFAULT NULL,
        status      ENUM('pending','approved','rejected') DEFAULT 'pending',
        created_at  DATETIME       DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_job_user (job_id, user_id),
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "courses" => "CREATE TABLE IF NOT EXISTS courses (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        admin_id    INT            NOT NULL,
        title       VARCHAR(255)   NOT NULL,
        description TEXT           DEFAULT NULL,
        topics      TEXT           DEFAULT NULL,
        instructor  VARCHAR(255)   NOT NULL,
        duration    VARCHAR(100)   DEFAULT '',
        modules     TEXT           DEFAULT NULL,
        status      ENUM('active','closed') DEFAULT 'active',
        created_at  DATETIME       DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "course_enrollments" => "CREATE TABLE IF NOT EXISTS course_enrollments (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        course_id   INT            NOT NULL,
        user_id     INT            DEFAULT NULL,
        user_name   VARCHAR(255)   DEFAULT '',
        user_email  VARCHAR(255)   DEFAULT '',
        enrolled_at DATETIME       DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_course_user (course_id, user_id),
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "✅ Table '$name' created/verified\n";
    } else {
        echo "❌ Error creating '$name': " . $conn->error . "\n";
    }
}

// Insert test admin (only if no admin exists)
$check = $conn->query("SELECT COUNT(*) AS cnt FROM admins")->fetch_assoc();
if ((int)$check['cnt'] === 0) {
    $testEmail = 'admin@hireready.com';
    $testPassword = 'Admin123';
    $hash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $conn->prepare("INSERT INTO admins (company_name, company_address, rep_name, rep_role, email, phone, password_hash, is_approved, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
    $company = 'HireReady Inc.';
    $address = '123 Tech Lane, Dhaka';
    $repName = 'Admin User';
    $repRole = 'HR Manager';
    $phone   = '+8801700000000';
    $stmt->bind_param('sssssss', $company, $address, $repName, $repRole, $testEmail, $phone, $hash);

    if ($stmt->execute()) {
        echo "\n✅ Test admin user created!\n";
        echo "   ┌──────────────────────────────────┐\n";
        echo "   │  Email:    admin@hireready.com    │\n";
        echo "   │  Password: Admin123               │\n";
        echo "   └──────────────────────────────────┘\n";
    } else {
        echo "❌ Error inserting test admin: " . $conn->error . "\n";
    }
} else {
    echo "\n⚠️  Admin(s) already exist — skipping test user insertion.\n";
}

$conn->close();

echo "\n═══════════════════════════════════════\n";
echo "  ✅ Setup Complete!\n";
echo "═══════════════════════════════════════\n\n";
echo "Next steps:\n";
echo "  1. Go to: http://localhost/hireready/login.php\n";
echo "  2. Login with:  admin@hireready.com / Admin123\n";
echo "  3. DELETE this setup.php file for security!\n";
echo "</pre>";
