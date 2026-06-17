<?php
session_start();

// ── Auth guard ──────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hireready_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$userId = (int)$_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userInfo = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$fullName = htmlspecialchars($userInfo['full_name'] ?? '');
$email    = htmlspecialchars($userInfo['email'] ?? '');
$phone    = htmlspecialchars($userInfo['phone'] ?? '');

// ── Handle final survey submission ──────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_survey'])) {
    $field  = trim($_POST['field'] ?? '');
    $cvData = trim($_POST['cv_data'] ?? '');
    $cvDataArray = json_decode($cvData, true);
    $role = $cvDataArray['role'] ?? '';

    // Auto-migrate: check if cv_data column exists in users table, if not add it
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'cv_data'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN cv_data LONGTEXT DEFAULT NULL");
    }
    
    // Auto-migrate: check if role column exists in users table, if not add it
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(255) DEFAULT NULL");
    }

    $stmt = $conn->prepare("UPDATE users SET field=?, role=?, cv_data=?, survey_done=1 WHERE id=?");
    $stmt->bind_param('sssi', $field, $role, $cvData, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    $_SESSION['survey_done'] = true;
    $_SESSION['field']       = $field;

    header('Location: dashboard.php');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireReady — Tell Us About You</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* ── Animations ─────────────────────── */
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-32px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(32px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0%,100% { transform: translateY(0px); }
            50%      { transform: translateY(-8px); }
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%     { transform: translateX(-6px); }
            40%     { transform: translateX(6px); }
            60%     { transform: translateX(-4px); }
            80%     { transform: translateX(4px); }
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.5; transform: scale(0.85); }
        }

        .anim-left  { animation: slideInLeft  0.7s ease forwards; }
        .anim-right { opacity: 0; animation: slideInRight 0.7s ease 0.15s forwards; }
        .float-1    { animation: float 4s ease-in-out infinite; }
        .float-2    { animation: float 4s ease-in-out 0.6s infinite; }
        .shake      { animation: shake 0.4s ease; }

        /* ── Panels ─────────────────────────── */
        .panel { display: none; }
        .panel.active {
            display: block;
            animation: fadeUp 0.35s ease forwards;
        }

        /* ── Deco card ──────────────────────── */
        .deco-card {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            padding: 18px 20px;
            backdrop-filter: blur(6px);
        }

        /* ── Left step dots ─────────────────── */
        .dot-inactive { background: #374151; color: #6b7280; }
        .dot-active   { background: #a8d5c2; color: #111; }
        .dot-done     { background: #22c55e; color: #fff; }

        /* ── Survey option cards ────────────── */
        .option-card {
            padding: 13px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            color: #374151;
            text-align: center;
            transition: all 0.2s;
            user-select: none;
        }
        .option-card:hover {
            border-color: #111;
            background: #f9fafb;
        }
        .option-card.selected {
            border-color: #111;
            background: #111;
            color: #fff;
        }

        /* ── Skill chips ────────────────────── */
        .skill-chip {
            padding: 8px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 99px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 500;
            color: #374151;
            transition: all 0.2s;
            user-select: none;
            white-space: nowrap;
        }
        .skill-chip:hover  { border-color: #111; background: #f9fafb; }
        .skill-chip.selected {
            background: #111;
            border-color: #111;
            color: #fff;
        }

        /* ── Range sliders ──────────────────── */
        .skill-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 5px;
            background: #e5e7eb;
            border-radius: 99px;
            outline: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .skill-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #111;
            cursor: pointer;
            border: 3px solid #fff;
            box-shadow: 0 0 0 1.5px #111;
            transition: transform 0.15s;
        }
        .skill-slider::-webkit-slider-thumb:hover { transform: scale(1.2); }
        .skill-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #111;
            cursor: pointer;
            border: 3px solid #fff;
            box-shadow: 0 0 0 1.5px #111;
        }

        /* ── Progress bar ───────────────────── */
        .progress-track {
            height: 4px;
            background: #e5e7eb;
            border-radius: 99px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .progress-fill {
            height: 100%;
            background: #111;
            border-radius: 99px;
            transition: width 0.5s ease;
        }

        /* ── Buttons ────────────────────────── */
        .btn-main {
            width: 100%;
            padding: 14px;
            background: #111;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        .btn-main:hover:not(:disabled) {
            background: #2d2d2d;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .btn-main:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn-back {
            width: 100%;
            padding: 13px;
            background: #fff;
            color: #111;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: border-color 0.2s, transform 0.2s;
        }
        .btn-back:hover {
            border-color: #111;
            transform: translateY(-1px);
        }

        /* ── Loading dots ───────────────────── */
        .loading-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #a8d5c2;
            animation: pulse 1.2s ease-in-out infinite;
        }
        .loading-dot:nth-child(2) { animation-delay: 0.2s; background: #b8cef0; }
        .loading-dot:nth-child(3) { animation-delay: 0.4s; background: #f5c5a3; }

        .spinner {
            width: 52px;
            height: 52px;
            border: 4px solid #e5e7eb;
            border-top-color: #111;
            border-radius: 50%;
            animation: spin 0.85s linear infinite;
            margin: 0 auto 32px;
        }
    </style>
</head>
<body class="bg-white min-h-screen">

<div class="flex min-h-screen">

    <!-- ════════════════════════════════════
         LEFT PANEL (CV Sections Checklist)
    ════════════════════════════════════ -->
    <div class="hidden lg:flex lg:w-1/2 bg-[#111] flex-col justify-between p-12 relative overflow-hidden anim-left">

        <!-- Deco Blobs -->
        <div class="absolute top-[-100px] left-[-100px] w-72 h-72 rounded-full bg-[#a8d5c2] opacity-20 blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-[-80px] right-[-80px] w-80 h-80 rounded-full bg-[#b8cef0] opacity-20 blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/3 right-[-40px] w-56 h-56 rounded-full bg-[#f5c5a3] opacity-10 blur-3xl pointer-events-none"></div>

        <!-- Logo -->
        <a href="index.php" class="text-white font-black text-2xl tracking-tight no-underline relative z-10">
            HireReady
        </a>

        <!-- Middle Content -->
        <div class="relative z-10">

            <!-- Deco Mini Cards -->
            <div class="flex gap-4 mb-8">
                <div class="deco-card flex-1 float-1">
                    <div class="text-white font-bold text-sm mb-1">Smart Matching</div>
                    <div class="text-gray-400 text-xs">Jobs matched to your real skills</div>
                </div>
                <div class="deco-card flex-1 float-2">
                    <div class="text-white font-bold text-sm mb-1">Guided Growth</div>
                    <div class="text-gray-400 text-xs">Courses that fill your gaps</div>
                </div>
            </div>

            <!-- Headline & Subtext -->
            <h2 id="leftHeadline" class="text-white font-black text-3xl leading-tight tracking-tight mb-4">
                Let's build<br>your professional<br><span class="text-[#a8d5c2]">CV profile.</span>
            </h2>

            <p id="leftSubtext" class="text-gray-400 text-xs leading-relaxed mb-8 max-w-xs">
                Provide your details to build a tailored profile and unlock recommended jobs and courses.
            </p>

            <!-- CV Sections Checklist -->
            <div class="flex flex-col gap-2 mt-4">
                <!-- Section 1: Personal Information -->
                <div class="flex items-center gap-4">
                    <div id="dot-personal" class="w-7 h-7 rounded-full dot-done flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300">
                        ✓
                    </div>
                    <div>
                        <div class="text-white text-xs font-semibold">Personal Information</div>
                        <div class="text-gray-400 text-[10px] mt-0.5 max-w-[280px] truncate">
                            <?= $fullName ?> · <?= $email ?> <?= $phone ? '· ' . $phone : '' ?>
                        </div>
                    </div>
                </div>

                <div class="w-px h-3 bg-gray-700 ml-3.5"></div>

                <!-- Section 2: Professional Summary -->
                <div class="flex items-center gap-4">
                    <div id="dot-summary" class="w-7 h-7 rounded-full dot-inactive flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300">
                        2
                    </div>
                    <div>
                        <div id="label-summary" class="text-gray-500 text-xs font-semibold transition-colors duration-300">Professional Summary</div>
                        <div id="sub-summary" class="text-[#a8d5c2] text-[10px] font-medium hidden">You are here</div>
                    </div>
                </div>

                <div class="w-px h-3 bg-gray-700 ml-3.5"></div>

                <!-- Section 3: Skills -->
                <div class="flex items-center gap-4">
                    <div id="dot-skills" class="w-7 h-7 rounded-full dot-inactive flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300">
                        3
                    </div>
                    <div>
                        <div id="label-skills" class="text-gray-500 text-xs font-semibold transition-colors duration-300">Skills &amp; Technologies</div>
                        <div id="sub-skills" class="text-[#a8d5c2] text-[10px] font-medium hidden">You are here</div>
                    </div>
                </div>

                <div class="w-px h-3 bg-gray-700 ml-3.5"></div>

                <!-- Section 4: Projects -->
                <div class="flex items-center gap-4">
                    <div id="dot-projects" class="w-7 h-7 rounded-full dot-inactive flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300">
                        4
                    </div>
                    <div>
                        <div id="label-projects" class="text-gray-500 text-xs font-semibold transition-colors duration-300">Projects &amp; Portfolio</div>
                        <div id="sub-projects" class="text-[#a8d5c2] text-[10px] font-medium hidden">You are here</div>
                    </div>
                </div>

                <div class="w-px h-3 bg-gray-700 ml-3.5"></div>

                <!-- Section 5: Education -->
                <div class="flex items-center gap-4">
                    <div id="dot-education" class="w-7 h-7 rounded-full dot-inactive flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300">
                        5
                    </div>
                    <div>
                        <div id="label-education" class="text-gray-500 text-xs font-semibold transition-colors duration-300">Education</div>
                        <div id="sub-education" class="text-[#a8d5c2] text-[10px] font-medium hidden">You are here</div>
                    </div>
                </div>

                <div class="w-px h-3 bg-gray-700 ml-3.5"></div>

                <!-- Section 6: Links -->
                <div class="flex items-center gap-4">
                    <div id="dot-links" class="w-7 h-7 rounded-full dot-inactive flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300">
                        6
                    </div>
                    <div>
                        <div id="label-links" class="text-gray-500 text-xs font-semibold transition-colors duration-300">Links (GitHub, LinkedIn, Portfolio)</div>
                        <div id="sub-links" class="text-[#a8d5c2] text-[10px] font-medium hidden">You are here</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Testimonial -->
        <div class="deco-card relative z-10 mt-6">
            <p class="text-gray-300 text-xs italic leading-relaxed mb-3">
                "The survey took 2 minutes. My dashboard generated a beautiful CV and matched me with relevant developer roles instantly."
            </p>
            <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-full bg-[#b8cef0] flex items-center justify-center text-black font-bold text-xs">
                    S
                </div>
                <div>
                    <div class="text-white text-xs font-bold">Saif K.</div>
                    <div class="text-gray-500 text-[10px]">Frontend Developer</div>
                </div>
                <div class="ml-auto text-yellow-400 text-xs tracking-widest">
                    ★ ★ ★ ★ ★
                </div>
            </div>
        </div>

    </div>

    <!-- ════════════════════════════════════
         RIGHT PANEL (Survey Questionnaire)
    ════════════════════════════════════ -->
    <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12 overflow-y-auto anim-right">
        <div class="w-full max-w-md">

            <!-- Mobile Logo -->
            <div class="lg:hidden mb-8">
                <a href="index.php" class="text-gray-900 font-black text-xl tracking-tight no-underline">
                    HireReady
                </a>
            </div>

            <!-- ══════════════════════════════
                 SURVEY STEP 1 — Career Info
            ══════════════════════════════ -->
            <div id="panel1" class="panel active">
                <div class="progress-track">
                    <div class="progress-fill" style="width: 16.66%;"></div>
                </div>

                <div class="mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">
                        Step 1 of 6
                    </p>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-1">
                        Career Information
                    </h1>
                    <p class="text-gray-400 text-sm">
                        Tell us about your targeted career path.
                    </p>
                </div>

                <!-- Q1: Technology Field -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Which technology field interests you most?
                    </label>
                    <div class="grid grid-cols-2 gap-2" id="fieldCards">
                        <div class="option-card" onclick="selectOption(this,'fieldCards','field')">Web Development</div>
                        <div class="option-card" onclick="selectOption(this,'fieldCards','field')">Mobile Development</div>
                        <div class="option-card" onclick="selectOption(this,'fieldCards','field')">Data Science</div>
                        <div class="option-card" onclick="selectOption(this,'fieldCards','field')">Cybersecurity</div>
                        <div class="option-card" onclick="selectOption(this,'fieldCards','field')">Cloud &amp; DevOps</div>
                        <div class="option-card" onclick="selectOption(this,'fieldCards','field')">UI/UX Design</div>
                        <div class="option-card" onclick="selectOption(this,'fieldCards','field')">Artificial Intelligence</div>
                    </div>
                    <p id="err-field" class="text-red-500 text-xs mt-1 hidden">Please select a field.</p>
                </div>

                <!-- Q2: Role Targeted -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Which role are you targeting?
                    </label>
                    <select id="targetRole" class="w-full border-1.5 border-gray-200 rounded-lg p-3 text-sm focus:border-black outline-none transition bg-white" onchange="survey.role = this.value">
                        <option value="">Select a targeted role...</option>
                        <option value="Frontend Developer">Frontend Developer</option>
                        <option value="Backend Developer">Backend Developer</option>
                        <option value="Full Stack Developer">Full Stack Developer</option>
                        <option value="Mobile Developer">Mobile Developer</option>
                        <option value="Data Analyst">Data Analyst</option>
                        <option value="Data Scientist">Data Scientist</option>
                        <option value="Cybersecurity Analyst">Cybersecurity Analyst</option>
                        <option value="DevOps Engineer">DevOps Engineer</option>
                        <option value="UI/UX Designer">UI/UX Designer</option>
                        <option value="AI/ML Engineer">AI/ML Engineer</option>
                    </select>
                    <p id="err-role" class="text-red-500 text-xs mt-1 hidden">Please select a targeted role.</p>
                </div>

                <!-- Q3: Experience Level -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Current experience level
                    </label>
                    <div class="grid grid-cols-3 gap-2" id="expCards">
                        <div class="option-card" onclick="selectOption(this,'expCards','experience_level')">Beginner</div>
                        <div class="option-card" onclick="selectOption(this,'expCards','experience_level')">Intermediate</div>
                        <div class="option-card" onclick="selectOption(this,'expCards','experience_level')">Advanced</div>
                    </div>
                    <p id="err-exp" class="text-red-500 text-xs mt-1 hidden">Please select your experience level.</p>
                </div>

                <!-- Q4: Current Status -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Current status
                    </label>
                    <div class="grid grid-cols-2 gap-2" id="statusCards">
                        <div class="option-card" onclick="selectOption(this,'statusCards','current_status')">Student</div>
                        <div class="option-card" onclick="selectOption(this,'statusCards','current_status')">Fresh Graduate</div>
                        <div class="option-card" onclick="selectOption(this,'statusCards','current_status')">Working Professional</div>
                        <div class="option-card" onclick="selectOption(this,'statusCards','current_status')">Career Switcher</div>
                    </div>
                    <p id="err-status" class="text-red-500 text-xs mt-1 hidden">Please select your current status.</p>
                </div>

                <button type="button" id="btn1" onclick="nextStep1()" class="btn-main">Next</button>
            </div>


            <!-- ══════════════════════════════
                 SURVEY STEP 2 — Skills
            ══════════════════════════════ -->
            <div id="panel2" class="panel">
                <div class="progress-track">
                    <div class="progress-fill" style="width: 33.33%;"></div>
                </div>

                <div class="mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">
                        Step 2 of 6
                    </p>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-1">
                        Skills &amp; Technologies
                    </h1>
                    <p class="text-gray-400 text-sm">
                        Select technologies you have used and rate your confidence.
                    </p>
                </div>

                <!-- Q1: Technology Chips Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Select technologies you have used
                    </label>
                    <p class="text-xs text-gray-400 mb-2">
                        Select all that apply.
                    </p>
                    <div class="flex flex-wrap gap-1.5 max-h-56 overflow-y-auto p-1 border border-gray-100 rounded-lg">
                        <?php
                        $techs = [
                            'HTML', 'CSS', 'JavaScript', 'TypeScript', 'PHP', 'Python', 'Java', 'C#',
                            'React', 'Vue', 'Node.js', 'Django', 'Laravel', 'MySQL', 'PostgreSQL', 
                            'MongoDB', 'Docker', 'Git', 'Linux', 'AWS', 'Swift', 'Kotlin', 'Go', 'Flutter'
                        ];
                        sort($techs);
                        foreach ($techs as $t):
                        ?>
                        <div class="skill-chip" onclick="toggleChip(this,'<?= $t ?>')">
                            <?= $t ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p id="err-techs" class="text-red-500 text-xs mt-1 hidden">Please select at least one technology.</p>
                </div>

                <!-- Q2: Competency Confidence Sliders -->
                <div class="mb-6 flex flex-col gap-4">
                    <label class="block text-sm font-semibold text-gray-700 -mb-2">
                        Rate your confidence (1-5)
                    </label>

                    <!-- Programming -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-semibold text-gray-700">Programming</span>
                            <span id="progVal" class="text-xs font-bold text-white bg-gray-900 px-2 py-0.5 rounded-full">1 / 5</span>
                        </div>
                        <input type="range" id="skillProg" class="skill-slider" min="1" max="5" value="1" oninput="updateSlider(this,'progVal')">
                    </div>

                    <!-- Databases -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-semibold text-gray-700">Databases</span>
                            <span id="dbVal" class="text-xs font-bold text-white bg-gray-900 px-2 py-0.5 rounded-full">1 / 5</span>
                        </div>
                        <input type="range" id="skillDb" class="skill-slider" min="1" max="5" value="1" oninput="updateSlider(this,'dbVal')">
                    </div>

                    <!-- Problem Solving -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-semibold text-gray-700">Problem Solving</span>
                            <span id="psVal" class="text-xs font-bold text-white bg-gray-900 px-2 py-0.5 rounded-full">1 / 5</span>
                        </div>
                        <input type="range" id="skillPs" class="skill-slider" min="1" max="5" value="1" oninput="updateSlider(this,'psVal')">
                    </div>

                    <!-- Communication -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-semibold text-gray-700">Communication</span>
                            <span id="commVal" class="text-xs font-bold text-white bg-gray-900 px-2 py-0.5 rounded-full">1 / 5</span>
                        </div>
                        <input type="range" id="skillComm" class="skill-slider" min="1" max="5" value="1" oninput="updateSlider(this,'commVal')">
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="goToPanel(1)" class="btn-back">Back</button>
                    <button type="button" id="btn2" onclick="nextStep2()" class="btn-main">Next</button>
                </div>
            </div>


            <!-- ══════════════════════════════
                 SURVEY STEP 3 — Projects & Portfolio
            ══════════════════════════════ -->
            <div id="panel3" class="panel">
                <div class="progress-track">
                    <div class="progress-fill" style="width: 50%;"></div>
                </div>

                <div class="mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">
                        Step 3 of 6
                    </p>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-1">
                        Projects &amp; Portfolio
                    </h1>
                    <p class="text-gray-400 text-sm">
                        Showcase completed projects and profile links.
                    </p>
                </div>

                <!-- Projects Completed Option -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Have you completed any projects?
                    </label>
                    <div class="grid grid-cols-2 gap-2" id="hasProjectsCards">
                        <div class="option-card" onclick="toggleProjectsSection(true, this)">Yes</div>
                        <div class="option-card" onclick="toggleProjectsSection(false, this)">No</div>
                    </div>
                    <p id="err-hasprojects" class="text-red-500 text-xs mt-1 hidden">Please select Yes or No.</p>
                </div>

                <!-- Dynamic Projects Fields -->
                <div id="projectsContainer" class="hidden mb-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-1.5 mb-3">
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Project Details</h3>
                    </div>
                    <div id="projectList" class="flex flex-col gap-3 max-h-80 overflow-y-auto p-1">
                        <!-- project blocks inserted here -->
                    </div>
                    <button type="button" onclick="addProjectInputBlock()" class="w-full mt-2.5 py-2 border border-dashed border-gray-300 rounded-lg text-xs font-bold text-gray-500 hover:border-black hover:text-black transition flex items-center justify-center gap-1.5">
                        + Add Another Project
                    </button>
                </div>

                <!-- Professional Links -->
                <div class="mb-6 border-t border-gray-100 pt-4 flex flex-col gap-3">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Professional Links</h3>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">GitHub Profile URL</label>
                        <input type="url" id="linkGithub" placeholder="https://github.com/username" class="w-full border-1.5 border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                        <p id="err-github" class="text-red-500 text-xs mt-1 hidden">GitHub profile link is required.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">LinkedIn Profile URL</label>
                        <input type="url" id="linkLinkedin" placeholder="https://linkedin.com/in/username" class="w-full border-1.5 border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                        <p id="err-linkedin" class="text-red-500 text-xs mt-1 hidden">LinkedIn profile link is required.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Portfolio Website URL (optional)</label>
                        <input type="url" id="linkPortfolio" placeholder="https://myportfolio.com" class="w-full border-1.5 border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="goToPanel(2)" class="btn-back">Back</button>
                    <button type="button" id="btn3" onclick="nextStep3()" class="btn-main">Next</button>
                </div>
            </div>


            <!-- ══════════════════════════════
                 SURVEY STEP 4 — Education
            ══════════════════════════════ -->
            <div id="panel4" class="panel">
                <div class="progress-track">
                    <div class="progress-fill" style="width: 66.66%;"></div>
                </div>

                <div class="mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">
                        Step 4 of 6
                    </p>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-1">
                        Education
                    </h1>
                    <p class="text-gray-400 text-sm">
                        Tell us about your educational background.
                    </p>
                </div>

                <!-- Education Level -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Education Level
                    </label>
                    <div class="grid grid-cols-3 gap-2" id="eduLevelCards">
                        <div class="option-card" onclick="selectOption(this,'eduLevelCards','education_level')">Diploma</div>
                        <div class="option-card" onclick="selectOption(this,'eduLevelCards','education_level')">Bachelor's</div>
                        <div class="option-card" onclick="selectOption(this,'eduLevelCards','education_level')">Master's</div>
                    </div>
                    <p id="err-edulevel" class="text-red-500 text-xs mt-1 hidden">Please select your education level.</p>
                </div>

                <!-- Institution Name -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Institution Name
                    </label>
                    <input type="text" id="eduInstitution" placeholder="e.g. University of Dhaka" class="w-full border-1.5 border-gray-200 rounded-lg p-3 text-sm focus:border-black outline-none transition bg-white">
                    <p id="err-eduinst" class="text-red-500 text-xs mt-1 hidden">Please enter your institution name.</p>
                </div>

                <!-- Degree Program -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Degree / Program of Study
                    </label>
                    <input type="text" id="eduDegree" placeholder="e.g. Computer Science &amp; Engineering" class="w-full border-1.5 border-gray-200 rounded-lg p-3 text-sm focus:border-black outline-none transition bg-white">
                    <p id="err-edudegree" class="text-red-500 text-xs mt-1 hidden">Please enter your degree program.</p>
                </div>

                <!-- Graduation Year -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Graduation Year (Completed or Expected)
                    </label>
                    <input type="number" id="eduYear" placeholder="e.g. 2026" min="1990" max="2035" class="w-full border-1.5 border-gray-200 rounded-lg p-3 text-sm focus:border-black outline-none transition bg-white">
                    <p id="err-eduyear" class="text-red-500 text-xs mt-1 hidden">Please enter a valid graduation year.</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="goToPanel(3)" class="btn-back">Back</button>
                    <button type="button" id="btn4" onclick="nextStep4()" class="btn-main">Next</button>
                </div>
            </div>


            <!-- ══════════════════════════════
                 SURVEY STEP 5 — Professional Summary
            ══════════════════════════════ -->
            <div id="panel5" class="panel">
                <div class="progress-track">
                    <div class="progress-fill" style="width: 83.33%;"></div>
                </div>

                <div class="mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">
                        Step 5 of 6
                    </p>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-1">
                        Professional Summary
                    </h1>
                    <p class="text-gray-400 text-sm">
                        Introduce yourself to potential employers.
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Tell us about yourself
                    </label>
                    <textarea id="profSummary" class="w-full border-1.5 border-gray-200 rounded-lg p-3 text-sm focus:border-black outline-none bg-white h-44 resize-y" placeholder="e.g. Highly motivated frontend developer targeting full-stack Web Development. Skilled in JavaScript, React, and MySQL, with practical experience building responsive web projects. Passionate about writing clean code and solving engineering problems in dynamic developer teams."></textarea>
                    <p class="text-xs text-gray-400 mt-2">Write a brief paragraph (2-4 sentences) summarizing your skills and target role.</p>
                    <p id="err-summary" class="text-red-500 text-xs mt-1.5 hidden">Please write a short summary about yourself (minimum 15 characters).</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="goToPanel(4)" class="btn-back">Back</button>
                    <button type="button" id="btn5" onclick="nextStep5()" class="btn-main">Next</button>
                </div>
            </div>


            <!-- ══════════════════════════════
                 SURVEY STEP 6 — Goals & Preferences
            ══════════════════════════════ -->
            <div id="panel6" class="panel">
                <div class="progress-track">
                    <div class="progress-fill" style="width: 100%;"></div>
                </div>

                <div class="mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">
                        Step 6 of 6
                    </p>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-1">
                        Goals &amp; Preferences
                    </h1>
                    <p class="text-gray-400 text-sm">
                        Customize how jobs and courses align to your schedule.
                    </p>
                </div>

                <!-- Q1: Primary Goal -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Primary Goal
                    </label>
                    <div class="grid grid-cols-2 gap-2" id="goalCards">
                        <div class="option-card" onclick="selectOption(this,'goalCards','primary_goal')">Get a Job Quickly</div>
                        <div class="option-card" onclick="selectOption(this,'goalCards','primary_goal')">Improve Skills First</div>
                        <div class="option-card" onclick="selectOption(this,'goalCards','primary_goal')">Find an Internship</div>
                        <div class="option-card" onclick="selectOption(this,'goalCards','primary_goal')">Explore Career Options</div>
                    </div>
                    <p id="err-goal" class="text-red-500 text-xs mt-1 hidden">Please select your primary goal.</p>
                </div>

                <!-- Q2: Preferred Arrangement -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Preferred Work Arrangement
                    </label>
                    <div class="grid grid-cols-3 gap-2" id="workCards">
                        <div class="option-card" onclick="selectOption(this,'workCards','work_arrangement')">Remote</div>
                        <div class="option-card" onclick="selectOption(this,'workCards','work_arrangement')">Hybrid</div>
                        <div class="option-card" onclick="selectOption(this,'workCards','work_arrangement')">On-site</div>
                    </div>
                    <p id="err-workarr" class="text-red-500 text-xs mt-1 hidden">Please select your work preference.</p>
                </div>

                <!-- Q3: Preferred Employment Type -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Preferred Employment Type
                    </label>
                    <div class="grid grid-cols-3 gap-2" id="employCards">
                        <div class="option-card" onclick="selectOption(this,'employCards','employment_type')">Full-time</div>
                        <div class="option-card" onclick="selectOption(this,'employCards','employment_type')">Internship</div>
                        <div class="option-card" onclick="selectOption(this,'employCards','employment_type')">Contract</div>
                    </div>
                    <p id="err-employtype" class="text-red-500 text-xs mt-1 hidden">Please select employment preference.</p>
                </div>

                <!-- Q4: Availability -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Weekly Learning Availability
                    </label>
                    <div class="grid grid-cols-3 gap-2" id="availCards">
                        <div class="option-card" onclick="selectOption(this,'availCards','availability')">Less than 5 hours</div>
                        <div class="option-card" onclick="selectOption(this,'availCards','availability')">5-10 hours</div>
                        <div class="option-card" onclick="selectOption(this,'availCards','availability')">10+ hours</div>
                    </div>
                    <p id="err-avail" class="text-red-500 text-xs mt-1 hidden">Please select your weekly availability.</p>
                </div>

                <!-- Form Submission Endpoint -->
                <form method="POST" id="surveyForm">
                    <input type="hidden" name="finish_survey" value="1">
                    <input type="hidden" name="field" id="h-field">
                    <input type="hidden" name="cv_data" id="h-cv-data">

                    <div class="flex gap-3">
                        <button type="button" onclick="goToPanel(5)" class="btn-back">Back</button>
                        <button type="button" id="finishBtn" onclick="finishSurvey()" class="btn-main">Finish</button>
                    </div>
                </form>
            </div>


            <!-- ══════════════════════════════
                 LOADING PANEL
            ══════════════════════════════ -->
            <div id="panelLoading" class="panel">
                <div class="text-center py-12">
                    <div class="spinner"></div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">
                        Setting up your dashboard...
                    </h2>
                    <p class="text-gray-400 text-sm mb-6">
                        We are building your professional CV and matching jobs/courses.
                    </p>
                    <div class="flex justify-center gap-3">
                        <div class="loading-dot"></div>
                        <div class="loading-dot"></div>
                        <div class="loading-dot"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════ -->
<script>
// ── Survey State Object ────────────────────────
const survey = {
    // Step 1: Career Info
    field:            null,
    role:             null,
    experience_level: null,
    current_status:   null,

    // Step 2: Skills & Tech
    technologies:     [],
    skill_prog:       1,
    skill_db:         1,
    skill_ps:         1,
    skill_comm:       1,

    // Step 3: Projects & Portfolio Links
    has_projects:     null,
    projects:         [], // List of {name, desc, techs, github}
    link_github:      '',
    link_linkedin:    '',
    link_portfolio:   '',

    // Step 4: Education
    education_level:  null,
    edu_institution:  '',
    edu_degree:       '',
    edu_year:         '',

    // Step 5: Professional Summary
    summary:          '',

    // Step 6: Goals & Preferences
    primary_goal:     null,
    work_arrangement: null,
    employment_type:  null,
    availability:     null
};

// ── Left panel content updates per step ─────────
const leftContent = {
    1: {
        headline: `Find your<br>dream career<br><span class="text-[#a8d5c2]">path.</span>`,
        subtext: `Select your technology field and targeted role to customize recommendations.`
    },
    2: {
        headline: `Map your<br>skills &amp;<br><span class="text-[#a8d5c2]">competencies.</span>`,
        subtext: `Highlighting the tools you've used helps us align courses and quizzes to your current level.`
    },
    3: {
        headline: `Showcase<br>your top<br><span class="text-[#a8d5c2]">projects.</span>`,
        subtext: `Adding projects and portfolio links builds a strong foundation for your generated CV.`
    },
    4: {
        headline: `Tell us about<br>your academic<br><span class="text-[#a8d5c2]">journey.</span>`,
        subtext: `Entering your educational background adds crucial credentials to your professional profile.`
    },
    5: {
        headline: `Introduce<br>yourself to<br><span class="text-[#a8d5c2]">employers.</span>`,
        subtext: `Your summary is the first thing recruiters read. Make it descriptive and highlighting your value.`
    },
    6: {
        headline: `Define your<br>preferences &amp;<br><span class="text-[#a8d5c2]">availability.</span>`,
        subtext: `Select preferred work setups and learning pace to customize your dashboard cards.`
    }
};

// ── Navigate between steps/panels ────────────────
function goToPanel(step) {
    // Hide all panels
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));

    // Show target step panel
    document.getElementById('panel' + step).classList.add('active');

    // Update left panel text content
    if (leftContent[step]) {
        document.getElementById('leftHeadline').innerHTML = leftContent[step].headline;
        document.getElementById('leftSubtext').textContent = leftContent[step].subtext;
    }

    // Update Left Panel checklist states
    updateDots(step);

    // Smooth scroll to top on mobile
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Left Panel CV checklist state mapping ────────
function updateDots(activeStep) {
    const sections = ['summary', 'skills', 'projects', 'education', 'links'];
    
    // Reset all dots
    sections.forEach(sec => {
        const dot   = document.getElementById('dot-' + sec);
        const label = document.getElementById('label-' + sec);
        const sub   = document.getElementById('sub-' + sec);
        if (!dot || !label) return;

        dot.className = "w-7 h-7 rounded-full dot-inactive flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        label.className = "text-gray-500 text-xs font-semibold transition-colors duration-300";
        if (sub) sub.classList.add('hidden');
    });

    // 1. Personal Information is always dot-done (Completed from signup)

    // 2. Professional Summary (Step 5)
    const summaryDot   = document.getElementById('dot-summary');
    const summaryLabel = document.getElementById('label-summary');
    const summarySub   = document.getElementById('sub-summary');
    if (activeStep === 5) {
        summaryDot.className = "w-7 h-7 rounded-full dot-active flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        summaryLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
        if (summarySub) summarySub.classList.remove('hidden');
    } else if (activeStep > 5) {
        summaryDot.className = "w-7 h-7 rounded-full dot-done flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        summaryDot.textContent = '✓';
        summaryLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
    }

    // 3. Skills & Technologies (Step 2)
    const skillsDot   = document.getElementById('dot-skills');
    const skillsLabel = document.getElementById('label-skills');
    const skillsSub   = document.getElementById('sub-skills');
    if (activeStep === 2) {
        skillsDot.className = "w-7 h-7 rounded-full dot-active flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        skillsLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
        if (skillsSub) skillsSub.classList.remove('hidden');
    } else if (activeStep > 2) {
        skillsDot.className = "w-7 h-7 rounded-full dot-done flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        skillsDot.textContent = '✓';
        skillsLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
    }

    // 4. Projects & Portfolio (Step 3)
    const projectsDot   = document.getElementById('dot-projects');
    const projectsLabel = document.getElementById('label-projects');
    const projectsSub   = document.getElementById('sub-projects');
    if (activeStep === 3) {
        projectsDot.className = "w-7 h-7 rounded-full dot-active flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        projectsLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
        if (projectsSub) projectsSub.classList.remove('hidden');
    } else if (activeStep > 3) {
        projectsDot.className = "w-7 h-7 rounded-full dot-done flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        projectsDot.textContent = '✓';
        projectsLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
    }

    // 5. Education (Step 4)
    const educationDot   = document.getElementById('dot-education');
    const educationLabel = document.getElementById('label-education');
    const educationSub   = document.getElementById('sub-education');
    if (activeStep === 4) {
        educationDot.className = "w-7 h-7 rounded-full dot-active flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        educationLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
        if (educationSub) educationSub.classList.remove('hidden');
    } else if (activeStep > 4) {
        educationDot.className = "w-7 h-7 rounded-full dot-done flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        educationDot.textContent = '✓';
        educationLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
    }

    // 6. Links (GitHub, LinkedIn) (Step 3)
    const linksDot   = document.getElementById('dot-links');
    const linksLabel = document.getElementById('label-links');
    const linksSub   = document.getElementById('sub-links');
    if (activeStep === 3) {
        linksDot.className = "w-7 h-7 rounded-full dot-active flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        linksLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
        if (linksSub) linksSub.classList.remove('hidden');
    } else if (activeStep > 3) {
        linksDot.className = "w-7 h-7 rounded-full dot-done flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
        linksDot.textContent = '✓';
        linksLabel.className = "text-white text-xs font-semibold transition-colors duration-300";
    }
    
    // Set numeric identifiers inside dots if they are not checkmarks
    if (activeStep <= 5) document.getElementById('dot-summary').textContent = '2';
    if (activeStep <= 2) document.getElementById('dot-skills').textContent = '3';
    if (activeStep <= 3) {
        document.getElementById('dot-projects').textContent = '4';
        document.getElementById('dot-links').textContent = '6';
    }
    if (activeStep <= 4) document.getElementById('dot-education').textContent = '5';
}

// ── Select an Option Card (Generic helper) ───────
function selectOption(el, groupId, stateKey) {
    document.querySelectorAll('#' + groupId + ' .option-card').forEach(card => {
        card.classList.remove('selected');
    });
    el.classList.add('selected');
    survey[stateKey] = el.textContent.trim();
}

// ── Skills: Technology Chips Selector ───────────
function toggleChip(el, value) {
    el.classList.toggle('selected');
    if (el.classList.contains('selected')) {
        if (!survey.technologies.includes(value)) {
            survey.technologies.push(value);
        }
    } else {
        survey.technologies = survey.technologies.filter(t => t !== value);
    }
}

// ── Skills: Confidence Sliders update ────────────
function updateSlider(input, labelId) {
    const val = input.value;
    document.getElementById(labelId).textContent = val + ' / 5';
    const pct = ((val - 1) / 4) * 100;
    input.style.background = `linear-gradient(to right, #111 ${pct}%, #e5e7eb ${pct}%)`;
}

// ── Projects: Toggle Section view ────────────────
function toggleProjectsSection(hasProj, el) {
    document.querySelectorAll('#hasProjectsCards .option-card').forEach(card => {
        card.classList.remove('selected');
    });
    el.classList.add('selected');
    survey.has_projects = hasProj;

    const container = document.getElementById('projectsContainer');
    if (hasProj) {
        container.classList.remove('hidden');
        if (document.getElementById('projectList').children.length === 0) {
            addProjectInputBlock();
        }
    } else {
        container.classList.add('hidden');
    }
}

// ── Projects: Add Input Form Card ────────────────
let projectCount = 0;
function addProjectInputBlock() {
    projectCount++;
    const list = document.getElementById('projectList');
    const div = document.createElement('div');
    div.className = "project-block bg-gray-50 border border-gray-100 rounded-lg p-4 relative flex flex-col gap-2.5 mt-2";
    div.innerHTML = `
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-2.5 right-3 text-gray-400 hover:text-red-500 font-bold text-sm">×</button>
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Project #${projectCount}</span>
        <div>
            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Project Name</label>
            <input type="text" class="proj-name w-full border border-gray-200 rounded-md p-2 text-xs focus:border-black outline-none bg-white" placeholder="e.g. E-Commerce Backend API">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Project Description</label>
            <textarea class="proj-desc w-full border border-gray-200 rounded-md p-2 text-xs focus:border-black outline-none bg-white h-16 resize-none" placeholder="Describe what you built and how it works..."></textarea>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Technologies Used</label>
            <input type="text" class="proj-techs w-full border border-gray-200 rounded-md p-2 text-xs focus:border-black outline-none bg-white" placeholder="e.g. Node.js, Express, MongoDB">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">GitHub Repository Link (optional)</label>
            <input type="url" class="proj-github w-full border border-gray-200 rounded-md p-2 text-xs focus:border-black outline-none bg-white" placeholder="https://github.com/username/project">
        </div>
    `;
    list.appendChild(div);
}

// ── STEP VALIDATIONS ─────────────────────────────

// Step 1 Validation
function nextStep1() {
    let valid = true;

    if (!survey.field) {
        document.getElementById('err-field').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-field').classList.add('hidden');
    }

    if (!survey.role) {
        document.getElementById('err-role').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-role').classList.add('hidden');
    }

    if (!survey.experience_level) {
        document.getElementById('err-exp').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-exp').classList.add('hidden');
    }

    if (!survey.current_status) {
        document.getElementById('err-status').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-status').classList.add('hidden');
    }

    if (!valid) {
        const btn = document.getElementById('btn1');
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    }

    goToPanel(2);
}

// Step 2 Validation
function nextStep2() {
    let valid = true;

    if (survey.technologies.length === 0) {
        document.getElementById('err-techs').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-techs').classList.add('hidden');
    }

    if (!valid) {
        const btn = document.getElementById('btn2');
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    }

    survey.skill_prog = parseInt(document.getElementById('skillProg').value);
    survey.skill_db   = parseInt(document.getElementById('skillDb').value);
    survey.skill_ps   = parseInt(document.getElementById('skillPs').value);
    survey.skill_comm = parseInt(document.getElementById('skillComm').value);

    goToPanel(3);
}

// Step 3 Validation
function nextStep3() {
    let valid = true;

    if (survey.has_projects === null) {
        document.getElementById('err-hasprojects').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-hasprojects').classList.add('hidden');
    }

    const gh = document.getElementById('linkGithub').value.trim();
    const li = document.getElementById('linkLinkedin').value.trim();

    if (!gh) {
        document.getElementById('err-github').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-github').classList.add('hidden');
    }

    if (!li) {
        document.getElementById('err-linkedin').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-linkedin').classList.add('hidden');
    }

    // Collect Dynamic Projects
    survey.projects = [];
    if (survey.has_projects) {
        const cards = document.querySelectorAll('#projectList .project-block');
        cards.forEach(card => {
            const name = card.querySelector('.proj-name').value.trim();
            const desc = card.querySelector('.proj-desc').value.trim();
            const techs = card.querySelector('.proj-techs').value.trim();
            const github = card.querySelector('.proj-github').value.trim();

            if (name && desc) {
                survey.projects.push({ name, desc, techs, github });
            }
        });

        if (survey.projects.length === 0) {
            alert('Please add at least one completed project with a Name and Description, or choose "No" completed projects.');
            valid = false;
        }
    }

    if (!valid) {
        const btn = document.getElementById('btn3');
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    }

    survey.link_github = gh;
    survey.link_linkedin = li;
    survey.link_portfolio = document.getElementById('linkPortfolio').value.trim();

    goToPanel(4);
}

// Step 4 Validation
function nextStep4() {
    let valid = true;

    if (!survey.education_level) {
        document.getElementById('err-edulevel').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-edulevel').classList.add('hidden');
    }

    const inst = document.getElementById('eduInstitution').value.trim();
    const degree = document.getElementById('eduDegree').value.trim();
    const year = document.getElementById('eduYear').value.trim();

    if (!inst) {
        document.getElementById('err-eduinst').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-eduinst').classList.add('hidden');
    }

    if (!degree) {
        document.getElementById('err-edudegree').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-edudegree').classList.add('hidden');
    }

    if (!year || isNaN(year) || year < 1990 || year > 2035) {
        document.getElementById('err-eduyear').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-eduyear').classList.add('hidden');
    }

    if (!valid) {
        const btn = document.getElementById('btn4');
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    }

    survey.edu_institution = inst;
    survey.edu_degree = degree;
    survey.edu_year = parseInt(year);

    goToPanel(5);
}

// Step 5 Validation
function nextStep5() {
    const summary = document.getElementById('profSummary').value.trim();

    if (summary.length < 15) {
        document.getElementById('err-summary').classList.remove('hidden');
        const btn = document.getElementById('btn5');
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    } else {
        document.getElementById('err-summary').classList.add('hidden');
    }

    survey.summary = summary;
    goToPanel(6);
}

// Finish Survey & Save
function finishSurvey() {
    let valid = true;

    if (!survey.primary_goal) {
        document.getElementById('err-goal').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-goal').classList.add('hidden');
    }

    if (!survey.work_arrangement) {
        document.getElementById('err-workarr').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-workarr').classList.add('hidden');
    }

    if (!survey.employment_type) {
        document.getElementById('err-employtype').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-employtype').classList.add('hidden');
    }

    if (!survey.availability) {
        document.getElementById('err-avail').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-avail').classList.add('hidden');
    }

    if (!valid) {
        const btn = document.getElementById('finishBtn');
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    }

    // Set hidden fields
    document.getElementById('h-field').value   = survey.field;
    document.getElementById('h-cv-data').value = JSON.stringify(survey);

    // Show loading panel
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panelLoading').classList.add('active');

    // Update left panel loading messages
    document.getElementById('leftHeadline').innerHTML = `Almost<br>there...<br><span class="text-[#a8d5c2]">hang on.</span>`;
    document.getElementById('leftSubtext').textContent = 'We are assembling your CV profile and matches right now.';

    // Mark all sections done
    const sections = ['summary', 'skills', 'projects', 'education', 'links'];
    sections.forEach(sec => {
        const dot = document.getElementById('dot-' + sec);
        const label = document.getElementById('label-' + sec);
        if (dot) {
            dot.className = "w-7 h-7 rounded-full dot-done flex items-center justify-center font-black text-xs flex-shrink-0 transition-all duration-300";
            dot.textContent = '✓';
        }
        if (label) label.className = "text-white text-xs font-semibold";
    });

    // Submit form after 2 seconds
    setTimeout(() => {
        document.getElementById('surveyForm').submit();
    }, 2000);
}

// ── INITIALIZATION ON LOAD ───────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Sliders
    ['skillProg', 'skillDb', 'skillPs', 'skillComm'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            updateSlider(el, id === 'skillProg' ? 'progVal' 
                            : id === 'skillDb'   ? 'dbVal' 
                            : id === 'skillPs'   ? 'psVal' 
                            :                      'commVal');
        }
    });

    // Sidebar dots start state
    updateDots(1);
});
</script>
</body>
</html>