<?php
session_start();

// ============================================================
// DATABASE INTEGRATION POINT — AUTH CHECK
// When DB is ready, uncomment the block below
// and remove the dummy session block beneath it
// ============================================================

// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// ============================================================
// DUMMY SESSION DATA — Replace with real session values later
// ============================================================
$user_id  = 1;
$username = "Tobias";          // First name (used in greeting + avatar)
$fullname = "Tobias Reaper";   // ← Full name changed here
$email    = "tobias@email.com";
$field    = "Web Development";

// ============================================================
// DATABASE INTEGRATION POINT — FETCH SKILLS FROM DB
// Replace $skills array below with a DB query like:
//
// $skills = [];
// $stmt = $pdo->prepare("SELECT skill_name, level FROM user_skills
//                         WHERE user_id = ?");
// $stmt->execute([$user_id]);
// $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
// ============================================================
$skills = [
    ["name" => "HTML & CSS",  "level" => 90, "color" => "#f97316"],
    ["name" => "JavaScript",  "level" => 72, "color" => "#eab308"],
    ["name" => "React",       "level" => 60, "color" => "#3b82f6"],
    ["name" => "SQL",         "level" => 35, "color" => "#8b5cf6"],
    ["name" => "Node.js",     "level" => 45, "color" => "#10b981"],
];

// ============================================================
// DATABASE INTEGRATION POINT — FETCH RECOMMENDED JOBS FROM DB
// Replace $jobs array below with a DB query like:
//
// $stmt = $pdo->prepare("SELECT jobs.*, match_score
//                         FROM jobs
//                         JOIN user_job_matches ON jobs.id = job_id
//                         WHERE user_id = ?
//                         ORDER BY match_score DESC
//                         LIMIT 5");
// $stmt->execute([$user_id]);
// $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
// ============================================================
$jobs = [
    [
        "id"          => 1,
        "title"       => "Frontend Developer",
        "company"     => "Google",
        "logo_emoji"  => "G",
        "logo_bg"     => "#e8f0fe",
        "logo_color"  => "#4285f4",
        "match"       => 92,
        "description" => "Build beautiful UIs for millions of users using React and modern CSS.",
        "tags"        => ["React", "CSS", "TypeScript"],
        "type"        => "Full-time",
        "location"    => "Remote",
        "salary"      => "\\$95k – \\$130k",
        "full_desc"   => "Join Google's frontend team to craft pixel-perfect interfaces for consumer-facing products. You'll collaborate with UX designers and backend engineers to ship impactful features.",
    ],
    [
        "id"          => 2,
        "title"       => "UI/UX Designer",
        "company"     => "Figma",
        "logo_emoji"  => "F",
        "logo_bg"     => "#fce7f3",
        "logo_color"  => "#ec4899",
        "match"       => 85,
        "description" => "Design intuitive user experiences for a world-class design tool.",
        "tags"        => ["Figma", "Prototyping", "User Research"],
        "type"        => "Full-time",
        "location"    => "San Francisco",
        "salary"      => "\\$90k – \\$120k",
        "full_desc"   => "Shape the future of design tooling at Figma. Work closely with product managers and engineers to create seamless experiences that help designers worldwide.",
    ],
    [
        "id"          => 3,
        "title"       => "JavaScript Engineer",
        "company"     => "Vercel",
        "logo_emoji"  => "V",
        "logo_bg"     => "#f1f5f9",
        "logo_color"  => "#0f172a",
        "match"       => 78,
        "description" => "Work on the edge runtime powering Next.js and modern web deployments.",
        "tags"        => ["JavaScript", "Node.js", "CDN"],
        "type"        => "Full-time",
        "location"    => "Remote",
        "salary"      => "\\$100k – \\$140k",
        "full_desc"   => "Help build the infrastructure that millions of developers rely on. You'll work on Vercel's edge network, serverless functions, and developer experience tools.",
    ],
    [
        "id"          => 4,
        "title"       => "React Native Developer",
        "company"     => "Shopify",
        "logo_emoji"  => "S",
        "logo_bg"     => "#d1fae5",
        "logo_color"  => "#065f46",
        "match"       => 71,
        "description" => "Build cross-platform mobile apps for Shopify merchants worldwide.",
        "tags"        => ["React Native", "Mobile", "GraphQL"],
        "type"        => "Full-time",
        "location"    => "Ottawa / Remote",
        "salary"      => "\\$88k – \\$115k",
        "full_desc"   => "Join Shopify's mobile team to build features used by 2 million+ merchants. You'll ship to both iOS and Android using React Native with a focus on performance.",
    ],
    [
        "id"          => 5,
        "title"       => "Web Developer",
        "company"     => "Stripe",
        "logo_emoji"  => "S",
        "logo_bg"     => "#ede9fe",
        "logo_color"  => "#7c3aed",
        "match"       => 65,
        "description" => "Develop and maintain Stripe's developer documentation and web tools.",
        "tags"        => ["HTML", "CSS", "API"],
        "type"        => "Contract",
        "location"    => "Remote",
        "salary"      => "\\$75k – \\$100k",
        "full_desc"   => "Help Stripe developers get up and running quickly. You'll work on the docs platform, interactive examples, and tools that lower the barrier to integrating Stripe.",
    ],
];

