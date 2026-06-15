<?php
// ============================================================
//  HireReady Admin — Sign Up  (signup.php)
//  Place this file inside:  C:/xampp/htdocs/hireready/
//  Access via:              http://localhost/hireready/signup.php
// ============================================================

session_start();

// ── Database config ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // default XAMPP user
define('DB_PASS', '');           // default XAMPP password (empty)
define('DB_NAME', 'hireready_db');

// ── DB connection helper ─────────────────────────────────────
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── Handle POST (AJAX signup request) ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Collect & sanitize all fields
    $companyName    = trim($_POST['companyName']    ?? '');
    $companyAddress = trim($_POST['companyAddress'] ?? '');
    $repName        = trim($_POST['repName']        ?? '');
    $repRole        = trim($_POST['repRole']        ?? '');
    $email          = trim($_POST['email']          ?? '');
    $phone          = trim($_POST['phone']          ?? '');
    $password       = $_POST['password']            ?? '';
    $confirmPw      = $_POST['confirmPassword']     ?? '';

    // ── Server-side validation ───────────────────────────────

    // Required fields
    if (empty($companyName)) {
        echo json_encode(['success' => false, 'field' => 'companyName', 'message' => 'Company name is required.']);
        exit;
    }

    if (empty($companyAddress)) {
        echo json_encode(['success' => false, 'field' => 'companyAddress', 'message' => 'Company address is required.']);
        exit;
    }

    if (empty($repName)) {
        echo json_encode(['success' => false, 'field' => 'repName', 'message' => 'Representative name is required.']);
        exit;
    }

    if (empty($repRole)) {
        echo json_encode(['success' => false, 'field' => 'repRole', 'message' => 'Representative role is required.']);
        exit;
    }

    if (empty($email)) {
        echo json_encode(['success' => false, 'field' => 'email', 'message' => 'Email address is required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'field' => 'email', 'message' => 'Please enter a valid email address.']);
        exit;
    }

    if (empty($phone)) {
        echo json_encode(['success' => false, 'field' => 'phone', 'message' => 'Phone number is required.']);
        exit;
    }

    if (empty($password)) {
        echo json_encode(['success' => false, 'field' => 'password', 'message' => 'Password is required.']);
        exit;
    }

    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'field' => 'password', 'message' => 'Password must be at least 8 characters.']);
        exit;
    }

    // Password strength: at least one uppercase, one lowercase, one number
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        echo json_encode(['success' => false, 'field' => 'password', 'message' => 'Password must contain uppercase, lowercase, and a number.']);
        exit;
    }

    if ($password !== $confirmPw) {
        echo json_encode(['success' => false, 'field' => 'confirmPassword', 'message' => 'Passwords do not match.']);
        exit;
    }

    // ── Check if email already exists ───────────────────────
    $db   = getDB();
    $chk  = $db->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $email);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        echo json_encode(['success' => false, 'field' => 'email', 'message' => 'An account with this email already exists.']);
        $chk->close();
        $db->close();
        exit;
    }
    $chk->close();

    // ── Hash password & insert ───────────────────────────────
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // is_approved = 0 means pending review (admin must approve manually in DB or via a super-admin panel)
    $stmt = $db->prepare("
        INSERT INTO admins
            (company_name, company_address, rep_name, rep_role, email, phone, password_hash, is_approved, created_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    $stmt->bind_param('sssssss',
        $companyName,
        $companyAddress,
        $repName,
        $repRole,
        $email,
        $phone,
        $passwordHash
    );

    if ($stmt->execute()) {
        $newId = $db->insert_id;
        $stmt->close();
        $db->close();

        echo json_encode([
            'success'  => true,
            'message'  => 'Account created successfully! You will be notified once approved.',
            'redirect' => 'admin_login.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again. Error: ' . $db->error]);
        $stmt->close();
        $db->close();
    }
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
  <title>HireReady — Admin Sign Up</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --color-bg:           #ffffff;
      --color-surface:      #f7f7f7;
      --color-border:       #e4e4e4;
      --color-text-primary: #0a0a0a;
      --color-text-muted:   #6b6b6b;
      --color-text-subtle:  #9b9b9b;
      --color-accent:       #0a0a0a;
      --color-accent-hover: #1f1f1f;
      --color-danger-bg:    #fef2f2;
      --color-danger-text:  #c0392b;
      --color-danger-border:#fecaca;
      --color-success-bg:   #f0fdf4;
      --color-success-text: #166534;
      --color-success-border:#bbf7d0;
      --font-family:        'DM Sans', sans-serif;
      --radius-sm:          6px;
      --radius-md:          8px;
      --radius-lg:          12px;
      --input-height:       42px;
      --btn-height:         42px;
      --transition:         0.18s ease;
    }

    html, body { height: 100%; }

    body {
      font-family:      var(--font-family);
      background-color: var(--color-bg);
      color:            var(--color-text-primary);
    }

    /* ── Split Shell ── */
    .split-shell { width: 100%; height: 100vh; display: flex; overflow: hidden; }

    /* ── Left Black Panel ── */
    .panel-left {
      width: 380px; flex-shrink: 0; background: #0a0a0a;
      display: flex; flex-direction: column; justify-content: space-between;
      padding: 3rem;
    }
    .panel-left .logo       { display: flex; align-items: baseline; gap: 8px; }
    .panel-left .logo-wordmark { font-size: 20px; font-weight: 700; letter-spacing: -0.4px; color: #fff; line-height: 1; }
    .panel-left .logo-badge { font-size: 10px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; color: #555; border: 1px solid #2a2a2a; border-radius: var(--radius-sm); padding: 2px 6px; line-height: 1.5; }
    .panel-body  { display: flex; flex-direction: column; gap: 1.5rem; }
    .panel-headline { font-size: 24px; font-weight: 700; color: #fff; line-height: 1.35; letter-spacing: -0.3px; }
    .panel-desc  { font-size: 13px; color: #555; line-height: 1.7; }
    .panel-chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .chip        { font-size: 11.5px; font-weight: 500; color: #aaa; background: #161616; border: 1px solid #222; border-radius: 999px; padding: 4px 11px; line-height: 1.5; }
    .panel-footer{ font-size: 11px; color: #333; line-height: 1.5; }

    /* ── Right White Panel ── */
    .panel-right {
      flex: 1; background: #fff;
      display: flex; align-items: flex-start; justify-content: center;
      padding: 3rem; border-left: 1px solid #ebebeb; overflow-y: auto;
    }

    .page-wrapper { width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 1.75rem; padding: 2rem 0; }

    /* ── Card ── */
    .card        { display: flex; flex-direction: column; gap: 1.5rem; }
    .card-header { display: flex; flex-direction: column; gap: 4px; }
    .card-title  { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; line-height: 1.2; }
    .card-subtitle { font-size: 13.5px; color: var(--color-text-muted); line-height: 1.5; }

    /* ── Form ── */
    .form { display: flex; flex-direction: column; gap: 14px; }

    .form-section-label {
      font-size: 10.5px; font-weight: 700; letter-spacing: 0.9px;
      text-transform: uppercase; color: var(--color-text-subtle);
      padding-bottom: 2px; border-bottom: 1px solid var(--color-border);
      margin-top: 6px; margin-bottom: 2px;
    }

    .field { display: flex; flex-direction: column; gap: 6px; }
    .field-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    label { font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); letter-spacing: 0.1px; }

    /* ── Inputs ── */
    .input-wrap { position: relative; }

    input[type="email"],
    input[type="password"],
    input[type="text"],
    input[type="tel"] {
      width: 100%; height: var(--input-height);
      border: 1.5px solid var(--color-border); border-radius: var(--radius-md);
      padding: 0 14px; font-family: var(--font-family); font-size: 13.5px;
      color: var(--color-text-primary); background: var(--color-bg);
      outline: none; transition: border-color var(--transition), box-shadow var(--transition);
      -webkit-appearance: none;
    }
    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="text"]:focus,
    input[type="tel"]:focus {
      border-color: var(--color-accent);
      box-shadow: 0 0 0 3px rgba(10,10,10,0.07);
    }
    input::placeholder { color: var(--color-text-subtle); }
    .input-wrap input  { padding-right: 42px; }

    input.is-error       { border-color: #e53e3e !important; }
    input.is-error:focus { box-shadow: 0 0 0 3px rgba(229,62,62,0.1); }
    input.is-valid       { border-color: #22c55e !important; }

    .toggle-pw {
      position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--color-text-subtle);
      padding: 0; display: flex; align-items: center; transition: color var(--transition);
    }
    .toggle-pw:hover { color: var(--color-text-primary); }

    /* ── Field-level error text ── */
    .field-error {
      font-size: 11.5px; color: var(--color-danger-text);
      margin-top: -2px; display: none;
      animation: fadeIn 0.15s ease;
    }
    .field-error.show { display: block; }

    /* ── Password strength bar ── */
    .pw-strength-wrap { margin-top: -4px; }
    .pw-strength-track {
      height: 4px; background: var(--color-border);
      border-radius: 999px; overflow: hidden;
    }
    .pw-strength-fill {
      height: 100%; width: 0%;
      border-radius: 999px;
      transition: width 0.3s ease, background 0.3s ease;
    }
    .pw-strength-label { font-size: 11px; color: var(--color-text-subtle); margin-top: 4px; }

    /* ── Alert boxes (top-level) ── */
    .alert {
      display: none; align-items: flex-start; gap: 9px;
      border-radius: var(--radius-md); padding: 11px 13px;
      font-size: 12.5px; font-weight: 500; line-height: 1.5;
      animation: fadeIn 0.2s ease;
    }
    .alert.show       { display: flex; }
    .alert-error      { background: var(--color-danger-bg);   color: var(--color-danger-text);   border: 1px solid var(--color-danger-border); }
    .alert-success    { background: var(--color-success-bg);  color: var(--color-success-text);  border: 1px solid var(--color-success-border); }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

    /* ── Submit Button ── */
    .btn-primary {
      width: 100%; height: var(--btn-height);
      background: var(--color-accent); color: #fff; border: none;
      border-radius: var(--radius-md); font-family: var(--font-family);
      font-size: 13.5px; font-weight: 600; letter-spacing: 0.1px;
      cursor: pointer; transition: background var(--transition), transform var(--transition), opacity var(--transition);
      margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary:hover    { background: var(--color-accent-hover); }
    .btn-primary:active   { transform: scale(0.99); }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

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

    /* ── Sign-in link ── */
    .signin-link-row { text-align: center; font-size: 13px; color: var(--color-text-muted); }
    .signin-link-row a { color: var(--color-text-primary); font-weight: 600; text-decoration: none; transition: opacity var(--transition); }
    .signin-link-row a:hover { opacity: 0.65; }

    /* ── Notice ── */
    .notice { display: flex; align-items: flex-start; gap: 8px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 10px 13px; }
    .notice svg { color: var(--color-text-subtle); flex-shrink: 0; margin-top: 1px; }
    .notice-text { font-size: 12px; color: var(--color-text-muted); line-height: 1.55; }

    /* ── Responsive ── */
    @media (max-width: 680px) {
      .split-shell   { flex-direction: column; height: auto; min-height: 100vh; }
      .panel-left    { width: 100%; padding: 2rem; }
      .panel-right   { border-left: none; border-top: 1px solid #ebebeb; padding: 2rem; }
      .panel-headline{ font-size: 20px; }
      .page-wrapper  { padding: 0; }
      .field-2col    { grid-template-columns: 1fr; }
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
      <h2 class="panel-headline">Get started with your admin account.</h2>
      <p class="panel-desc">Register your company and take full control of jobs, users, assessments, and courses from day one.</p>
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
          <h1 class="card-title">Admin Sign up</h1>
          <p class="card-subtitle">Register your company to access the HireReady admin dashboard.</p>
        </div>

        <!-- Top-level alert (success / generic error) -->
        <div class="alert alert-error"   id="alertError">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span id="alertErrorText"></span>
        </div>
        <div class="alert alert-success" id="alertSuccess">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <span id="alertSuccessText"></span>
        </div>

        <!-- ── Form ── -->
        <div class="form" id="signupForm">

          <!-- Section 1: Company Info -->
          <div class="form-section-label">Company Info</div>

          <div class="field">
            <label for="companyName">Company Name</label>
            <input type="text" id="companyName" name="companyName" placeholder="Enter company name" autocomplete="organization" />
            <span class="field-error" id="err-companyName"></span>
          </div>

          <div class="field">
            <label for="companyAddress">Company Address</label>
            <input type="text" id="companyAddress" name="companyAddress" placeholder="Enter company address" autocomplete="street-address" />
            <span class="field-error" id="err-companyAddress"></span>
          </div>

          <!-- Section 2: Representative Info -->
          <div class="form-section-label">Representative Info</div>

          <div class="field-2col">
            <div class="field">
              <label for="repName">Representative Name</label>
              <input type="text" id="repName" name="repName" placeholder="Full name" autocomplete="name" />
              <span class="field-error" id="err-repName"></span>
            </div>
            <div class="field">
              <label for="repRole">Representative Role</label>
              <input type="text" id="repRole" name="repRole" placeholder="Enter your role" />
              <span class="field-error" id="err-repRole"></span>
            </div>
          </div>

          <div class="field">
            <label for="email">Company Email</label>
            <input type="email" id="email" name="email" placeholder="admin@company.com" autocomplete="email" />
            <span class="field-error" id="err-email"></span>
          </div>

          <div class="field">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="+8801XXXXXXXXX" autocomplete="tel" />
            <span class="field-error" id="err-phone"></span>
          </div>

          <!-- Section 3: Security -->
          <div class="form-section-label">Security</div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
              <input type="password" id="password" name="password" placeholder="Min. 8 characters" autocomplete="new-password" />
              <button class="toggle-pw" type="button" id="togglePw" aria-label="Toggle password visibility">
                <svg id="iconEye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg id="iconEyeOff" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <!-- Password strength bar -->
            <div class="pw-strength-wrap" id="pwStrengthWrap" style="display:none;">
              <div class="pw-strength-track"><div class="pw-strength-fill" id="pwStrengthFill"></div></div>
              <div class="pw-strength-label" id="pwStrengthLabel"></div>
            </div>
            <span class="field-error" id="err-password"></span>
          </div>

          <div class="field">
            <label for="confirmPassword">Confirm Password</label>
            <div class="input-wrap">
              <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter password" autocomplete="new-password" />
              <button class="toggle-pw" type="button" id="toggleConfirmPw" aria-label="Toggle confirm password visibility">
                <svg id="iconEye2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg id="iconEyeOff2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <span class="field-error" id="err-confirmPassword"></span>
          </div>

          <!-- Submit -->
          <button class="btn-primary" type="button" id="signUpBtn">
            Create Admin Account →
          </button>

        </div><!-- /.form -->

        <!-- Sign-in link -->
        <p class="signin-link-row">
          Already have an account? <a href="admin_login.php">Sign in</a>
        </p>

        <!-- Review notice -->
        <div class="notice">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span class="notice-text">This account will be reviewed before admin access is granted.</span>
        </div>

      </div><!-- /.card -->
    </div><!-- /.page-wrapper -->
  </div><!-- /.panel-right -->

</div><!-- /.split-shell -->

<script>

  // ════════════════════════════════════════════════════════
  //  PASSWORD TOGGLE — Password field
  // ════════════════════════════════════════════════════════
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

  // ════════════════════════════════════════════════════════
  //  PASSWORD TOGGLE — Confirm field
  // ════════════════════════════════════════════════════════
  const confirmPwInput   = document.getElementById('confirmPassword');
  const toggleConfirmBtn = document.getElementById('toggleConfirmPw');
  const iconEye2         = document.getElementById('iconEye2');
  const iconEyeOff2      = document.getElementById('iconEyeOff2');

  toggleConfirmBtn.addEventListener('click', () => {
    const hidden = confirmPwInput.type === 'password';
    confirmPwInput.type       = hidden ? 'text' : 'password';
    iconEye2.style.display    = hidden ? 'none' : '';
    iconEyeOff2.style.display = hidden ? ''     : 'none';
  });

  // ════════════════════════════════════════════════════════
  //  PASSWORD STRENGTH METER
  // ════════════════════════════════════════════════════════
  pwInput.addEventListener('input', () => {
    const val   = pwInput.value;
    const wrap  = document.getElementById('pwStrengthWrap');
    const fill  = document.getElementById('pwStrengthFill');
    const label = document.getElementById('pwStrengthLabel');

    if (!val) { wrap.style.display = 'none'; return; }
    wrap.style.display = '';

    let score = 0;
    if (val.length >= 8)              score++;
    if (/[A-Z]/.test(val))            score++;
    if (/[a-z]/.test(val))            score++;
    if (/[0-9]/.test(val))            score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    const levels = [
      { pct: '20%', color: '#ef4444', text: 'Very weak'  },
      { pct: '40%', color: '#f97316', text: 'Weak'       },
      { pct: '60%', color: '#eab308', text: 'Fair'       },
      { pct: '80%', color: '#84cc16', text: 'Strong'     },
      { pct: '100%',color: '#22c55e', text: 'Very strong'},
    ];
    const lvl = levels[score - 1] || levels[0];
    fill.style.width      = lvl.pct;
    fill.style.background = lvl.color;
    label.textContent     = lvl.text;
    label.style.color     = lvl.color;
  });

  // ════════════════════════════════════════════════════════
  //  LIVE CONFIRM PASSWORD MATCH CHECK
  // ════════════════════════════════════════════════════════
  confirmPwInput.addEventListener('input', () => {
    if (!confirmPwInput.value) { clearFieldError('confirmPassword'); return; }
    if (confirmPwInput.value !== pwInput.value) {
      showFieldError('confirmPassword', 'Passwords do not match.');
    } else {
      clearFieldError('confirmPassword');
      confirmPwInput.classList.add('is-valid');
    }
  });

  // ════════════════════════════════════════════════════════
  //  FIELD ERROR HELPERS
  // ════════════════════════════════════════════════════════
  function showFieldError(fieldId, msg) {
    const input = document.getElementById(fieldId);
    const err   = document.getElementById('err-' + fieldId);
    if (input) { input.classList.add('is-error'); input.classList.remove('is-valid'); }
    if (err)   { err.textContent = msg; err.classList.add('show'); }
  }

  function clearFieldError(fieldId) {
    const input = document.getElementById(fieldId);
    const err   = document.getElementById('err-' + fieldId);
    if (input) { input.classList.remove('is-error'); }
    if (err)   { err.classList.remove('show'); err.textContent = ''; }
  }

  function clearAllErrors() {
    ['companyName','companyAddress','repName','repRole','email','phone','password','confirmPassword']
      .forEach(id => clearFieldError(id));
    document.getElementById('alertError').classList.remove('show');
    document.getElementById('alertSuccess').classList.remove('show');
  }

  // ════════════════════════════════════════════════════════
  //  TOP-LEVEL ALERT HELPERS
  // ════════════════════════════════════════════════════════
  function showTopError(msg) {
    const el = document.getElementById('alertError');
    document.getElementById('alertErrorText').textContent = msg;
    el.classList.add('show');
    document.getElementById('alertSuccess').classList.remove('show');
    // scroll to top of form
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function showTopSuccess(msg) {
    const el = document.getElementById('alertSuccess');
    document.getElementById('alertSuccessText').textContent = msg;
    el.classList.add('show');
    document.getElementById('alertError').classList.remove('show');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  // ════════════════════════════════════════════════════════
  //  CLIENT-SIDE VALIDATION
  // ════════════════════════════════════════════════════════
  function validateForm() {
    let valid = true;
    clearAllErrors();

    const fields = [
      { id: 'companyName',    label: 'Company name'         },
      { id: 'companyAddress', label: 'Company address'      },
      { id: 'repName',        label: 'Representative name'  },
      { id: 'repRole',        label: 'Representative role'  },
      { id: 'phone',          label: 'Phone number'         },
    ];

    fields.forEach(f => {
      const val = document.getElementById(f.id).value.trim();
      if (!val) {
        showFieldError(f.id, f.label + ' is required.');
        valid = false;
      }
    });

    // Email
    const email = document.getElementById('email').value.trim();
    if (!email) {
      showFieldError('email', 'Email address is required.');
      valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showFieldError('email', 'Please enter a valid email address.');
      valid = false;
    }

    // Password
    const pw = pwInput.value;
    if (!pw) {
      showFieldError('password', 'Password is required.');
      valid = false;
    } else if (pw.length < 8) {
      showFieldError('password', 'Password must be at least 8 characters.');
      valid = false;
    } else if (!/[A-Z]/.test(pw) || !/[a-z]/.test(pw) || !/[0-9]/.test(pw)) {
      showFieldError('password', 'Must include uppercase, lowercase, and a number.');
      valid = false;
    }

    // Confirm password
    const cpw = confirmPwInput.value;
    if (!cpw) {
      showFieldError('confirmPassword', 'Please confirm your password.');
      valid = false;
    } else if (cpw !== pw) {
      showFieldError('confirmPassword', 'Passwords do not match.');
      valid = false;
    }

    return valid;
  }

  // ════════════════════════════════════════════════════════
  //  SIGNUP AJAX SUBMIT
  // ════════════════════════════════════════════════════════
  const signUpBtn = document.getElementById('signUpBtn');

  signUpBtn.addEventListener('click', async () => {
    if (!validateForm()) return;

    // Loading state
    signUpBtn.disabled  = true;
    signUpBtn.innerHTML = '<span class="spinner"></span> Creating account…';

    const formData = new FormData();
    formData.append('companyName',    document.getElementById('companyName').value.trim());
    formData.append('companyAddress', document.getElementById('companyAddress').value.trim());
    formData.append('repName',        document.getElementById('repName').value.trim());
    formData.append('repRole',        document.getElementById('repRole').value.trim());
    formData.append('email',          document.getElementById('email').value.trim());
    formData.append('phone',          document.getElementById('phone').value.trim());
    formData.append('password',       pwInput.value);
    formData.append('confirmPassword',confirmPwInput.value);

    try {
      const response = await fetch('admin_signup.php', { method: 'POST', body: formData });
      const data     = await response.json();

      if (data.success) {
        showTopSuccess(data.message);
        signUpBtn.innerHTML = '✓ Account Created';

        // Disable all inputs after success
        document.querySelectorAll('#signupForm input').forEach(i => i.disabled = true);

        // Redirect to login after 2.5 seconds
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 2500);

      } else {
        // Field-level error from server
        if (data.field) {
          showFieldError(data.field, data.message);
          const errInput = document.getElementById(data.field);
          if (errInput) errInput.focus();
        } else {
          showTopError(data.message);
        }
        signUpBtn.disabled  = false;
        signUpBtn.innerHTML = 'Create Admin Account →';
      }

    } catch (err) {
      showTopError('Something went wrong. Please try again.');
      signUpBtn.disabled  = false;
      signUpBtn.innerHTML = 'Create Admin Account →';
    }
  });

  // ════════════════════════════════════════════════════════
  //  CLEAR FIELD ERROR ON INPUT
  // ════════════════════════════════════════════════════════
  ['companyName','companyAddress','repName','repRole','email','phone','password'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => clearFieldError(id));
  });

</script>

</body>
</html>
