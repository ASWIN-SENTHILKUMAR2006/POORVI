<?php

ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

function respondJson($statusCode, $payload)
{
  if (ob_get_length()) {
    ob_clean();
  }

  http_response_code($statusCode);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode($payload);
  exit;
}

set_exception_handler(function ($e) {
  respondJson(500, [
    'ok' => false,
    'message' => 'Sorry, we could not send your message right now. Please try again.'
  ]);
});

set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respondJson(405, [
        'ok' => false,
        'message' => 'Method not allowed.'
    ]);
}

$name = trim(isset($_POST['name']) ? $_POST['name'] : '');
$email = trim(isset($_POST['email']) ? $_POST['email'] : '');
$sessionType = trim(isset($_POST['session_type']) ? $_POST['session_type'] : '');
$preferredDate = trim(isset($_POST['preferred_date']) ? $_POST['preferred_date'] : '');
$message = trim(isset($_POST['message']) ? $_POST['message'] : '');

if ($name === '' || $email === '') {
  respondJson(422, [
        'ok' => false,
        'message' => 'Please provide your name and email address.'
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respondJson(422, [
        'ok' => false,
        'message' => 'Please enter a valid email address.'
    ]);
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'triospark.in@gmail.com';
    $mail->Password = 'rvjm colt jpwt apxt';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('triospark.in@gmail.com', 'Poorvi Photography Website');
    $mail->addAddress('poorviphotography@gmail.com');
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true); 
    $mail->Subject = 'New Contact Form Submission - Poorvi Photography';

    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeSessionType = htmlspecialchars($sessionType !== '' ? $sessionType : 'Not specified', ENT_QUOTES, 'UTF-8');
    $safePreferredDate = htmlspecialchars($preferredDate !== '' ? $preferredDate : 'Not specified', ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message !== '' ? $message : 'No additional message provided.', ENT_QUOTES, 'UTF-8'));
    $year = date('Y');

    $mail->Body = '
    <div style="margin:0; padding:24px; background:#0e0e0c; font-family:Montserrat, Arial, sans-serif; color:#f5f0ea;">
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:680px; margin:0 auto; background:#1a1a18; border:1px solid rgba(201,169,110,0.28); border-radius:14px; overflow:hidden;">
        <tr>
          <td style="padding:24px 28px; background:linear-gradient(135deg, #c9a96e 0%, #b0894f 100%); color:#0e0e0c;">
            <div style="font-size:11px; letter-spacing:0.24em; text-transform:uppercase; font-weight:700; margin-bottom:6px;">New Enquiry</div>
            <h2 style="margin:0; font-size:26px; line-height:1.2; font-family:Cormorant Garamond, Georgia, serif;">Poorvi Photography Contact Form</h2>
          </td>
        </tr>
        <tr>
          <td style="padding:26px 28px;">
            <p style="margin:0 0 20px; color:#d8c8a6; font-size:13px; line-height:1.7;">
              You have received a new form submission from the website.
            </p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;">
              <tr>
                <td style="padding:10px 0; border-bottom:1px solid rgba(201,169,110,0.2); font-size:12px; color:#c9a96e; width:160px; text-transform:uppercase; letter-spacing:0.12em;">Full Name</td>
                <td style="padding:10px 0; border-bottom:1px solid rgba(201,169,110,0.2); font-size:14px; color:#f5f0ea;">' . $safeName . '</td>
              </tr>
              <tr>
                <td style="padding:10px 0; border-bottom:1px solid rgba(201,169,110,0.2); font-size:12px; color:#c9a96e; text-transform:uppercase; letter-spacing:0.12em;">Email</td>
                <td style="padding:10px 0; border-bottom:1px solid rgba(201,169,110,0.2); font-size:14px; color:#f5f0ea;">' . $safeEmail . '</td>
              </tr>
              <tr>
                <td style="padding:10px 0; border-bottom:1px solid rgba(201,169,110,0.2); font-size:12px; color:#c9a96e; text-transform:uppercase; letter-spacing:0.12em;">Session Type</td>
                <td style="padding:10px 0; border-bottom:1px solid rgba(201,169,110,0.2); font-size:14px; color:#f5f0ea;">' . $safeSessionType . '</td>
              </tr>
              <tr>
                <td style="padding:10px 0; border-bottom:1px solid rgba(201,169,110,0.2); font-size:12px; color:#c9a96e; text-transform:uppercase; letter-spacing:0.12em;">Preferred Date</td>
                <td style="padding:10px 0; border-bottom:1px solid rgba(201,169,110,0.2); font-size:14px; color:#f5f0ea;">' . $safePreferredDate . '</td>
              </tr>
            </table>
            <div style="margin-top:20px; padding:14px; border:1px solid rgba(201,169,110,0.2); background:#141412; border-radius:10px;">
              <div style="font-size:11px; color:#c9a96e; letter-spacing:0.12em; text-transform:uppercase; margin-bottom:8px;">Message</div>
              <div style="font-size:14px; color:#f5f0ea; line-height:1.8;">' . $safeMessage . '</div>
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:14px 20px; background:#121210; border-top:1px solid rgba(201,169,110,0.25); text-align:center; font-size:12px; color:#a99876; line-height:1.7;">
            Designed by <a href="https://triospark.in" style="color:#e8d5b0; text-decoration:none;">Trio\'s Park</a> Solutions<br />
            &copy; ' . $year . ' Poorvi Photography
          </td>
        </tr>
      </table>
    </div>';

    $mail->AltBody = "New Contact Form Submission\n"
        . "Name: {$name}\n"
        . "Email: {$email}\n"
        . "Session Type: " . ($sessionType !== '' ? $sessionType : 'Not specified') . "\n"
        . "Preferred Date: " . ($preferredDate !== '' ? $preferredDate : 'Not specified') . "\n"
        . "Message: " . ($message !== '' ? $message : 'No additional message provided.') . "\n\n"
        . "Designed by Trio's Park Solutions\n"
        . "(c) {$year} Poorvi Photography";

    $mail->send();

    respondJson(200, [
        'ok' => true,
        'message' => 'Your message has been sent successfully.'
    ]);
} catch (Exception $e) {
    respondJson(500, [
        'ok' => false,
        'message' => 'Sorry, we could not send your message right now. Please try again.'
    ]);
}