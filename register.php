<?php
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm']        ?? '';

    // ── Validation ──────────────────────────
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email.';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9+\s\-]{7,15}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if (empty($confirm)) {
        $errors['confirm'] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $errors['confirm'] = 'Passwords do not match.';
    }

    // ── If no errors
    if (empty($errors)) {

        // ── DB connection ──────────────────────
        define('DB_HOST', 'localhost');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_NAME', 'hireready_db');

        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            $errors['general'] = 'Database error. Please try again later.';
        } else {
            $conn->set_charset('utf8mb4');

            // Check for existing email
            $chk = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();

            if ($chk->num_rows > 0) {
                $errors['email'] = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, survey_done, created_at)
                                         VALUES (?, ?, ?, ?, 0, NOW())");
                $stmt->bind_param('ssss', $full_name, $email, $phone, $hash);

                if ($stmt->execute()) {
                    $name_parts = explode(' ', $full_name, 2);
                    $_SESSION['user_id']     = $conn->insert_id;
                    $_SESSION['user_name']   = $name_parts[0];
                    $_SESSION['full_name']   = $full_name;
                    $_SESSION['email']       = $email;
                    $_SESSION['role']        = 'user';
                    $_SESSION['survey_done'] = false;

                    header('Location: survey.php');
                    exit;
                } else {
                    $errors['general'] = 'Could not create account. Please try again.';
                }
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireReady — Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-32px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(32px); }
            to   { opacity: 1; transform: translateX(0); }
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

        .anim-left  { animation: slideInLeft  0.7s ease forwards; }
        .anim-right { opacity: 0; animation: slideInRight 0.7s ease 0.15s forwards; }
        .float-1    { animation: float 4s ease-in-out infinite; }
        .float-2    { animation: float 4s ease-in-out 0.6s infinite; }

        /* ── Inputs ─────────────────────────── */
        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #111;
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        .input-field:focus {
            border-color: #111;
            box-shadow: 0 0 0 3px rgba(17,17,17,0.07);
        }
        .input-field.is-error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.1);
        }
        .input-field.is-success {
            border-color: #22c55e;
        }

        /* ── Password wrapper ───────────────── */
        .pass-wrapper {
            position: relative;
        }
        .pass-wrapper .input-field {
            padding-right: 56px;
        }

        /* ── Eye toggle button ──────────────── */
        .eye-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
            border-radius: 4px;
        }
        .eye-toggle:hover {
            color: #374151;
        }
        .eye-toggle svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ── Strength bar ───────────────────── */
        .strength-bar {
            height: 4px;
            border-radius: 99px;
            transition: width 0.35s ease, background-color 0.35s ease;
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
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        .btn-main:hover:not(:disabled) {
            background: #2d2d2d;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .btn-main:active  { transform: translateY(0); }
        .btn-main:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 11px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            background: #fff;
            color: #111;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
            font-family: inherit;
        }
        .btn-social:hover {
            border-color: #111;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        /* ── Deco card ──────────────────────── */
        .deco-card {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            padding: 18px 20px;
            backdrop-filter: blur(6px);
        }

        .shake { animation: shake 0.4s ease; }

    </style>
</head>
<body class="bg-white min-h-screen">

<div class="flex min-h-screen">

    <!-- ════════════════════════════════
         LEFT PANEL
    ════════════════════════════════ -->
    <div class="hidden lg:flex lg:w-1/2 bg-[#111] flex-col
                justify-between p-12 relative overflow-hidden anim-left">

        <!-- Blobs -->
        <div class="absolute top-[-100px] left-[-100px] w-72 h-72
                    rounded-full bg-[#a8d5c2] opacity-20 blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-[-80px] right-[-80px] w-80 h-80
                    rounded-full bg-[#b8cef0] opacity-20 blur-3xl pointer-events-none"></div>

        <!-- Logo -->
        <a href="index.php"
           class="text-white font-black text-2xl tracking-tight no-underline relative z-10">
            HireReady
        </a>

        <!-- Middle -->
        <div class="relative z-10">

            <!-- Floating cards -->
            <div class="flex gap-4 mb-10">
                <div class="deco-card flex-1 float-1">
                    <div class="text-white font-bold text-sm mb-1">Smart Matching</div>
                    <div class="text-gray-400 text-xs leading-relaxed">
                        Jobs matched to your real skills
                    </div>
                </div>
                <div class="deco-card flex-1 float-2">
                    <div class="text-white font-bold text-sm mb-1">Guided Growth</div>
                    <div class="text-gray-400 text-xs leading-relaxed">
                        Courses that fill your gaps
                    </div>
                </div>
            </div>

            <!-- Headline -->
            <h2 class="text-white font-black text-4xl leading-tight tracking-tight mb-5">
                Start your journey<br>
                to getting hired<br>
                <span class="text-[#a8d5c2]">smarter.</span>
            </h2>

            <p class="text-gray-400 text-sm leading-relaxed mb-10 max-w-xs">
                Answer a few questions after signing up and we will
                match you with jobs and courses that actually fit you.
            </p>

            <!-- Steps -->
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full bg-[#a8d5c2]
                                flex items-center justify-center
                                text-black font-black text-sm flex-shrink-0">
                        1
                    </div>
                    <div>
                        <div class="text-white text-sm font-semibold">
                            Create your account
                        </div>
                        <div class="text-[#a8d5c2] text-xs font-medium">
                            You are here
                        </div>
                    </div>
                </div>

                <div class="w-px h-5 bg-gray-700 ml-4"></div>

                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full bg-gray-700
                                flex items-center justify-center
                                text-gray-500 font-black text-sm flex-shrink-0">
                        2
                    </div>
                    <div class="text-gray-500 text-sm">Tell us about your skills</div>
                </div>

                <div class="w-px h-5 bg-gray-700 ml-4"></div>

                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full bg-gray-700
                                flex items-center justify-center
                                text-gray-500 font-black text-sm flex-shrink-0">
                        3
                    </div>
                    <div class="text-gray-500 text-sm">Get your personalized dashboard</div>
                </div>
            </div>
        </div>

        <!-- Testimonial -->
        <div class="deco-card relative z-10">
            <p class="text-gray-300 text-sm italic leading-relaxed mb-4">
                "I failed the first quiz, took the recommended course,
                retried and got hired in 3 weeks!"
            </p>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#a8d5c2]
                            flex items-center justify-center
                            text-black font-bold text-sm">
                    S
                </div>
                <div>
                    <div class="text-white text-xs font-bold">Sarah K.</div>
                    <div class="text-gray-500 text-xs">Frontend Developer</div>
                </div>
                <div class="ml-auto text-yellow-400 text-xs tracking-widest">
                    * * * * *
                </div>
            </div>
        </div>

    </div>


    <!-- ════════════════════════════════
         RIGHT PANEL
    ════════════════════════════════ -->
    <div class="w-full lg:w-1/2 flex items-center justify-center
                px-6 py-12 overflow-y-auto anim-right">

        <div class="w-full max-w-md">

            <!-- Mobile logo -->
            <div class="lg:hidden mb-8">
                <a href="index.php"
                   class="text-gray-900 font-black text-xl tracking-tight no-underline">
                    HireReady
                </a>
            </div>

            <!-- Heading -->
            <div class="mb-8">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight mb-2">
                    Create your account
                </h1>
                <p class="text-gray-400 text-sm">
                    Takes less than a minute to get started.
                </p>
            </div>

            <!-- Server errors -->
            <?php if (!empty($errors)): ?>
            <div class="mb-5 p-4 bg-red-50 border border-red-200
                        rounded-xl text-red-600 text-sm">
                <div class="font-bold mb-1">Please fix the following:</div>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- ── FORM ── -->
            <form method="POST" id="signupForm" novalidate>

                <!-- Full Name -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Full Name
                    </label>
                    <input
                        type="text"
                        name="full_name"
                        id="fullName"
                        class="input-field <?= isset($errors['full_name']) ? 'is-error' : '' ?>"
                        placeholder="Web P"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        autocomplete="name"
                    >
                    <p id="err-name"
                       class="text-red-500 text-xs mt-1.5
                              <?= isset($errors['full_name']) ? '' : 'hidden' ?>">
                        <?= $errors['full_name'] ?? '' ?>
                    </p>
                </div>

                <!-- Email -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Email Address
                    </label>
                    <input
                        type="email"
                        name="email"
                        id="emailInput"
                        class="input-field <?= isset($errors['email']) ? 'is-error' : '' ?>"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                    >
                    <p id="err-email"
                       class="text-red-500 text-xs mt-1.5
                              <?= isset($errors['email']) ? '' : 'hidden' ?>">
                        <?= $errors['email'] ?? '' ?>
                    </p>
                </div>

                <!-- Phone Number -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Phone Number
                    </label>
                    <input
                        type="tel"
                        name="phone"
                        id="phoneInput"
                        class="input-field <?= isset($errors['phone']) ? 'is-error' : '' ?>"
                        placeholder="+880"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                        autocomplete="tel"
                    >
                    <p id="err-phone"
                       class="text-red-500 text-xs mt-1.5
                              <?= isset($errors['phone']) ? '' : 'hidden' ?>">
                        <?= $errors['phone'] ?? '' ?>
                    </p>
                </div>

                <!-- Password -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Password
                    </label>
                    <div class="pass-wrapper">
                        <input
                            type="password"
                            name="password"
                            id="passwordInput"
                            class="input-field <?= isset($errors['password']) ? 'is-error' : '' ?>"
                            placeholder="Minimum 8 characters"
                            autocomplete="new-password"
                            oninput="checkStrength(this.value)"
                        >
                        <button type="button"
                                class="eye-toggle"
                                onclick="togglePass('passwordInput', this)"
                                aria-label="Show password">
                            <!-- Eye open SVG — shown by default -->
                            <svg class="eye-open" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <!-- Eye closed SVG — hidden by default -->
                            <svg class="eye-closed" viewBox="0 0 24 24" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Strength bar -->
                    <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                        <div id="strengthBar" class="strength-bar"
                             style="width:0%; background:#e5e7eb;"></div>
                    </div>
                    <p id="strengthText" class="text-xs text-gray-400 mt-1 min-h-[16px]"></p>

                    <p id="err-pass"
                       class="text-red-500 text-xs mt-1
                              <?= isset($errors['password']) ? '' : 'hidden' ?>">
                        <?= $errors['password'] ?? '' ?>
                    </p>
                </div>

                <!-- Confirm Password -->
                <div class="mb-7">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Confirm Password
                    </label>
                    <div class="pass-wrapper">
                        <input
                            type="password"
                            name="confirm"
                            id="confirmInput"
                            class="input-field <?= isset($errors['confirm']) ? 'is-error' : '' ?>"
                            placeholder="Repeat your password"
                            autocomplete="new-password"
                            oninput="checkMatch()"
                        >
                        <button type="button"
                                class="eye-toggle"
                                onclick="togglePass('confirmInput', this)"
                                aria-label="Show confirm password">
                            <svg class="eye-open" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>

                    <p id="matchText" class="text-xs mt-1.5 min-h-[16px]"></p>

                    <p id="err-confirm"
                       class="text-red-500 text-xs mt-1
                              <?= isset($errors['confirm']) ? '' : 'hidden' ?>">
                        <?= $errors['confirm'] ?? '' ?>
                    </p>
                </div>

                <!-- Submit -->
                <button type="button"
                        id="submitBtn"
                        onclick="submitSignup()"
                        class="btn-main mb-3">
                    Create Account
                </button>

                <p class="text-center text-xs text-gray-400 mb-6 leading-relaxed">
                    We will use this to recommend the best jobs and courses for you.
                </p>

                <!-- Divider -->
                <div class="flex items-center gap-3 mb-5">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-gray-400 text-xs font-medium">OR</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <!-- Social -->
                <!-- ================================================
                     TODO: Replace with real OAuth when ready
                ================================================ -->
                <div class="flex flex-col gap-3 mb-8">
                    <button type="button" class="btn-social"
                            onclick="socialAlert('Google')">
                        <svg width="18" height="18" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Continue with Google
                    </button>
                    <button type="button" class="btn-social"
                            onclick="socialAlert('GitHub')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#111">
                            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                        </svg>
                        Continue with GitHub
                    </button>
                </div>

                <!-- Login link -->
                <p class="text-center text-sm text-gray-500">
                    Already have an account?
                    <a href="login.php"
                       class="text-gray-900 font-bold hover:underline">
                        Log In
                    </a>
                </p>

            </form>
        </div>
    </div>

</div>


<!-- ════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════ -->
<script>

// ── 1. Toggle password visibility ────────────
function togglePass(inputId, btn) {
    const input     = document.getElementById(inputId);
    const eyeOpen   = btn.querySelector('.eye-open');
    const eyeClosed = btn.querySelector('.eye-closed');

    if (input.type === 'password') {
        input.type              = 'text';
        eyeOpen.style.display   = 'none';
        eyeClosed.style.display = 'block';
    } else {
        input.type              = 'password';
        eyeOpen.style.display   = 'block';
        eyeClosed.style.display = 'none';
    }
}


// ── 2. Password strength ──────────────────────
function checkStrength(val) {
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score  = 0;

    if (val.length >= 6)             score++;
    if (val.length >= 10)            score++;
    if (/[A-Z]/.test(val))           score++;
    if (/[0-9]/.test(val))           score++;
    if (/[^A-Za-z0-9]/.test(val))    score++;

    const levels = [
        { pct:'0%',   color:'#e5e7eb', label:'',            css:'#9ca3af' },
        { pct:'25%',  color:'#ef4444', label:'Weak',        css:'#ef4444' },
        { pct:'50%',  color:'#f59e0b', label:'Fair',        css:'#f59e0b' },
        { pct:'75%',  color:'#3b82f6', label:'Good',        css:'#3b82f6' },
        { pct:'90%',  color:'#22c55e', label:'Strong',      css:'#22c55e' },
        { pct:'100%', color:'#16a34a', label:'Very Strong', css:'#16a34a' },
    ];

    const lvl = levels[Math.min(score, 5)];
    bar.style.width           = lvl.pct;
    bar.style.backgroundColor = lvl.color;
    text.textContent          = lvl.label;
    text.style.color          = lvl.css;
}


// ── 3. Confirm password match ─────────────────
function checkMatch() {
    const pass    = document.getElementById('passwordInput').value;
    const confirm = document.getElementById('confirmInput').value;
    const text    = document.getElementById('matchText');
    const field   = document.getElementById('confirmInput');

    if (!confirm) {
        text.textContent = '';
        field.classList.remove('is-error', 'is-success');
        return;
    }

    if (pass === confirm) {
        text.textContent  = 'Passwords match';
        text.style.color  = '#22c55e';
        field.classList.remove('is-error');
        field.classList.add('is-success');
    } else {
        text.textContent  = 'Passwords do not match';
        text.style.color  = '#ef4444';
        field.classList.remove('is-success');
        field.classList.add('is-error');
    }
}


// ── 4. Submit with validation ─────────────────
function submitSignup() {
    const name    = document.getElementById('fullName').value.trim();
    const email   = document.getElementById('emailInput').value.trim();
    const phone   = document.getElementById('phoneInput').value.trim();
    const pass    = document.getElementById('passwordInput').value;
    const confirm = document.getElementById('confirmInput').value;
    const btn     = document.getElementById('submitBtn');

    let valid = true;

    // Clear all errors first
    ['err-name','err-email','err-phone','err-pass','err-confirm'].forEach(clearError);
    ['fullName','emailInput','phoneInput','passwordInput','confirmInput']
        .forEach(id => document.getElementById(id).classList.remove('is-error'));

    if (!name) {
        showError('err-name', 'Full name is required.');
        markError('fullName');
        valid = false;
    }
    if (!email || !email.includes('@')) {
        showError('err-email', 'Please enter a valid email.');
        markError('emailInput');
        valid = false;
    }
    if (!phone) {
        showError('err-phone', 'Phone number is required.');
        markError('phoneInput');
        valid = false;
    }
    if (pass.length < 6) {
        showError('err-pass', 'Password must be at least 6 characters.');
        markError('passwordInput');
        valid = false;
    }
    if (!confirm || pass !== confirm) {
        showError('err-confirm', 'Passwords do not match.');
        markError('confirmInput');
        valid = false;
    }

    if (!valid) {
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    }

    // Loading state — then submit
    btn.textContent = 'Creating account...';
    btn.disabled    = true;
    document.getElementById('signupForm').setAttribute('method', 'POST');
    document.getElementById('signupForm').submit();
}


// ── 5. Error helpers ──────────────────────────
function showError(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('hidden');
}
function clearError(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = '';
    el.classList.add('hidden');
}
function markError(id) {
    document.getElementById(id)?.classList.add('is-error');
}


// ── 6. Clear errors on typing ─────────────────
document.querySelectorAll('.input-field').forEach(input => {
    input.addEventListener('input', function() {
        this.classList.remove('is-error');
    });
});


// ── 7. Social placeholder ─────────────────────
// TODO: Replace with real OAuth
function socialAlert(provider) {
    alert(provider + ' sign-in coming soon!');
}

</script>
</body>
</html>