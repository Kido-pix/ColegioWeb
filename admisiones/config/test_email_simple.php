<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Email - Trinity School</h1><hr>";

// Cargar configuración
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    
    // Configurar SMTP
    $mail->isSMTP();
    $mail->Host = EmailConfig::SMTP_HOST;
    $mail->SMTPAuth = EmailConfig::SMTP_AUTH;
    $mail->Username = EmailConfig::SMTP_USERNAME;
    $mail->Password = EmailConfig::SMTP_PASSWORD;
    $mail->SMTPSecure = EmailConfig::SMTP_SECURE;
    $mail->Port = EmailConfig::SMTP_PORT;
    $mail->CharSet = EmailConfig::CHARSET;
    $mail->SMTPDebug = 2; // Ver mensajes de debug
    
    // Configurar email
    $mail->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
    $mail->addAddress(EmailConfig::ADMIN_EMAIL);
    $mail->Subject = 'Test - Trinity School';
    $mail->Body = '<h1>¡Funciona!</h1><p>El email se configuró correctamente.</p>';
    $mail->isHTML(true);
    
    echo "<pre>";
    $mail->send();
    echo "</pre>";
    
    echo "<h2 style='color: green;'>✅ EMAIL ENVIADO CORRECTAMENTE</h2>";
    echo "<p>Revisa tu bandeja: " . EmailConfig::ADMIN_EMAIL . "</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR</h2>";
    echo "<p><strong>Mensaje:</strong> {$mail->ErrorInfo}</p>";
}
?>