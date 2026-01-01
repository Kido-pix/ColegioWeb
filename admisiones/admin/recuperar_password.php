<?php
session_start();
require_once '../config/database.php';
require_once '../config/emailer.php';

$mensaje = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id, usuario, nombre FROM usuarios_admin WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $db->prepare("UPDATE usuarios_admin SET reset_token = ?, reset_token_expira = ? WHERE id = ?");
            $stmt->execute([$token, $expira, $usuario['id']]);
            
            $enlaceRecuperacion = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/restablecer_password.php?token={$token}";
            
            $emailer = new Emailer();
            $asunto = "Recuperación de Contraseña - Trinity School";
            $cuerpo = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;'>
                    <div style='background: white; padding: 30px; border-radius: 10px;'>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <h2 style='color: #1B4B5A;'>🔐 Recuperación de Contraseña</h2>
                        </div>
                        <p>Hola <strong>{$usuario['nombre']}</strong>,</p>
                        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en el Panel Administrativo de Trinity School.</p>
                        <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$enlaceRecuperacion}' style='display: inline-block; padding: 15px 30px; background: #1B4B5A; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Restablecer Contraseña</a>
                        </div>
                        <p style='color: #666; font-size: 0.9rem;'>Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                        <a href='{$enlaceRecuperacion}' style='color: #3AAFA9; word-break: break-all;'>{$enlaceRecuperacion}</a></p>
                        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                            <p style='color: #999; font-size: 0.85rem; margin: 0;'>⚠️ <strong>Importante:</strong><br>• Este enlace expira en 1 hora<br>• Si no solicitaste este cambio, ignora este correo<br>• Tu contraseña actual seguirá funcionando si no la cambias</p>
                        </div>
                        <div style='margin-top: 20px; text-align: center; color: #999; font-size: 0.8rem;'>
                            <p>Trinity School - Chincha Alta<br>Este es un correo automático, por favor no responder.</p>
                        </div>
                    </div>
                </div>
            ";
            
            $resultado = $emailer->enviarEmailPersonalizado($email, $asunto, $cuerpo);
            
            if ($resultado['success']) {
                $mensaje = "✅ Se ha enviado un enlace de recuperación a tu correo electrónico.";
                $tipo = 'success';
            } else {
                $mensaje = "⚠️ Email encontrado pero hubo un error al enviar el correo: " . $resultado['message'];
                $tipo = 'warning';
            }
        } else {
            $mensaje = "✅ Si el correo está registrado, recibirás un enlace de recuperación.";
            $tipo = 'success';
        }
    } catch (PDOException $e) {
        $mensaje = "❌ Error en el sistema. Por favor intenta más tarde.";
        $tipo = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Trinity School</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0A1929 0%, #1B4B5A 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .login-header {
            background: linear-gradient(135deg, #1B4B5A, #0A1929);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .login-header img { width: 80px; height: 80px; border-radius: 15px; margin-bottom: 15px; background: white; padding: 10px; }
        .login-header h1 { font-size: 1.6rem; margin-bottom: 5px; }
        .login-header p { font-size: 0.9rem; opacity: 0.9; }
        .login-body { padding: 40px 30px; }
        .titulo-recuperar { text-align: center; margin-bottom: 25px; }
        .titulo-recuperar h2 { color: #1B4B5A; font-size: 1.4rem; margin-bottom: 10px; }
        .titulo-recuperar p { color: #666; font-size: 0.9rem; line-height: 1.6; }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: rgba(46, 212, 122, 0.1); border: 1px solid rgba(46, 212, 122, 0.3); color: #2ED47A; }
        .alert-error { background: rgba(247, 70, 74, 0.1); border: 1px solid rgba(247, 70, 74, 0.3); color: #F7464A; }
        .alert-warning { background: rgba(255, 182, 72, 0.1); border: 1px solid rgba(255, 182, 72, 0.3); color: #FFB648; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 0.9rem; }
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        .form-group input:focus { outline: none; border-color: #3AAFA9; box-shadow: 0 0 0 4px rgba(58, 175, 169, 0.1); }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1B4B5A, #0A1929);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(27, 75, 90, 0.3); }
        .btn-login:active { transform: translateY(0); }
        .login-footer { text-align: center; margin-top: 25px; padding-top: 25px; border-top: 1px solid #e0e0e0; }
        .login-footer a { color: #3AAFA9; text-decoration: none; font-weight: 500; font-size: 0.9rem; transition: all 0.3s ease; }
        .login-footer a:hover { text-decoration: underline; }
        .info-box {
            background: rgba(58, 175, 169, 0.1);
            border-left: 4px solid #3AAFA9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #666;
        }
        .info-box strong { color: #1B4B5A; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../img/logo.png" alt="Trinity School">
            <h1>Trinity School</h1>
            <p>Panel Administrativo</p>
        </div>
        <div class="login-body">
            <div class="titulo-recuperar">
                <h2>Recuperar Contraseña</h2>
                <p>Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
            </div>
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo; ?>">
                    <i class="fas fa-<?php echo $tipo === 'success' ? 'check-circle' : ($tipo === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="recuperar_password.php">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" required autofocus>
                </div>
                <button type="submit" class="btn-login"><i class="fas fa-paper-plane"></i> Enviar Enlace de Recuperación</button>
            </form>
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Nota:</strong><br>
                • El enlace expirará en 1 hora<br>
                • Revisa tu bandeja de spam si no recibes el correo<br>
                • El enlace solo funciona una vez
            </div>
            <div class="login-footer">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
</body>
</html>