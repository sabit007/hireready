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

// ════════════════════════════════════════════════════════════
//  AJAX HANDLER — course enrollment
// ════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'enroll_course') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $userId   = (int)$_SESSION['user_id'];
        $uName    = $_SESSION['full_name'] ?? ($_SESSION['user_name'] ?? '');
        $uEmail   = $_SESSION['email'] ?? '';

        if (!$courseId) {
            echo json_encode(['success' => false, 'message' => 'Invalid course.']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO course_enrollments (course_id, user_id, user_name, user_email, enrolled_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE enrolled_at = enrolled_at
        ");
        $stmt->bind_param('iiss', $courseId, $userId, $uName, $uEmail);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ── GET HANDLER — course enrollment from quiz result page
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['enroll_course_id'])) {
    $courseId = (int)$_GET['enroll_course_id'];
    $userId   = (int)$_SESSION['user_id'];
    $uName    = $_SESSION['full_name'] ?? ($_SESSION['user_name'] ?? '');
    $uEmail   = $_SESSION['email'] ?? '';

    if ($courseId > 0) {
        $stmt = $conn->prepare("
            INSERT INTO course_enrollments (course_id, user_id, user_name, user_email, enrolled_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE enrolled_at = enrolled_at
        ");
        $stmt->bind_param('iiss', $courseId, $userId, $uName, $uEmail);
        $stmt->execute();
        
        // Redirect back to dashboard to clear query parameter
        header("Location: dashboard.php");
        exit;
    }
}

// ── Session shorthand ────────────────────────────────────────
$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? 'User';
$fullname = $_SESSION['full_name'] ?? $username;
$email    = $_SESSION['email']     ?? '';
$field    = $_SESSION['field']     ?? 'General';

// ── Skills (placeholder until skill-tracking is built) ─────────
$skills = [
    ["name" => "HTML & CSS",  "level" => 90, "color" => "#f97316"],
    ["name" => "JavaScript",  "level" => 72, "color" => "#eab308"],
    ["name" => "React",       "level" => 60, "color" => "#3b82f6"],
    ["name" => "SQL",         "level" => 35, "color" => "#8b5cf6"],
    ["name" => "Node.js",     "level" => 45, "color" => "#10b981"],
];

$jobs = [];
$result = $conn->query("
    SELECT j.*, a.company_name,
           q.id AS quiz_id, q.title AS quiz_title, q.pass_mark, q.time_limit,
           (SELECT COUNT(*) FROM applicants ap WHERE ap.job_id = j.id AND ap.user_id = $user_id AND ap.quiz_passed = 1) AS has_applied,
           (SELECT COUNT(*) FROM applicants ap WHERE ap.job_id = j.id AND ap.user_id = $user_id AND ap.quiz_passed = 1 AND ap.status = 'approved') AS is_hired
    FROM jobs j
    JOIN admins a ON a.id = j.admin_id
    LEFT JOIN quizzes q ON q.job_id = j.id AND q.status = 'active'
    WHERE j.status = 'active'
    ORDER BY j.created_at DESC
");

$logoPalette = [
    ["bg" => "#e8f0fe", "color" => "#4285f4"],
    ["bg" => "#fce7f3", "color" => "#ec4899"],
    ["bg" => "#f1f5f9", "color" => "#0f172a"],
    ["bg" => "#d1fae5", "color" => "#065f46"],
    ["bg" => "#ede9fe", "color" => "#7c3aed"],
];

$i = 0;
while ($row = $result->fetch_assoc()) {
    $palette = $logoPalette[$i % count($logoPalette)];
    $i++;

    $skillsList = array_filter(array_map('trim', explode(',', $row['skills'] ?? '')));

    $jobs[] = [
        "id"          => (int)$row['id'],
        "title"       => $row['title'],
        "company"     => $row['company_name'],
        "logo_emoji"  => strtoupper(substr($row['company_name'], 0, 1)),
        "logo_bg"     => $palette['bg'],
        "logo_color"  => $palette['color'],
        "match"       => 75, // placeholder match score
        "description" => $row['description'] ? substr($row['description'], 0, 140) : '',
        "tags"        => array_values($skillsList),
        "type"        => $row['job_type'],
        "location"    => $row['location'],
        "salary"      => $row['salary'] ?: 'Not specified',
        "full_desc"   => $row['description'] ?: '',
        "quiz_id"     => $row['quiz_id'] ? (int)$row['quiz_id'] : null,
        "applied"     => (int)$row['has_applied'] > 0,
        "hired"       => (int)$row['is_hired'] > 0,
    ];
}

// ── Recommended courses (live from DB) ───────────────────────────
$courses = [];
$courseIcons = ["🗄️", "⚛️", "🟢", "🔷", "📘", "🚀"];
$courseRes = $conn->query("SELECT * FROM courses WHERE status='active' ORDER BY created_at DESC LIMIT 6");
$ci = 0;

// Find courses this user is already enrolled in
$enrolledIds = [];
$enrollRes = $conn->prepare("SELECT course_id FROM course_enrollments WHERE user_id = ?");
$enrollRes->bind_param('i', $user_id);
$enrollRes->execute();
$er = $enrollRes->get_result();
while ($row = $er->fetch_assoc()) {
    $enrolledIds[] = (int)$row['course_id'];
}

while ($row = $courseRes->fetch_assoc()) {
    $diff = "Beginner";
    $diffClass = "diff-beginner";

    $courses[] = [
        "id"         => (int)$row['id'],
        "title"      => $row['title'],
        "icon"       => $courseIcons[$ci % count($courseIcons)],
        "icon_bg"    => "#ede9fe",
        "skill"      => $row['description'] ? substr($row['description'], 0, 90) : '',
        "difficulty" => $diff,
        "diff_class" => $diffClass,
        "duration"   => $row['duration'] ?: '—',
        "enrolled"   => in_array((int)$row['id'], $enrolledIds),
    ];
    $ci++;
}

// ── Profile stats ──────────────────────────────────────────────
$jobMatches = count($jobs);

$applicantRes = $conn->prepare("SELECT COUNT(*) AS cnt FROM applicants WHERE user_id = ?");
$applicantRes->bind_param('i', $user_id);
$applicantRes->execute();
$appliedCount = (int)$applicantRes->get_result()->fetch_assoc()['cnt'];

$profileComplete = 40;
if (!empty($email))    $profileComplete += 20;
if (!empty($fullname)) $profileComplete += 20;
if (!empty($field) && $field !== 'General') $profileComplete += 20;

$stats = [
    "job_matches"       => $jobMatches,
    "new_courses"       => count($courses),
    "profile_complete"  => min($profileComplete, 100),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HireReady — Dashboard</title>
  <link rel="stylesheet" href="css/dashboard.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  />
</head>
<body>

  <!-- ===================== NAVBAR ===================== -->
  <nav class="navbar">
    <div class="nav-logo">
      <span class="logo-icon"></span>
      <span class="logo-text">HireReady</span>
    </div>

    <div class="nav-right">
      <div class="nav-profile">
        <!-- First letter of username as avatar -->
        <div class="avatar">
          <?php echo htmlspecialchars(strtoupper($username[0])); ?>
        </div>
        <span class="nav-username">
          <?php echo htmlspecialchars($username); ?>
        </span>
      </div>

      <!-- Logout -->
      <a href="index.php" class="logout-btn">
        <!-- ============================================================
             DATABASE INTEGRATION POINT — LOGOUT
             Create logout.php with:
             session_start();
             session_destroy();
             header("Location: login.php");
             exit();
             ============================================================ -->
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </nav>

  <!-- ===================== MAIN CONTENT ===================== -->
  <main class="main-content">

    <!-- ===== NOTIFICATIONS / HIRING ALERTS ===== -->
    <?php
    $hiredStmt = $conn->prepare("
        SELECT ap.*, j.title AS job_title, a.company_name
        FROM applicants ap
        JOIN jobs j ON j.id = ap.job_id
        JOIN admins a ON a.id = j.admin_id
        WHERE ap.user_id = ? AND ap.quiz_passed = 1 AND ap.status = 'approved'
        ORDER BY ap.created_at DESC
    ");
    $hiredStmt->bind_param('i', $user_id);
    $hiredStmt->execute();
    $hiredResults = $hiredStmt->get_result();
    if ($hiredResults->num_rows > 0):
      while ($hiredRow = $hiredResults->fetch_assoc()):
    ?>
      <div class="hired-notification-banner" style="display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #d4ede5 0%, #e4f3e0 100%); border-left: 5px solid #16a34a; border-radius: 12px; padding: 18px 24px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); animation: slideDown 0.4s ease forwards;">
        <div style="display: flex; align-items: center; gap: 16px;">
          <div style="width: 42px; height: 42px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
            🎉
          </div>
          <div>
            <h4 style="margin: 0; font-size: 15px; font-weight: 800; color: #111;">Application Approved!</h4>
            <p style="margin: 3px 0 0; font-size: 13px; color: #444; line-height: 1.4;">
              Congratulations! Your application for <strong><?php echo htmlspecialchars($hiredRow['job_title']); ?></strong> at <strong><?php echo htmlspecialchars($hiredRow['company_name']); ?></strong> has been approved. You are hired!
            </p>
          </div>
        </div>
        <button onclick="this.parentElement.style.display='none';" style="background: none; border: none; font-size: 16px; font-weight: 700; color: #666; cursor: pointer; padding: 4px 8px; transition: color 0.2s;">✕</button>
      </div>
    <?php
      endwhile;
    endif;
    $hiredStmt->close();
    ?>

    <!-- ===== WELCOME SECTION ===== -->
    <section class="welcome-section">
      <div class="welcome-text">
        <h2>Welcome back, <?php echo htmlspecialchars($username); ?> </h2>
        <p>Here are jobs and courses picked just for you</p>
        <div class="interest-tag">
          <i class="fas fa-bullseye"></i>
          <span>Based on your interest in <?php echo htmlspecialchars($field); ?></span>
        </div>
      </div>

      <div class="welcome-stats">
        <div class="stat-card">
          <span class="stat-number">
            <?php echo $stats['job_matches']; ?>
          </span>
          <span class="stat-label">Job Matches</span>
        </div>
        <div class="stat-card">
          <span class="stat-number">
            <?php echo $stats['new_courses']; ?>
          </span>
          <span class="stat-label">New Courses</span>
        </div>
        <div class="stat-card">
          <span class="stat-number">
            <?php echo $stats['profile_complete']; ?>%
          </span>
          <span class="stat-label">Profile Complete</span>
        </div>
      </div>
    </section>

    <!-- ===== RECOMMENDED JOBS ===== -->
    <section class="section-block">
      <div class="section-header">
        <h3>
          <i class="fas fa-briefcase"></i>
          Recommended Jobs
        </h3>
        <a href="#" class="see-all-link">See all →</a>
      </div>

      <div class="jobs-grid">
        <?php foreach ($jobs as $job): ?>
          <?php
            // Match badge color logic
            if ($job['match'] >= 80)      $match_class = "match-high";
            elseif ($job['match'] >= 60)  $match_class = "match-medium";
            else                          $match_class = "match-low";

            // Match emoji
            if ($job['match'] >= 80)      $match_emoji = "";
            elseif ($job['match'] >= 60)  $match_emoji = "";
            else                          $match_emoji = "";
          ?>

          <div class="job-card" <?php if (!$job['applied']): ?>onclick="openJobModal(<?php echo $job['id']; ?>)"<?php endif; ?> style="<?php echo $job['applied'] ? 'cursor: default;' : ''; ?>">

            <div class="job-card-top">
              <div class="job-company-logo"
                style="background:<?php echo $job['logo_bg']; ?>;
                       color:<?php echo $job['logo_color']; ?>">
                <?php echo htmlspecialchars($job['logo_emoji']); ?>
              </div>
              <span class="match-badge <?php echo $match_class; ?>">
                <?php echo $match_emoji; ?> Match: <?php echo $job['match']; ?>%
              </span>
            </div>

            <div>
              <p class="job-title"><?php echo htmlspecialchars($job['title']); ?></p>
              <p class="job-company">
                <i class="fas fa-building"></i>
                <?php echo htmlspecialchars($job['company']); ?>
              </p>
            </div>

            <p class="job-description">
              <?php echo htmlspecialchars($job['description']); ?>
            </p>

            <div class="job-tags">
              <?php foreach ($job['tags'] as $tag): ?>
                <span class="job-tag"><?php echo htmlspecialchars($tag); ?></span>
              <?php endforeach; ?>
            </div>

            <?php if ($job['hired']): ?>
            <button class="view-job-btn hired-btn" disabled style="background: #d4ede5; color: #1a5c42; border: 1.5px solid #1a5c42; cursor: not-allowed; pointer-events: none; width: 100%; font-weight: bold;">
              Hired 🎉
            </button>
            <?php elseif ($job['applied']): ?>
            <button class="view-job-btn applied-btn" disabled style="background: #e5e7eb; color: #9ca3af; border: none; cursor: not-allowed; pointer-events: none; width: 100%;">
              Applied <i class="fas fa-check"></i>
            </button>
            <?php else: ?>
            <button class="view-job-btn"
              onclick="event.stopPropagation(); openJobModal(<?php echo $job['id']; ?>)">
              View Job →
            </button>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ===== RECOMMENDED COURSES ===== -->
    <section class="section-block">
      <div class="section-header">
        <h3>
          <i class="fas fa-graduation-cap"></i>
          Improve Your Skills
        </h3>
        <a href="#" class="see-all-link">See all →</a>
      </div>

      <div class="courses-grid">
        <?php foreach ($courses as $course): ?>
          <div class="course-card">

            <div class="course-icon"
              style="background:<?php echo $course['icon_bg']; ?>">
              <?php echo $course['icon']; ?>
            </div>

            <div>
              <p class="course-title">
                <?php echo htmlspecialchars($course['title']); ?>
              </p>
              <p class="course-skill">
                <?php echo htmlspecialchars($course['skill']); ?>
              </p>
            </div>

            <div class="course-meta">
              <span class="difficulty-badge <?php echo $course['diff_class']; ?>">
                <?php echo htmlspecialchars($course['difficulty']); ?>
              </span>
              <span class="course-duration">
                <i class="fas fa-clock"></i>
                <?php echo htmlspecialchars($course['duration']); ?>
              </span>
            </div>

            <?php if ($course['enrolled']): ?>
            <button class="start-course-btn enrolled-btn" disabled>
              <i class="fas fa-check"></i> Completed
            </button>
            <?php else: ?>
            <a href="course_details.php?id=<?php echo $course['id']; ?>" class="start-course-btn" style="text-decoration:none; display:inline-block; text-align:center;">
              <i class="fas fa-play"></i> Start Course
            </a>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ===== PROFILE SUMMARY ===== -->
    <section class="section-block">
      <div class="section-header">
        <h3>
          <i class="fas fa-user-circle"></i>
          Your Profile
        </h3>
        <a href="profile.php" class="see-all-link">Edit Profile →</a>
      </div>

      <div class="profile-summary">

        <!-- Left: Info -->
        <div class="profile-info">
          <div class="profile-avatar-large">
            <?php echo htmlspecialchars(strtoupper($username[0])); ?>
          </div>

          <div class="profile-details">
            <h4><?php echo htmlspecialchars($fullname); ?></h4>
            <p>
              <i class="fas fa-code"></i>
              <?php echo htmlspecialchars($field); ?>
            </p>
            <p>
              <i class="fas fa-envelope"></i>
              <?php echo htmlspecialchars($email); ?>
            </p>

            <div class="profile-completion">
              <span>Profile Completion</span>
              <div class="completion-bar">
                <div class="completion-fill"
                  style="width: <?php echo $stats['profile_complete']; ?>%">
                </div>
              </div>
              <span class="completion-pct">
                <?php echo $stats['profile_complete']; ?>%
              </span>
            </div>
          </div>
        </div>

        <!-- Right: Skill Bars -->
        <div class="profile-skills">
          <h4>Skill Overview</h4>

          <?php foreach ($skills as $skill): ?>
            <div class="skill-row">
              <div class="skill-label-row">
                <span class="skill-name">
                  <?php echo htmlspecialchars($skill['name']); ?>
                </span>
                <span class="skill-pct">
                  <?php echo $skill['level']; ?>%
                </span>
              </div>
              <div class="skill-bar">
                <!-- data-target used by JS to animate the bar -->
                <div class="skill-fill"
                  style="width: 0%; background: <?php echo $skill['color']; ?>"
                  data-target="<?php echo $skill['level']; ?>">
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>
    </section>

  </main>

  <!-- ===== JOB DETAIL MODAL ===== -->
  <div class="modal-overlay" id="modalOverlay" onclick="closeModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
      <button class="modal-close" onclick="closeModal()">
        <i class="fas fa-times"></i>
      </button>
      <div class="modal-content" id="modalContent"></div>
    </div>
  </div>

  <!-- ============================================================
       Pass PHP jobs data to JavaScript as a JSON object
       When DB is ready, $jobs will already be populated above
       so this block needs NO changes — it just works
       ============================================================ -->
  <script>
    const jobsData = <?php echo json_encode($jobs); ?>;
  </script>

  <script src="js/dashboard.js"></script>

</body>
</html>