// ============================================================
// DATABASE INTEGRATION POINT — FETCH RECOMMENDED COURSES FROM DB
// Replace $courses array below with a DB query like:
//
// $stmt = $pdo->prepare("SELECT courses.* FROM courses
//                         JOIN user_course_recommendations
//                         ON courses.id = course_id
//                         WHERE user_id = ?
//                         ORDER BY priority ASC");
// $stmt->execute([$user_id]);
// $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
// ============================================================
$courses = [
    [
        "title"      => "SQL for Beginners",
        "icon"       => "🗄️",
        "icon_bg"    => "#ede9fe",
        "skill"      => "Master database queries — your weakest area right now",
        "difficulty" => "Beginner",
        "diff_class" => "diff-beginner",
        "duration"   => "4h 30m",
    ],
    [
        "title"      => "Advanced React Patterns",
        "icon"       => "⚛️",
        "icon_bg"    => "#dbeafe",
        "skill"      => "Level up your React with hooks, context and performance tricks",
        "difficulty" => "Intermediate",
        "diff_class" => "diff-intermediate",
        "duration"   => "6h",
    ],
    [
        "title"      => "Node.js Fundamentals",
        "icon"       => "🟢",
        "icon_bg"    => "#d1fae5",
        "skill"      => "Build backend APIs and understand server-side JavaScript",
        "difficulty" => "Beginner",
        "diff_class" => "diff-beginner",
        "duration"   => "5h 15m",
    ],
    [
        "title"      => "TypeScript Crash Course",
        "icon"       => "🔷",
        "icon_bg"    => "#dbeafe",
        "skill"      => "Type safety for JavaScript developers — a must-have skill",
        "difficulty" => "Intermediate",
        "diff_class" => "diff-intermediate",
        "duration"   => "3h 45m",
    ],
];

// ============================================================
// DATABASE INTEGRATION POINT — FETCH PROFILE STATS FROM DB
// Replace $stats array below with real counts from DB like:
//
// $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_job_matches
//                         WHERE user_id = ?");
// $stmt->execute([$user_id]);
// $job_match_count = $stmt->fetchColumn();
// ============================================================
$stats = [
    "job_matches"       => 5,   // count from user_job_matches table
    "new_courses"       => 3,   // count from user_course_recommendations table
    "profile_complete"  => 72,  // calculate based on filled profile fields
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
      <a href="logout.php" class="logout-btn">
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

          <div class="job-card" onclick="openJobModal(<?php echo $job['id']; ?>)">

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

            <button class="view-job-btn"
              onclick="event.stopPropagation(); openJobModal(<?php echo $job['id']; ?>)">
              View Job →
            </button>

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

            <button class="start-course-btn">
              <i class="fas fa-play"></i> Start Course
            </button>

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