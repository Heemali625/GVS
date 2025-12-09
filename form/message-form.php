<?php
// message-form.php
header('Content-Type: application/json; charset=utf-8');

// --- config: set your DB credentials here ---
$db_host = 'localhost';
$db_user = 'your_db_user';
$db_pass = 'your_db_password';
$db_name = 'your_database_name';
// --------------------------------------------

// Basic response helper
function resp($ok, $msg) {
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp(false, 'Invalid request method.');
}

// Simple rate limit using session (basic)
session_start();
if (!isset($_SESSION['last_contact'])) $_SESSION['last_contact'] = 0;
if (time() - $_SESSION['last_contact'] < 5) { // 5s throttle
    resp(false, 'Please wait a moment before sending again.');
}

// get raw POST values
$name = isset($_POST['user-name']) ? trim($_POST['user-name']) : '';
$email = isset($_POST['user-email']) ? trim($_POST['user-email']) : '';
$phone = isset($_POST['user-phone']) ? trim($_POST['user-phone']) : '';
$message = isset($_POST['user-message']) ? trim($_POST['user-message']) : '';
$honeypot = isset($_POST['hp_field']) ? trim($_POST['hp_field']) : ''; // honeypot

// Basic validation
if ($honeypot !== '') {
    // bot detected
    resp(false, 'Spam detected.');
}
if ($name === '' || $email === '' || $message === '') {
    resp(false, 'Please complete all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    resp(false, 'Invalid email address.');
}
if (mb_strlen($message) > 5000) {
    resp(false, 'Message too long.');
}

// store client ip
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// connect to DB (mysqli)
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    error_log('DB connect error: ' . $mysqli->connect_error);
    resp(false, 'Database connection failed.');
}
$mysqli->set_charset('utf8mb4');

// insert using prepared statement
$stmt = $mysqli->prepare("INSERT INTO contact_messages (name, email, phone, message, ip) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log('Prepare failed: ' . $mysqli->error);
    resp(false, 'Database error (prepare).');
}
$stmt->bind_param('sssss', $name, $email, $phone, $message, $ip);
$ok = $stmt->execute();
if (!$ok) {
    error_log('Execute failed: ' . $stmt->error);
    resp(false, 'Database error (execute).');
}
$insertId = $stmt->insert_id;
$stmt->close();
$mysqli->close();

// optional: send admin email (uncomment and configure if desired)
// mail('you@yourdomain.com', 'New contact message', "Name: $name\nEmail: $email\nPhone: $phone\n\n$message");

// update session throttle
$_SESSION['last_contact'] = time();

resp(true, 'Message sent. We will contact you shortly.');
