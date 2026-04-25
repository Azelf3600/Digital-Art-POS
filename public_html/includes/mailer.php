<?php
// ============================================
// includes/mailer.php
// Starflow — Email Sending via Brevo SMTP
// ============================================

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

// Load PHPMailer
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============================================
// SMTP Credentials — Brevo
// ============================================
define('MAIL_HOST',     'smtp-relay.brevo.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'a920e2001@smtp-brevo.com');
define('MAIL_PASSWORD', 'xsmtpsib-744ea9d1f7f86215ed8feb75c336b55181379b49dd22088e158fc71d93c7acff-Tt0SkL8vu5qFquqw');
define('MAIL_FROM',     'starloomdevs@gmail.com');
define('MAIL_FROM_NAME', 'Starflow');

// ============================================
// Base mailer setup — returns a configured PHPMailer instance
// ============================================
function create_mailer(): PHPMailer {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host        = MAIL_HOST;
    $mail->SMTPAuth    = true;
    $mail->Username    = MAIL_USERNAME;
    $mail->Password    = MAIL_PASSWORD;
    $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port        = MAIL_PORT;
    $mail->CharSet     = 'UTF-8';

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->isHTML(true);

    return $mail;
}

// ============================================
// Send Password Reset Email
// ============================================
function send_password_reset_email(
    string $to_email,
    string $to_name,
    string $reset_url
): bool {
    try {
        $mail = create_mailer();
        $mail->addAddress($to_email, $to_name);
        $mail->Subject = 'Reset Your Starflow Password';
        $mail->Body    = '
            <div style="font-family:sans-serif;max-width:560px;margin:0 auto;background:#0b0b0f;color:#e8e6e0;padding:2rem;border-radius:12px;">
                <h2 style="color:#c9a96e;font-size:1.5rem;margin-bottom:0.5rem;">Starflow</h2>
                <h3 style="font-weight:300;margin-bottom:1.5rem;">Password Reset Request</h3>
                <p>Hi <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
                <p>We received a request to reset your password. Click the button below to set a new one.</p>
                <p style="text-align:center;margin:2rem 0;">
                    <a href="' . $reset_url . '"
                       style="background:#c9a96e;color:#0b0b0f;padding:0.875rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">
                        Reset Password
                    </a>
                </p>
                <p style="color:#9a9890;font-size:0.85rem;">This link expires in <strong>1 hour</strong>. If you did not request this, you can safely ignore this email.</p>
                <hr style="border:none;border-top:1px solid #222;margin:1.5rem 0;">
                <p style="color:#5a5856;font-size:0.75rem;">© ' . date('Y') . ' Starflow. All rights reserved.</p>
            </div>
        ';
        $mail->AltBody = 'Reset your Starflow password by visiting: ' . $reset_url . ' — This link expires in 1 hour.';

        $mail->send();
        return true;

    } catch (Exception $e) {
        if (DEV_MODE) {
            error_log('Mailer error: ' . $e->getMessage());
        }
        return false;
    }
}

// ============================================
// Send Welcome Email after Registration
// ============================================
function send_welcome_email(
    string $to_email,
    string $to_name
): bool {
    try {
        $mail = create_mailer();
        $mail->addAddress($to_email, $to_name);
        $mail->Subject = 'Welcome to Starflow!';
        $mail->Body    = '
            <div style="font-family:sans-serif;max-width:560px;margin:0 auto;background:#0b0b0f;color:#e8e6e0;padding:2rem;border-radius:12px;">
                <h2 style="color:#c9a96e;font-size:1.5rem;margin-bottom:0.5rem;">Starflow</h2>
                <h3 style="font-weight:300;margin-bottom:1.5rem;">Welcome aboard! ✦</h3>
                <p>Hi <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
                <p>Your Starflow account has been created. You can now browse our gallery, save favorites, and commission original digital artwork.</p>
                <p style="text-align:center;margin:2rem 0;">
                    <a href="' . SITE_URL . '/gallery.php"
                       style="background:#c9a96e;color:#0b0b0f;padding:0.875rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">
                        Browse Gallery
                    </a>
                </p>
                <hr style="border:none;border-top:1px solid #222;margin:1.5rem 0;">
                <p style="color:#5a5856;font-size:0.75rem;">© ' . date('Y') . ' Starflow. All rights reserved.</p>
            </div>
        ';
        $mail->AltBody = 'Welcome to Starflow, ' . $to_name . '! Visit ' . SITE_URL . '/gallery.php to get started.';

        $mail->send();
        return true;

    } catch (Exception $e) {
        if (DEV_MODE) {
            error_log('Mailer error: ' . $e->getMessage());
        }
        return false;
    }
}

// ============================================
// Send Order Confirmation Email
// ============================================
function send_order_confirmation_email(
    string $to_email,
    string $to_name,
    string $order_number,
    float  $total
): bool {
    try {
        $mail = create_mailer();
        $mail->addAddress($to_email, $to_name);
        $mail->Subject = 'Order Confirmed — ' . $order_number;
        $mail->Body    = '
            <div style="font-family:sans-serif;max-width:560px;margin:0 auto;background:#0b0b0f;color:#e8e6e0;padding:2rem;border-radius:12px;">
                <h2 style="color:#c9a96e;font-size:1.5rem;margin-bottom:0.5rem;">Starflow</h2>
                <h3 style="font-weight:300;margin-bottom:1.5rem;">Order Confirmed ✦</h3>
                <p>Hi <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
                <p>We received your order <strong>' . $order_number . '</strong> for <strong>₱' . number_format($total, 2) . '</strong>. We will start processing it shortly.</p>
                <p style="text-align:center;margin:2rem 0;">
                    <a href="' . SITE_URL . '/order-tracking.php"
                       style="background:#c9a96e;color:#0b0b0f;padding:0.875rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">
                        Track Your Order
                    </a>
                </p>
                <hr style="border:none;border-top:1px solid #222;margin:1.5rem 0;">
                <p style="color:#5a5856;font-size:0.75rem;">© ' . date('Y') . ' Starflow. All rights reserved.</p>
            </div>
        ';
        $mail->AltBody = 'Your order ' . $order_number . ' has been confirmed. Total: ₱' . number_format($total, 2);

        $mail->send();
        return true;

    } catch (Exception $e) {
        if (DEV_MODE) {
            error_log('Mailer error: ' . $e->getMessage());
        }
        return false;
    }
}