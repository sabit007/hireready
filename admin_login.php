<?php
// ============================================================
//  HireReady Admin — Login  (login.php)
//  Place this file inside:  C:/xampp/htdocs/hireready/
//  Access via:              http://localhost/hireready/login.php
// ============================================================

session_start();

// ── Database config ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // default XAMPP user
define('DB_PASS', '');            // default XAMPP password (empty)
define('DB_NAME', 'hireready_db');

// ── DB connection helper ─────────────────────────────────────
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── Handle POST (AJAX login request) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic validation
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT id, email, password_hash, rep_name, rep_role, company_name, is_approved FROM admins WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
        $stmt->close();
        $db->close();
        exit;
    }

    $admin = $result->fetch_assoc();

    // Check approval status
    if (!$admin['is_approved']) {
        echo json_encode(['success' => false, 'message' => 'Your account is pending approval by a super admin.']);
        $stmt->close();
        $db->close();
        exit;
    }

    // Verify password
    if (!password_verify($password, $admin['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
        $stmt->close();
        $db->close();
        exit;
    }

    // ── Success: store session ────────────────────────────
    $_SESSION['admin_id']       = $admin['id'];
    $_SESSION['admin_email']    = $admin['email'];
    $_SESSION['admin_name']     = $admin['rep_name'];
    $_SESSION['admin_role']     = $admin['rep_role'];
    $_SESSION['company_name']   = $admin['company_name'];

    // Update last_login timestamp
    $upd = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
    $upd->bind_param('i', $admin['id']);
    $upd->execute();
    $upd->close();

    $stmt->close();
    $db->close();

    echo json_encode([
        'success'  => true,
        'message'  => 'Login successful.',
        'redirect' => 'admin_dashboard.php'
    ]);
    exit;
}

// ── If already logged in, redirect to dashboard ──────────────
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HireReady — Admin Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --color-bg:          #ffffff;
      --color-surface:     #f7f7f7;
      --color-border:      #e4e4e4;
      --color-text-primary:#0a0a0a;
      --color-text-muted:  #6b6b6b;
      --color-text-subtle: #9b9b9b;
      --color-accent:      #0a0a0a;
      --color-accent-hover:#1f1f1f;
      --color-danger-bg:   #fef2f2;
      --color-danger-text: #c0392b;
      --color-success-bg:  #f0fdf4;
      --color-success-text:#166534;
      --color-success-border:#bbf7d0;
      --font-family:       'DM Sans', sans-serif;
      --radius-sm:         6px;
      --radius-md:         8px;
      --radius-lg:         12px;
      --input-height:      42px;
      --btn-height:        42px;
      --transition:        0.18s ease;
    }

    html, body { height: 100%; }

    body {
      font-family:      var(--font-family);
      background-color: var(--color-bg);
      color:            var(--color-text-primary);
    }

    /* ── Split Shell ── */
    .split-shell {
      width: 100%; height: 100vh;
      display: flex; overflow: hidden;
    }

    /* ── Left Black Panel ── */
    .panel-left {
      width: 380px; flex-shrink: 0;
      background: #0a0a0a;
      display: flex; flex-direction: column; justify-content: space-between;
      padding: 3rem;
    }

    .panel-left .logo { display: flex; align-items: baseline; gap: 8px; }
    .panel-left .logo-wordmark { font-size: 20px; font-weight: 700; letter-spacing: -0.4px; color: #fff; line-height: 1; }
    .panel-left .logo-badge { font-size: 10px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; color: #555; border: 1px solid #2a2a2a; border-radius: var(--radius-sm); padding: 2px 6px; line-height: 1.5; }

    .panel-body { display: flex; flex-direction: column; gap: 1.5rem; }
    .panel-headline { font-size: 24px; font-weight: 700; color: #fff; line-height: 1.35; letter-spacing: -0.3px; }
    .panel-desc { font-size: 13px; color: #555; line-height: 1.7; }

    .panel-chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .chip { font-size: 11.5px; font-weight: 500; color: #aaa; background: #161616; border: 1px solid #222; border-radius: 999px; padding: 4px 11px; line-height: 1.5; }

    .panel-footer { font-size: 11px; color: #333; line-height: 1.5; }

    /* ── Right White Panel ── */
    .panel-right {
      flex: 1; background: #fff;
      display: flex; align-items: center; justify-content: center;
      padding: 3rem; border-left: 1px solid #ebebeb; overflow-y: auto;
    }

    .page-wrapper { width: 100%; max-width: 340px; display: flex; flex-direction: column; gap: 1.75rem; }

    /* ── Card ── */
    .card { display: flex; flex-direction: column; gap: 1.5rem; }
    .card-header { display: flex; flex-direction: column; gap: 4px; }
    .card-title { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; line-height: 1.2; }
    .card-subtitle { font-size: 13.5px; color: var(--color-text-muted); line-height: 1.5; }

    /* ── Form ── */
    .form { display: flex; flex-direction: column; gap: 14px; }

    .field { display: flex; flex-direction: column; gap: 6px; }
    .field-row { display: flex; justify-content: space-between; align-items: center; }

    label { font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); letter-spacing: 0.1px; }

    .forgot-link { font-size: 12.5px; color: var(--color-text-muted); text-decoration: none; transition: color var(--transition); }
    .forgot-link:hover { color: var(--color-text-primary); }

    /* ── Inputs ── */
    .input-wrap { position: relative; }

    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%; height: var(--input-height);
      border: 1.5px solid var(--color-border); border-radius: var(--radius-md);
      padding: 0 14px; font-family: var(--font-family); font-size: 13.5px;
      color: var(--color-text-primary); background: var(--color-bg);
      outline: none; transition: border-color var(--transition), box-shadow var(--transition);
      -webkit-appearance: none;
    }

    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="text"]:focus {
      border-color: var(--color-accent);
      box-shadow: 0 0 0 3px rgba(10,10,10,0.07);
    }

    input::placeholder { color: var(--color-text-subtle); }
    .input-wrap input { padding-right: 42px; }

    input.is-error { border-color: #e53e3e; }
    input.is-error:focus { box-shadow: 0 0 0 3px rgba(229,62,62,0.1); }

    .toggle-pw {
      position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--color-text-subtle);
      padding: 0; display: flex; align-items: center; transition: color var(--transition);
    }
    .toggle-pw:hover { color: var(--color-text-primary); }

    /* ── Checkbox ── */
    .checkbox-row { display: flex; align-items: center; gap: 8px; }
    input[type="checkbox"] { width: 15px; height: 15px; accent-color: var(--color-accent); cursor: pointer; flex-shrink: 0; }
    .checkbox-label { font-size: 13px; font-weight: 400; color: var(--color-text-muted); cursor: pointer; }

    /* ── Submit Button ── */
    .btn-primary {
      width: 100%; height: var(--btn-height);
      background: var(--color-accent); color: #fff; border: none;
      border-radius: var(--radius-md); font-family: var(--font-family);
      font-size: 13.5px; font-weight: 600; letter-spacing: 0.1px;
      cursor: pointer; transition: background var(--transition), transform var(--transition), opacity var(--transition);
      margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary:hover { background: var(--color-accent-hover); }
    .btn-primary:active { transform: scale(0.99); }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

    /* ── Alert boxes ── */
    .alert {
      display: none; align-items: flex-start; gap: 9px;
      border-radius: var(--radius-md); padding: 11px 13px;
      font-size: 12.5px; font-weight: 500; line-height: 1.5;
      animation: fadeIn 0.2s ease;
    }
    .alert.show { display: flex; }
    .alert-error   { background: var(--color-danger-bg);   color: var(--color-danger-text);   border: 1px solid #fecaca; }
    .alert-success { background: var(--color-success-bg);  color: var(--color-success-text);  border: 1px solid var(--color-success-border); }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

    /* ── Spinner ── */
    .spinner {
      width: 15px; height: 15px;
      border: 2px solid rgba(255,255,255,0.35);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.65s linear infinite;
      flex-shrink: 0;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Notice ── */
    .notice { display: flex; align-items: flex-start; gap: 8px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 10px 13px; }
    .notice svg { color: var(--color-text-subtle); flex-shrink: 0; margin-top: 1px; }
    .notice-text { font-size: 12px; color: var(--color-text-muted); line-height: 1.55; }

    /* ── Responsive ── */
    @media (max-width: 680px) {
      .split-shell  { flex-direction: column; height: auto; min-height: 100vh; }
      .panel-left   { width: 100%; padding: 2rem; }
      .panel-right  { border-left: none; border-top: 1px solid #ebebeb; padding: 2rem; }
      .panel-headline { font-size: 20px; }
    }

  </style>
</head>
<body>

<div class="split-shell">

  <!-- ── Left black panel ── -->
  <div class="panel-left">
    <div class="logo">
      <span class="logo-wordmark">HireReady</span>
      <span class="logo-badge">Admin</span>
    </div>
    <div class="panel-body">
      <h2 class="panel-headline">Manage everything in one place.</h2>
      <p class="panel-desc">Jobs, users, assessments, and courses — all controlled from your admin dashboard.</p>
      <div class="panel-chips">
        <span class="chip">Users</span>
        <span class="chip">Jobs</span>
        <span class="chip">Courses</span>
        <span class="chip">Quizzes</span>
        <span class="chip">Settings</span>
      </div>
    </div>
    <p class="panel-footer">© 2025 HireReady · Admin access only</p>
  </div>

  <!-- ── Right white panel ── -->
  <div class="panel-right">
    <div class="page-wrapper">

      <div class="card">

        <div class="card-header">
          <h1 class="card-title">Admin sign in</h1>
          <p class="card-subtitle">Enter your credentials to access the dashboard.</p>
        </div>

        <!-- Alert messages (shown by JS after AJAX response) -->
        <div class="alert alert-error"   id="alertError">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span id="alertErrorText"></span>
        </div>
        <div class="alert alert-success" id="alertSuccess">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <span id="alertSuccessText"></span>
        </div>

        <div class="form" id="loginForm">

          <!-- Email -->
          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="admin@hireready.com" autocomplete="email" />
          </div>

          <!-- Password -->
          <div class="field">
            <div class="field-row">
              <label for="password">Password</label>
              <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            <div class="input-wrap">
              <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" />
              <button class="toggle-pw" type="button" id="togglePw" aria-label="Toggle password visibility">
                <svg id="iconEye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg id="iconEyeOff" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>

          <!-- Remember me -->
          <div class="checkbox-row">
            <input type="checkbox" id="remember" name="remember" />
            <label for="remember" class="checkbox-label">Keep me signed in for 30 days</label>
          </div>

          <!-- Submit -->
          <button class="btn-primary" type="button" id="signInBtn">
            Sign in →
          </button>

        </div>

        <!-- Security notice -->
        <div class="notice">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span class="notice-text">Restricted to HireReady administrators. Unauthorized access is prohibited.</span>
        </div>

        <!-- Sign-up link -->
        <p style="text-align:center; font-size:13px; color:#6b6b6b;">
          Don't have an account? <a href="admin_signup.php" style="color:#0a0a0a; font-weight:600; text-decoration:none;" onmouseover="this.style.opacity='0.6'" onmouseout="this.style.opacity='1'">Create admin account</a>
        </p>

      </div><!-- /.card -->

    </div><!-- /.page-wrapper -->
  </div><!-- /.panel-right -->

</div><!-- /.split-shell -->

<script>
  // ── Password toggle ──────────────────────────────────────
  const pwInput    = document.getElementById('password');
  const toggleBtn  = document.getElementById('togglePw');
  const iconEye    = document.getElementById('iconEye');
  const iconEyeOff = document.getElementById('iconEyeOff');

  toggleBtn.addEventListener('click', () => {
    const hidden = pwInput.type === 'password';
    pwInput.type             = hidden ? 'text' : 'password';
    iconEye.style.display    = hidden ? 'none' : '';
    iconEyeOff.style.display = hidden ? ''     : 'none';
  });

  // ── Alert helpers ────────────────────────────────────────
  function showError(msg) {
    const el = document.getElementById('alertError');
    document.getElementById('alertErrorText').textContent = msg;
    el.classList.add('show');
    document.getElementById('alertSuccess').classList.remove('show');
    // Mark fields red
    document.getElementById('email').classList.add('is-error');
    document.getElementById('password').classList.add('is-error');
  }

  function showSuccess(msg) {
    const el = document.getElementById('alertSuccess');
    document.getElementById('alertSuccessText').textContent = msg;
    el.classList.add('show');
    document.getElementById('alertError').classList.remove('show');
    document.getElementById('email').classList.remove('is-error');
    document.getElementById('password').classList.remove('is-error');
  }

  function clearAlerts() {
    document.getElementById('alertError').classList.remove('show');
    document.getElementById('alertSuccess').classList.remove('show');
    document.getElementById('email').classList.remove('is-error');
    document.getElementById('password').classList.remove('is-error');
  }

  // ── Sign-in AJAX submit ──────────────────────────────────
  const signInBtn = document.getElementById('signInBtn');

  signInBtn.addEventListener('click', async () => {
    clearAlerts();

    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();

    // Client-side validation
    if (!email || !password) {
      showError('Please enter your email and password.');
      return;
    }

    // Loading state
    signInBtn.disabled  = true;
    signInBtn.innerHTML = '<span class="spinner"></span> Signing in…';

    try {
      const formData = new FormData();
      formData.append('email',    email);
      formData.append('password', password);

      const response = await fetch('admin_login.php', {
        method: 'POST',
        body:   formData
      });

      const data = await response.json();

      if (data.success) {
        showSuccess(data.message + ' Redirecting…');
        signInBtn.innerHTML = '✓ Signed in';
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 900);
      } else {
        showError(data.message);
        signInBtn.disabled  = false;
        signInBtn.innerHTML = 'Sign in →';
      }

    } catch (err) {
      showError('Something went wrong. Please try again.');
      signInBtn.disabled  = false;
      signInBtn.innerHTML = 'Sign in →';
    }
  });

  // ── Clear error on input ─────────────────────────────────
  document.getElementById('email').addEventListener('input', clearAlerts);
  document.getElementById('password').addEventListener('input', clearAlerts);

  // ── Allow Enter key to submit ────────────────────────────
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') signInBtn.click();
  });
</script>

</body>
</html>
