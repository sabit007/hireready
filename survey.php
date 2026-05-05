<?php
session_start();

// ====================================================
// TODO: Uncomment when auth is fully set up
// if (!isset($_SESSION['user_id'])) {
//     header('Location: register.php');
//     exit;
// }
// ====================================================

// ── Handle final survey submission ──────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_survey'])) {

    // ====================================================
    // DB TODO: Save survey answers
    // ====================================================
    // $user_id  = $_SESSION['user_id'];
    // $field    = $_POST['field']        ?? '';
    // $role     = $_POST['role']         ?? '';
    // $prog     = (int)($_POST['skill_prog'] ?? 1);
    // $db       = (int)($_POST['skill_db']   ?? 1);
    // $ps       = (int)($_POST['skill_ps']   ?? 1);
    // $techs    = $_POST['technologies'] ?? '';
    // $goal     = $_POST['goal']         ?? '';
    // $style    = $_POST['learn_style']  ?? '';
    // $avail    = $_POST['availability'] ?? '';
    //
    // $stmt = $conn->prepare("
    //     INSERT INTO user_profiles
    //         (user_id, preferred_field, preferred_role,
    //          skill_programming, skill_databases, skill_problem_solving,
    //          technologies, goal, learning_style, availability)
    //     VALUES (?,?,?,?,?,?,?,?,?,?)
    //     ON DUPLICATE KEY UPDATE
    //         preferred_field       = VALUES(preferred_field),
    //         preferred_role        = VALUES(preferred_role),
    //         skill_programming     = VALUES(skill_programming),
    //         skill_databases       = VALUES(skill_databases),
    //         skill_problem_solving = VALUES(skill_problem_solving),
    //         technologies          = VALUES(technologies),
    //         goal                  = VALUES(goal),
    //         learning_style        = VALUES(learning_style),
    //         availability          = VALUES(availability)
    // ");
    // $stmt->bind_param('sssiiissss',
    //     $user_id,$field,$role,$prog,$db,$ps,$techs,$goal,$style,$avail
    // );
    // $stmt->execute();
    // $conn->query("UPDATE users SET profile_complete=1 WHERE id=$user_id");
    // ====================================================

    // DUMMY: redirect to dashboard
    // TODO: change to dashboard.php when built
    header('Location: dashboard.php');
    exit;
}
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
         LEFT PANEL
    ════════════════════════════════════ -->
    <div class="hidden lg:flex lg:w-1/2 bg-[#111] flex-col
                justify-between p-12 relative overflow-hidden anim-left">

        <!-- Blobs -->
        <div class="absolute top-[-100px] left-[-100px] w-72 h-72
                    rounded-full bg-[#a8d5c2] opacity-20 blur-3xl
                    pointer-events-none"></div>
        <div class="absolute bottom-[-80px] right-[-80px] w-80 h-80
                    rounded-full bg-[#b8cef0] opacity-20 blur-3xl
                    pointer-events-none"></div>
        <div class="absolute top-1/3 right-[-40px] w-56 h-56
                    rounded-full bg-[#f5c5a3] opacity-10 blur-3xl
                    pointer-events-none"></div>

        <!-- Logo -->
        <a href="index.php"
           class="text-white font-black text-2xl tracking-tight
                  no-underline relative z-10">
            HireReady
        </a>

        <!-- Middle -->
        <div class="relative z-10">

            <!-- Mini cards -->
            <div class="flex gap-4 mb-10">
                <div class="deco-card flex-1 float-1">
                    <div class="text-white font-bold text-sm mb-1">Smart Matching</div>
                    <div class="text-gray-400 text-xs">Jobs matched to your real skills</div>
                </div>
                <div class="deco-card flex-1 float-2">
                    <div class="text-white font-bold text-sm mb-1">Guided Growth</div>
                    <div class="text-gray-400 text-xs">Courses that fill your gaps</div>
                </div>
            </div>

            <!-- Headline -->
            <h2 id="leftHeadline"
                class="text-white font-black text-4xl leading-tight
                       tracking-tight mb-5">
                Tell us what<br>
                you are looking<br>
                <span class="text-[#a8d5c2]">for.</span>
            </h2>

            <p id="leftSubtext"
               class="text-gray-400 text-sm leading-relaxed mb-10 max-w-xs">
                This takes 2 minutes and makes everything
                on your dashboard relevant to you.
            </p>

            <!-- Steps -->
            <div class="flex flex-col gap-1">

                <!-- Step 1 — already done -->
                <div class="flex items-center gap-4">
                    <div id="dot1"
                         class="w-8 h-8 rounded-full dot-done
                                flex items-center justify-center
                                font-black text-sm flex-shrink-0
                                transition-all duration-500">
                        ✓
                    </div>
                    <div>
                        <div id="label1"
                             class="text-white text-sm font-semibold">
                            Account created
                        </div>
                        <div class="text-[#22c55e] text-xs font-medium">
                            Done
                        </div>
                    </div>
                </div>

                <div class="w-px h-5 bg-gray-700 ml-4"></div>

                <!-- Step 2 -->
                <div class="flex items-center gap-4">
                    <div id="dot2"
                         class="w-8 h-8 rounded-full dot-active
                                flex items-center justify-center
                                font-black text-sm flex-shrink-0
                                transition-all duration-500">
                        2
                    </div>
                    <div>
                        <div id="label2"
                             class="text-white text-sm font-semibold
                                    transition-colors duration-500">
                            Career interests
                        </div>
                        <div id="sub2"
                             class="text-[#a8d5c2] text-xs font-medium">
                            You are here
                        </div>
                    </div>
                </div>

                <div class="w-px h-5 bg-gray-700 ml-4"></div>

                <!-- Step 3 -->
                <div class="flex items-center gap-4">
                    <div id="dot3"
                         class="w-8 h-8 rounded-full dot-inactive
                                flex items-center justify-center
                                font-black text-sm flex-shrink-0
                                transition-all duration-500">
                        3
                    </div>
                    <div id="label3"
                         class="text-gray-500 text-sm
                                transition-colors duration-500">
                        Skills &amp; experience
                    </div>
                </div>

                <div class="w-px h-5 bg-gray-700 ml-4"></div>

                <!-- Step 4 -->
                <div class="flex items-center gap-4">
                    <div id="dot4"
                         class="w-8 h-8 rounded-full dot-inactive
                                flex items-center justify-center
                                font-black text-sm flex-shrink-0
                                transition-all duration-500">
                        4
                    </div>
                    <div id="label4"
                         class="text-gray-500 text-sm
                                transition-colors duration-500">
                        Your goals
                    </div>
                </div>

            </div>
        </div>

        <!-- Testimonial -->
        <div class="deco-card relative z-10">
            <p class="text-gray-300 text-sm italic leading-relaxed mb-4">
                "The survey took 2 minutes. My dashboard showed exactly
                the jobs and courses I needed."
            </p>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#b8cef0]
                            flex items-center justify-center
                            text-black font-bold text-sm">
                    A
                </div>
                <div>
                    <div class="text-white text-xs font-bold">Ahmed M.</div>
                    <div class="text-gray-500 text-xs">Data Analyst</div>
                </div>
                <div class="ml-auto text-yellow-400 text-xs tracking-widest">
                    * * * * *
                </div>
            </div>
        </div>

    </div>


    <!-- ════════════════════════════════════
         RIGHT PANEL
    ════════════════════════════════════ -->
    <div class="w-full lg:w-1/2 flex items-center justify-center
                px-6 py-12 overflow-y-auto anim-right">

        <div class="w-full max-w-md">

            <!-- Mobile logo -->
            <div class="lg:hidden mb-8">
                <a href="index.php"
                   class="text-gray-900 font-black text-xl
                          tracking-tight no-underline">
                    HireReady
                </a>
            </div>


            <!-- ══════════════════════════════
                 SURVEY STEP 1 — Career
            ══════════════════════════════ -->
            <div id="panel1" class="panel active">

                <!-- Progress -->
                <div class="progress-track">
                    <div class="progress-fill" style="width: 33%;"></div>
                </div>

                <div class="mb-7">
                    <p class="text-xs font-bold text-gray-400
                               uppercase tracking-widest mb-1">
                        Step 1 of 3
                    </p>
                    <h1 class="text-2xl font-black text-gray-900
                               tracking-tight mb-2">
                        What are you interested in?
                    </h1>
                    <p class="text-gray-400 text-sm">
                        Pick the field you want to work in.
                    </p>
                </div>

                <!-- Field selection -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold
                                  text-gray-700 mb-3">
                        Choose a field
                    </label>
                    <div class="grid grid-cols-2 gap-3" id="fieldCards">
                        <div class="option-card"
                             onclick="selectOption(this,'fieldCards','field')">
                            Web Development
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'fieldCards','field')">
                            Data Science
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'fieldCards','field')">
                            Cybersecurity
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'fieldCards','field')">
                            Mobile Development
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'fieldCards','field')">
                            Cloud &amp; DevOps
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'fieldCards','field')">
                            UI / UX Design
                        </div>
                    </div>
                    <p id="err-field"
                       class="text-red-500 text-xs mt-2 hidden">
                        Please select a field.
                    </p>
                </div>

                <!-- Role selection -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold
                                  text-gray-700 mb-3">
                        What role suits you best?
                    </label>
                    <div class="grid grid-cols-2 gap-3" id="roleCards">
                        <div class="option-card"
                             onclick="selectOption(this,'roleCards','role')">
                            Frontend
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'roleCards','role')">
                            Backend
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'roleCards','role')">
                            Fullstack
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'roleCards','role')">
                            Analyst
                        </div>
                        <div class="option-card col-span-2"
                             onclick="selectOption(this,'roleCards','role')">
                            Not sure yet
                        </div>
                    </div>
                    <p id="err-role"
                       class="text-red-500 text-xs mt-2 hidden">
                        Please select a role.
                    </p>
                </div>

                <button type="button"
                        id="btn1"
                        onclick="nextStep1()"
                        class="btn-main">
                    Next
                </button>

            </div><!-- end panel1 -->


            <!-- ══════════════════════════════
                 SURVEY STEP 2 — Skills
            ══════════════════════════════ -->
            <div id="panel2" class="panel">

                <!-- Progress -->
                <div class="progress-track">
                    <div class="progress-fill" style="width: 66%;"></div>
                </div>

                <div class="mb-7">
                    <p class="text-xs font-bold text-gray-400
                               uppercase tracking-widest mb-1">
                        Step 2 of 3
                    </p>
                    <h1 class="text-2xl font-black text-gray-900
                               tracking-tight mb-2">
                        Tell us about your skills
                    </h1>
                    <p class="text-gray-400 text-sm">
                        Be honest — this helps us match you better.
                    </p>
                </div>

                <!-- Sliders -->
                <div class="mb-6 flex flex-col gap-6">

                    <!-- Programming -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-semibold text-gray-700">
                                Programming
                            </label>
                            <span id="progVal"
                                  class="text-xs font-bold text-white
                                         bg-gray-900 px-2.5 py-1 rounded-full">
                                1 / 5
                            </span>
                        </div>
                        <input type="range"
                               id="skillProg"
                               class="skill-slider"
                               min="1" max="5" value="1"
                               oninput="updateSlider(this,'progVal')">
                        <div class="flex justify-between text-xs
                                    text-gray-400 mt-1">
                            <span>Beginner</span>
                            <span>Expert</span>
                        </div>
                    </div>

                    <!-- Databases -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-semibold text-gray-700">
                                Databases
                            </label>
                            <span id="dbVal"
                                  class="text-xs font-bold text-white
                                         bg-gray-900 px-2.5 py-1 rounded-full">
                                1 / 5
                            </span>
                        </div>
                        <input type="range"
                               id="skillDb"
                               class="skill-slider"
                               min="1" max="5" value="1"
                               oninput="updateSlider(this,'dbVal')">
                        <div class="flex justify-between text-xs
                                    text-gray-400 mt-1">
                            <span>Beginner</span>
                            <span>Expert</span>
                        </div>
                    </div>

                    <!-- Problem Solving -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-semibold text-gray-700">
                                Problem Solving
                            </label>
                            <span id="psVal"
                                  class="text-xs font-bold text-white
                                         bg-gray-900 px-2.5 py-1 rounded-full">
                                1 / 5
                            </span>
                        </div>
                        <input type="range"
                               id="skillPs"
                               class="skill-slider"
                               min="1" max="5" value="1"
                               oninput="updateSlider(this,'psVal')">
                        <div class="flex justify-between text-xs
                                    text-gray-400 mt-1">
                            <span>Beginner</span>
                            <span>Expert</span>
                        </div>
                    </div>

                </div>

                <!-- Tech chips -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold
                                  text-gray-700 mb-1">
                        What have you worked with?
                    </label>
                    <p class="text-xs text-gray-400 mb-3">
                        Select all that apply.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <?php
                        $techs = [
                            'PHP','JavaScript','TypeScript','Python',
                            'React','Vue','Node.js','Django','Laravel',
                            'MySQL','PostgreSQL','MongoDB',
                            'Git','Docker','Linux','AWS',
                            'Java','C#','Swift','Kotlin'
                        ];
                        foreach ($techs as $t):
                        ?>
                        <div class="skill-chip"
                             onclick="toggleChip(this,'<?= $t ?>')">
                            <?= $t ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            onclick="goToPanel(1)"
                            class="btn-back">
                        Back
                    </button>
                    <button type="button"
                            id="btn2"
                            onclick="nextStep2()"
                            class="btn-main">
                        Next
                    </button>
                </div>

            </div><!-- end panel2 -->


            <!-- ══════════════════════════════
                 SURVEY STEP 3 — Goals
            ══════════════════════════════ -->
            <div id="panel3" class="panel">

                <!-- Progress -->
                <div class="progress-track">
                    <div class="progress-fill" style="width: 100%;"></div>
                </div>

                <div class="mb-7">
                    <p class="text-xs font-bold text-gray-400
                               uppercase tracking-widest mb-1">
                        Step 3 of 3
                    </p>
                    <h1 class="text-2xl font-black text-gray-900
                               tracking-tight mb-2">
                        What is your goal?
                    </h1>
                    <p class="text-gray-400 text-sm">
                        This shapes what we show you first on your dashboard.
                    </p>
                </div>

                <!-- Goal -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold
                                  text-gray-700 mb-3">
                        Primary goal
                    </label>
                    <div class="flex flex-col gap-3" id="goalCards">
                        <div class="option-card text-left px-5"
                             onclick="selectOption(this,'goalCards','goal')">
                            <span class="font-bold">Get a job fast</span>
                            <span class="block text-xs text-gray-400
                                         mt-0.5 option-sub">
                                Show me matched jobs right away
                            </span>
                        </div>
                        <div class="option-card text-left px-5"
                             onclick="selectOption(this,'goalCards','goal')">
                            <span class="font-bold">Improve my skills first</span>
                            <span class="block text-xs text-gray-400
                                         mt-0.5 option-sub">
                                Prioritize courses before applying
                            </span>
                        </div>
                        <div class="option-card text-left px-5"
                             onclick="selectOption(this,'goalCards','goal')">
                            <span class="font-bold">Switch careers</span>
                            <span class="block text-xs text-gray-400
                                         mt-0.5 option-sub">
                                I want to move into a new field
                            </span>
                        </div>
                        <div class="option-card text-left px-5"
                             onclick="selectOption(this,'goalCards','goal')">
                            <span class="font-bold">Just exploring</span>
                            <span class="block text-xs text-gray-400
                                         mt-0.5 option-sub">
                                Show me everything
                            </span>
                        </div>
                    </div>
                    <p id="err-goal"
                       class="text-red-500 text-xs mt-2 hidden">
                        Please select a goal.
                    </p>
                </div>

                <!-- Learning style -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold
                                  text-gray-700 mb-3">
                        How do you prefer to learn?
                    </label>
                    <div class="grid grid-cols-3 gap-3" id="styleCards">
                        <div class="option-card"
                             onclick="selectOption(this,'styleCards','learn_style')">
                            Video
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'styleCards','learn_style')">
                            Reading
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'styleCards','learn_style')">
                            Practice
                        </div>
                    </div>
                    <p id="err-style"
                       class="text-red-500 text-xs mt-2 hidden">
                        Please select a learning style.
                    </p>
                </div>

                <!-- Availability -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold
                                  text-gray-700 mb-3">
                        Weekly time available for learning
                    </label>
                    <div class="flex flex-col gap-3" id="availCards">
                        <div class="option-card"
                             onclick="selectOption(this,'availCards','availability')">
                            Less than 5 hours / week
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'availCards','availability')">
                            5 – 10 hours / week
                        </div>
                        <div class="option-card"
                             onclick="selectOption(this,'availCards','availability')">
                            10+ hours / week
                        </div>
                    </div>
                    <p id="err-avail"
                       class="text-red-500 text-xs mt-2 hidden">
                        Please select your availability.
                    </p>
                </div>

                <!-- Hidden form fields — filled by JS before submit -->
                <form method="POST" id="surveyForm">
                    <input type="hidden" name="finish_survey" value="1">
                    <input type="hidden" name="field"         id="h-field">
                    <input type="hidden" name="role"          id="h-role">
                    <input type="hidden" name="skill_prog"    id="h-prog"  value="1">
                    <input type="hidden" name="skill_db"      id="h-db"    value="1">
                    <input type="hidden" name="skill_ps"      id="h-ps"    value="1">
                    <input type="hidden" name="technologies"  id="h-techs">
                    <input type="hidden" name="goal"          id="h-goal">
                    <input type="hidden" name="learn_style"   id="h-style">
                    <input type="hidden" name="availability"  id="h-avail">

                    <div class="flex gap-3">
                        <button type="button"
                                onclick="goToPanel(2)"
                                class="btn-back">
                            Back
                        </button>
                        <button type="button"
                                id="finishBtn"
                                onclick="finishSurvey()"
                                class="btn-main">
                            Finish
                        </button>
                    </div>
                </form>

            </div><!-- end panel3 -->


            <!-- ══════════════════════════════
                 LOADING SCREEN
            ══════════════════════════════ -->
            <div id="panelLoading" class="panel">
                <div class="text-center py-12">
                    <div class="spinner"></div>
                    <h2 class="text-2xl font-black text-gray-900
                               tracking-tight mb-3">
                        Setting up your dashboard...
                    </h2>
                    <p class="text-gray-400 text-sm mb-8">
                        We are matching jobs and courses just for you.
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

