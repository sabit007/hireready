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
         CV UPLOAD SECTION (only shown if passed)
    ===================================================== -->
    <div class="cv-section">

      <!-- Big bold CTA -->
      <div class="cv-cta">
        <div class="cv-cta-badge">Next Step</div>
        <h1 class="cv-cta-title">
          Now upload your CV
        </h1>
        <p class="cv-cta-sub">
          Tailor it to the
          <strong><?php echo htmlspecialchars($quiz_job_title); ?></strong>
          role at
          <strong><?php echo htmlspecialchars($quiz_company); ?></strong>
          before uploading.
        </p>
      </div>

      <!-- Tailoring tips -->
      <div class="tailor-tips">
        <h3 class="tips-title">
          <i class="fas fa-wand-magic-sparkles"></i>
          How to tailor your CV for this role
        </h3>
        <div class="tips-grid">
          <div class="tip-item">
            <div class="tip-num">01</div>
            <div class="tip-text">
              <strong>Lead with relevant skills</strong>
              <span>Highlight React, CSS, and TypeScript at the top of your skills section.</span>
            </div>
          </div>
          <div class="tip-item">
            <div class="tip-num">02</div>
            <div class="tip-text">
              <strong>Match the job keywords</strong>
              <span>Use the exact terms from the job description — ATS systems scan for them.</span>
            </div>
          </div>
          <div class="tip-item">
            <div class="tip-num">03</div>
            <div class="tip-text">
              <strong>Quantify your impact</strong>
              <span>Instead of "built a website", say "built a platform serving 10,000+ users".</span>
            </div>
          </div>
          <div class="tip-item">
            <div class="tip-num">04</div>
            <div class="tip-text">
              <strong>Keep it one page</strong>
              <span>Recruiters spend an average of 7 seconds on a CV. Make every line count.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Upload box -->
      <div class="upload-section">

        <?php if ($upload_success): ?>
          <!-- Success state -->
          <div class="upload-success-state">
            <div class="success-icon-big">
              <i class="fas fa-circle-check"></i>
            </div>
            <h3>CV Uploaded Successfully!</h3>
            <p>
              Your CV has been submitted with your application for
              <strong><?php echo htmlspecialchars($quiz_job_title); ?></strong>
              at <strong><?php echo htmlspecialchars($quiz_company); ?></strong>.
            </p>
            <a href="dashboard.php" class="btn-dashboard">
              <i class="fas fa-house"></i>
              Back to Dashboard
            </a>
          </div>

        <?php else: ?>

          <form method="POST" enctype="multipart/form-data"
                id="cvForm" novalidate>

            <?php if ($upload_error): ?>
              <div class="upload-error-msg">
                <i class="fas fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($upload_error); ?>
              </div>
            <?php endif; ?>

            <!-- Drop zone -->
            <div class="drop-zone" id="dropZone"
                 onclick="document.getElementById('cvFile').click()"
                 ondragover="handleDragOver(event)"
                 ondragleave="handleDragLeave(event)"
                 ondrop="handleDrop(event)">

              <div class="drop-zone-content" id="dropContent">
                <div class="drop-icon">
                  <i class="fas fa-file-arrow-up"></i>
                </div>
                <h3>Drag & drop your CV here</h3>
                <p>or <span class="browse-link">browse files</span></p>
                <div class="drop-formats">
                  <span>PDF</span>
                  <span>DOC</span>
                  <span>DOCX</span>
                  <span class="dot-sep">·</span>
                  <span>Max 5MB</span>
                </div>
              </div>

              <!-- File selected state (hidden by default) -->
              <div class="drop-zone-selected" id="dropSelected"
                   style="display:none">
                <div class="file-preview">
                  <div class="file-icon">
                    <i class="fas fa-file-pdf" id="fileTypeIcon"></i>
                  </div>
                  <div class="file-info">
                    <span class="file-name" id="fileName">document.pdf</span>
                    <span class="file-size" id="fileSize">0 KB</span>
                  </div>
                  <button type="button" class="file-remove"
                          onclick="removeFile(event)">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>

            </div>

            <!-- Hidden file input -->
            <input
              type="file"
              name="cv_file"
              id="cvFile"
              accept=".pdf,.doc,.docx"
              style="display:none"
              onchange="handleFileSelect(this)"
            />

            <!-- Job context reminder -->
            <div class="upload-job-reminder">
              <div class="reminder-left">
                <i class="fas fa-briefcase"></i>
                <div>
                  <span class="reminder-title">
                    <?php echo htmlspecialchars($quiz_job_title); ?>
                  </span>
                  <span class="reminder-company">
                    @ <?php echo htmlspecialchars($quiz_company); ?>
                  </span>
                </div>
              </div>
              <div class="reminder-score">
                <span>Your Score</span>
                <strong><?php echo $score_pct; ?>%</strong>
              </div>
            </div>

            <!-- Submit button -->
            <button type="submit"
                    class="btn-upload" id="uploadBtn" disabled>
              <i class="fas fa-paper-plane"></i>
              Submit Application
            </button>

            <p class="upload-note">
              <i class="fas fa-lock"></i>
              Your CV is only shared with
              <?php echo htmlspecialchars($quiz_company); ?>
            </p>

          </form>

        <?php endif; ?>

      </div><!-- end upload-section -->

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