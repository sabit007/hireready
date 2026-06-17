<?php
session_start();

// ── Auth guard ──────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ── Database config ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hireready_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("DB error: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? 'User';
$fullname = $_SESSION['full_name'] ?? $username;

// ── Result from last quiz attempt ────────────────────────────
$result = $_SESSION['last_result'] ?? null;

if (!$result) {
    // No result in session — pull most recent attempt from DB
    $stmt = $conn->prepare("
        SELECT ap.*, j.title AS job_title, a.company_name
        FROM applicants ap
        JOIN jobs j ON j.id = ap.job_id
        JOIN admins a ON a.id = j.admin_id
        WHERE ap.user_id = ?
        ORDER BY ap.created_at DESC LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $result = [
            'job_id'    => (int)$row['job_id'],
            'job_title' => $row['job_title'],
            'company'   => $row['company_name'],
            'score'     => (int)$row['quiz_score'],
            'total'     => (int)$row['total_marks'],
            'pct'       => (int)$row['quiz_score'],
            'pass_mark' => 0,
            'passed'    => (bool)$row['quiz_passed'],
            'time_taken'=> 0,
        ];
    } else {
        // No attempts at all — back to dashboard
        header('Location: dashboard.php');
        exit();
    }
}

$quiz_job_title = $result['job_title'];
$quiz_company   = $result['company'];
$score          = $result['score'];
$total_mc       = $result['total'];
$passed         = $result['passed'];
$score_pct      = $result['pct'];

$mins = floor(($result['time_taken'] ?? 0) / 60);
$secs = ($result['time_taken'] ?? 0) % 60;
$time_spent = sprintf('%02d:%02d', $mins, $secs);