// ── State ─────────────────────────────────────
const survey = {
    field:        null,
    role:         null,
    skill_prog:   1,
    skill_db:     1,
    skill_ps:     1,
    technologies: [],
    goal:         null,
    learn_style:  null,
    availability: null,
};

// ── Left panel content per step ───────────────
const leftContent = {
    1: {
        headline: `Tell us what<br>you are looking<br><span class="text-[#a8d5c2]">for.</span>`,
        subtext: `Pick your field and role so we can filter
                  the right jobs and assessments for you.`,
    },
    2: {
        headline: `How skilled<br>are you<br><span class="text-[#a8d5c2]">right now?</span>`,
        subtext: `Your skill levels help us set the right
                  difficulty for quizzes and courses.`,
    },
    3: {
        headline: `What do you<br>want to<br><span class="text-[#a8d5c2]">achieve?</span>`,
        subtext: `Your goals shape your entire dashboard
                  — we prioritize what matters most to you.`,
    },
};


// ── Navigate between panels ───────────────────
function goToPanel(step) {
    // Hide all panels
    document.querySelectorAll('.panel').forEach(p => {
        p.classList.remove('active');
    });

    // Show target panel
    document.getElementById('panel' + step).classList.add('active');

    // Update left panel
    if (leftContent[step]) {
        document.getElementById('leftHeadline').innerHTML =
            leftContent[step].headline;
        document.getElementById('leftSubtext').textContent =
            leftContent[step].subtext;
    }

    // Update dots
    updateDots(step);

    // Scroll to top on mobile
    window.scrollTo({ top: 0, behavior: 'smooth' });
}


