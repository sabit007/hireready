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
$email    = $_SESSION['email']     ?? '';

// ── Job / Quiz lookup ──────────────────────────────────────────
$jobId = (int)($_GET['job_id'] ?? $_POST['job_id'] ?? 0);

$jobStmt = $conn->prepare("
    SELECT j.*, a.company_name,
           q.id AS quiz_id, q.title AS quiz_title, q.topics, q.pass_mark, q.time_limit
    FROM jobs j
    JOIN admins a ON a.id = j.admin_id
    LEFT JOIN quizzes q ON q.job_id = j.id AND q.status = 'active'
    WHERE j.id = ?
    LIMIT 1
");
$jobStmt->bind_param('i', $jobId);
$jobStmt->execute();
$jobRow = $jobStmt->get_result()->fetch_assoc();

if (!$jobRow || !$jobRow['quiz_id']) {
    // No job or no active quiz for this job — back to dashboard
    header('Location: dashboard.php');
    exit();
}

$quizId    = (int)$jobRow['quiz_id'];
$passMark  = (int)$jobRow['pass_mark'];
$timeLimit = (int)$jobRow['time_limit']; // minutes

// ── Fetch questions for this quiz ──────────────────────────────
$questions = [];
$qStmt = $conn->prepare("SELECT id, question_text, question_type, mark, options_json, correct_answer
                          FROM quiz_questions WHERE quiz_id = ?");
$qStmt->bind_param('i', $quizId);
$qStmt->execute();
$qRes = $qStmt->get_result();

$totalMarks = 0;
while ($row = $qRes->fetch_assoc()) {
    $type = strtoupper(trim($row['question_type']));
    $jsType = ($type === 'MCQ') ? 'mc' : (($type === 'TRUE/FALSE' || $type === 'TF') ? 'tf' : 'text');

    $q = [
        "id"      => (int)$row['id'],
        "type"    => $jsType,
        "text"    => $row['question_text'],
        "mark"    => (int)$row['mark'],
        "correct" => $row['correct_answer'],
    ];

    if ($jsType === 'mc') {
        $opts = json_decode($row['options_json'] ?? '[]', true) ?: [];
        $q['options'] = $opts;
        // correct stored as option text/index — normalize to index
        $idx = array_search($row['correct_answer'], $opts);
        $q['correct'] = $idx !== false ? $idx : (is_numeric($row['correct_answer']) ? (int)$row['correct_answer'] : 0);
    } elseif ($jsType === 'tf') {
        $q['options'] = ['True', 'False'];
        $ca = strtolower(trim($row['correct_answer']));
        $q['correct'] = ($ca === 'true' || $ca === '1') ? 0 : 1;
    } else {
        $q['placeholder'] = 'Type your answer here...';
        $q['minChars'] = 20;
    }

    $totalMarks += (int)$row['mark'];
    $questions[] = $q;
}

// ============================================================
// Handle quiz submission (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {

    $clientScore = (int)($_POST['score'] ?? 0);
    $answersJson = $_POST['answers'] ?? '{}';
    $answers     = json_decode($answersJson, true) ?: [];

    // ── Recompute score server-side from DB-stored correct answers (security) ──
    $score = 0;
    foreach ($questions as $i => $q) {
        if (($q['type'] === 'mc' || $q['type'] === 'tf') && isset($answers[$i])) {
            if ((int)$answers[$i] === (int)$q['correct']) {
                $score += $q['mark'];
            }
        }
    }

    $passed = ($totalMarks > 0 && ($score / $totalMarks) * 100 >= $passMark) ? 1 : 0;
    $scorePct = $totalMarks > 0 ? round(($score / $totalMarks) * 100) : 0;

    $stmt = $conn->prepare("
        INSERT INTO applicants (job_id, quiz_id, user_id, name, email, quiz_passed, quiz_score, total_marks, answers_json, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ON DUPLICATE KEY UPDATE
            quiz_id = VALUES(quiz_id),
            quiz_passed = VALUES(quiz_passed),
            quiz_score = VALUES(quiz_score),
            total_marks = VALUES(total_marks),
            answers_json = VALUES(answers_json),
            status = 'pending',
            created_at = NOW()
    ");
    // quiz_score stored as percentage (0-100) so admin dashboard's score bar renders correctly
    $stmt->bind_param('iiisssiis', $jobId, $quizId, $user_id, $fullname, $email, $passed, $scorePct, $totalMarks, $answersJson);
    $stmt->execute();

    // Stash result info for quiz_result.php
    $_SESSION['last_result'] = [
        'job_id'    => $jobId,
        'job_title' => $jobRow['title'],
        'company'   => $jobRow['company_name'],
        'score'     => $score,
        'total'     => $totalMarks,
        'pct'       => $scorePct,
        'pass_mark' => $passMark,
        'passed'    => (bool)$passed,
        'time_taken'=> (int)($_POST['time_taken'] ?? 0),
    ];

    header('Location: quiz_result.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HireReady — Skill Quiz</title>
  <link rel="stylesheet" href="css/quiz.css"/>
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
    <div class="nav-center" id="jobContext">
      <!-- Filled by JS from sessionStorage -->
    </div>
    <div class="nav-right">
      <div class="avatar">
        <?php echo htmlspecialchars(strtoupper($username[0])); ?>
      </div>
      <span class="nav-username">
        <?php echo htmlspecialchars($username); ?>
      </span>
    </div>
  </nav>

  <!-- ===================== TIMER BAR ===================== -->
  <div class="timer-bar" id="timerBar">
    <div class="timer-inner">
      <div class="timer-left">
        <i class="fas fa-clock"></i>
        <span class="timer-label">Time Remaining</span>
      </div>
      <div class="timer-display" id="timerDisplay">30:00</div>
      <div class="timer-track-wrap">
        <div class="timer-track">
          <div class="timer-fill" id="timerFill"></div>
        </div>
      </div>
      <div class="timer-right">
        <span class="timer-questions" id="timerProgress">
          Question 1 of 8
        </span>
      </div>
    </div>
  </div>

  <!-- ===================== MAIN ===================== -->
  <main class="quiz-main">
    <form method="POST" id="quizForm">
      <input type="hidden" name="submit_quiz" value="1">
      <input type="hidden" name="job_id"      id="h-job-id">
      <input type="hidden" name="score"       id="h-score"  value="0">
      <input type="hidden" name="time_taken"  id="h-time"   value="0">
      <input type="hidden" name="answers"     id="h-answers" value="{}">

      <!-- ===== QUIZ HEADER ===== -->
      <div class="quiz-header">
        <div class="quiz-header-left">
          <div class="quiz-badge">Skill Assessment</div>
          <h1 id="quizTitle">Web Development Quiz</h1>
          <p id="quizSubtitle">
            For your application to
            <strong id="quizCompany">Google</strong>
          </p>
        </div>
        <div class="quiz-header-right">
          <div class="quiz-meta-item">
            <i class="fas fa-list-check"></i>
            <span>8 Questions</span>
          </div>
          <div class="quiz-meta-item">
            <i class="fas fa-clock"></i>
            <span>30 Minutes</span>
          </div>
          <div class="quiz-meta-item">
            <i class="fas fa-star"></i>
            <span>Scored</span>
          </div>
        </div>
      </div>

      <!-- ===== PROGRESS DOTS ===== -->
      <div class="progress-dots" id="progressDots">
        <!-- Injected by JS -->
      </div>

      <!-- ===== QUESTION PANELS ===== -->
      <div class="questions-wrap" id="questionsWrap">
        <!-- Injected by JS -->
      </div>

      <!-- ===== NAVIGATION ===== -->
      <div class="quiz-nav">
        <button type="button" class="btn-back" id="btnBack"
          onclick="prevQuestion()" style="visibility:hidden">
          <i class="fas fa-arrow-left"></i> Back
        </button>

        <div class="nav-center-text" id="navCenterText">
          Question <span id="currentQ">1</span> of 8
        </div>

        <button type="button" class="btn-next" id="btnNext"
          onclick="nextQuestion()">
          Next <i class="fas fa-arrow-right"></i>
        </button>
      </div>

      <!-- ===== SUBMIT (hidden until last question) ===== -->
      <div class="submit-wrap" id="submitWrap" style="display:none">
        <div class="submit-summary" id="submitSummary">
          <!-- Filled by JS -->
        </div>
        <button type="button" class="btn-submit" id="btnSubmit"
          onclick="submitQuiz()">
          Submit Assessment
          <i class="fas fa-paper-plane"></i>
        </button>
        <p class="submit-note">
          <i class="fas fa-shield-halved"></i>
          Your answers are reviewed by the employer
        </p>
      </div>

    </form>
  </main>

  <!-- ===== TIMEOUT MODAL ===== -->
  <div class="modal-overlay" id="timeoutModal">
    <div class="modal-box">
      <div class="modal-icon timeout-icon">
        <i class="fas fa-clock"></i>
      </div>
      <h2>Time's Up!</h2>
      <p>Your 30 minutes have ended. Your answers so far have been submitted automatically.</p>
      <button class="btn-submit" onclick="forceSubmit()">
        View Results <i class="fas fa-arrow-right"></i>
      </button>
    </div>
  </div>

  <!-- ===== CONFIRM SUBMIT MODAL ===== -->
  <div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
      <div class="modal-icon confirm-icon">
        <i class="fas fa-paper-plane"></i>
      </div>
      <h2>Submit Assessment?</h2>
      <p id="confirmText">You have answered all 8 questions. Ready to submit?</p>
      <div class="modal-btns">
        <button class="btn-back-modal"
          onclick="document.getElementById('confirmModal').classList.remove('open')">
          Review Answers
        </button>
        <button class="btn-submit" onclick="forceSubmit()">
          Yes, Submit
        </button>
      </div>
    </div>
  </div>

  <script>
    const questionsData = <?php echo json_encode($questions); ?>;
    const quizMeta = {
      jobId:     <?php echo (int)$jobId; ?>,
      jobTitle:  <?php echo json_encode($jobRow['title']); ?>,
      company:   <?php echo json_encode($jobRow['company_name']); ?>,
      quizTitle: <?php echo json_encode($jobRow['quiz_title']); ?>,
      passMark:  <?php echo (int)$passMark; ?>,
      timeLimit: <?php echo (int)$timeLimit; ?>,
      totalMarks:<?php echo (int)$totalMarks; ?>
    };
  </script>
  <script src="js/quiz.js"></script>
</body>
</html>