// ============================================================
// Handle CV upload (POST)
// ============================================================
$upload_success = false;
$upload_error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_file'])) {

    $file     = $_FILES['cv_file'];
    $allowed  = ['pdf', 'doc', 'docx'];
    $maxSize  = 5 * 1024 * 1024; // 5MB

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_error = "Upload failed. Please try again.";

    } elseif (!in_array($ext, $allowed)) {
        $upload_error = "Only PDF, DOC, and DOCX files are allowed.";

    } elseif ($file['size'] > $maxSize) {
        $upload_error = "File is too large. Maximum size is 5MB.";

    } else {
        $uploadDir = __DIR__ . '/uploads/cvs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename   = 'cv_' . $user_id . '_' . time() . '.' . $ext;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $relPath = 'uploads/cvs/' . $filename;

            $stmt = $conn->prepare("UPDATE applicants SET cv_path=? WHERE job_id=? AND user_id=?");
            $stmt->bind_param('sii', $relPath, $result['job_id'], $user_id);
            $stmt->execute();

            $stmt2 = $conn->prepare("UPDATE users SET cv_path=? WHERE id=?");
            $stmt2->bind_param('si', $relPath, $user_id);
            $stmt2->execute();

            $upload_success = true;
        } else {
            $upload_error = "Could not save file. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HireReady — Quiz Result</title>
  <link rel="stylesheet" href="css/quiz_result.css"/>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  />
</head>
<body>

  <!-- ===================== NAVBAR ===================== -->
  <nav class="navbar">
    <div class="nav-logo">
      <a href="dashboard.php" class="logo-text">HireReady</a>
    </div>
    <div class="nav-right">
      <div class="avatar">
        <?php echo htmlspecialchars(strtoupper($username[0])); ?>
      </div>
      <span class="nav-username">
        <?php echo htmlspecialchars($username); ?>
      </span>
      <a href="dashboard.php" class="back-dash-btn">
        <i class="fas fa-arrow-left"></i>
        Dashboard
      </a>
    </div>
  </nav>

  <main class="result-main">

    <!-- =====================================================
         PASS BANNER (top)
    ===================================================== -->
    <div class="pass-banner <?php echo $passed ? 'passed' : 'failed'; ?>">
      <div class="pass-banner-inner">

        <div class="pass-icon-wrap">
          <?php if ($passed): ?>
            <div class="pass-icon">
              <i class="fas fa-circle-check"></i>
            </div>
          <?php else: ?>
            <div class="fail-icon">
              <i class="fas fa-circle-xmark"></i>
            </div>
          <?php endif; ?>
        </div>

        <div class="pass-text">
          <?php if ($passed): ?>
            <h2>
              <?php echo htmlspecialchars($fullname); ?> passed the quiz!
            </h2>
            <p>
              Great work on the
              <strong><?php echo htmlspecialchars($quiz_job_title); ?></strong>
              assessment at
              <strong><?php echo htmlspecialchars($quiz_company); ?></strong>.
              You scored <strong><?php echo $score_pct; ?>%</strong> —
              above the required threshold.
            </p>
          <?php else: ?>
            <h2>
              <?php echo htmlspecialchars($fullname); ?> — not quite yet.
            </h2>
            <p>
              You scored <?php echo $score_pct; ?>% on this assessment.
              Review the recommended courses and try again.
            </p>
          <?php endif; ?>
        </div>

        <!-- Score pill -->
        <div class="score-pill">
          <span class="score-num"><?php echo $score_pct; ?>%</span>
          <span class="score-label">Score</span>
        </div>

      </div>
    </div>

    <!-- =====================================================
         SCORE BREAKDOWN CARDS
    ===================================================== -->
    <div class="score-cards">
      <div class="score-card">
        <div class="sc-icon mint-bg">
          <i class="fas fa-bullseye"></i>
        </div>
        <div class="sc-num"><?php echo $score; ?>/<?php echo $total_mc; ?></div>
        <div class="sc-label">MC Correct</div>
      </div>
      <div class="score-card">
        <div class="sc-icon peach-bg">
          <i class="fas fa-clock"></i>
        </div>
        <div class="sc-num"><?php echo $time_spent; ?></div>
        <div class="sc-label">Time Taken</div>
      </div>
      <div class="score-card">
        <div class="sc-icon blue-bg">
          <i class="fas fa-pen-to-square"></i>
        </div>
        <div class="sc-num"><?php echo $total_mc; ?></div>
        <div class="sc-label">Total Marks</div>
      </div>
      <div class="score-card">
        <div class="sc-icon green-bg">
          <i class="fas fa-ranking-star"></i>
        </div>
        <div class="sc-num">
          <?php echo $passed ? "Pass" : "Fail"; ?>
        </div>
        <div class="sc-label">Result</div>
      </div>
    </div>

    <?php if ($passed): ?>

    <!-- =====================================================
         APPLICATION SUCCESS SECTION (only shown if passed)
    ===================================================== -->
    <div class="cv-section" style="text-align: center; background: var(--white); padding: 50px 30px; border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow);">
      
      <div class="upload-success-state">
        <div class="success-icon-big" style="background: var(--mint-bg); color: var(--black); width: 60px; height: 60px; font-size: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
          <i class="fas fa-circle-check"></i>
        </div>
        <h3 style="font-size: 22px; font-weight: 900; margin-bottom: 12px; color: var(--black);">Your CV has been forwarded to the company</h3>
        <p style="font-size: 15px; color: var(--text-muted); line-height: 1.6; margin-bottom: 30px;">
          Please wait for their approval.
        </p>
        <a href="dashboard.php" class="btn-dashboard" style="text-decoration: none;">
          <i class="fas fa-house"></i>
          Return to Dashboard
        </a>
      </div>

    </div><!-- end cv-section -->

    <?php else: ?>

    <!-- =====================================================
         FAILED STATE — show courses instead
    ===================================================== -->
    <div class="failed-section">
      <h2>Keep improving and try again</h2>
      <p>
        These courses will help you prepare for your next attempt.
      </p>
      <div class="retry-actions">
        <a href="dashboard.php" class="btn-retry-dash">
          <i class="fas fa-graduation-cap"></i>
          View Recommended Courses
        </a>
        <a href="quiz.php" class="btn-retry-quiz">
          <i class="fas fa-rotate-right"></i>
          Retake Quiz
        </a>
      </div>
    </div>

    <?php endif; ?>

  </main>

  <script src="js/quiz_result.js"></script>
</body>
</html>