// ── Update left panel step dots ───────────────
function updateDots(activeStep) {
    // Survey has steps 2,3,4 on left panel
    // Step 1 (account) is always done
    // Survey step 1 = dot 2, survey step 2 = dot 3, survey step 3 = dot 4

    const dotMap = { 1: 2, 2: 3, 3: 4 };
    const activeDot = dotMap[activeStep];

    for (let i = 2; i <= 4; i++) {
        const dot   = document.getElementById('dot' + i);
        const label = document.getElementById('label' + i);
        const sub   = document.getElementById('sub' + i);

        dot.classList.remove('dot-active', 'dot-done', 'dot-inactive');

        if (i < activeDot) {
            // Done
            dot.classList.add('dot-done');
            dot.textContent = '✓';
            if (label) label.className = 'text-white text-sm font-semibold transition-colors duration-500';
            if (sub)   sub.classList.add('hidden');

        } else if (i === activeDot) {
            // Active
            dot.classList.add('dot-active');
            dot.textContent = i;
            if (label) label.className = 'text-white text-sm font-semibold transition-colors duration-500';
            if (sub) {
                sub.classList.remove('hidden');
                sub.textContent = 'You are here';
            }

        } else {
            // Inactive
            dot.classList.add('dot-inactive');
            dot.textContent = i;
            if (label) label.className = 'text-gray-500 text-sm transition-colors duration-500';
            if (sub)   sub?.classList.add('hidden');
        }
    }
}


