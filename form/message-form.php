<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/PHPMailer-master/src/PHPMailer.php";
require __DIR__ . "/PHPMailer-master/src/SMTP.php";
require __DIR__ . "/PHPMailer-master/src/Exception.php";

header('Content-Type: application/json; charset=utf-8');

/* =======================
   DATABASE CONFIG
======================= */
$db_host = 'localhost';
$db_name = 'gvsindia_new_gvs_contact';
$db_user = 'gvsindia_new_gvs_user';
$db_pass = 'DL;yfA@NJoWToVZy';

/* =======================
   RESPONSE HELPER
======================= */
function respond($ok, $msg) {
    echo json_encode([
        'success' => $ok,
        'message' => $msg
    ]);
    exit;
}

/* =======================
   ONLY POST
======================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request.');
}

/* =======================
   RATE LIMIT
======================= */
session_start();
$_SESSION['last_contact'] ??= 0;
if (time() - $_SESSION['last_contact'] < 5) {
    respond(false, 'Please wait before sending again.');
}

/* =======================
   INPUT
======================= */
$name    = trim($_POST['user-name'] ?? '');
$email   = trim($_POST['user-email'] ?? '');
$phone   = trim($_POST['user-phone'] ?? '');
$message = trim($_POST['user-message'] ?? '');
$honeypot = trim($_POST['hp_field'] ?? '');

if ($honeypot !== '') respond(false, 'Spam detected.');
if (!$name || !$email || !$message) respond(false, 'All required fields missing.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Invalid email.');

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

/* =======================
   DB SAVE
======================= */
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    respond(false, 'Database connection failed.');
}
$conn->set_charset('utf8mb4');

$stmt = $conn->prepare(
    "INSERT INTO new_gvs_contact_message 
     (name, email, phone, message, ip) 
     VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) respond(false, 'Database error (prepare).');

$stmt->bind_param("sssss", $name, $email, $phone, $message, $ip);

if (!$stmt->execute()) {
    respond(false, 'Database error (execute).');
}

$stmt->close();
$conn->close();
$_SESSION['last_contact'] = time();

/* =======================
   EMAIL (PHPMailer)
======================= */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'rautheemali04@gmail.com';
    $mail->Password   = 'drqp wrjm tnif wwou'; // App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('rautheemali04@gmail.com', 'GVS Website');
    $mail->addAddress('rautheemali04@gmail.com');
    $mail->addReplyTo($email, $name);

    $mail->Subject = 'New Contact Form Submission';
    $mail->Body = "
New contact form submission:

Name: $name
Email: $email
Phone: $phone

Message:
$message

IP: $ip
";

    $mail->send();

} catch (Exception $e) {
    // Log email error but DO NOT fail user
    error_log("Contact mail failed: " . $mail->ErrorInfo);
}

/* =======================
   FINAL RESPONSE
======================= */
respond(true, 'Message sent successfully. We will contact you shortly.');
  