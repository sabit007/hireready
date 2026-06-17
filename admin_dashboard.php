<?php
// ============================================================
//  HireReady Admin — Dashboard  (dashboard.php)
//  Place this file inside:  C:/xampp/htdocs/hireready/
//  Access via:              http://localhost/hireready/dashboard.php
// ============================================================

session_start();

// ── Database config ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hireready_db');

// ── Auth guard ───────────────────────────────────────────────
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// ── DB connection helper ─────────────────────────────────────
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'DB error: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── Session shorthand ────────────────────────────────────────
$adminId      = (int)$_SESSION['admin_id'];
$adminName    = htmlspecialchars($_SESSION['admin_name']    ?? 'Admin');
$adminRole    = htmlspecialchars($_SESSION['admin_role']    ?? '');
$companyName  = htmlspecialchars($_SESSION['company_name']  ?? '');
$adminEmail   = htmlspecialchars($_SESSION['admin_email']   ?? '');
$adminInitials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $adminName), 0, 2))));

// ════════════════════════════════════════════════════════════
//  AJAX HANDLER  – all POST requests with action= param
// ════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $db     = getDB();

    // ── Helper ────────────────────────────────────────────
    function jsonOut($data) { echo json_encode($data); exit; }
    function required($key) { return trim($_POST[$key] ?? ''); }

    // ════════════════════════
    //  JOBS
    // ════════════════════════

    if ($action === 'add_job') {
        $title       = required('title');
        $type        = required('type');
        $location    = required('location');
        $salary      = required('salary');
        $skills      = required('skills');
        $description = required('description');

        if (!$title || !$type || !$location) {
            jsonOut(['success' => false, 'message' => 'Title, type, and location are required.']);
        }

        $stmt = $db->prepare("INSERT INTO jobs (admin_id, title, job_type, location, salary, skills, description, status, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->bind_param('issssss', $adminId, $title, $type, $location, $salary, $skills, $description);
        if ($stmt->execute()) {
            jsonOut(['success' => true, 'job_id' => $db->insert_id]);
        }
        jsonOut(['success' => false, 'message' => $db->error]);
    }

    if ($action === 'edit_job') {
        $jobId       = (int)required('job_id');
        $title       = required('title');
        $type        = required('type');
        $location    = required('location');
        $salary      = required('salary');
        $skills      = required('skills');
        $description = required('description');

        $stmt = $db->prepare("UPDATE jobs SET title=?, job_type=?, location=?, salary=?, skills=?, description=? WHERE id=? AND admin_id=?");
        $stmt->bind_param('ssssssii', $title, $type, $location, $salary, $skills, $description, $jobId, $adminId);
        $stmt->execute();
        jsonOut(['success' => true]);
    }

    if ($action === 'close_job') {
        $jobId = (int)required('job_id');
        $stmt  = $db->prepare("UPDATE jobs SET status='closed' WHERE id=? AND admin_id=?");
        $stmt->bind_param('ii', $jobId, $adminId);
        $stmt->execute();
        jsonOut(['success' => true]);
    }

    if ($action === 'reopen_job') {
        $jobId = (int)required('job_id');
        $stmt  = $db->prepare("UPDATE jobs SET status='active' WHERE id=? AND admin_id=?");
        $stmt->bind_param('ii', $jobId, $adminId);
        $stmt->execute();
        jsonOut(['success' => true]);
    }

    if ($action === 'delete_job') {
        $jobId = (int)required('job_id');
        $stmt  = $db->prepare("DELETE FROM jobs WHERE id=? AND admin_id=?");
        $stmt->bind_param('ii', $jobId, $adminId);
        $stmt->execute();
        jsonOut(['success' => true]);
    }

    // ════════════════════════
    //  QUIZZES
    // ════════════════════════

    if ($action === 'add_quiz') {
        $jobId     = (int)required('job_id');
        $title     = required('title');
        $topics    = required('topics');
        $passMark  = (int)required('pass_mark');
        $timeLimit = (int)required('time_limit');
        $questions = json_decode($_POST['questions'] ?? '[]', true);

        $stmt = $db->prepare("INSERT INTO quizzes (admin_id, job_id, title, pass_mark, time_limit, status, created_at)
                              VALUES (?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->bind_param('iisii', $adminId, $jobId, $title, $passMark, $timeLimit);
        if (!$stmt->execute()) jsonOut(['success' => false, 'message' => $db->error]);
        $quizId = $db->insert_id;

        // Insert quiz topics
        if ($topics) {
            $topicIds = explode(',', $topics);
            $qtStmt = $db->prepare("INSERT INTO quiz_topics (quiz_id, topic_id) VALUES (?, ?)");
            foreach ($topicIds as $tid) {
                $tid = (int)trim($tid);
                if ($tid > 0) {
                    $qtStmt->bind_param('ii', $quizId, $tid);
                    $qtStmt->execute();
                }
            }
        }

        // Insert questions
        if (is_array($questions)) {
            $qStmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, mark, options_json, correct_answer)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $qTopicStmt = $db->prepare("INSERT INTO question_topics (question_id, topic_id) VALUES (?, ?)");
            foreach ($questions as $q) {
                $qText   = $q['text']    ?? '';
                $qType   = $q['type']    ?? 'MCQ';
                $qMark   = (int)($q['mark'] ?? 1);
                $opts    = json_encode($q['options'] ?? []);
                $correct = $q['correct'] ?? '';
                $qStmt->bind_param('ississ', $quizId, $qText, $qType, $qMark, $opts, $correct);
                if ($qStmt->execute()) {
                    $qId = $qStmt->insert_id;
                    // Assign topics to question
                    if (!empty($q['topics'])) {
                        foreach ($q['topics'] as $qTid) {
                            $qTid = (int)$qTid;
                            if ($qTid > 0) {
                                $qTopicStmt->bind_param('ii', $qId, $qTid);
                                $qTopicStmt->execute();
                            }
                        }
                    }
                }
            }
        }

        jsonOut(['success' => true, 'quiz_id' => $quizId]);
    }

    if ($action === 'edit_quiz') {
        $quizId    = (int)required('quiz_id');
        $title     = required('title');
        $topics    = required('topics');
        $passMark  = (int)required('pass_mark');
        $timeLimit = (int)required('time_limit');
        $questions = json_decode($_POST['questions'] ?? '[]', true);

        $stmt = $db->prepare("UPDATE quizzes SET title=?, pass_mark=?, time_limit=? WHERE id=? AND admin_id=?");
        $stmt->bind_param('siiii', $title, $passMark, $timeLimit, $quizId, $adminId);
        $stmt->execute();

        // Update quiz topics
        $db->query("DELETE FROM quiz_topics WHERE quiz_id = $quizId");
        if ($topics) {
            $topicIds = explode(',', $topics);
            $qtStmt = $db->prepare("INSERT INTO quiz_topics (quiz_id, topic_id) VALUES (?, ?)");
            foreach ($topicIds as $tid) {
                $tid = (int)trim($tid);
                if ($tid > 0) {
                    $qtStmt->bind_param('ii', $quizId, $tid);
                    $qtStmt->execute();
                }
            }
        }

        // Replace questions
        $db->query("DELETE FROM quiz_questions WHERE quiz_id = $quizId");
        if (is_array($questions)) {
            $qStmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, mark, options_json, correct_answer)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $qTopicStmt = $db->prepare("INSERT INTO question_topics (question_id, topic_id) VALUES (?, ?)");
            foreach ($questions as $q) {
                $qText   = $q['text']    ?? '';
                $qType   = $q['type']    ?? 'MCQ';
                $qMark   = (int)($q['mark'] ?? 1);
                $opts    = json_encode($q['options'] ?? []);
                $correct = $q['correct'] ?? '';
                $qStmt->bind_param('ississ', $quizId, $qText, $qType, $qMark, $opts, $correct);
                if ($qStmt->execute()) {
                    $qId = $qStmt->insert_id;
                    // Assign topics to question
                    if (!empty($q['topics'])) {
                        foreach ($q['topics'] as $qTid) {
                            $qTid = (int)$qTid;
                            if ($qTid > 0) {
                                $qTopicStmt->bind_param('ii', $qId, $qTid);
                                $qTopicStmt->execute();
                            }
                        }
                    }
                }
            }
        }

        jsonOut(['success' => true]);
    }

    if ($action === 'close_quiz') {
        $quizId = (int)required('quiz_id');
        $stmt   = $db->prepare("UPDATE quizzes SET status='closed' WHERE id=? AND admin_id=?");
        $stmt->bind_param('ii', $quizId, $adminId);
        $stmt->execute();
        jsonOut(['success' => true]);
    }

    // ════════════════════════
    //  APPLICANTS
    // ════════════════════════

    if ($action === 'decide_applicant') {
        $appId    = (int)required('applicant_id');
        $decision = required('decision'); // 'approved' or 'rejected'
        if (!in_array($decision, ['approved', 'rejected'])) jsonOut(['success' => false, 'message' => 'Invalid decision.']);

        $stmt = $db->prepare("UPDATE applicants SET status=? WHERE id=? AND job_id IN (SELECT id FROM jobs WHERE admin_id=?)");
        $stmt->bind_param('sii', $decision, $appId, $adminId);
        $stmt->execute();
        jsonOut(['success' => true]);
    }

    if ($action === 'get_applicant_cv') {
        $applicantUserId = (int)required('user_id');
        $stmt = $db->prepare("SELECT id, full_name AS name, email, phone, cv_data FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $applicantUserId);
        $stmt->execute();
        $userRow = $stmt->get_result()->fetch_assoc();

        if ($userRow) {
            $userRow['cv_data_decoded'] = json_decode($userRow['cv_data'] ?? 'null', true);
            jsonOut(['success' => true, 'user' => $userRow]);
        } else {
            jsonOut(['success' => false, 'message' => 'Applicant profile not found.']);
        }
    }

    // ════════════════════════
    //  COURSES
    // ════════════════════════

    if ($action === 'add_course') {
        $title      = required('title');
        $desc       = required('description');
        $topics     = required('topics');
        $instructor = required('instructor');
        $duration   = required('duration');
        $modules    = required('modules');

        if (!$title || !$instructor) jsonOut(['success' => false, 'message' => 'Title and instructor are required.']);

        $stmt = $db->prepare("INSERT INTO courses (admin_id, title, description, instructor, duration, modules, status, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->bind_param('isssss', $adminId, $title, $desc, $instructor, $duration, $modules);
        if ($stmt->execute()) {
            $courseId = $db->insert_id;
            
            // Insert course topics
            if ($topics) {
                $topicIds = explode(',', $topics);
                $ctStmt = $db->prepare("INSERT INTO course_topics (course_id, topic_id) VALUES (?, ?)");
                foreach ($topicIds as $tid) {
                    $tid = (int)trim($tid);
                    if ($tid > 0) {
                        $ctStmt->bind_param('ii', $courseId, $tid);
                        $ctStmt->execute();
                    }
                }
            }
            jsonOut(['success' => true, 'course_id' => $courseId]);
        }
        jsonOut(['success' => false, 'message' => $db->error]);
    }

    if ($action === 'toggle_course') {
        $courseId  = (int)required('course_id');
        $newStatus = required('new_status'); // 'active' or 'closed'
        $stmt = $db->prepare("UPDATE courses SET status=? WHERE id=? AND admin_id=?");
        $stmt->bind_param('sii', $newStatus, $courseId, $adminId);
        $stmt->execute();
        jsonOut(['success' => true]);
    }

    // ════════════════════════
    //  SETTINGS
    // ════════════════════════

    if ($action === 'save_company') {
        $cName    = required('company_name');
        $cAddress = required('company_address');
        if (!$cName) jsonOut(['success' => false, 'message' => 'Company name is required.']);

        $stmt = $db->prepare("UPDATE admins SET company_name=?, company_address=? WHERE id=?");
        $stmt->bind_param('ssi', $cName, $cAddress, $adminId);
        $stmt->execute();
        $_SESSION['company_name'] = $cName;
        jsonOut(['success' => true]);
    }

    if ($action === 'save_profile') {
        $repName = required('rep_name');
        $repRole = required('rep_role');
        $email   = required('email');
        $phone   = required('phone');

        if (!$repName || !$email) jsonOut(['success' => false, 'message' => 'Name and email are required.']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonOut(['success' => false, 'message' => 'Invalid email.']);

        // Check email uniqueness (exclude current admin)
        $chk = $db->prepare("SELECT id FROM admins WHERE email=? AND id != ? LIMIT 1");
        $chk->bind_param('si', $email, $adminId);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) jsonOut(['success' => false, 'message' => 'Email already used by another account.']);

        $stmt = $db->prepare("UPDATE admins SET rep_name=?, rep_role=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param('ssssi', $repName, $repRole, $email, $phone, $adminId);
        $stmt->execute();
        $_SESSION['admin_name']  = $repName;
        $_SESSION['admin_role']  = $repRole;
        $_SESSION['admin_email'] = $email;
        jsonOut(['success' => true, 'name' => $repName, 'role' => $repRole]);
    }

    if ($action === 'change_password') {
        $curPw   = $_POST['current_password'] ?? '';
        $newPw   = $_POST['new_password']     ?? '';
        $confPw  = $_POST['confirm_password'] ?? '';

        if (!$curPw || !$newPw) jsonOut(['success' => false, 'message' => 'All password fields are required.']);
        if ($newPw !== $confPw) jsonOut(['success' => false, 'message' => 'New passwords do not match.']);
        if (strlen($newPw) < 8) jsonOut(['success' => false, 'message' => 'New password must be at least 8 characters.']);
        if (!preg_match('/[A-Z]/', $newPw) || !preg_match('/[a-z]/', $newPw) || !preg_match('/[0-9]/', $newPw)) {
            jsonOut(['success' => false, 'message' => 'Password must contain uppercase, lowercase, and a number.']);
        }

        $res  = $db->query("SELECT password_hash FROM admins WHERE id=$adminId LIMIT 1");
        $row  = $res->fetch_assoc();
        if (!password_verify($curPw, $row['password_hash'])) {
            jsonOut(['success' => false, 'message' => 'Current password is incorrect.']);
        }

        $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("UPDATE admins SET password_hash=? WHERE id=?");
        $stmt->bind_param('si', $hash, $adminId);
        $stmt->execute();
        jsonOut(['success' => true]);
    }

    $db->close();
    jsonOut(['success' => false, 'message' => 'Unknown action.']);
}

// ════════════════════════════════════════════════════════════
//  DATA FETCH  for page render
// ════════════════════════════════════════════════════════════
$db = getDB();

// ── Jobs ──────────────────────────────────────────────────
$jobsResult = $db->query("
    SELECT j.*,
           COUNT(DISTINCT a.id)                                              AS total_applicants,
           SUM(CASE WHEN a.quiz_passed = 1 THEN 1 ELSE 0 END)               AS pass_count,
           SUM(CASE WHEN a.quiz_passed = 0 AND a.id IS NOT NULL THEN 1 ELSE 0 END) AS fail_count
    FROM   jobs j
    LEFT JOIN applicants a ON a.job_id = j.id
    WHERE  j.admin_id = $adminId
    GROUP  BY j.id
    ORDER  BY total_applicants DESC
");
$jobs = $jobsResult ? $jobsResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Quizzes ───────────────────────────────────────────────
$quizzesResult = $db->query("
    SELECT q.*, j.title AS job_title,
           COUNT(qq.id) AS question_count
    FROM   quizzes q
    LEFT JOIN jobs j ON j.id = q.job_id
    LEFT JOIN quiz_questions qq ON qq.quiz_id = q.id
    WHERE  q.admin_id = $adminId
    GROUP  BY q.id
    ORDER  BY q.created_at DESC
");
$quizzes = $quizzesResult ? $quizzesResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Applicants (quiz-passed only) ─────────────────────────
$applicantsResult = $db->query("
    SELECT a.*, j.title AS job_title
    FROM   applicants a
    JOIN   jobs j ON j.id = a.job_id
    WHERE  j.admin_id = $adminId AND a.quiz_passed = 1
    ORDER  BY a.quiz_score DESC
");
$applicants = $applicantsResult ? $applicantsResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Courses ───────────────────────────────────────────────
$coursesResult = $db->query("
    SELECT c.*,
           COUNT(ce.id) AS participants
    FROM   courses c
    LEFT JOIN course_enrollments ce ON ce.course_id = c.id
    WHERE  c.admin_id = $adminId
    GROUP  BY c.id
    ORDER  BY participants DESC
");
$courses = $coursesResult ? $coursesResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Overview stats ────────────────────────────────────────
$totalJobsPosted = (int)($db->query("SELECT COUNT(*) c FROM jobs WHERE admin_id=$adminId")->fetch_assoc()['c'] ?? 0);
$activeJobs      = (int)($db->query("SELECT COUNT(*) c FROM jobs WHERE admin_id=$adminId AND status='active'")->fetch_assoc()['c'] ?? 0);
$totalCourses    = count($courses);
$activeCourses   = count(array_filter($courses, fn($c) => $c['status'] === 'active'));
$totalApplicants = (int)($db->query("SELECT COUNT(*) c FROM applicants a JOIN jobs j ON j.id=a.job_id WHERE j.admin_id=$adminId")->fetch_assoc()['c'] ?? 0);
$quizPassedCount = count($applicants);
$pendingReview   = count(array_filter($applicants, fn($a) => ($a['status'] ?? 'pending') === 'pending'));

// ── Topics ────────────────────────────────────────────────
$topicsResult = $db->query("SELECT * FROM topics ORDER BY category, name");
$allTopics = $topicsResult ? $topicsResult->fetch_all(MYSQLI_ASSOC) : [];
$topicsByCategory = [];
foreach ($allTopics as $t) {
    $topicsByCategory[$t['category']][] = $t;
}

// ── Fetch Quizzes with their assigned Topics ──────────────
foreach ($quizzes as &$q) {
    $qId = $q['id'];
    $qtRes = $db->query("SELECT t.id, t.name FROM quiz_topics qt JOIN topics t ON t.id = qt.topic_id WHERE qt.quiz_id = $qId");
    $qTopics = $qtRes ? $qtRes->fetch_all(MYSQLI_ASSOC) : [];
    $q['topic_ids'] = array_column($qTopics, 'id');
    $q['topic_names'] = array_column($qTopics, 'name');
    
    // Fetch questions and their topics
    $qqRes = $db->query("SELECT qq.* FROM quiz_questions qq WHERE qq.quiz_id = $qId");
    $q['questions'] = $qqRes ? $qqRes->fetch_all(MYSQLI_ASSOC) : [];
    foreach ($q['questions'] as &$qq) {
        $qqId = $qq['id'];
        $qqtRes = $db->query("SELECT topic_id FROM question_topics WHERE question_id = $qqId");
        $qqTopics = $qqtRes ? $qqtRes->fetch_all(MYSQLI_ASSOC) : [];
        $qq['topic_ids'] = array_column($qqTopics, 'topic_id');
    }
}
unset($q);

// ── Fetch Courses with their assigned Topics ──────────────
foreach ($courses as &$c) {
    $cId = $c['id'];
    $ctRes = $db->query("SELECT t.id, t.name FROM course_topics ct JOIN topics t ON t.id = ct.topic_id WHERE ct.course_id = $cId");
    $cTopics = $ctRes ? $ctRes->fetch_all(MYSQLI_ASSOC) : [];
    $c['topic_ids'] = array_column($cTopics, 'id');
    $c['topic_names'] = array_column($cTopics, 'name');
}
unset($c);

// ── Admin profile for settings panel ─────────────────────
$adminProfile = $db->query("SELECT * FROM admins WHERE id=$adminId LIMIT 1")->fetch_assoc();
$companyAddress = htmlspecialchars($adminProfile['company_address'] ?? '');
$adminPhone     = htmlspecialchars($adminProfile['phone'] ?? '');
$companyNameFull = htmlspecialchars($adminProfile['company_name'] ?? $companyName);

$db->close();

// ── Helper: format topics chips ──────────────────────────
function topicChips(string $topics): string {
    $chips = '';
    foreach (explode(',', $topics) as $t) {
        $t = trim(htmlspecialchars($t));
        if ($t) $chips .= "<span class=\"topic-chip\">$t</span>";
    }
    return $chips;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HireReady — Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --color-bg:            #ffffff;
      --color-surface:       #f7f7f7;
      --color-surface-2:     #f0f0f0;
      --color-border:        #e4e4e4;
      --color-text-primary:  #0a0a0a;
      --color-text-muted:    #6b6b6b;
      --color-text-subtle:   #9b9b9b;
      --color-accent:        #0a0a0a;
      --color-accent-hover:  #1f1f1f;
      --color-success-bg:    #f0fdf4;
      --color-success-text:  #166534;
      --color-success-border:#bbf7d0;
      --color-warning-bg:    #fffbeb;
      --color-warning-text:  #92400e;
      --color-warning-border:#fde68a;
      --color-danger-bg:     #fef2f2;
      --color-danger-text:   #c0392b;
      --color-danger-border: #fecaca;
      --sidebar-width:       240px;
      --topbar-height:       60px;
      --font-family:         'DM Sans', sans-serif;
      --radius-sm:           6px;
      --radius-md:           8px;
      --radius-lg:           12px;
      --transition:          0.18s ease;
    }
    html,body{height:100%;}
    body{font-family:var(--font-family);background:var(--color-surface);color:var(--color-text-primary);overflow:hidden;}
    .shell{display:flex;height:100vh;overflow:hidden;}

    /* SIDEBAR */
    .sidebar{width:var(--sidebar-width);flex-shrink:0;background:#0a0a0a;display:flex;flex-direction:column;overflow:hidden;}
    .sidebar-logo{padding:1.25rem 1.5rem;display:flex;align-items:baseline;gap:8px;border-bottom:1px solid #1a1a1a;}
    .logo-wordmark{font-size:17px;font-weight:700;letter-spacing:-0.4px;color:#fff;}
    .logo-badge{font-size:9px;font-weight:600;letter-spacing:0.8px;text-transform:uppercase;color:#555;border:1px solid #2a2a2a;border-radius:var(--radius-sm);padding:2px 5px;}
    .sidebar-nav{flex:1;padding:1rem 0;overflow-y:auto;}
    .nav-section-label{font-size:9.5px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:#333;padding:.75rem 1.5rem .35rem;}
    .nav-item{display:flex;align-items:center;gap:10px;padding:9px 1.5rem;cursor:pointer;transition:background var(--transition),color var(--transition);color:#777;font-size:13.5px;font-weight:500;border:none;background:none;width:100%;text-align:left;}
    .nav-item svg{flex-shrink:0;}
    .nav-item:hover{background:#141414;color:#ccc;}
    .nav-item.active{background:#1a1a1a;color:#fff;}
    .nav-item .nav-badge{margin-left:auto;background:#1f1f1f;border:1px solid #2a2a2a;border-radius:999px;font-size:10px;font-weight:600;color:#888;padding:1px 7px;}
    .nav-item.active .nav-badge{background:#2a2a2a;color:#aaa;}
    .sidebar-footer{border-top:1px solid #1a1a1a;padding:1rem 1.5rem;}
    .admin-info{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
    .admin-avatar{width:34px;height:34px;border-radius:50%;background:#1f1f1f;border:1px solid #2a2a2a;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#aaa;flex-shrink:0;}
    .admin-meta{flex:1;min-width:0;}
    .admin-name{font-size:12.5px;font-weight:600;color:#ccc;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .admin-role{font-size:11px;color:#444;}
    .btn-settings,.btn-logout{display:flex;align-items:center;gap:8px;width:100%;padding:8px 12px;background:#111;border:1px solid #1e1e1e;border-radius:var(--radius-md);color:#555;font-size:12.5px;font-weight:500;cursor:pointer;transition:all var(--transition);font-family:var(--font-family);margin-bottom:6px;}
    .btn-logout{margin-bottom:0;}
    .btn-settings:hover,.btn-logout:hover{background:#1a1a1a;color:#aaa;}

    /* MAIN */
    .main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
    .topbar{height:var(--topbar-height);background:var(--color-bg);border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;padding:0 1.75rem;flex-shrink:0;}
    .topbar-left h1{font-size:16px;font-weight:700;letter-spacing:-0.2px;}
    .topbar-left p{font-size:12px;color:var(--color-text-muted);margin-top:1px;}
    .topbar-right{display:flex;align-items:center;gap:10px;}
    .topbar-btn{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--radius-md);font-family:var(--font-family);font-size:13px;font-weight:600;cursor:pointer;transition:all var(--transition);border:1.5px solid var(--color-border);background:var(--color-bg);color:var(--color-text-primary);}
    .topbar-btn.primary{background:var(--color-accent);color:#fff;border-color:var(--color-accent);}
    .topbar-btn:hover{opacity:.8;}
    .content{flex:1;overflow-y:auto;padding:1.75rem;}
    .section{display:none;}
    .section.active{display:block;}

    /* STATS */
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;}
    .stat-card{background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:1.25rem 1.5rem;}
    .stat-label{font-size:11.5px;font-weight:600;color:var(--color-text-muted);letter-spacing:.3px;text-transform:uppercase;margin-bottom:.5rem;}
    .stat-value{font-size:28px;font-weight:700;letter-spacing:-.5px;line-height:1;}
    .stat-sub{font-size:12px;color:var(--color-text-muted);margin-top:5px;}
    .stat-sub span{font-weight:600;color:var(--color-text-primary);}
    .overview-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
    .overview-card{background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:1.25rem 1.5rem;}
    .overview-card-title{font-size:13px;font-weight:600;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;}
    .overview-card-title a{font-size:11.5px;font-weight:500;color:var(--color-text-muted);text-decoration:none;}
    .overview-card-title a:hover{color:var(--color-text-primary);}
    .mini-list{display:flex;flex-direction:column;gap:10px;}
    .mini-row{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--color-surface);border-radius:var(--radius-md);border:1px solid var(--color-border);}
    .mini-row-left{display:flex;flex-direction:column;gap:2px;}
    .mini-row-title{font-size:13px;font-weight:600;}
    .mini-row-sub{font-size:11.5px;color:var(--color-text-muted);}
    .mini-row-right{display:flex;align-items:center;gap:8px;}
    .empty-mini{font-size:12.5px;color:var(--color-text-subtle);text-align:center;padding:1rem 0;}

    /* TABLE */
    .section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;}
    .section-header-left h2{font-size:16px;font-weight:700;letter-spacing:-.2px;}
    .section-header-left p{font-size:12.5px;color:var(--color-text-muted);margin-top:2px;}
    .table-card{background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-lg);overflow:hidden;}
    .table-toolbar{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--color-border);gap:10px;}
    .search-input{display:flex;align-items:center;gap:8px;background:var(--color-surface);border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:7px 12px;flex:1;max-width:280px;}
    .search-input input{border:none;background:none;outline:none;font-family:var(--font-family);font-size:13px;color:var(--color-text-primary);width:100%;}
    .search-input input::placeholder{color:var(--color-text-subtle);}
    .filter-btn{display:flex;align-items:center;gap:6px;padding:7px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-md);background:var(--color-bg);font-family:var(--font-family);font-size:12.5px;font-weight:500;cursor:pointer;color:var(--color-text-primary);transition:all var(--transition);}
    .filter-btn:hover{background:var(--color-surface);}
    .filter-btn.active{background:var(--color-accent);color:#fff;border-color:var(--color-accent);}
    .filter-group{display:flex;align-items:center;gap:6px;}
    table{width:100%;border-collapse:collapse;font-size:13px;}
    thead th{padding:10px 16px;text-align:left;font-size:11px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--color-text-muted);background:var(--color-surface);border-bottom:1px solid var(--color-border);white-space:nowrap;}
    tbody tr{border-bottom:1px solid var(--color-border);transition:background var(--transition);}
    tbody tr:last-child{border-bottom:none;}
    tbody tr:hover{background:var(--color-surface);}
    td{padding:12px 16px;color:var(--color-text-primary);vertical-align:middle;}
    td .cell-main{font-weight:600;font-size:13px;}
    td .cell-sub{font-size:11.5px;color:var(--color-text-muted);margin-top:1px;}
    .empty-state{text-align:center;padding:3rem 1rem;}
    .empty-state p{font-size:13.5px;color:var(--color-text-muted);margin-top:.5rem;}

    /* BADGES */
    .badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;border-radius:999px;padding:3px 9px;white-space:nowrap;}
    .badge-active{background:var(--color-success-bg);color:var(--color-success-text);border:1px solid var(--color-success-border);}
    .badge-inactive{background:var(--color-surface);color:var(--color-text-muted);border:1px solid var(--color-border);}
    .badge-pending{background:var(--color-warning-bg);color:var(--color-warning-text);border:1px solid var(--color-warning-border);}
    .badge-type{background:var(--color-surface);color:var(--color-text-muted);border:1px solid var(--color-border);font-size:10.5px;padding:2px 8px;border-radius:var(--radius-sm);}
    .dot{width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0;}

    /* ACTIONS */
    .action-group{display:flex;align-items:center;gap:6px;}
    .btn-action{padding:5px 11px;border-radius:var(--radius-sm);font-family:var(--font-family);font-size:12px;font-weight:600;cursor:pointer;transition:all var(--transition);border:1.5px solid var(--color-border);background:var(--color-bg);color:var(--color-text-primary);}
    .btn-action:hover{background:var(--color-surface);}
    .btn-action.danger{color:var(--color-danger-text);border-color:var(--color-danger-border);background:var(--color-danger-bg);}
    .btn-action.success{color:var(--color-success-text);border-color:var(--color-success-border);background:var(--color-success-bg);}
    .btn-action.dark{background:var(--color-accent);color:#fff;border-color:var(--color-accent);}
    .btn-action.dark:hover{background:var(--color-accent-hover);}

    /* SCORE */
    .score-wrap{display:flex;align-items:center;gap:8px;}
    .score-bar{width:70px;height:5px;background:var(--color-border);border-radius:999px;overflow:hidden;}
    .score-fill{height:100%;background:var(--color-accent);border-radius:999px;}
    .score-text{font-size:12.5px;font-weight:600;}

    /* MODAL */
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center;z-index:1000;padding:1.5rem;}
    .modal-overlay.open{display:flex;}
    .modal{background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-lg);width:100%;max-width:560px;max-height:90vh;overflow-y:auto;animation:modalIn .18s ease;}
    @keyframes modalIn{from{opacity:0;transform:scale(.97) translateY(8px);}to{opacity:1;transform:scale(1) translateY(0);}}
    @keyframes slideUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
    .modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--color-border);}
    .modal-header h3{font-size:15px;font-weight:700;letter-spacing:-.2px;}
    .modal-header p{font-size:12px;color:var(--color-text-muted);margin-top:2px;}
    .modal-close{background:none;border:none;cursor:pointer;color:var(--color-text-subtle);padding:4px;border-radius:var(--radius-sm);display:flex;transition:all var(--transition);}
    .modal-close:hover{background:var(--color-surface);color:var(--color-text-primary);}
    .modal-body{padding:1.5rem;display:flex;flex-direction:column;gap:14px;}
    .modal-footer{display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:1rem 1.5rem;border-top:1px solid var(--color-border);}

    /* STEPS */
    .steps{display:flex;align-items:center;gap:6px;padding:0 1.5rem 1rem;}
    .step-item{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:var(--color-text-subtle);}
    .step-item.active{color:var(--color-text-primary);}
    .step-num{width:22px;height:22px;border-radius:50%;border:1.5px solid var(--color-border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;}
    .step-item.active .step-num{background:var(--color-accent);color:#fff;border-color:var(--color-accent);}
    .step-item.done  .step-num{background:var(--color-success-bg);color:var(--color-success-text);border-color:var(--color-success-border);}
    .step-divider{height:1px;flex:1;background:var(--color-border);}

    /* FORM FIELDS */
    .field{display:flex;flex-direction:column;gap:6px;}
    .field label{font-size:12.5px;font-weight:600;color:var(--color-text-primary);}
    .field input,.field select,.field textarea{width:100%;border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:9px 12px;font-family:var(--font-family);font-size:13px;color:var(--color-text-primary);background:var(--color-bg);outline:none;transition:border-color var(--transition),box-shadow var(--transition);}
    .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--color-accent);box-shadow:0 0 0 3px rgba(10,10,10,.07);}
    .field textarea{resize:vertical;min-height:80px;}
    .field input::placeholder,.field textarea::placeholder{color:var(--color-text-subtle);}
    .field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .field-hint{font-size:11.5px;color:var(--color-text-muted);}

    /* TAGS */
    .tags-wrap{display:flex;flex-wrap:wrap;gap:6px;border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:7px 10px;min-height:42px;align-items:center;transition:border-color var(--transition);cursor:text;}
    .tags-wrap:focus-within{border-color:var(--color-accent);box-shadow:0 0 0 3px rgba(10,10,10,.07);}
    .tag-pill{display:flex;align-items:center;gap:5px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:999px;padding:2px 8px 2px 10px;font-size:12px;font-weight:500;}
    .tag-pill button{background:none;border:none;cursor:pointer;color:var(--color-text-subtle);font-size:14px;line-height:1;padding:0;}
    .tags-wrap input{border:none!important;outline:none!important;box-shadow:none!important;padding:0!important;font-family:var(--font-family);font-size:13px;min-width:80px;flex:1;background:transparent!important;}

    /* QUESTIONS */
    .question-block{background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:12px;display:flex;flex-direction:column;gap:10px;}
    .question-block-header{display:flex;align-items:center;justify-content:space-between;}
    .question-num{font-size:11.5px;font-weight:600;color:var(--color-text-muted);}
    .btn-icon{background:none;border:none;cursor:pointer;color:var(--color-text-subtle);padding:4px;border-radius:var(--radius-sm);display:flex;transition:all var(--transition);}
    .btn-icon:hover{background:var(--color-surface-2);color:var(--color-danger-text);}
    .btn-add-question{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:9px;border:1.5px dashed var(--color-border);border-radius:var(--radius-md);background:none;font-family:var(--font-family);font-size:13px;font-weight:500;color:var(--color-text-muted);cursor:pointer;transition:all var(--transition);}
    .btn-add-question:hover{border-color:var(--color-accent);color:var(--color-text-primary);background:var(--color-surface);}
    .answer-area{margin-top:4px;display:flex;flex-direction:column;gap:8px;}
    .mcq-option{display:flex;align-items:center;gap:8px;}
    .mcq-option-letter{width:26px;height:26px;border-radius:var(--radius-sm);background:var(--color-surface-2);border:1px solid var(--color-border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--color-text-muted);flex-shrink:0;}
    .mcq-opt-input{flex:1;height:36px;border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:0 10px;font-family:var(--font-family);font-size:13px;color:var(--color-text-primary);background:var(--color-bg);outline:none;transition:border-color var(--transition);}
    .mcq-opt-input:focus{border-color:var(--color-accent);}
    .mcq-option .btn-remove-opt{background:none;border:none;cursor:pointer;color:var(--color-text-subtle);font-size:16px;line-height:1;padding:0 2px;transition:color var(--transition);}
    .mcq-option .btn-remove-opt:hover{color:var(--color-danger-text);}
    .btn-add-option{display:flex;align-items:center;gap:5px;background:none;border:1.5px dashed var(--color-border);border-radius:var(--radius-md);padding:6px 10px;font-family:var(--font-family);font-size:12px;font-weight:500;color:var(--color-text-muted);cursor:pointer;transition:all var(--transition);width:fit-content;}
    .btn-add-option:hover{border-color:var(--color-accent);color:var(--color-text-primary);}
    .tf-options{display:flex;gap:10px;}
    .tf-option{display:flex;align-items:center;gap:7px;padding:8px 16px;border:1.5px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;font-size:13px;font-weight:500;color:var(--color-text-muted);background:var(--color-bg);transition:all var(--transition);user-select:none;}
    .tf-option input[type="radio"]{width:14px;height:14px;accent-color:var(--color-accent);cursor:pointer;}
    .tf-option:has(input:checked){border-color:var(--color-accent);background:var(--color-surface);color:var(--color-text-primary);}
    .correct-answer-row{display:flex;align-items:center;gap:10px;margin-top:10px;padding:10px 12px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:var(--radius-md);}
    .correct-answer-label{display:flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:#166534;white-space:nowrap;flex-shrink:0;}
    .correct-answer-select{flex:1;border:1.5px solid #bbf7d0;border-radius:var(--radius-md);padding:7px 10px;font-family:var(--font-family);font-size:13px;font-weight:500;color:var(--color-text-primary);background:#fff;outline:none;cursor:pointer;}
    .correct-answer-select:focus{border-color:#166534;}
    .correct-answer-text-input{flex:1;border:1.5px solid #bbf7d0;border-radius:var(--radius-md);padding:7px 10px;font-family:var(--font-family);font-size:13px;color:var(--color-text-primary);background:#fff;outline:none;}
    .correct-answer-text-input::placeholder{color:var(--color-text-subtle);}
    .mcq-options-list{display:flex;flex-direction:column;gap:8px;margin-bottom:8px;}
    .answer-area-label{font-size:11.5px;font-weight:600;color:var(--color-text-muted);margin-bottom:2px;}

    /* SETTINGS */
    .settings-tab{padding:10px 16px;background:none;border:none;border-bottom:2px solid transparent;font-family:var(--font-family);font-size:13px;font-weight:500;color:var(--color-text-muted);cursor:pointer;transition:all var(--transition);margin-bottom:-1px;}
    .settings-tab:hover{color:var(--color-text-primary);}
    .settings-tab.active{color:var(--color-text-primary);font-weight:700;border-bottom-color:var(--color-accent);}
    .settings-panel{display:none;}
    .settings-panel.active{display:block;}
    .notif-row{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;background:var(--color-bg);gap:12px;}
    .notif-row-info{flex:1;}
    .notif-row-title{font-size:13px;font-weight:600;}
    .notif-row-sub{font-size:11.5px;color:var(--color-text-muted);margin-top:2px;}
    .toggle{position:relative;display:inline-block;width:38px;height:22px;flex-shrink:0;}
    .toggle input{opacity:0;width:0;height:0;}
    .toggle-slider{position:absolute;inset:0;background:var(--color-border);border-radius:999px;cursor:pointer;transition:background var(--transition);}
    .toggle-slider::before{content:'';position:absolute;width:16px;height:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:transform var(--transition);box-shadow:0 1px 3px rgba(0,0,0,.2);}
    .toggle input:checked + .toggle-slider{background:var(--color-accent);}
    .toggle input:checked + .toggle-slider::before{transform:translateX(16px);}
    .s-pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-text-subtle);padding:0;display:flex;align-items:center;transition:color var(--transition);}
    .s-pw-toggle:hover{color:var(--color-text-primary);}

    /* TOPIC CHIPS */
    .quiz-topics{display:flex;flex-wrap:wrap;gap:4px;}
    .topic-chip{font-size:11px;font-weight:500;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:2px 7px;color:var(--color-text-muted);}

    /* CATEGORIZED TOPICS SELECTION UI */
    .topics-select-group {
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-md);
      padding: 12px;
      background: var(--color-bg);
      max-height: 200px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .topics-cat-section {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .topics-cat-title {
      font-size: 10.5px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--color-text-muted);
      letter-spacing: 0.5px;
      border-bottom: 1px solid var(--color-border);
      padding-bottom: 2px;
      margin-bottom: 2px;
    }
    .topics-chips-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    .topic-chip-checkbox {
      display: inline-flex;
      align-items: center;
      cursor: pointer;
      user-select: none;
    }
    .topic-chip-checkbox input[type="checkbox"] {
      display: none !important;
    }
    .topic-chip-checkbox span {
      font-size: 11.5px;
      font-weight: 500;
      color: var(--color-text-muted);
      background: var(--color-bg);
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-sm);
      padding: 4px 10px;
      transition: all var(--transition);
    }
    .topic-chip-checkbox input[type="checkbox"]:checked + span {
      color: var(--color-text-primary);
      border-color: var(--color-text-primary);
      background: var(--color-surface-2);
      font-weight: 600;
    }

    /* QUESTION TOPICS SELECTION UI (COLLAPSIBLE) */
    .q-topics-details {
      margin-top: 6px;
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-md);
      background: var(--color-bg);
    }
    .q-topics-summary {
      padding: 8px 12px;
      font-size: 12.5px;
      font-weight: 600;
      cursor: pointer;
      user-select: none;
      display: flex;
      align-items: center;
      justify-content: space-between;
      outline: none;
    }
    .q-topics-summary::after {
      content: '▼';
      font-size: 9px;
      color: var(--color-text-muted);
      transition: transform var(--transition);
    }
    .q-topics-details[open] .q-topics-summary::after {
      transform: rotate(180deg);
    }
    .q-topics-content {
      padding: 10px 12px;
      border-top: 1.5px solid var(--color-border);
      max-height: 150px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    /* CV Modal Styling */
    .cv-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
      font-family: 'DM Sans', sans-serif;
      color: #333;
    }
    .cv-header {
      border-bottom: 2px solid #000;
      padding-bottom: 15px;
      margin-bottom: 10px;
    }
    .cv-name {
      font-size: 26px;
      font-weight: 800;
      color: #000;
      letter-spacing: -0.5px;
      margin-bottom: 5px;
    }
    .cv-contact {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      font-size: 13px;
      color: #555;
    }
    .cv-contact span {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .cv-section-title {
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #000;
      border-bottom: 1.5px solid #e0e0e0;
      padding-bottom: 4px;
      margin-bottom: 10px;
      margin-top: 10px;
    }
    .cv-summary {
      font-size: 13.5px;
      line-height: 1.6;
      color: #444;
    }
    .cv-skills-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    .cv-skill-tag {
      font-size: 12px;
      font-weight: 600;
      border: 1.5px solid #000;
      border-radius: 4px;
      padding: 4px 10px;
      background: #f7f7f7;
      color: #000;
    }
    .cv-projects-list, .cv-experience-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    .cv-project-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .cv-item-header {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
    }
    .cv-item-title {
      font-size: 14px;
      font-weight: 700;
      color: #000;
    }
    .cv-item-meta {
      font-size: 12px;
      color: #666;
      font-style: italic;
    }
    .cv-item-desc {
      font-size: 13px;
      line-height: 1.5;
      color: #555;
      margin-top: 4px;
    }
    .cv-link {
      color: #000;
      text-decoration: underline;
      font-weight: 600;
    }

    /* SPINNER */
    .spinner{width:15px;height:15px;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:spin .65s linear infinite;flex-shrink:0;}
    @keyframes spin{to{transform:rotate(360deg);}}
  </style>
</head>
<body>
<div class="shell">

  <!-- ═══════ SIDEBAR ═══════ -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <span class="logo-wordmark">HireReady</span>
      <span class="logo-badge">Admin</span>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <button class="nav-item active" data-tab="overview" onclick="switchTab('overview',this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Overview
      </button>
      <button class="nav-item" data-tab="jobs" onclick="switchTab('jobs',this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        Jobs
        <span class="nav-badge" id="jobs-badge"><?= count($jobs) ?></span>
      </button>
      <button class="nav-item" data-tab="applicants" onclick="switchTab('applicants',this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Applicants
        <span class="nav-badge" id="applicants-badge"><?= count($applicants) ?></span>
      </button>
      <button class="nav-item" data-tab="quizzes" onclick="switchTab('quizzes',this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Quizzes
        <span class="nav-badge" id="quizzes-badge"><?= count($quizzes) ?></span>
      </button>
      <button class="nav-item" data-tab="courses" onclick="switchTab('courses',this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        Courses
        <span class="nav-badge" id="courses-badge"><?= count($courses) ?></span>
      </button>
    </nav>
    <div class="sidebar-footer">
      <div class="admin-info">
        <div class="admin-avatar" id="sidebar-avatar"><?= $adminInitials ?></div>
        <div class="admin-meta">
          <div class="admin-name" id="sidebar-name"><?= $adminName ?></div>
          <div class="admin-role" id="sidebar-role"><?= $adminRole ?></div>
        </div>
      </div>
      <button class="btn-settings" onclick="openModal('modal-settings')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Settings
      </button>
      <button class="btn-logout" onclick="window.location.href='logout.php'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign out
      </button>
    </div>
  </aside>

  <!-- ═══════ MAIN ═══════ -->
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1 id="topbar-title">Overview</h1>
        <p id="topbar-sub">Welcome back, <?= $adminName ?>. Here's what's happening today.</p>
      </div>
      <div class="topbar-right">
        <button class="topbar-btn primary" id="primaryBtn" onclick="handlePrimaryAction()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span id="primaryBtnLabel">Post a Job</span>
        </button>
      </div>
    </div>

    <div class="content">

      <!-- ═══ OVERVIEW ═══ -->
      <div class="section active" id="section-overview">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Total Jobs Posted</div>
            <div class="stat-value"><?= $totalJobsPosted ?></div>
            <div class="stat-sub"><span><?= $activeJobs ?></span> currently active</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Total Courses</div>
            <div class="stat-value"><?= $totalCourses ?></div>
            <div class="stat-sub"><span><?= $activeCourses ?></span> active</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Total Applicants</div>
            <div class="stat-value"><?= $totalApplicants ?></div>
            <div class="stat-sub"><span><?= $quizPassedCount ?></span> passed quiz</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Pending Review</div>
            <div class="stat-value"><?= $pendingReview ?></div>
            <div class="stat-sub">Awaiting approve / reject</div>
          </div>
        </div>

        <div class="overview-grid">
          <div class="overview-card">
            <div class="overview-card-title">
              Active Jobs
              <a href="#" onclick="switchTab('jobs',document.querySelectorAll('.nav-item')[1]);return false;">View all →</a>
            </div>
            <div class="mini-list">
              <?php
              $activeJobsForOverview = array_filter($jobs, fn($j) => $j['status'] === 'active');
              $topJobs = array_slice(array_values($activeJobsForOverview), 0, 3);
              if (empty($topJobs)) { echo '<p class="empty-mini">No active jobs.</p>'; }
              foreach ($topJobs as $j): ?>
              <div class="mini-row">
                <div class="mini-row-left">
                  <div class="mini-row-title"><?= htmlspecialchars($j['title']) ?></div>
                  <div class="mini-row-sub"><?= htmlspecialchars($j['job_type']) ?> · <?= htmlspecialchars($j['location']) ?></div>
                </div>
                <div class="mini-row-right">
                  <span class="badge badge-active"><span class="dot"></span>Active</span>
                  <span style="font-size:12px;font-weight:700;"><?= (int)$j['total_applicants'] ?></span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="overview-card">
            <div class="overview-card-title">
              Popular Courses
              <a href="#" onclick="switchTab('courses',document.querySelectorAll('.nav-item')[4]);return false;">View all →</a>
            </div>
            <div class="mini-list">
              <?php
              $topCourses = array_slice($courses, 0, 3);
              if (empty($topCourses)) { echo '<p class="empty-mini">No courses yet.</p>'; }
              foreach ($topCourses as $c): ?>
              <div class="mini-row">
                <div class="mini-row-left">
                  <div class="mini-row-title"><?= htmlspecialchars($c['title']) ?></div>
                  <div class="mini-row-sub"><?= htmlspecialchars($c['duration'] ?? '—') ?> · <?= htmlspecialchars($c['instructor']) ?></div>
                </div>
                <div class="mini-row-right">
                  <span style="font-size:12px;font-weight:700;"><?= (int)$c['participants'] ?> enrolled</span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ JOBS ═══ -->
      <div class="section" id="section-jobs">
        <div class="section-header">
          <div class="section-header-left">
            <h2>Job Listings</h2>
            <p>Sorted by most applicants. Click a row to manage.</p>
          </div>
        </div>
        <div class="table-card">
          <div class="table-toolbar">
            <div class="search-input">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" placeholder="Search jobs…" oninput="searchTable('jobs-tbody',this.value)">
            </div>
            <div class="filter-group">
              <button class="filter-btn active" onclick="filterJobs('all',this)">All</button>
              <button class="filter-btn" onclick="filterJobs('active',this)">Active</button>
              <button class="filter-btn" onclick="filterJobs('closed',this)">Closed</button>
              <button class="filter-btn" onclick="sortJobs()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Sort
              </button>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Job Title</th><th>Type</th><th>Location</th><th>Applicants</th><th>Salary</th><th>Status</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="jobs-tbody">
              <?php if (empty($jobs)): ?>
              <tr><td colspan="7" class="empty-state"><p>No jobs posted yet. Click "Post a Job" to get started.</p></td></tr>
              <?php else: foreach ($jobs as $j):
                $jId     = (int)$j['id'];
                $jTitle  = htmlspecialchars($j['title']);
                $jSkills = htmlspecialchars($j['skills'] ?? '');
                $jType   = htmlspecialchars($j['job_type']);
                $jLoc    = htmlspecialchars($j['location']);
                $jSalary = htmlspecialchars($j['salary'] ?? '—');
                $jStatus = $j['status'];
                $jPass   = (int)$j['pass_count'];
                $jFail   = (int)$j['fail_count'];
                $jTotal  = (int)$j['total_applicants'];
                $badgeClass = $jStatus === 'active' ? 'badge-active' : 'badge-inactive';
                $badgeLabel = $jStatus === 'active' ? 'Active' : 'Closed';
              ?>
              <tr data-status="<?= $jStatus ?>" data-id="<?= $jId ?>">
                <td><div class="cell-main"><?= $jTitle ?></div><div class="cell-sub"><?= $jSkills ?></div></td>
                <td><span class="badge-type"><?= $jType ?></span></td>
                <td><?= $jLoc ?></td>
                <td>
                  <div class="cell-main"><?= $jTotal ?></div>
                  <div class="cell-sub" style="display:flex;gap:6px;">
                    <span style="color:var(--color-success-text);font-weight:600;"><?= $jPass ?> pass</span>
                    <span style="color:var(--color-danger-text);font-weight:600;"><?= $jFail ?> fail</span>
                  </div>
                </td>
                <td><?= $jSalary ?></td>
                <td><span class="badge <?= $badgeClass ?>"><span class="dot"></span><?= $badgeLabel ?></span></td>
                <td>
                  <div class="action-group">
                    <?php if ($jStatus === 'active'): ?>
                    <button class="btn-action" onclick="openEditJobModal(this)">Edit</button>
                    <button class="btn-action danger" onclick="doCloseJob(this)">Close</button>
                    <button class="btn-action danger" onclick="doDeleteJob(this)">Delete</button>
                    <?php else: ?>
                    <button class="btn-action dark" onclick="doReopenJob(this)">Reopen</button>
                    <button class="btn-action danger" onclick="doDeleteJob(this)">Delete</button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ═══ APPLICANTS ═══ -->
      <div class="section" id="section-applicants">
        <div class="section-header">
          <div class="section-header-left">
            <h2>Applicants</h2>
            <p>Only candidates who passed the quiz are shown here.</p>
          </div>
        </div>
        <div class="table-card">
          <div class="table-toolbar">
            <div class="search-input">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" placeholder="Search applicants…" oninput="searchTable('applicants-tbody',this.value)">
            </div>
            <div class="filter-group">
              <button class="filter-btn active" onclick="filterApplicants('all',this)">All</button>
              <button class="filter-btn" onclick="filterApplicants('pending',this)">Pending</button>
              <button class="filter-btn" onclick="filterApplicants('approved',this)">Approved</button>
              <button class="filter-btn" onclick="filterApplicants('rejected',this)">Rejected</button>
            </div>
          </div>
          <table>
            <thead>
              <tr><th>Applicant</th><th>Applied For</th><th>Score</th><th>Date Applied</th><th>CV</th><th>Decision</th></tr>
            </thead>
            <tbody id="applicants-tbody">
              <?php if (empty($applicants)): ?>
              <tr><td colspan="6" class="empty-state"><p>No qualified applicants yet.</p></td></tr>
              <?php else: foreach ($applicants as $a):
                $aId     = (int)$a['id'];
                $aName   = htmlspecialchars($a['name'] ?? 'Unknown');
                $aEmail  = htmlspecialchars($a['email'] ?? '');
                $aJob    = htmlspecialchars($a['job_title'] ?? '—');
                $aScore  = (int)($a['quiz_score'] ?? 0);
                $aDate   = date('M j, Y', strtotime($a['created_at']));
                $aStatus = $a['status'] ?? 'pending';
                $aCvPath = htmlspecialchars($a['cv_path'] ?? '');
              ?>
              <tr data-status="<?= $aStatus ?>" data-id="<?= $aId ?>">
                <td><div class="cell-main"><?= $aName ?></div><div class="cell-sub"><?= $aEmail ?></div></td>
                <td><?= $aJob ?></td>
                <td>
                  <div class="score-wrap">
                    <div class="score-bar"><div class="score-fill" style="width:<?= $aScore ?>%;"></div></div>
                    <span class="score-text"><?= $aScore ?>%</span>
                  </div>
                </td>
                <td style="color:var(--color-text-muted);font-size:12.5px;"><?= $aDate ?></td>
                <td>
                  <button class="btn-action dark" onclick="viewApplicantCV(<?= (int)$a['user_id'] ?>)">View CV</button>
                </td>
                <td>
                  <?php if ($aStatus === 'pending'): ?>
                  <div class="action-group">
                    <button class="btn-action success" onclick="decideApplicant(this,'approved')">Approve</button>
                    <button class="btn-action danger"  onclick="decideApplicant(this,'rejected')">Reject</button>
                  </div>
                  <?php elseif ($aStatus === 'approved'): ?>
                  <span class="badge badge-active" style="padding:5px 12px;">Approved</span>
                  <?php else: ?>
                  <span class="badge badge-inactive" style="padding:5px 12px;">Rejected</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ═══ QUIZZES ═══ -->
      <div class="section" id="section-quizzes">
        <div class="section-header">
          <div class="section-header-left">
            <h2>Quizzes</h2>
            <p>All quizzes tied to your job listings.</p>
          </div>
        </div>
        <div class="table-card">
          <div class="table-toolbar">
            <div class="search-input">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" placeholder="Search quizzes…" oninput="searchTable('quizzes-tbody',this.value)">
            </div>
            <div class="filter-group">
              <button class="filter-btn active" onclick="filterQuizzes('all',this)">All</button>
              <button class="filter-btn" onclick="filterQuizzes('active',this)">Active</button>
              <button class="filter-btn" onclick="filterQuizzes('closed',this)">Closed</button>
            </div>
          </div>
          <table>
            <thead>
              <tr><th>Quiz Title</th><th>Topics</th><th>For Job</th><th>Questions</th><th>Pass Mark</th><th>Time Limit</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="quizzes-tbody">
              <?php if (empty($quizzes)): ?>
              <tr><td colspan="8" class="empty-state"><p>No quizzes yet. Create one when posting a job.</p></td></tr>
              <?php else: foreach ($quizzes as $q):
                $qId     = (int)$q['id'];
                $qTitle  = htmlspecialchars($q['title']);
                $qTopics = '';
                if (!empty($q['topic_names'])) {
                    foreach ($q['topic_names'] as $tName) {
                        $qTopics .= '<span class="topic-chip">' . htmlspecialchars($tName) . '</span>';
                    }
                }
                $qJob    = htmlspecialchars($q['job_title'] ?? '—');
                $qQCount = (int)$q['question_count'];
                $qPass   = (int)$q['pass_mark'];
                $qTime   = (int)$q['time_limit'];
                $qStatus = $q['status'];
                $qBadgeClass = $qStatus === 'active' ? 'badge-active' : 'badge-inactive';
                $qBadgeLabel = $qStatus === 'active' ? 'Active' : 'Closed';
              ?>
              <tr data-status="<?= $qStatus ?>" data-id="<?= $qId ?>">
                <td><div class="cell-main"><?= $qTitle ?></div></td>
                <td><div class="quiz-topics"><?= $qTopics ?></div></td>
                <td style="font-size:12.5px;"><?= $qJob ?></td>
                <td><?= $qQCount ?></td>
                <td><?= $qPass ?>%</td>
                <td><?= $qTime ?> min</td>
                <td><span class="badge <?= $qBadgeClass ?>"><span class="dot"></span><?= $qBadgeLabel ?></span></td>
                <td>
                  <div class="action-group">
                    <?php if ($qStatus === 'active'): ?>
                    <button class="btn-action" onclick="openEditQuizModal(<?= $qId ?>)">Edit</button>
                    <button class="btn-action danger" onclick="doCloseQuiz(this)">Close</button>
                    <?php else: ?>
                    <button class="btn-action" style="opacity:.5;cursor:not-allowed;" disabled>Edit</button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ═══ COURSES ═══ -->
      <div class="section" id="section-courses">
        <div class="section-header">
          <div class="section-header-left">
            <h2>Courses</h2>
            <p>Sorted by number of participants.</p>
          </div>
        </div>
        <div class="table-card">
          <div class="table-toolbar">
            <div class="search-input">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" placeholder="Search courses…" oninput="searchTable('courses-tbody',this.value)">
            </div>
            <div class="filter-group">
              <button class="filter-btn active" onclick="filterCourses('all',this)">All</button>
              <button class="filter-btn" onclick="filterCourses('active',this)">Active</button>
              <button class="filter-btn" onclick="filterCourses('closed',this)">Closed</button>
            </div>
          </div>
          <table>
            <thead>
              <tr><th>Course Title</th><th>Topics</th><th>Instructor</th><th>Participants</th><th>Duration</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="courses-tbody">
              <?php if (empty($courses)): ?>
              <tr><td colspan="7" class="empty-state"><p>No courses yet. Click "Add Course" to publish one.</p></td></tr>
              <?php else: foreach ($courses as $c):
                $cId     = (int)$c['id'];
                $cTitle  = htmlspecialchars($c['title']);
                $cDesc   = htmlspecialchars($c['description'] ?? '');
                $cTopics = '';
                if (!empty($c['topic_names'])) {
                    foreach ($c['topic_names'] as $tName) {
                        $cTopics .= '<span class="topic-chip">' . htmlspecialchars($tName) . '</span>';
                    }
                }
                $cInstr  = htmlspecialchars($c['instructor']);
                $cParts  = (int)$c['participants'];
                $cDur    = htmlspecialchars($c['duration'] ?? '—');
                $cStatus = $c['status'];
                $cBadgeClass = $cStatus === 'active' ? 'badge-active' : 'badge-inactive';
                $cBadgeLabel = $cStatus === 'active' ? 'Active' : 'Closed';
              ?>
              <tr data-status="<?= $cStatus ?>" data-id="<?= $cId ?>">
                <td><div class="cell-main"><?= $cTitle ?></div><div class="cell-sub"><?= $cDesc ?></div></td>
                <td><div class="quiz-topics"><?= $cTopics ?></div></td>
                <td style="font-size:13px;"><?= $cInstr ?></td>
                <td><strong><?= $cParts ?></strong></td>
                <td><?= $cDur ?></td>
                <td><span class="badge <?= $cBadgeClass ?>"><span class="dot"></span><?= $cBadgeLabel ?></span></td>
                <td>
                  <div class="action-group">
                    <?php if ($cStatus === 'active'): ?>
                    <button class="btn-action danger" onclick="doToggleCourse(this,'closed')">Unlist</button>
                    <?php else: ?>
                    <button class="btn-action dark" onclick="doToggleCourse(this,'active')">Relist</button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /.content -->
  </div><!-- /.main -->
</div><!-- /.shell -->

<!-- ══════════ MODAL: ADD JOB (2-step) ══════════ -->
<div class="modal-overlay" id="modal-add-job">
  <div class="modal">
    <div class="modal-header">
      <div><h3>Post a New Job</h3><p>Fill in the job details, then set up the quiz.</p></div>
      <button class="modal-close" onclick="closeModal('modal-add-job')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="steps" id="job-steps">
      <div class="step-item active" id="step-1-indicator"><div class="step-num">1</div>Job Details</div>
      <div class="step-divider"></div>
      <div class="step-item" id="step-2-indicator"><div class="step-num">2</div>Quiz Setup</div>
    </div>

    <!-- Step 1 -->
    <div id="job-step-1">
      <div class="modal-body">
        <div class="field"><label>Job Title</label><input type="text" id="job-title-input" placeholder="e.g. Senior React Developer"></div>
        <div class="field-row">
          <div class="field"><label>Job Type</label><select id="job-type-input"><option value="">Select type</option><option>Full-time</option><option>Part-time</option><option>Contract</option><option>Internship</option></select></div>
          <div class="field"><label>Location</label><select id="job-location-input"><option value="">Select location</option><option>Onsite</option><option>Remote</option><option>Hybrid</option></select></div>
        </div>
        <div class="field"><label>Salary</label><input type="text" id="job-salary-input" placeholder="e.g. $60k – $80k or $25/hr"></div>
        <div class="field">
          <label>Required Skills</label>
          <div class="tags-wrap" id="skills-tags" onclick="document.getElementById('skills-input').focus()">
            <input type="text" id="skills-input" placeholder="Type skill and press Enter…" onkeydown="addTag(event,'skills-tags','skills-input')">
          </div>
          <span class="field-hint">Press Enter after each skill.</span>
        </div>
        <div class="field"><label>Short Description</label><textarea id="job-desc-input" placeholder="Briefly describe the role…"></textarea></div>
        <div id="add-job-error" style="display:none;font-size:12.5px;color:var(--color-danger-text);padding:8px 12px;background:var(--color-danger-bg);border:1px solid var(--color-danger-border);border-radius:var(--radius-md);"></div>
      </div>
      <div class="modal-footer">
        <button class="topbar-btn" onclick="closeModal('modal-add-job')">Cancel</button>
        <button class="topbar-btn primary" onclick="goToStep2()">Next: Set Up Quiz →</button>
      </div>
    </div>

    <!-- Step 2 -->
    <div id="job-step-2" style="display:none;">
      <div class="modal-body">
        <div class="field"><label>Quiz Title</label><input type="text" id="quiz-title-input" placeholder="e.g. Frontend Engineer Assessment"></div>
        <div class="field">
          <label>Topics Covered</label>
          <div id="topics-tags-container"></div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;"><label style="font-size:12.5px;font-weight:600;">Questions</label></div>
        <div id="questions-list" style="display:flex;flex-direction:column;gap:10px;"></div>
        <button class="btn-add-question" onclick="addQuestion()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Question
        </button>
        <div class="field-row">
          <div class="field"><label>Pass Mark (%)</label><input type="number" id="quiz-pass-mark" placeholder="e.g. 70" min="1" max="100"></div>
          <div class="field">
            <label>Total Marks</label>
            <div style="height:38px;border:1.5px solid var(--color-border);border-radius:var(--radius-md);padding:0 12px;display:flex;align-items:center;background:var(--color-surface);gap:6px;">
              <span id="quiz-total-marks" style="font-size:14px;font-weight:700;">0</span>
              <span style="font-size:12px;color:var(--color-text-muted);">pts (auto)</span>
            </div>
          </div>
          <div class="field"><label>Time Limit (min)</label><input type="number" id="quiz-time-limit" placeholder="e.g. 45" min="5"></div>
        </div>
        <div id="add-quiz-error" style="display:none;font-size:12.5px;color:var(--color-danger-text);padding:8px 12px;background:var(--color-danger-bg);border:1px solid var(--color-danger-border);border-radius:var(--radius-md);"></div>
      </div>
      <div class="modal-footer">
        <button class="topbar-btn" onclick="goToStep1()">← Back</button>
        <button class="topbar-btn primary" id="postJobBtn" onclick="postJob()">Post Job</button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ MODAL: EDIT JOB ══════════ -->
<div class="modal-overlay" id="modal-edit-job">
  <div class="modal">
    <div class="modal-header">
      <div><h3>Edit Job</h3><p>Update the job listing details below.</p></div>
      <button class="modal-close" onclick="closeModal('modal-edit-job')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-job-id">
      <div class="field"><label>Job Title</label><input type="text" id="edit-job-title"></div>
      <div class="field-row">
        <div class="field"><label>Job Type</label><select id="edit-job-type"><option>Full-time</option><option>Part-time</option><option>Contract</option><option>Internship</option></select></div>
        <div class="field"><label>Location</label><select id="edit-job-location"><option>Remote</option><option>Onsite</option><option>Hybrid</option></select></div>
      </div>
      <div class="field"><label>Salary</label><input type="text" id="edit-job-salary" placeholder="e.g. $60k – $80k"></div>
      <div class="field">
        <label>Required Skills</label>
        <div class="tags-wrap" id="edit-skills-tags" onclick="document.getElementById('edit-skills-input').focus()">
          <input type="text" id="edit-skills-input" placeholder="Type skill and press Enter…" onkeydown="addTag(event,'edit-skills-tags','edit-skills-input')">
        </div>
      </div>
      <div class="field"><label>Short Description</label><textarea id="edit-job-desc" placeholder="Briefly describe the role…"></textarea></div>
      <div id="edit-job-error" style="display:none;font-size:12.5px;color:var(--color-danger-text);padding:8px 12px;background:var(--color-danger-bg);border:1px solid var(--color-danger-border);border-radius:var(--radius-md);"></div>
    </div>
    <div class="modal-footer">
      <button class="topbar-btn" onclick="closeModal('modal-edit-job')">Cancel</button>
      <button class="topbar-btn primary" id="saveEditJobBtn" onclick="saveEditJob()">Save Changes</button>
    </div>
  </div>
</div>

<!-- ══════════ MODAL: ADD COURSE ══════════ -->
<div class="modal-overlay" id="modal-add-course">
  <div class="modal">
    <div class="modal-header">
      <div><h3>Add a New Course</h3><p>Fill in the course details to publish it.</p></div>
      <button class="modal-close" onclick="closeModal('modal-add-course')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="field"><label>Course Title</label><input type="text" id="course-title" placeholder="e.g. Advanced JavaScript Patterns"></div>
      <div class="field"><label>Short Description</label><textarea id="course-desc" placeholder="What will students learn?"></textarea></div>
      <div class="field">
        <label>Topics Covered</label>
        <div id="course-topics-container"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Instructor Name</label><input type="text" id="course-instructor" placeholder="e.g. Dr. Jane Doe"></div>
        <div class="field"><label>Duration</label><input type="text" id="course-duration" placeholder="e.g. 6 weeks"></div>
      </div>
      <div class="field"><label>Modules / Lessons</label><textarea id="course-modules" placeholder="List modules, one per line.&#10;e.g. Module 1: Intro to Closures"></textarea></div>
      <div id="add-course-error" style="display:none;font-size:12.5px;color:var(--color-danger-text);padding:8px 12px;background:var(--color-danger-bg);border:1px solid var(--color-danger-border);border-radius:var(--radius-md);"></div>
    </div>
    <div class="modal-footer">
      <button class="topbar-btn" onclick="closeModal('modal-add-course')">Cancel</button>
      <button class="topbar-btn primary" id="publishCourseBtn" onclick="publishCourse()">Publish Course</button>
    </div>
  </div>
</div>

<!-- ══════════ MODAL: EDIT QUIZ ══════════ -->
<div class="modal-overlay" id="modal-edit-quiz">
  <div class="modal">
    <div class="modal-header">
      <div><h3>Edit Quiz</h3><p>Update questions, time limit, or pass mark.</p></div>
      <button class="modal-close" onclick="closeModal('modal-edit-quiz')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" id="edit-quiz-body">
      <div style="text-align:center;padding:2rem;color:var(--color-text-muted);">Loading quiz…</div>
    </div>
    <div class="modal-footer">
      <button class="topbar-btn" onclick="closeModal('modal-edit-quiz')">Cancel</button>
      <button class="topbar-btn primary" id="saveEditQuizBtn" onclick="saveEditQuiz()">Save Changes</button>
    </div>
  </div>
</div>

<!-- ══════════ MODAL: VIEW CV ══════════ -->
<div class="modal-overlay" id="modal-view-cv">
  <div class="modal" style="max-width:760px;">
    <div class="modal-header">
      <div><h3>Applicant CV Profile</h3><p>Detailed profile compiled from the onboarding survey.</p></div>
      <button class="modal-close" onclick="closeModal('modal-view-cv')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" id="view-cv-body" style="padding: 2rem; max-height: 70vh; overflow-y: auto; background: #fff; color: #333;">
      <div style="text-align:center;padding:2rem;color:var(--color-text-muted);">Loading profile…</div>
    </div>
    <div class="modal-footer">
      <button class="topbar-btn primary" onclick="closeModal('modal-view-cv')">Close</button>
    </div>
  </div>
</div>

<!-- ══════════ MODAL: SETTINGS ══════════ -->
<div class="modal-overlay" id="modal-settings">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div><h3>Settings</h3><p>Manage your company info, profile, and security.</p></div>
      <button class="modal-close" onclick="closeModal('modal-settings')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div style="display:flex;gap:0;border-bottom:1px solid var(--color-border);padding:0 1.5rem;">
      <button class="settings-tab active" onclick="switchSettingsTab('company',this)">Company Info</button>
      <button class="settings-tab" onclick="switchSettingsTab('profile',this)">Representative</button>
      <button class="settings-tab" onclick="switchSettingsTab('notifications',this)">Notifications</button>
      <button class="settings-tab" onclick="switchSettingsTab('security',this)">Security</button>
    </div>

    <!-- Company Info -->
    <div class="settings-panel active" id="settings-company">
      <div class="modal-body">
        <div class="field"><label>Company Name</label><input type="text" id="s-company-name" value="<?= $companyNameFull ?>"></div>
        <div class="field"><label>Company Address</label><input type="text" id="s-company-address" value="<?= $companyAddress ?>"></div>
        <div id="company-msg" style="display:none;font-size:12.5px;padding:8px 12px;border-radius:var(--radius-md);"></div>
      </div>
      <div class="modal-footer">
        <button class="topbar-btn" onclick="closeModal('modal-settings')">Cancel</button>
        <button class="topbar-btn primary" onclick="saveCompanyInfo()">Save Changes</button>
      </div>
    </div>

    <!-- Representative -->
    <div class="settings-panel" id="settings-profile">
      <div class="modal-body">
        <div style="display:flex;align-items:center;gap:14px;padding:4px 0 6px;">
          <div id="s-avatar" style="width:52px;height:52px;border-radius:50%;background:#f0f0f0;border:1.5px solid var(--color-border);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#555;flex-shrink:0;"><?= $adminInitials ?></div>
          <div>
            <div id="s-avatar-name" style="font-size:13.5px;font-weight:700;"><?= $adminName ?></div>
            <div id="s-avatar-role" style="font-size:12px;color:var(--color-text-muted);margin-top:2px;"><?= $adminRole ?> · <?= $companyNameFull ?></div>
          </div>
        </div>
        <div class="field-row">
          <div class="field"><label>Representative Name</label><input type="text" id="s-rep-name" value="<?= $adminName ?>" oninput="updateSettingsAvatar()"></div>
          <div class="field"><label>Representative Role</label><input type="text" id="s-rep-role" value="<?= $adminRole ?>" oninput="updateSettingsAvatar()"></div>
        </div>
        <div class="field"><label>Company Email</label><input type="email" id="s-email" value="<?= $adminEmail ?>"></div>
        <div class="field"><label>Phone Number</label><input type="tel" id="s-phone" value="<?= $adminPhone ?>"></div>
        <div id="profile-msg" style="display:none;font-size:12.5px;padding:8px 12px;border-radius:var(--radius-md);"></div>
      </div>
      <div class="modal-footer">
        <button class="topbar-btn" onclick="closeModal('modal-settings')">Cancel</button>
        <button class="topbar-btn primary" onclick="saveProfileInfo()">Save Changes</button>
      </div>
    </div>

    <!-- Notifications -->
    <div class="settings-panel" id="settings-notifications">
      <div class="modal-body">
        <div style="display:flex;flex-direction:column;gap:0;border:1.5px solid var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
          <div class="notif-row"><div class="notif-row-info"><div class="notif-row-title">New Applicant</div><div class="notif-row-sub">When a candidate applies to one of your jobs</div></div><label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
          <div class="notif-row" style="border-top:1px solid var(--color-border);"><div class="notif-row-info"><div class="notif-row-title">Quiz Passed</div><div class="notif-row-sub">When a candidate passes the assessment quiz</div></div><label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
          <div class="notif-row" style="border-top:1px solid var(--color-border);"><div class="notif-row-info"><div class="notif-row-title">Job Closing Soon</div><div class="notif-row-sub">48 hours before a job listing closes</div></div><label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
          <div class="notif-row" style="border-top:1px solid var(--color-border);"><div class="notif-row-info"><div class="notif-row-title">Course Enrollment</div><div class="notif-row-sub">When someone enrolls in one of your courses</div></div><label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label></div>
          <div class="notif-row" style="border-top:1px solid var(--color-border);"><div class="notif-row-info"><div class="notif-row-title">Weekly Summary</div><div class="notif-row-sub">A weekly digest of activity on your account</div></div><label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="topbar-btn" onclick="closeModal('modal-settings')">Cancel</button>
        <button class="topbar-btn primary" onclick="showToast('✓ Notification preferences saved!');closeModal('modal-settings');">Save Preferences</button>
      </div>
    </div>

    <!-- Security -->
    <div class="settings-panel" id="settings-security">
      <div class="modal-body">
        <div style="border:1.5px solid var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
          <div style="padding:10px 14px;background:var(--color-surface);border-bottom:1px solid var(--color-border);"><span style="font-size:12px;font-weight:700;">Change Password</span></div>
          <div style="padding:14px;display:flex;flex-direction:column;gap:12px;">
            <div class="field"><label>Current Password</label><div style="position:relative;"><input type="password" id="s-cur-pw" placeholder="••••••••" style="padding-right:40px;"><button type="button" class="s-pw-toggle" onclick="toggleSettingsPw('s-cur-pw',this)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
            <div class="field"><label>New Password</label><div style="position:relative;"><input type="password" id="s-new-pw" placeholder="Min. 8 characters" style="padding-right:40px;"><button type="button" class="s-pw-toggle" onclick="toggleSettingsPw('s-new-pw',this)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
            <div class="field"><label>Confirm New Password</label><div style="position:relative;"><input type="password" id="s-conf-pw" placeholder="Re-enter new password" style="padding-right:40px;" oninput="checkSettingsPwMatch()"><button type="button" class="s-pw-toggle" onclick="toggleSettingsPw('s-conf-pw',this)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div><span id="s-pw-mismatch" style="display:none;font-size:11.5px;color:var(--color-danger-text);">Passwords do not match.</span></div>
            <div id="pw-change-msg" style="display:none;font-size:12.5px;padding:8px 12px;border-radius:var(--radius-md);"></div>
          </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 14px;border:1.5px solid var(--color-border);border-radius:var(--radius-md);">
          <div><div style="font-size:13px;font-weight:600;">Two-Factor Authentication</div><div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;">Add an extra layer of security</div></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
        <div style="padding:13px 14px;background:var(--color-danger-bg);border:1.5px solid var(--color-danger-border);border-radius:var(--radius-md);">
          <div style="font-size:12.5px;font-weight:700;color:var(--color-danger-text);">Danger Zone</div>
          <div style="font-size:12px;color:var(--color-danger-text);opacity:.8;margin:4px 0 10px;">Permanently delete your admin account. This cannot be undone.</div>
          <button class="btn-action danger" onclick="alert('Account deletion requires confirmation from the super admin.')">Delete Account</button>
        </div>
      </div>
      <div class="modal-footer">
        <button class="topbar-btn" onclick="closeModal('modal-settings')">Cancel</button>
        <button class="topbar-btn primary" onclick="changePassword()">Update Password</button>
      </div>
    </div>

  </div>
</div>

<!-- ═══════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════ -->
<script>
// ── PHP data injected for JS ─────────────────────────────
const JOBS = <?= json_encode(array_map(fn($j) => [
  'id'    => $j['id'],
  'title' => $j['title'],
  'type'  => $j['job_type'],
], $jobs)) ?>;
const TOPICS_BY_CATEGORY = <?= json_encode($topicsByCategory) ?>;
const ALL_TOPICS = <?= json_encode($allTopics) ?>;
const QUIZZES = <?= json_encode($quizzes) ?>;

// ── TAB SWITCHING ────────────────────────────────────────
const topbarMeta = {
  overview:   { title: 'Overview',   sub: 'Welcome back, <?= $adminName ?>. Here\'s what\'s happening today.', btn: 'Post a Job' },
  jobs:       { title: 'Jobs',       sub: 'Manage your job listings and hiring pipeline.',                      btn: 'Post a Job' },
  applicants: { title: 'Applicants', sub: 'Candidates who passed the assessment quiz.',                        btn: null },
  quizzes:    { title: 'Quizzes',    sub: 'Manage assessments tied to your job listings.',                     btn: null },
  courses:    { title: 'Courses',    sub: 'Publish and manage learning content for candidates.',               btn: 'Add Course' },
};

function switchTab(tab, el) {
  localStorage.setItem('activeTab', tab);
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('section-' + tab).classList.add('active');
  el.classList.add('active');
  const m = topbarMeta[tab];
  document.getElementById('topbar-title').textContent = m.title;
  document.getElementById('topbar-sub').textContent   = m.sub;
  const pb = document.getElementById('primaryBtn');
  pb.style.display = m.btn ? '' : 'none';
  if (m.btn) document.getElementById('primaryBtnLabel').textContent = m.btn;
}

function handlePrimaryAction() {
  const tab = document.querySelector('.section.active').id.replace('section-','');
  if (tab === 'overview' || tab === 'jobs') openModal('modal-add-job');
  if (tab === 'courses') openModal('modal-add-course');
}

// ── MODAL HELPERS ────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

// ── AJAX HELPER ──────────────────────────────────────────
async function api(formData) {
  const r = await fetch('', { method: 'POST', body: formData });
  return r.json();
}

function fd(obj) {
  const f = new FormData();
  for (const [k, v] of Object.entries(obj)) f.append(k, v);
  return f;
}

function showErr(elId, msg) {
  const el = document.getElementById(elId);
  if (!el) return;
  el.textContent = msg;
  el.style.display = 'block';
  el.style.background = 'var(--color-danger-bg)';
  el.style.color = 'var(--color-danger-text)';
  el.style.border = '1px solid var(--color-danger-border)';
}

function showMsg(elId, msg, success = true) {
  const el = document.getElementById(elId);
  if (!el) return;
  el.textContent = msg;
  el.style.display = 'block';
  el.style.background = success ? 'var(--color-success-bg)' : 'var(--color-danger-bg)';
  el.style.color      = success ? 'var(--color-success-text)' : 'var(--color-danger-text)';
  el.style.border     = success ? '1px solid var(--color-success-border)' : '1px solid var(--color-danger-border)';
}

function hideErr(elId) {
  const el = document.getElementById(elId);
  if (el) el.style.display = 'none';
}

// ── TOAST ────────────────────────────────────────────────
function showToast(msg) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#0a0a0a;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;animation:slideUp .25s ease;';
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2800);
}

// ── SEARCH TABLE ─────────────────────────────────────────
function searchTable(tbodyId, val) {
  const v = val.toLowerCase();
  document.querySelectorAll('#' + tbodyId + ' tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}

// ── FILTER HELPERS ───────────────────────────────────────
function filterRows(tbodyId, status, btn, btnGroupSelector) {
  document.querySelectorAll(btnGroupSelector + ' .filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#' + tbodyId + ' tr[data-status]').forEach(row => {
    row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
  });
}

function filterJobs(status, btn)       { filterRows('jobs-tbody',       status, btn, '#section-jobs .filter-group'); }
function filterQuizzes(status, btn)    { filterRows('quizzes-tbody',    status, btn, '#section-quizzes .filter-group'); }
function filterCourses(status, btn)    { filterRows('courses-tbody',    status, btn, '#section-courses .filter-group'); }
function filterApplicants(status, btn) { filterRows('applicants-tbody', status, btn, '#section-applicants .filter-group'); }

let sortAsc = true;
function sortJobs() {
  const tbody = document.getElementById('jobs-tbody');
  const rows  = Array.from(tbody.querySelectorAll('tr[data-id]'));
  rows.sort((a, b) => {
    const av = parseInt(a.querySelector('td:nth-child(4) .cell-main')?.textContent || 0);
    const bv = parseInt(b.querySelector('td:nth-child(4) .cell-main')?.textContent || 0);
    return sortAsc ? bv - av : av - bv;
  });
  sortAsc = !sortAsc;
  rows.forEach(r => tbody.appendChild(r));
}

// ── POST JOB (2-step) ────────────────────────────────────
let questionCount = 0;
const LETTERS = ['A','B','C','D','E','F'];

function goToStep2() {
  const title    = document.getElementById('job-title-input').value.trim();
  const type     = document.getElementById('job-type-input').value;
  const location = document.getElementById('job-location-input').value;
  if (!title)    { showErr('add-job-error', 'Job title is required.'); return; }
  if (!type)     { showErr('add-job-error', 'Please select a job type.'); return; }
  if (!location) { showErr('add-job-error', 'Please select a location.'); return; }
  hideErr('add-job-error');
  document.getElementById('job-step-1').style.display = 'none';
  document.getElementById('job-step-2').style.display = '';
  document.getElementById('step-1-indicator').className = 'step-item done';
  document.getElementById('step-2-indicator').className = 'step-item active';
}

function goToStep1() {
  document.getElementById('job-step-2').style.display = 'none';
  document.getElementById('job-step-1').style.display = '';
  document.getElementById('step-1-indicator').className = 'step-item active';
  document.getElementById('step-2-indicator').className = 'step-item';
}

async function postJob() {
  const title    = document.getElementById('job-title-input').value.trim();
  const type     = document.getElementById('job-type-input').value;
  const loc      = document.getElementById('job-location-input').value;
  const salary   = document.getElementById('job-salary-input').value.trim();
  const desc     = document.getElementById('job-desc-input').value.trim();
  const skills   = collectTags('skills-tags', 'skills-input');

  const quizTitle = document.getElementById('quiz-title-input').value.trim() || title + ' Assessment';
  const passMark  = document.getElementById('quiz-pass-mark').value || '70';
  const timeLimit = document.getElementById('quiz-time-limit').value || '30';
  const topics    = Array.from(document.querySelectorAll('#topics-tags-container input[type="checkbox"]:checked')).map(cb => cb.value).join(',');
  const questions = collectQuestions('#questions-list');

  hideErr('add-quiz-error');
  const btn = document.getElementById('postJobBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Posting…';

  // 1. Add job
  const jobData = await api(fd({ action: 'add_job', title, type, location: loc, salary, skills, description: desc }));
  if (!jobData.success) { showErr('add-quiz-error', jobData.message); btn.disabled = false; btn.textContent = 'Post Job'; return; }

  // 2. Add quiz
  const f = fd({ action: 'add_quiz', job_id: jobData.job_id, title: quizTitle, topics, pass_mark: passMark, time_limit: timeLimit, questions: JSON.stringify(questions) });
  const quizData = await api(f);

  if (quizData.success) {
    closeModal('modal-add-job');
    resetJobModal();
    sessionStorage.setItem('toastMessage', '✓ Job and quiz posted successfully!');
    localStorage.setItem('activeTab', 'jobs');
    window.location.reload();
  } else {
    showErr('add-quiz-error', quizData.message || 'Quiz creation failed.');
    btn.disabled = false; btn.textContent = 'Post Job';
  }
}

function resetJobModal() {
  goToStep1();
  ['job-title-input','job-salary-input','job-desc-input','quiz-title-input','quiz-pass-mark','quiz-time-limit'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.getElementById('job-type-input').value     = '';
  document.getElementById('job-location-input').value = '';
  document.getElementById('quiz-total-marks').textContent = '0';
  document.querySelectorAll('#skills-tags .tag-pill, #topics-tags .tag-pill').forEach(p => p.remove());
  document.getElementById('questions-list').innerHTML = '';
  questionCount = 0;
}

// ── EDIT JOB ─────────────────────────────────────────────
let _editJobRow = null;

function openEditJobModal(btn) {
  const row = btn.closest('tr');
  _editJobRow = row;
  document.getElementById('edit-job-id').value    = row.dataset.id;
  document.getElementById('edit-job-title').value = row.querySelector('.cell-main').textContent.trim();
  document.getElementById('edit-job-salary').value = row.querySelector('td:nth-child(5)').textContent.trim();
  const type = row.querySelector('.badge-type').textContent.trim();
  const loc  = row.querySelector('td:nth-child(3)').textContent.trim();
  const typeSelect = document.getElementById('edit-job-type');
  [...typeSelect.options].forEach(o => o.selected = o.text === type);
  const locSelect = document.getElementById('edit-job-location');
  [...locSelect.options].forEach(o => o.selected = o.text === loc);
  const skills = row.querySelector('.cell-sub')?.textContent.trim() || '';
  const wrap = document.getElementById('edit-skills-tags');
  wrap.querySelectorAll('.tag-pill').forEach(p => p.remove());
  skills.split(',').map(s => s.trim()).filter(Boolean).forEach(skill => {
    const pill = document.createElement('span');
    pill.className = 'tag-pill';
    pill.innerHTML = `${skill} <button onclick="removeTag(this)">×</button>`;
    wrap.insertBefore(pill, document.getElementById('edit-skills-input'));
  });
  hideErr('edit-job-error');
  openModal('modal-edit-job');
}

async function saveEditJob() {
  const jobId  = document.getElementById('edit-job-id').value;
  const title  = document.getElementById('edit-job-title').value.trim();
  const type   = document.getElementById('edit-job-type').value;
  const loc    = document.getElementById('edit-job-location').value;
  const salary = document.getElementById('edit-job-salary').value.trim();
  const desc   = document.getElementById('edit-job-desc').value.trim();
  const skills = collectTags('edit-skills-tags', 'edit-skills-input');

  if (!title) { showErr('edit-job-error', 'Title is required.'); return; }

  const btn = document.getElementById('saveEditJobBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving…';

  const data = await api(fd({ action: 'edit_job', job_id: jobId, title, type, location: loc, salary, skills, description: desc }));
  btn.disabled = false; btn.textContent = 'Save Changes';

  if (data.success) {
    if (_editJobRow) {
      _editJobRow.querySelector('.cell-main').textContent = title;
      _editJobRow.querySelector('.cell-sub').textContent  = skills;
      _editJobRow.querySelector('.badge-type').textContent = type;
      _editJobRow.querySelector('td:nth-child(3)').textContent = loc;
      _editJobRow.querySelector('td:nth-child(5)').textContent = salary;
    }
    closeModal('modal-edit-job');
    showToast('✓ Job updated!');
  } else {
    showErr('edit-job-error', data.message);
  }
}

// ── CLOSE / REOPEN / DELETE JOB ──────────────────────────
async function doCloseJob(btn) {
  const row = btn.closest('tr');
  const data = await api(fd({ action: 'close_job', job_id: row.dataset.id }));
  if (data.success) {
    row.querySelector('.badge').className = 'badge badge-inactive';
    row.querySelector('.badge').innerHTML = '<span class="dot"></span>Closed';
    row.dataset.status = 'closed';
    const ag = row.querySelector('.action-group');
    ag.innerHTML = '<button class="btn-action dark" onclick="doReopenJob(this)">Reopen</button><button class="btn-action danger" onclick="doDeleteJob(this)">Delete</button>';
    showToast('Job closed.');
  }
}

async function doReopenJob(btn) {
  const row = btn.closest('tr');
  const data = await api(fd({ action: 'reopen_job', job_id: row.dataset.id }));
  if (data.success) {
    row.querySelector('.badge').className = 'badge badge-active';
    row.querySelector('.badge').innerHTML = '<span class="dot"></span>Active';
    row.dataset.status = 'active';
    const ag = row.querySelector('.action-group');
    ag.innerHTML = '<button class="btn-action" onclick="openEditJobModal(this)">Edit</button><button class="btn-action danger" onclick="doCloseJob(this)">Close</button><button class="btn-action danger" onclick="doDeleteJob(this)">Delete</button>';
    showToast('Job reopened.');
  }
}

async function doDeleteJob(btn) {
  const row = btn.closest('tr');
  const title = row.querySelector('.cell-main').textContent.trim();
  if (!confirm(`Delete "${title}"? This cannot be undone.`)) return;
  const data = await api(fd({ action: 'delete_job', job_id: row.dataset.id }));
  if (data.success) {
    row.style.transition = 'opacity .25s ease,transform .25s ease';
    row.style.opacity    = '0';
    row.style.transform  = 'translateX(12px)';
    setTimeout(() => row.remove(), 260);
    showToast('🗑 Job deleted.');
  }
}

// ── CLOSE QUIZ ───────────────────────────────────────────
async function doCloseQuiz(btn) {
  const row = btn.closest('tr');
  const data = await api(fd({ action: 'close_quiz', quiz_id: row.dataset.id }));
  if (data.success) {
    row.querySelector('.badge').className = 'badge badge-inactive';
    row.querySelector('.badge').innerHTML = '<span class="dot"></span>Closed';
    row.dataset.status = 'closed';
    btn.closest('.action-group').innerHTML = '';
    showToast('Quiz closed.');
  }
}

// ── EDIT QUIZ MODAL ──────────────────────────────────────
let _editQuizId = null;
let editQuizQCount = 0;

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
}

function renderTopicsSelector(containerId, inputName) {
  const container = document.getElementById(containerId);
  if (!container) return;
  
  let html = `<div class="topics-select-group">`;
  for (const [category, list] of Object.entries(TOPICS_BY_CATEGORY)) {
    html += `
      <div class="topics-cat-section">
        <div class="topics-cat-title">${escapeHtml(category)}</div>
        <div class="topics-chips-grid">
    `;
    list.forEach(t => {
      html += `
          <label class="topic-chip-checkbox">
            <input type="checkbox" name="${inputName}" value="${t.id}" data-name="${escapeHtml(t.name)}">
            <span>${escapeHtml(t.name)}</span>
          </label>
      `;
    });
    html += `
        </div>
      </div>
    `;
  }
  html += `</div>`;
  container.innerHTML = html;
}

async function openEditQuizModal(quizId) {
  _editQuizId = quizId;
  editQuizQCount = 0;
  openModal('modal-edit-quiz');
  const body = document.getElementById('edit-quiz-body');
  
  const quizObj = QUIZZES.find(q => parseInt(q.id) === parseInt(quizId));
  if (!quizObj) {
    body.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--color-danger-text);">Error: Quiz not found.</div>';
    return;
  }

  body.innerHTML = `
    <div class="field"><label>Quiz Title</label><input type="text" id="eq-title" placeholder="Quiz Title" value="${escapeHtml(quizObj.title)}"></div>
    <div class="field">
      <label>Topics</label>
      <div id="eq-topics-tags-container"></div>
    </div>
    <div id="eq-questions-list" style="display:flex;flex-direction:column;gap:10px;"></div>
    <button class="btn-add-question" onclick="addEqQuestion()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Question
    </button>
    <div class="field-row">
      <div class="field"><label>Pass Mark (%)</label><input type="number" id="eq-pass-mark" placeholder="e.g. 70" value="${quizObj.pass_mark}"></div>
      <div class="field"><label>Time Limit (min)</label><input type="number" id="eq-time-limit" placeholder="e.g. 45" value="${quizObj.time_limit}"></div>
    </div>
    <div id="edit-quiz-error" style="display:none;font-size:12.5px;color:var(--color-danger-text);padding:8px 12px;background:var(--color-danger-bg);border:1px solid var(--color-danger-border);border-radius:var(--radius-md);"></div>
  `;
  
  // Render topics selector
  renderTopicsSelector('eq-topics-tags-container', 'eq-quiz-topics');
  
  // Pre-select topics
  if (quizObj.topic_ids) {
    quizObj.topic_ids.forEach(tid => {
      const cb = body.querySelector(`#eq-topics-tags-container input[type="checkbox"][value="${tid}"]`);
      if (cb) cb.checked = true;
    });
  }

  // Populate questions
  const qList = document.getElementById('eq-questions-list');
  if (quizObj.questions && quizObj.questions.length > 0) {
    quizObj.questions.forEach((q) => {
      editQuizQCount++;
      const uid = 'eq' + Date.now() + editQuizQCount;
      const block = makeQuestionBlock(editQuizQCount, uid, 'eq-questions-list');
      qList.appendChild(block);
      updateQuestionTopics(uid, 'eq-topics-tags-container');
      
      block.querySelector('.field input[type="text"]').value = q.question_text;
      
      const typeSel = block.querySelector(`#type-sel-${uid}`);
      typeSel.value = q.question_type;
      onTypeChange(typeSel, uid);
      
      block.querySelector('.question-mark-input').value = q.mark;
      
      let opts = [];
      try {
        opts = JSON.parse(q.options_json || '[]');
      } catch(e) {}
      
      if (q.question_type === 'MCQ') {
        const optsList = block.querySelector(`#opts-${uid}`);
        optsList.innerHTML = '';
        opts.forEach((optText, optIdx) => {
          const letter = LETTERS[optIdx];
          const div = document.createElement('div');
          div.className = 'mcq-option';
          div.innerHTML = `<div class="mcq-option-letter">${letter}</div><input type="text" class="mcq-opt-input" placeholder="Option ${letter}…" value="${escapeHtml(optText)}" oninput="syncCorrectDropdown('${uid}')">${optIdx > 0 ? `<button class="btn-remove-opt" onclick="removeMCQOption(this,'${uid}')">×</button>` : `<span style="width:22px;flex-shrink:0;"></span>`}`;
          optsList.appendChild(div);
        });
        syncCorrectDropdown(uid);
        block.querySelector(`#correct-${uid}`).value = q.correct_answer;
      } else {
        block.querySelector(`#correct-${uid}`).value = q.correct_answer;
      }
      
      // Pre-select question topics
      if (q.topic_ids) {
        q.topic_ids.forEach(tid => {
          const cb = block.querySelector(`.q-topic-chk-${uid}[value="${tid}"]`);
          if (cb) cb.checked = true;
        });
      }
    });
  } else {
    addEqQuestion();
  }
}

function addEqQuestion() {
  editQuizQCount++;
  const list  = document.getElementById('eq-questions-list');
  const uid   = 'eq' + Date.now() + editQuizQCount;
  const block = makeQuestionBlock(editQuizQCount, uid, 'eq-questions-list');
  list.appendChild(block);
}

async function saveEditQuiz() {
  const title    = document.getElementById('eq-title')?.value.trim();
  const topics   = Array.from(document.querySelectorAll('#eq-topics-tags-container input[type="checkbox"]:checked')).map(cb => cb.value).join(',');
  const passMark = document.getElementById('eq-pass-mark')?.value || '70';
  const timeLimit= document.getElementById('eq-time-limit')?.value || '30';
  const questions = collectQuestions('#eq-questions-list');

  if (!title) { showErr('edit-quiz-error', 'Quiz title is required.'); return; }

  const btn = document.getElementById('saveEditQuizBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving…';

  const data = await api(fd({ action: 'edit_quiz', quiz_id: _editQuizId, title, topics, pass_mark: passMark, time_limit: timeLimit, questions: JSON.stringify(questions) }));
  btn.disabled = false; btn.textContent = 'Save Changes';

  if (data.success) {
    closeModal('modal-edit-quiz');
    sessionStorage.setItem('toastMessage', '✓ Quiz updated!');
    location.reload();
  } else {
    showErr('edit-quiz-error', data.message || 'Failed to save quiz.');
  }
}

// ── PUBLISH COURSE ───────────────────────────────────────
async function publishCourse() {
  const title      = document.getElementById('course-title').value.trim();
  const desc       = document.getElementById('course-desc').value.trim();
  const instructor = document.getElementById('course-instructor').value.trim();
  const duration   = document.getElementById('course-duration').value.trim();
  const modules    = document.getElementById('course-modules').value.trim();

  const topics = Array.from(document.querySelectorAll('#course-topics-container input[type="checkbox"]:checked')).map(cb => cb.value).join(',');

  if (!title || !instructor) { showErr('add-course-error', 'Title and instructor are required.'); return; }
  hideErr('add-course-error');

  const btn = document.getElementById('publishCourseBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Publishing…';

  const data = await api(fd({ action: 'add_course', title, description: desc, topics, instructor, duration, modules }));
  btn.disabled = false; btn.textContent = 'Publish Course';

  if (data.success) {
    closeModal('modal-add-course');
    sessionStorage.setItem('toastMessage', '✓ Course published!');
    location.reload();
  } else {
    showErr('add-course-error', data.message);
  }
}

// ── TOGGLE COURSE ────────────────────────────────────────
async function doToggleCourse(btn, newStatus) {
  const row = btn.closest('tr');
  const data = await api(fd({ action: 'toggle_course', course_id: row.dataset.id, new_status: newStatus }));
  if (data.success) {
    const badge = row.querySelector('.badge');
    if (newStatus === 'active') {
      badge.className = 'badge badge-active';
      badge.innerHTML = '<span class="dot"></span>Active';
      btn.closest('.action-group').innerHTML = '<button class="btn-action danger" onclick="doToggleCourse(this,\'closed\')">Unlist</button>';
    } else {
      badge.className = 'badge badge-inactive';
      badge.innerHTML = '<span class="dot"></span>Closed';
      btn.closest('.action-group').innerHTML = '<button class="btn-action dark" onclick="doToggleCourse(this,\'active\')">Relist</button>';
    }
    row.dataset.status = newStatus;
    showToast(newStatus === 'active' ? '✓ Course relisted.' : 'Course unlisted.');
  }
}

// ── DECIDE APPLICANT ─────────────────────────────────────
async function decideApplicant(btn, decision) {
  const row  = btn.closest('tr');
  const data = await api(fd({ action: 'decide_applicant', applicant_id: row.dataset.id, decision }));
  if (data.success) {
    const ag = row.querySelector('.action-group') || btn.parentElement;
    if (decision === 'approved') {
      ag.outerHTML = '<span class="badge badge-active" style="padding:5px 12px;">Approved</span>';
    } else {
      ag.outerHTML = '<span class="badge badge-inactive" style="padding:5px 12px;">Rejected</span>';
    }
    row.dataset.status = decision;
    showToast(decision === 'approved' ? '✓ Applicant approved.' : 'Applicant rejected.');
  }
}

async function viewApplicantCV(userId) {
  openModal('modal-view-cv');
  const body = document.getElementById('view-cv-body');
  body.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--color-text-muted);"><span class="spinner" style="border-top-color:#000;display:inline-block;"></span> Loading profile…</div>';

  try {
    const data = await api(fd({ action: 'get_applicant_cv', user_id: userId }));
    if (!data.success) {
      body.innerHTML = `<div style="text-align:center;padding:2rem;color:var(--color-danger-text);">${escapeHtml(data.message)}</div>`;
      return;
    }

    const u = data.user;
    const cv = u.cv_data_decoded || {};
    
    // Format sections
    let summaryHtml = cv.summary ? `<div class="cv-summary">${escapeHtml(cv.summary)}</div>` : '<div style="color:var(--color-text-subtle);font-style:italic;font-size:13px;">No summary provided.</div>';
    
    // Skills
    let skillsHtml = '<div style="color:var(--color-text-subtle);font-style:italic;font-size:13px;">No skills listed.</div>';
    if (cv.technologies && cv.technologies.length > 0) {
      skillsHtml = `<div class="cv-skills-grid">`;
      cv.technologies.forEach(s => {
        skillsHtml += `<span class="cv-skill-tag">${escapeHtml(s)}</span>`;
      });
      skillsHtml += `</div>`;
    }
    
    // Confidence metrics
    if (cv.skill_prog || cv.skill_db || cv.skill_ps || cv.skill_comm) {
      skillsHtml += `
        <div style="margin-top: 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; font-size: 12px; color: #555;">
          ${cv.skill_prog ? `<div><strong>Programming:</strong> ${cv.skill_prog}/5</div>` : ''}
          ${cv.skill_db ? `<div><strong>Databases:</strong> ${cv.skill_db}/5</div>` : ''}
          ${cv.skill_ps ? `<div><strong>Problem Solving:</strong> ${cv.skill_ps}/5</div>` : ''}
          ${cv.skill_comm ? `<div><strong>Communication:</strong> ${cv.skill_comm}/5</div>` : ''}
        </div>
      `;
    }
    
    // Projects
    let projectsHtml = '<div style="color:var(--color-text-subtle);font-style:italic;font-size:13px;">No projects listed.</div>';
    if (cv.projects && cv.projects.length > 0) {
      projectsHtml = `<div class="cv-projects-list">`;
      cv.projects.forEach(p => {
        projectsHtml += `
          <div class="cv-project-item">
            <div class="cv-item-header">
              <span class="cv-item-title">${escapeHtml(p.name)}</span>
              ${p.github ? `<a href="${escapeHtml(p.github)}" class="cv-link" target="_blank">Repository →</a>` : ''}
            </div>
            ${p.techs ? `<div style="font-size:11.5px;color:#666;margin: 2px 0;">Technologies: ${escapeHtml(p.techs)}</div>` : ''}
            <div class="cv-item-desc">${escapeHtml(p.desc)}</div>
          </div>
        `;
      });
      projectsHtml += `</div>`;
    }

    // Education
    let eduHtml = '<div style="color:var(--color-text-subtle);font-style:italic;font-size:13px;">No education history provided.</div>';
    if (cv.edu_institution || cv.edu_degree || cv.education_level) {
      eduHtml = `
        <div class="cv-experience-list">
          <div class="cv-project-item">
            <div class="cv-item-header">
              <span class="cv-item-title">${escapeHtml(cv.edu_institution || '—')}</span>
              <span class="cv-item-meta">${escapeHtml(cv.edu_year || '—')}</span>
            </div>
            <div class="cv-item-desc">${escapeHtml(cv.edu_degree || '—')} (${escapeHtml(cv.education_level || '—')})</div>
          </div>
        </div>
      `;
    }

    // Preferences / Arrangement
    let arrangementHtml = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;color:#444;">
        <div><strong>Arrangement:</strong> ${escapeHtml(cv.work_arrangement || '—')}</div>
        <div><strong>Employment Type:</strong> ${escapeHtml(cv.employment_type || '—')}</div>
        <div><strong>Primary Goal:</strong> ${escapeHtml(cv.primary_goal || '—')}</div>
        <div><strong>Weekly Availability:</strong> ${escapeHtml(cv.availability || '—')}</div>
      </div>
    `;

    body.innerHTML = `
      <div class="cv-container">
        <div class="cv-header">
          <h1 class="cv-name">${escapeHtml(u.name)}</h1>
          <div style="font-size:14px;font-weight:700;color:#000;margin-bottom:8px;">Target Role: ${escapeHtml(cv.role || '—')} | Field: ${escapeHtml(cv.field || '—')} (${escapeHtml(cv.experience_level || '—')})</div>
          <div class="cv-contact">
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> ${escapeHtml(u.email)}</span>
            ${u.phone ? `<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> ${escapeHtml(u.phone)}</span>` : ''}
            ${cv.link_portfolio ? `<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg> <a href="${escapeHtml(cv.link_portfolio)}" class="cv-link" target="_blank">Portfolio</a></span>` : ''}
            ${cv.link_linkedin ? `<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg> <a href="${escapeHtml(cv.link_linkedin)}" class="cv-link" target="_blank">LinkedIn</a></span>` : ''}
            ${cv.link_github ? `<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg> <a href="${escapeHtml(cv.link_github)}" class="cv-link" target="_blank">GitHub</a></span>` : ''}
          </div>
        </div>

        <div class="cv-section-title">Professional Summary</div>
        ${summaryHtml}

        <div class="cv-section-title">Skills & Technologies</div>
        ${skillsHtml}

        <div class="cv-section-title">Projects</div>
        ${projectsHtml}

        <div class="cv-section-title">Education</div>
        ${eduHtml}

        <div class="cv-section-title">Preferences & Goals</div>
        ${arrangementHtml}
      </div>
    `;
  } catch (err) {
    console.error("CV Load Error:", err);
    body.innerHTML = `<div style="text-align:center;padding:2rem;color:var(--color-text-muted);">Failed to retrieve profile info.</div>`;
  }
}

// ── SETTINGS ─────────────────────────────────────────────
function switchSettingsTab(tab, el) {
  document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('settings-' + tab).classList.add('active');
}

async function saveCompanyInfo() {
  const name    = document.getElementById('s-company-name').value.trim();
  const address = document.getElementById('s-company-address').value.trim();
  if (!name) { showMsg('company-msg', 'Company name is required.', false); return; }
  const data = await api(fd({ action: 'save_company', company_name: name, company_address: address }));
  showMsg('company-msg', data.success ? '✓ Company info saved!' : data.message, data.success);
  if (data.success) setTimeout(() => closeModal('modal-settings'), 1200);
}

async function saveProfileInfo() {
  const repName = document.getElementById('s-rep-name').value.trim();
  const repRole = document.getElementById('s-rep-role').value.trim();
  const email   = document.getElementById('s-email').value.trim();
  const phone   = document.getElementById('s-phone').value.trim();
  const data    = await api(fd({ action: 'save_profile', rep_name: repName, rep_role: repRole, email, phone }));
  showMsg('profile-msg', data.success ? '✓ Profile saved!' : data.message, data.success);
  if (data.success) {
    document.getElementById('sidebar-name').textContent = data.name;
    document.getElementById('sidebar-role').textContent = data.role;
    const initials = data.name.split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
    document.getElementById('sidebar-avatar').textContent = initials;
    setTimeout(() => closeModal('modal-settings'), 1200);
  }
}

async function changePassword() {
  const cur  = document.getElementById('s-cur-pw').value;
  const np   = document.getElementById('s-new-pw').value;
  const conf = document.getElementById('s-conf-pw').value;
  if (np !== conf) { showMsg('pw-change-msg', 'Passwords do not match.', false); return; }
  const data = await api(fd({ action: 'change_password', current_password: cur, new_password: np, confirm_password: conf }));
  showMsg('pw-change-msg', data.success ? '✓ Password updated!' : data.message, data.success);
  if (data.success) { ['s-cur-pw','s-new-pw','s-conf-pw'].forEach(id => document.getElementById(id).value = ''); }
}

function updateSettingsAvatar() {
  const name    = document.getElementById('s-rep-name')?.value.trim() || '';
  const role    = document.getElementById('s-rep-role')?.value.trim() || '';
  const company = document.getElementById('s-company-name')?.value.trim() || '';
  const initials = name.split(' ').filter(Boolean).map(w => w[0]).join('').slice(0,2).toUpperCase() || 'AD';
  const av = document.getElementById('s-avatar');
  if (av) av.textContent = initials;
  const an = document.getElementById('s-avatar-name');
  if (an) an.textContent = name || 'Your Name';
  const ar = document.getElementById('s-avatar-role');
  if (ar) ar.textContent = [role, company].filter(Boolean).join(' · ') || 'Your Role';
}

function toggleSettingsPw(id, btn) {
  const input = document.getElementById(id);
  input.type = input.type === 'password' ? 'text' : 'password';
  btn.innerHTML = input.type === 'text'
    ? `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
    : `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
}

function checkSettingsPwMatch() {
  const np = document.getElementById('s-new-pw').value;
  const cp = document.getElementById('s-conf-pw').value;
  const m  = document.getElementById('s-pw-mismatch');
  const i  = document.getElementById('s-conf-pw');
  if (cp && cp !== np) { m.style.display = ''; i.style.borderColor = 'var(--color-danger-text)'; }
  else                 { m.style.display = 'none'; i.style.borderColor = ''; }
}

// ── TAG INPUT ────────────────────────────────────────────
function addTag(e, wrapId, inputId) {
  if (e.key !== 'Enter') return;
  e.preventDefault();
  const input = document.getElementById(inputId);
  const val   = input.value.trim();
  if (!val) return;
  const wrap = document.getElementById(wrapId);
  const pill = document.createElement('span');
  pill.className = 'tag-pill';
  pill.innerHTML = `${val} <button onclick="removeTag(this)">×</button>`;
  wrap.insertBefore(pill, input);
  input.value = '';
}

function removeTag(btn) { btn.closest('.tag-pill').remove(); }

// Collects all confirmed tag pills from a wrap, plus any leftover
// un-Entered text still sitting in its input field.
function collectTags(wrapId, inputId) {
  const tags = Array.from(document.querySelectorAll(`#${wrapId} .tag-pill`))
    .map(p => p.textContent.replace('×','').trim());
  const input = document.getElementById(inputId);
  if (input) {
    const leftover = input.value.trim();
    if (leftover) tags.push(leftover);
  }
  return tags.join(', ');
}

// ── QUESTION BUILDER ─────────────────────────────────────
function buildAnswerArea(type, uid) {
  if (type === 'MCQ') return `<div class="answer-area" id="ans-${uid}">
    <div class="answer-area-label">Answer Options</div>
    <div class="mcq-options-list" id="opts-${uid}">
      <div class="mcq-option"><div class="mcq-option-letter">A</div><input type="text" class="mcq-opt-input" placeholder="Option A…" oninput="syncCorrectDropdown('${uid}')"><span style="width:22px;flex-shrink:0;"></span></div>
      <div class="mcq-option"><div class="mcq-option-letter">B</div><input type="text" class="mcq-opt-input" placeholder="Option B…" oninput="syncCorrectDropdown('${uid}')"><button class="btn-remove-opt" onclick="removeMCQOption(this,'${uid}')">×</button></div>
    </div>
    <button class="btn-add-option" id="addopt-${uid}" onclick="addMCQOption('${uid}')">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add option
    </button>
    <div class="correct-answer-row">
      <span class="correct-answer-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Correct Answer</span>
      <select class="correct-answer-select" id="correct-${uid}"><option value="">— select —</option><option value="A">Option A</option><option value="B">Option B</option></select>
    </div></div>`;

  if (type === 'True/False') return `<div class="answer-area" id="ans-${uid}">
    <div class="correct-answer-row" style="margin-top:10px;">
      <span class="correct-answer-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Correct Answer</span>
      <select class="correct-answer-select" id="correct-${uid}"><option value="">— select —</option><option value="True">True</option><option value="False">False</option></select>
    </div></div>`;

  return `<div class="answer-area" id="ans-${uid}">
    <div class="correct-answer-row">
      <span class="correct-answer-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Correct Answer</span>
      <input type="text" class="correct-answer-text-input" placeholder="Type the correct answer…" id="correct-${uid}">
    </div>
    <span style="font-size:11.5px;color:var(--color-text-subtle);margin-top:4px;display:block;">Candidates type a free-form response.</span>
  </div>`;
}

function syncCorrectDropdown(uid) {
  const sel = document.getElementById('correct-' + uid);
  if (!sel || sel.tagName !== 'SELECT') return;
  const prev = sel.value;
  const opts = document.querySelectorAll(`#opts-${uid} .mcq-option`);
  sel.innerHTML = '<option value="">— select —</option>';
  opts.forEach((opt, i) => {
    const letter = LETTERS[i];
    const text   = opt.querySelector('.mcq-opt-input')?.value.trim() || 'Option ' + letter;
    const o = document.createElement('option');
    o.value = letter; o.textContent = letter + '. ' + text;
    sel.appendChild(o);
  });
  if (prev) sel.value = prev;
}

function addMCQOption(uid) {
  const optsList = document.getElementById('opts-' + uid);
  const addBtn   = document.getElementById('addopt-' + uid);
  const opts     = optsList.querySelectorAll('.mcq-option');
  if (opts.length >= 6) { addBtn.style.display = 'none'; return; }
  const letter = LETTERS[opts.length];
  const div    = document.createElement('div');
  div.className = 'mcq-option';
  div.innerHTML = `<div class="mcq-option-letter">${letter}</div><input type="text" class="mcq-opt-input" placeholder="Option ${letter}…" oninput="syncCorrectDropdown('${uid}')"><button class="btn-remove-opt" onclick="removeMCQOption(this,'${uid}')">×</button>`;
  optsList.appendChild(div);
  syncCorrectDropdown(uid);
  if (opts.length + 1 >= 6) addBtn.style.display = 'none';
}

function removeMCQOption(btn, uid) {
  const optsList = document.getElementById('opts-' + uid);
  btn.closest('.mcq-option').remove();
  optsList.querySelectorAll('.mcq-option').forEach((opt, i) => {
    opt.querySelector('.mcq-option-letter').textContent = LETTERS[i];
    opt.querySelector('.mcq-opt-input').placeholder    = 'Option ' + LETTERS[i] + '…';
  });
  document.getElementById('addopt-' + uid).style.display = '';
  syncCorrectDropdown(uid);
}

function onTypeChange(select, uid) {
  const block   = select.closest('.question-block');
  const oldArea = block.querySelector('.answer-area');
  const tmp     = document.createElement('div');
  tmp.innerHTML  = buildAnswerArea(select.value, uid);
  if (oldArea) block.replaceChild(tmp.firstElementChild, oldArea);
}

function updateQuestionTopics(uid, parentContainerId) {
  const contentDiv = document.getElementById(`q-topics-content-${uid}`);
  if (!contentDiv) return;

  // Get currently selected quiz topic IDs
  const checkedTopicIds = Array.from(document.querySelectorAll(`#${parentContainerId} input[type="checkbox"]:checked`))
                               .map(cb => cb.value.toString());

  if (checkedTopicIds.length === 0) {
    contentDiv.innerHTML = '<div style="font-size:12px;color:var(--color-text-muted);padding:8px 0;text-align:center;">Please select covered topics at the top of the quiz form first.</div>';
    return;
  }

  // Get currently checked topics inside this question so we don't lose the selection
  const currentCheckedIds = Array.from(contentDiv.querySelectorAll('input[type="checkbox"]:checked'))
                                 .map(cb => cb.value.toString());

  // Filter global topics to only include those selected in the quiz
  const activeTopicsByCategory = {};
  for (const [category, list] of Object.entries(TOPICS_BY_CATEGORY)) {
    const filteredList = list.filter(t => checkedTopicIds.includes(t.id.toString()));
    if (filteredList.length > 0) {
      activeTopicsByCategory[category] = filteredList;
    }
  }

  let html = '';
  for (const [category, list] of Object.entries(activeTopicsByCategory)) {
    html += `
      <div class="topics-cat-section">
        <div class="topics-cat-title">${escapeHtml(category)}</div>
        <div class="topics-chips-grid">
    `;
    list.forEach(t => {
      const isChecked = currentCheckedIds.includes(t.id.toString()) ? 'checked' : '';
      html += `
          <label class="topic-chip-checkbox">
            <input type="checkbox" class="q-topic-chk-${uid}" value="${t.id}" ${isChecked}>
            <span>${escapeHtml(t.name)}</span>
          </label>
      `;
    });
    html += `
        </div>
      </div>
    `;
  }
  contentDiv.innerHTML = html || '<div style="font-size:12px;color:var(--color-text-muted);padding:8px 0;text-align:center;">No topics found.</div>';
}

function makeQuestionBlock(num, uid, listId) {
  const parentContainerId = listId === 'eq-questions-list' ? 'eq-topics-tags-container' : 'topics-tags-container';
  const block = document.createElement('div');
  block.className = 'question-block';
  block.innerHTML = `
    <div class="question-block-header">
      <span class="question-num">Question ${num}</span>
      <button class="btn-icon" onclick="this.closest('.question-block').remove();renumberQuestions('${listId}');recalcTotalMarks();">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
      </button>
    </div>
    <div class="field"><input type="text" placeholder="Enter your question…"></div>
    <div class="field-row" style="grid-template-columns:1fr 80px;">
      <div class="field"><label>Question Type</label><select id="type-sel-${uid}"><option value="MCQ">MCQ (Multiple Choice)</option><option value="True/False">True / False</option><option value="Text">Text (Free Response)</option></select></div>
      <div class="field"><label>Mark</label><input type="number" class="question-mark-input" placeholder="5" min="1" oninput="recalcTotalMarks()"></div>
    </div>
    <div class="field">
      <label style="font-size:12px;font-weight:600;margin-top:4px;">Question Topics</label>
      <details class="q-topics-details" ontoggle="if(this.open) updateQuestionTopics('${uid}', '${parentContainerId}')">
        <summary class="q-topics-summary">Select Topics</summary>
        <div class="q-topics-content" id="q-topics-content-${uid}">
          <div style="font-size:12.5px;color:var(--color-text-muted);text-align:center;">Please select covered topics at the top of the quiz form first.</div>
        </div>
      </details>
    </div>`;
  const tmp = document.createElement('div');
  tmp.innerHTML = buildAnswerArea('MCQ', uid);
  block.appendChild(tmp.firstElementChild);
  block.querySelector(`#type-sel-${uid}`).addEventListener('change', function() { onTypeChange(this, uid); });
  return block;
}

function addQuestion() {
  questionCount++;
  const uid   = 'q' + Date.now() + questionCount;
  const block = makeQuestionBlock(questionCount, uid, 'questions-list');
  document.getElementById('questions-list').appendChild(block);
}

function renumberQuestions(listId) {
  document.querySelectorAll('#' + listId + ' .question-num').forEach((el, i) => {
    el.textContent = 'Question ' + (i + 1);
  });
  questionCount = document.getElementById(listId)?.children.length || 0;
}

function recalcTotalMarks() {
  const total = Array.from(document.querySelectorAll('#questions-list .question-mark-input'))
    .reduce((sum, i) => sum + (parseFloat(i.value) || 0), 0);
  document.getElementById('quiz-total-marks').textContent = total;
}

function collectQuestions(listSelector) {
  const blocks = document.querySelectorAll(listSelector + ' .question-block');
  return Array.from(blocks).map(block => {
    const text    = block.querySelector('.field input[type="text"]')?.value.trim() || '';
    const typeEl  = block.querySelector('select[id^="type-sel-"],select[id^="eq-type-"]');
    const type    = typeEl?.value || 'MCQ';
    const mark    = parseFloat(block.querySelector('.question-mark-input,input[type="number"]')?.value) || 1;
    const uid     = typeEl?.id.replace('type-sel-','').replace('eq-type-','');
    
    // Collect selected topic IDs
    const topics  = Array.from(block.querySelectorAll(`input[type="checkbox"][class^="q-topic-chk-"]:checked`))
                         .map(cb => parseInt(cb.value));

    let options   = [];
    let correct   = '';
    if (type === 'MCQ' && uid) {
      const opts = block.querySelectorAll(`#opts-${uid} .mcq-opt-input`);
      options    = Array.from(opts).map(o => o.value.trim());
      const sel  = block.querySelector(`#correct-${uid}`);
      correct    = sel ? sel.value : '';
    } else if (type === 'True/False' && uid) {
      const sel  = block.querySelector(`#correct-${uid}`);
      correct    = sel ? sel.value : '';
    } else if (type === 'Text' && uid) {
      const inp  = block.querySelector(`#correct-${uid}`);
      correct    = inp ? inp.value.trim() : '';
    }
    return { text, type, mark, options, correct, topics };
  });
}

// ── DOM CONTENT LOADED INITIALIZER ────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Render topics multiselect for creation forms
  renderTopicsSelector('topics-tags-container', 'quiz_topics');
  renderTopicsSelector('course-topics-container', 'course_topics');

  // Restore active tab
  const savedTab = localStorage.getItem('activeTab') || 'overview';
  const navBtn = document.querySelector(`.sidebar-nav .nav-item[data-tab="${savedTab}"]`);
  if (navBtn) {
    switchTab(savedTab, navBtn);
  } else {
    const firstNavBtn = document.querySelector('.sidebar-nav .nav-item');
    if (firstNavBtn) switchTab('overview', firstNavBtn);
  }

  // Show pending toast if any
  const pendingToast = sessionStorage.getItem('toastMessage');
  if (pendingToast) {
    showToast(pendingToast);
    sessionStorage.removeItem('toastMessage');
  }
});
</script>
</body>
</html>