// ── Step 1 next ───────────────────────────────
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

    if (!valid) {
        const btn = document.getElementById('btn1');
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    }

    goToPanel(2);
}


// ── Step 2 next ───────────────────────────────
function nextStep2() {
    // Skills always have a value (sliders default to 1)
    // so no required validation needed here
    survey.skill_prog = parseInt(
        document.getElementById('skillProg').value
    );
    survey.skill_db   = parseInt(
        document.getElementById('skillDb').value
    );
    survey.skill_ps   = parseInt(
        document.getElementById('skillPs').value
    );

    goToPanel(3);
}


// ── Finish survey ─────────────────────────────
function finishSurvey() {
    let valid = true;

    if (!survey.goal) {
        document.getElementById('err-goal').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-goal').classList.add('hidden');
    }

    if (!survey.learn_style) {
        document.getElementById('err-style').classList.remove('hidden');
        valid = false;
    } else {
        document.getElementById('err-style').classList.add('hidden');
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

    // Fill hidden fields
    document.getElementById('h-field').value  = survey.field;
    document.getElementById('h-role').value   = survey.role;
    document.getElementById('h-prog').value   = survey.skill_prog;
    document.getElementById('h-db').value     = survey.skill_db;
    document.getElementById('h-ps').value     = survey.skill_ps;
    document.getElementById('h-techs').value  = survey.technologies.join(',');
    document.getElementById('h-goal').value   = survey.goal;
    document.getElementById('h-style').value  = survey.learn_style;
    document.getElementById('h-avail').value  = survey.availability;

    // Show loading screen
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panelLoading').classList.add('active');

    // Update left panel for loading
    document.getElementById('leftHeadline').innerHTML =
        `Almost<br>there...<br><span class="text-[#a8d5c2]">hang on.</span>`;
    document.getElementById('leftSubtext').textContent =
        'We are building your personalized dashboard right now.';

    // All dots done
    for (let i = 2; i <= 4; i++) {
        const dot   = document.getElementById('dot' + i);
        const label = document.getElementById('label' + i);
        const sub   = document.getElementById('sub' + i);
        dot.classList.remove('dot-active','dot-inactive');
        dot.classList.add('dot-done');
        dot.textContent = '✓';
        if (label) label.className = 'text-white text-sm font-semibold';
        if (sub)   sub.classList.add('hidden');
    }

    // Submit after 2 seconds (let loading screen show)
    setTimeout(() => {
        document.getElementById('surveyForm').submit();
    }, 2000);
}


// ── Option card selection ─────────────────────
function selectOption(el, groupId, stateKey) {
    // Deselect all in group
    document.querySelectorAll('#' + groupId + ' .option-card').forEach(card => {
        card.classList.remove('selected');
        // Reset sub-text color if present
        const sub = card.querySelector('.option-sub');
        if (sub) sub.style.color = '';
    });

    // Select clicked
    el.classList.add('selected');

    // Update sub-text color for selected
    const sub = el.querySelector('.option-sub');
    if (sub) sub.style.color = 'rgba(255,255,255,0.65)';

    // Save to state
    survey[stateKey] = el.querySelector('.option-sub')
        ? el.querySelector('span:first-child').textContent.trim()
        : el.textContent.trim();
}


// ── Skill chip toggle ─────────────────────────
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


// ── Slider update ─────────────────────────────
function updateSlider(input, labelId) {
    const val = input.value;
    document.getElementById(labelId).textContent = val + ' / 5';

    // Update track fill color
    const pct = ((val - 1) / 4) * 100;
    input.style.background =
        `linear-gradient(to right, #111 ${pct}%, #e5e7eb ${pct}%)`;
}

// Init sliders on load
['skillProg','skillDb','skillPs'].forEach(id => {
    const el = document.getElementById(id);
    if (el) updateSlider(el, id === 'skillProg' ? 'progVal'
                            : id === 'skillDb'   ? 'dbVal'
                            :                      'psVal');
});

// Init dots
updateDots(1);

</script>
</body>
</html>