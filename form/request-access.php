<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/PHPMailer-master/src/PHPMailer.php";
require __DIR__ . "/PHPMailer-master/src/SMTP.php";
require __DIR__ . "/PHPMailer-master/src/Exception.php";

header("Content-Type: application/json");


// ================= DB CONFIG =================
$db_host = "localhost";
$db_name = "gvsindia_new_gvs_contact";
$db_user = "gvsindia_new_gvs_user";
$db_pass = "DL;yfA@NJoWToVZy";

// ================= INPUT =====================
$name    = trim($_POST["name"] ?? "");
$email   = trim($_POST["email"] ?? "");
$phone   = trim($_POST["phone"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($name === "" || $email === "" || $message === "") {
    echo json_encode(["success"=>false,"message"=>"All fields are required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success"=>false,"message"=>"Invalid email address"]);
    exit;
}

$ip = $_SERVER["REMOTE_ADDR"] ?? "";

// ================= DB SAVE ===================
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare(
    "INSERT INTO new_gvs_access_requests (name,email,phone,message,ip)
     VALUES (?,?,?,?,?)"
);

if (!$stmt) {
    echo json_encode(["success"=>false,"message"=>"Database error"]);
    exit;
}

$stmt->bind_param("sssss", $name, $email, $phone, $message, $ip);
$stmt->execute();
$stmt->close();
$conn->close();

// ================= EMAIL =====================
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = "smtp.gmail.com";
    $mail->SMTPAuth   = true;

    // ✅ CORRECT EMAIL
    $mail->Username   = "rautheemali04@gmail.com";

    // 🔐 PASTE YOUR 16-CHAR APP PASSWORD HERE
    $mail->Password   = "drqp wrjm tnif wwou";

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom("rautheemali04@gmail.com", "GVS Website");
    $mail->addAddress("rautheemali04@gmail.com");
    $mail->addReplyTo($email, $name);

    $mail->Subject = "New Product Access Request";
    $mail->Body = "
New access request received:

Name: $name
Email: $email
Phone: $phone

Purpose:
$message

IP: $ip
";

    $mail->send();

    echo json_encode([
        "success"=>true,
        "message"=>"Request submitted successfully. Our team will contact you."
    ]);

} catch (Exception $e) {
    // Email failed but DB saved
    error_log("Mailer error: " . $mail->ErrorInfo);

    echo json_encode([
        "success"=>true,
        "message"=>"Request saved successfully."
    ]);
}
