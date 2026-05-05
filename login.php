<?php
session_start();

// // Redirect if already logged in
// if (isset($_SESSION['user_id'])) {
//     header('Location: dashboard.php');
//     exit;
// }

$errors = [];
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    // ── Validation ──────────────────────
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    // ── If inputs are valid, check credentials ──
    if (empty($errors)) {

        // ====================================================
        // DB TODO: Replace this block with real DB check
        // ====================================================
        // $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        // $stmt->bind_param('s', $email);
        // $stmt->execute();
        // $user = $stmt->get_result()->fetch_assoc();
        //
        // if (!$user) {
        //     $errors['general'] = 'No account found with that email.';
        // } elseif (!password_verify($password, $user['password'])) {
        //     $errors['general'] = 'Invalid email or password.';
        // } else {
        //     $_SESSION['user_id']        = $user['id'];
        //     $_SESSION['user_name']      = $user['first_name'];
        //     $_SESSION['role']           = $user['role'];
        //     $_SESSION['survey_done']    = $user['profile_complete'];
        //
        //     if (!$user['profile_complete']) {
        //         header('Location: register.php');  // back to survey
        //     } elseif ($user['role'] === 'admin') {
        //         header('Location: admin/dashboard.php');
        //     } else {
        //         header('Location: dashboard.php');
        //     }
        //     exit;
        // }
        // ====================================================

        // DUMMY: Simulate login with test credentials
        // Test email:    test@test.com
        // Test password: 123456
        if ($email === 'test@test.com' && $password === '123456') {

            // DUMMY: Simulate a returning user who finished survey
            $_SESSION['user_id']     = 1;
            $_SESSION['user_name']   = 'John';
            $_SESSION['role']        = 'user';
            $_SESSION['survey_done'] = true;

            header('Location: dashboard.php');
            exit;

        } elseif ($email === 'new@test.com' && $password === '123456') {

            // DUMMY: Simulate a user who never finished survey
            $_SESSION['user_id']     = 2;
            $_SESSION['user_name']   = 'Jane';
            $_SESSION['role']        = 'user';
            $_SESSION['survey_done'] = false;

            // Send back to survey if not done
            header('Location: register.php');
            exit;

        } else {
            // Wrong credentials
            $errors['general'] = 'Invalid email or password. Try test@test.com / 123456';
        }

        $email_value = $email;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireReady — Welcome Back</title>
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
            from { opacity: 0; transform: translateY(16px); }
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
        @keyframes pulse-border {
            0%,100% { box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
            50%      { box-shadow: 0 0 0 5px rgba(239,68,68,0.2); }
        }

        .anim-left  { animation: slideInLeft  0.7s ease forwards; }
        .anim-right { opacity: 0; animation: slideInRight 0.7s ease 0.15s forwards; }

        .float-1 { animation: float 4s ease-in-out infinite; }
        .float-2 { animation: float 4s ease-in-out 0.7s infinite; }

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
            animation: pulse-border 1.5s ease infinite;
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
            letter-spacing: 0.2px;
        }
        .btn-main:hover:not(:disabled) {
            background: #2d2d2d;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .btn-main:active { transform: translateY(0); }
        .btn-main:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
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

        /* ── Error alert ────────────────────── */
        .error-alert {
            animation: fadeUp 0.3s ease forwards;
        }

        .shake { animation: shake 0.4s ease; }

        /* ── Progress dots (left panel) ─────── */
        .journey-dot-done {
            background: #22c55e;
            color: #fff;
        }
        .journey-dot-active {
            background: #a8d5c2;
            color: #111;
        }
        .journey-dot-inactive {
            background: #374151;
            color: #6b7280;
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

        <!-- Background blobs -->
        <div class="absolute top-[-100px] left-[-100px] w-72 h-72
                    rounded-full bg-[#a8d5c2] opacity-20 blur-3xl
                    pointer-events-none"></div>
        <div class="absolute bottom-[-80px] right-[-80px] w-80 h-80
                    rounded-full bg-[#b8cef0] opacity-20 blur-3xl
                    pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/4 w-64 h-64
                    rounded-full bg-[#f5c5a3] opacity-10 blur-3xl
                    pointer-events-none"></div>

        <!-- Logo -->
        <a href="index.php"
           class="text-white font-black text-2xl tracking-tight
                  no-underline relative z-10">
            HireReady
        </a>

        <!-- Middle content -->
        <div class="relative z-10">

            <!-- Floating mini cards -->
            <div class="flex gap-4 mb-10">
                <div class="deco-card flex-1 float-1">
                    <div class="text-white font-bold text-sm mb-1">
                        Your Progress
                    </div>
                    <div class="text-gray-400 text-xs leading-relaxed">
                        Pick up exactly where you left off
                    </div>
                </div>
                <div class="deco-card flex-1 float-2">
                    <div class="text-white font-bold text-sm mb-1">
                        Your Matches
                    </div>
                    <div class="text-gray-400 text-xs leading-relaxed">
                        Jobs waiting for you
                    </div>
                </div>
            </div>

            <!-- Headline -->
            <h2 class="text-white font-black text-4xl leading-tight
                       tracking-tight mb-5">
                Continue your<br>
                journey to getting<br>
                <span class="text-[#a8d5c2]">hired.</span>
            </h2>

            <p class="text-gray-400 text-sm leading-relaxed mb-10 max-w-xs">
                Your matched jobs, course progress, and applications
                are all waiting for you inside.
            </p>

            <!-- Journey progress — showing user is returning -->
            <div class="flex flex-col gap-1">

                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full journey-dot-done
                                flex items-center justify-center
                                font-black text-sm flex-shrink-0
                                transition-all duration-500">
                        ✓
                    </div>
                    <div>
                        <div class="text-white text-sm font-semibold">
                            Account created
                        </div>
                        <div class="text-[#22c55e] text-xs font-medium">
                            Done
                        </div>
                    </div>
                </div>

                <div class="w-px h-5 bg-gray-700 ml-4"></div>

                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full journey-dot-done
                                flex items-center justify-center
                                font-black text-sm flex-shrink-0">
                        ✓
                    </div>
                    <div>
                        <div class="text-white text-sm font-semibold">
                            Profile set up
                        </div>
                        <div class="text-[#22c55e] text-xs font-medium">
                            Done
                        </div>
                    </div>
                </div>

                <div class="w-px h-5 bg-gray-700 ml-4"></div>

                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full journey-dot-active
                                flex items-center justify-center
                                font-black text-sm flex-shrink-0">
                        3
                    </div>
                    <div>
                        <div class="text-white text-sm font-semibold">
                            Apply for jobs
                        </div>
                        <div class="text-[#a8d5c2] text-xs font-medium">
                            In progress
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Bottom testimonial -->
        <div class="deco-card relative z-10">
            <p class="text-gray-300 text-sm italic leading-relaxed mb-4">
                "The platform remembered exactly where I was.
                I logged back in and my matched jobs were right there."
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
         RIGHT PANEL — Login Form
    ════════════════════════════════════ -->
    <div class="w-full lg:w-1/2 flex items-center justify-center
                px-6 py-12 anim-right">

        <div class="w-full max-w-md">

            <!-- Mobile logo -->
            <div class="lg:hidden mb-8">
                <a href="index.php"
                   class="text-gray-900 font-black text-xl
                          tracking-tight no-underline">
                    HireReady
                </a>
            </div>

            <!-- Heading -->
            <div class="mb-8">
                <h1 class="text-3xl font-black text-gray-900
                           tracking-tight mb-2">
                    Welcome back
                </h1>
                <p class="text-gray-400 text-sm">
                    Log in to continue your progress.
                </p>
            </div>

            <!-- ── General Error Alert ── -->
            <?php if (!empty($errors['general'])): ?>
            <div class="error-alert mb-6 p-4 bg-red-50 border border-red-200
                        rounded-xl flex items-start gap-3">
                <span class="text-red-500 text-lg leading-none mt-0.5">✕</span>
                <div>
                    <p class="text-red-700 text-sm font-semibold mb-0.5">
                        Login failed
                    </p>
                    <p class="text-red-600 text-sm">
                        <?= htmlspecialchars($errors['general']) ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Test credentials hint (remove when DB is live) ── -->
            <!-- ====================================================
                 DUMMY: Remove this hint block when DB is connected
            ==================================================== -->
            <div class="mb-6 p-3 bg-gray-50 border border-gray-200
                        rounded-xl text-xs text-gray-500 leading-relaxed">
                <span class="font-semibold text-gray-700">Test credentials:</span><br>
                Returning user: <span class="font-mono">test@test.com</span> /
                <span class="font-mono">123456</span><br>
                New user (no survey): <span class="font-mono">new@test.com</span> /
                <span class="font-mono">123456</span>
            </div>

            <!-- ── LOGIN FORM ── -->
            <form method="POST" id="loginForm" novalidate>

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
                        value="<?= htmlspecialchars($email_value) ?>"
                        autocomplete="email"
                    >
                    <p id="err-email"
                       class="text-red-500 text-xs mt-1.5
                              <?= isset($errors['email']) ? '' : 'hidden' ?>">
                        <?= $errors['email'] ?? '' ?>
                    </p>
                </div>

                <!-- Password -->
                <div class="mb-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="passwordInput"
                            class="input-field <?= isset($errors['password']) ? 'is-error' : '' ?>"
                            placeholder="Your password"
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            onclick="togglePass('passwordInput', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2
                                   text-xs font-semibold text-gray-400
                                   hover:text-gray-800 px-2 py-1 rounded
                                   transition-colors">
                            Show
                        </button>
                    </div>
                    <p id="err-password"
                       class="text-red-500 text-xs mt-1.5
                              <?= isset($errors['password']) ? '' : 'hidden' ?>">
                        <?= $errors['password'] ?? '' ?>
                    </p>
                </div>

                <!-- Forgot password -->
                <div class="flex justify-end mb-7">
                    <!-- ================================================
                         TODO: Build forgot password page later
                    ================================================ -->
                    <a href="#"
                       onclick="forgotPassword(event)"
                       class="text-xs text-gray-500 hover:text-gray-900
                              font-medium transition-colors hover:underline">
                        Forgot password?
                    </a>
                </div>

                <!-- Submit -->
                <button
                    type="button"
                    id="loginBtn"
                    onclick="submitLogin()"
                    class="btn-main mb-3">
                    Continue
                </button>

                <!-- Divider -->
                <div class="flex items-center gap-3 my-6">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-gray-400 text-xs font-medium">OR</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <!-- Social buttons -->
                <!-- ================================================
                     TODO: Replace with real OAuth when ready
                ================================================ -->
                <div class="flex flex-col gap-3 mb-8">
                    <button type="button"
                            class="btn-social"
                            onclick="socialAlert('Google')">
                        <svg width="18" height="18" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Continue with Google
                    </button>

                    <button type="button"
                            class="btn-social"
                            onclick="socialAlert('GitHub')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#111">
                            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                        </svg>
                        Continue with GitHub
                    </button>
                </div>

                <!-- Signup redirect -->
                <p class="text-center text-sm text-gray-500">
                    Don't have an account?
                    <a href="register.php"
                       class="text-gray-900 font-bold hover:underline">
                        Sign up free
                    </a>
                </p>

            </form>
        </div>
    </div>

</div><!-- end layout -->


<!-- ════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════ -->
<script>

// ── Submit login (client-side validation) ────
function submitLogin() {
    const email    = document.getElementById('emailInput').value.trim();
    const password = document.getElementById('passwordInput').value;
    const btn      = document.getElementById('loginBtn');

    let valid = true;

    // Clear previous errors
    clearError('err-email');
    clearError('err-password');
    document.getElementById('emailInput').classList.remove('is-error');
    document.getElementById('passwordInput').classList.remove('is-error');

    // Validate
    if (!email) {
        showError('err-email', 'Email is required.');
        markError('emailInput');
        valid = false;
    } else if (!email.includes('@') || !email.includes('.')) {
        showError('err-email', 'Please enter a valid email address.');
        markError('emailInput');
        valid = false;
    }

    if (!password) {
        showError('err-password', 'Password is required.');
        markError('passwordInput');
        valid = false;
    }

    if (!valid) {
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 500);
        return;
    }

    // Loading state then submit
    btn.textContent = 'Logging in...';
    btn.disabled    = true;

    // Submit the form to PHP
    document.getElementById('loginForm').setAttribute('method', 'POST');
    document.getElementById('loginForm').submit();
}


// ── Allow Enter key to submit ─────────────────
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const active = document.activeElement;
        if (active && (active.id === 'emailInput' ||
                       active.id === 'passwordInput')) {
            submitLogin();
        }
    }
});


// ── Password show/hide ────────────────────────
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type      = 'text';
        btn.textContent = 'Hide';
    } else {
        input.type      = 'password';
        btn.textContent = 'Show';
    }
}


// ── Error helpers ─────────────────────────────
function showError(id, msg) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = msg;
        el.classList.remove('hidden');
    }
}

function clearError(id) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = '';
        el.classList.add('hidden');
    }
}

function markError(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('is-error');
        setTimeout(() => el.classList.remove('is-error'), 3000);
    }
}


// ── Clear error on typing ─────────────────────
document.querySelectorAll('.input-field').forEach(input => {
    input.addEventListener('input', function() {
        this.classList.remove('is-error');
        const errId = 'err-' + this.id.replace('Input','').toLowerCase();
        clearError(errId);
    });
});


// ── Social placeholder ────────────────────────
// ================================================
// TODO: Replace with real OAuth when ready
// ================================================
function socialAlert(provider) {
    alert(provider + ' login coming soon!');
}


// ── Forgot password placeholder ───────────────
// ================================================
// TODO: Build forgot password flow later
// ================================================
function forgotPassword(e) {
    e.preventDefault();
    alert('Forgot password — coming soon!\n\nFor now use the test credentials shown above.');
}

</script>
</body>
</html>