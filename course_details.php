<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hireready_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("DB error: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_course'])) {
    $userId   = (int)$_SESSION['user_id'];
    $uName    = $_SESSION['full_name'] ?? ($_SESSION['user_name'] ?? '');
    $uEmail   = $_SESSION['email'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO course_enrollments (course_id, user_id, user_name, user_email, enrolled_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE enrolled_at = enrolled_at
    ");
    $stmt->bind_param('iiss', $courseId, $userId, $uName, $uEmail);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    echo "Course not found.";
    exit();
}

$username = $_SESSION['user_name'] ?? 'User';
$modules = nl2br(htmlspecialchars($course['modules'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HireReady — <?php echo htmlspecialchars($course['title']); ?></title>
  <link rel="stylesheet" href="css/dashboard.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  />
  <style>
    .course-header {
      background: var(--black);
      color: var(--white);
      padding: 40px;
      border-radius: 16px;
      margin-bottom: 30px;
    }
    .course-header h1 {
      font-size: 32px;
      margin-bottom: 10px;
    }
    .course-header p {
      color: rgba(255, 255, 255, 0.7);
      font-size: 16px;
    }
    .course-body {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 30px;
      box-shadow: var(--shadow);
      margin-bottom: 30px;
    }
    .course-body h3 {
      font-size: 20px;
      margin-bottom: 15px;
      border-bottom: 1px solid var(--border);
      padding-bottom: 10px;
    }
    .course-body p {
      color: var(--text-muted);
      line-height: 1.6;
      margin-bottom: 20px;
    }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .info-box {
      background: var(--bg);
      padding: 15px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
    }
    .info-box h4 {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 5px;
    }
    .info-box p {
      color: var(--text);
      font-weight: 600;
      margin-bottom: 0;
    }
    .finish-container {
      text-align: center;
      margin-top: 40px;
    }
    .finish-btn {
      padding: 15px 40px;
      font-size: 16px;
      font-weight: 700;
      background: var(--black);
      color: var(--white);
      border: none;
      border-radius: 30px;
      cursor: pointer;
      transition: all var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }
    .finish-btn:hover {
      background: #10b981;
      transform: translateY(-2px);
    }
    .back-link {
      display: inline-block;
      margin-bottom: 20px;
      color: var(--text-muted);
      text-decoration: none;
      font-weight: 600;
    }
    .back-link:hover {
      color: var(--black);
    }
    .modules-list {
      background: var(--bg);
      padding: 20px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      color: var(--text);
      line-height: 1.8;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <a href="dashboard.php" class="nav-logo">
      <span class="logo-icon"></span>
      <span class="logo-text">HireReady</span>
    </a>
    <div class="nav-right">
      <div class="nav-profile">
        <div class="avatar">
          <?php echo htmlspecialchars(strtoupper($username[0])); ?>
        </div>
        <span class="nav-username">
          <?php echo htmlspecialchars($username); ?>
        </span>
      </div>
      <a href="index.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <div class="course-header">
      <h1><?php echo htmlspecialchars($course['title']); ?></h1>
      <p><i class="fas fa-graduation-cap"></i> Complete this course to improve your skills.</p>
    </div>

    <div class="course-body">
      <div class="info-grid">
        <div class="info-box">
          <h4>Instructor</h4>
          <p><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($course['instructor'] ?: 'N/A'); ?></p>
        </div>
        <div class="info-box">
          <h4>Duration</h4>
          <p><i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration'] ?: 'N/A'); ?> Hours</p>
        </div>
        <div class="info-box">
          <h4>Topics</h4>
          <p><i class="fas fa-list"></i> <?php echo htmlspecialchars($course['topics'] ?: 'General'); ?></p>
        </div>
      </div>

      <h3>About the Course</h3>
      <p><?php echo nl2br(htmlspecialchars($course['description'] ?: 'No description provided.')); ?></p>

      <h3>Course Modules</h3>
      <div class="modules-list">
        <?php echo $modules ?: 'No modules provided.'; ?>
      </div>

      <div class="finish-container">
        <form method="POST">
          <button type="submit" name="finish_course" class="finish-btn">
            <i class="fas fa-check-circle"></i> Finish Course
          </button>
        </form>
      </div>
    </div>
  </main>

</body>
</html>
