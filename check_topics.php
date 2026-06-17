<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hireready_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$res = $conn->query("SELECT COUNT(*) as c FROM topics");
$row = $res->fetch_assoc();
echo "Topics count: " . $row['c'];
?>
