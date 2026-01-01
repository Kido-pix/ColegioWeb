<?php
session_start();
require_once '../config/database.php';

$tokenValido = false;
$mensaje = '';
$tipo = '';
$usuario = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT id, usuario, nombre, email 
            FROM usuarios_admin 
            WHERE reset_token = ? 
            AND reset_token_expira > NOW()
            AND activo = 1
        ");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            $tokenValido = true;
        } else {
            $mensaje = "❌ El enlace de recuperación ha expirado o no es válido.";
            $tipo = 'error';
        }
    } catch (PDOException $e) {
        $mensaje = "❌ Error en el sistema.";
        $tipo = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $nuevaPassword = $_POST['nueva_password'];
    $confirmarPassword = $_POST['confirmar_password'];
    
    try {
        if (strlen($nuevaPassword) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres");
        }
        
        if ($nuevaPassword !== $confirmarPassword) {
            throw new Exception("Las contraseñas no coinciden");
        }
        
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id FROM usuarios_admin WHERE reset_token = ? AND reset_token_expira > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("El token ha expirado");
        }
        
        $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE usuarios_admin SET password = ?, reset_token = NULL, reset_token_expira = NULL WHERE id = ?");
        $stmt->execute([$passwordHash, $user['id']]);
        
        $mensaje = "✅ ¡Contraseña actualizada correctamente! Ya puedes iniciar sesión.";
        $tipo = 'success';
        $tokenValido = false;
        
    } catch (Exception $e) {
        $mensaje = "❌ " . $e->getMessage();
        $tipo = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Trinity School</title>
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
        .titulo-recuperar p { color: #666; font-size: 0.9rem; }
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
        .form-group { margin-bottom: 20px; }
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
        .password-strength { margin-top: 8px; font-size: 0.8rem; color: #666; }
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
        .login-footer a { color: #3AAFA9; text-decoration: none; font-weight: 500; font-size: 0.9rem; }
        .login-footer a:hover { text-decoration: underline; }
        .user-info {
            background: rgba(58, 175, 169, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }
        .user-info strong { color: #1B4B5A; }
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
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo; ?>">
                    <i class="fas fa-<?php echo $tipo === 'success' ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <?php if ($tokenValido): ?>
                <div class="titulo-recuperar">
                    <h2>Nueva Contraseña</h2>
                    <p>Ingresa tu nueva contraseña</p>
                </div>

                <?php if ($usuario): ?>
                    <div class="user-info">
                        <i class="fas fa-user-circle" style="font-size: 2rem; color: #1B4B5A;"></i><br>
                        <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong><br>
                        <small><?php echo htmlspecialchars($usuario['email']); ?></small>
                    </div>
                <?php endif; ?>

                <form method="POST" action="restablecer_password.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="nueva_password"><i class="fas fa-lock"></i> Nueva Contraseña</label>
                        <input type="password" id="nueva_password" name="nueva_password" placeholder="Mínimo 6 caracteres" required minlength="6" autofocus>
                        <div class="password-strength">
                            <i class="fas fa-info-circle"></i> La contraseña debe tener al menos 6 caracteres
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_password"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                        <input type="password" id="confirmar_password" name="confirmar_password" placeholder="Repite la contraseña" required minlength="6">
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-check"></i> Restablecer Contraseña
                    </button>
                </form>
            <?php else: ?>
                <div class="login-footer">
                    <a href="index.php"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const pass1 = document.getElementById('nueva_password').value;
            const pass2 = document.getElementById('confirmar_password').value;
            
            if (pass1 !== pass2) {
                e.preventDefault();
                alert('❌ Las contraseñas no coinciden');
                return false;
            }
            
            if (pass1.length < 6) {
                e.preventDefault();
                alert('❌ La contraseña debe tener al menos 6 caracteres');
                return false;
            }
        });
    </script>
</body>
</html>