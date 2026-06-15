<?php
// ============================================================
//  HireReady Admin — Logout
// ============================================================
session_start();
session_unset();
session_destroy();
header('Location: admin_login.php');
exit;
