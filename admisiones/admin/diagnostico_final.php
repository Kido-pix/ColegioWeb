<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico Sistema de Recuperación</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 30px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1B4B5A; margin-bottom: 30px; }
        h2 { color: #333; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; }
        h3 { color: #555; margin: 20px 0 10px 0; }
        .ok { color: #2ED47A; font-weight: bold; }
        .error { color: #F7464A; font-weight: bold; }
        .info { background: #f0f8ff; padding: 15px; border-left: 4px solid #3AAFA9; margin: 10px 0; border-radius: 5px; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; overflow-x: auto; word-break: break-all; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: 600; }
        .test-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0; }
        button { padding: 10px 20px; background: #1B4B5A; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; font-size: 14px; }
        button:hover { background: #0A1929; }
        input[type='email'] { padding: 10px; width: 100%; max-width: 400px; border: 2px solid #e0e0e0; border-radius: 5px; margin: 10px 0; }
        a { color: #3AAFA9; word-break: break-all; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Diagnóstico del Sistema de Recuperación</h1>";

// 1. ARCHIVOS
echo "<h2>1. Verificación de Archivos</h2>";
$archivos = [
    '../config/database.php' => 'Database',
    '../config/emailer.php' => 'Emailer',
    '../config/email.php' => 'Email Config',
    'recuperar_password.php' => 'Recuperar Password',
    'restablecer_password.php' => 'Restablecer Password',
    '../vendor/autoload.php' => 'PHPMailer (Composer)'
];

echo "<table><tr><th>Archivo</th><th>Estado</th></tr>";
foreach ($archivos as $archivo => $nombre) {
    $existe = file_exists($archivo);
    $estado = $existe ? "<span class='ok'>✅ OK</span>" : "<span class='error'>❌ FALTA</span>";
    echo "<tr><td>{$nombre}</td><td>{$estado}</td></tr>";
}
echo "</table>";

// 2. BASE DE DATOS
echo "<h2>2. Base de Datos</h2>";
try {
    require_once '../config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p class='ok'>✅ Conexión exitosa</p>";
    
    $stmt = $db->query("DESCRIBE usuarios_admin");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnasExistentes = array_column($columnas, 'Field');
    
    $necesarias = ['reset_token', 'reset_token_expira'];
    echo "<h3>Columnas necesarias:</h3>";
    foreach ($necesarias as $col) {
        $tiene = in_array($col, $columnasExistentes);
        echo "<p>" . ($tiene ? "<span class='ok'>✅</span>" : "<span class='error'>❌</span>") . " {$col}</p>";
    }
    
    $stmt = $db->query("SELECT id, usuario, nombre, email, activo FROM usuarios_admin WHERE usuario = 'admin'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<h3>Usuario Admin:</h3>";
        echo "<p><strong>Email:</strong> {$admin['email']}</p>";
        echo "<p><strong>Activo:</strong> " . ($admin['activo'] ? '✅ Sí' : '❌ No') . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error BD: {$e->getMessage()}</p>";
}

// 3. PHPMAILER
echo "<h2>3. PHPMailer</h2>";
if (file_exists('../vendor/autoload.php')) {
    echo "<p class='ok'>✅ PHPMailer instalado</p>";
    
    try {
        require_once '../config/email.php';
        echo "<h3>Configuración:</h3>";
        echo "<p>Host: " . EmailConfig::SMTP_HOST . "</p>";
        echo "<p>Usuario: " . EmailConfig::SMTP_USERNAME . "</p>";
        echo "<p>Admin Email: " . EmailConfig::ADMIN_EMAIL . "</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error config: {$e->getMessage()}</p>";
    }
} else {
    echo "<p class='error'>❌ PHPMailer NO instalado</p>";
    echo "<div class='info'>Ejecuta: <code>composer install</code> en la raíz del proyecto</div>";
}

// 4. EMAILER
echo "<h2>4. Clase Emailer</h2>";
try {
    require_once '../config/emailer.php';
    $emailer = new Emailer();
    echo "<p class='ok'>✅ Clase Emailer OK</p>";
    
    $metodo = method_exists($emailer, 'enviarEmailPersonalizado');
    echo "<p>" . ($metodo ? "<span class='ok'>✅</span>" : "<span class='error'>❌</span>") . " Método enviarEmailPersonalizado()</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: {$e->getMessage()}</p>";
}

// 5. GENERAR TOKEN Y ENLACE
echo "<h2>5. Generar Enlace de Prueba</h2>";
echo "<div class='test-section'>";

$tokenGenerado = bin2hex(random_bytes(32));
$expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

if (isset($db)) {
    try {
        $stmt = $db->prepare("UPDATE usuarios_admin SET reset_token = ?, reset_token_expira = ? WHERE usuario = 'admin'");
        $stmt->execute([$tokenGenerado, $expira]);
        echo "<p class='ok'>✅ Token guardado en BD</p>";
        
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $ruta = dirname($_SERVER['PHP_SELF']);
        $enlace = "{$protocolo}://{$host}{$ruta}/restablecer_password.php?token={$tokenGenerado}";
        
        echo "<p><strong>📎 ENLACE DIRECTO PARA PROBAR:</strong></p>";
        echo "<div class='code'><a href='{$enlace}' target='_blank'>{$enlace}</a></div>";
        echo "<p class='info'><strong>Instrucciones:</strong> Haz clic en el enlace de arriba para probar el restablecimiento de contraseña SIN necesidad de email.</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: {$e->getMessage()}</p>";
    }
}

echo "</div>";

// 6. TEST DE EMAIL
echo "<h2>6. Probar Envío de Email</h2>";
echo "<div class='test-section'>";

if (isset($_POST['test_email'])) {
    try {
        if (!isset($emailer)) {
            require_once '../config/emailer.php';
            $emailer = new Emailer();
        }
        
        $destino = trim($_POST['email_destino']);
        $asunto = "Test Recuperación - Trinity School";
        $cuerpo = "<h2>✅ Test Exitoso</h2><p>Si recibes este email, PHPMailer funciona correctamente.</p>";
        
        echo "<p>📧 Enviando a: <strong>{$destino}</strong>...</p>";
        
        $resultado = $emailer->enviarEmailPersonalizado($destino, $asunto, $cuerpo);
        
        if ($resultado['success']) {
            echo "<p class='ok'>✅ EMAIL ENVIADO EXITOSAMENTE</p>";
            echo "<p>Revisa tu bandeja de entrada (y spam)</p>";
        } else {
            echo "<p class='error'>❌ Error al enviar: {$resultado['message']}</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Excepción: {$e->getMessage()}</p>";
        echo "<p>Detalles: " . $e->getTraceAsString() . "</p>";
    }
}

echo "<form method='POST'>
    <p><label><strong>Email de prueba:</strong></label></p>
    <input type='email' name='email_destino' value='julcadelacruzjoe@gmail.com' required>
    <br>
    <button type='submit' name='test_email'>🧪 Enviar Email de Prueba</button>
</form>";

echo "</div>";

// RESUMEN FINAL
echo "<h2>✅ Resumen</h2>";
echo "<div class='info'>";
echo "<p><strong>Para usar el sistema de recuperación:</strong></p>";
echo "<ol>";
echo "<li>Si el email funciona → Usa la opción normal en el login</li>";
echo "<li>Si el email NO funciona → Usa el enlace directo generado arriba (sección 5)</li>";
echo "<li>El enlace expira en 1 hora</li>";
echo "</ol>";
echo "</div>";

echo "</div></body></html>";
?>