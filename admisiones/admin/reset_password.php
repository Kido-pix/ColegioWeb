<?php
require_once '../config/database.php';

// ============================================
// CONFIGURACIÓN - CAMBIA ESTOS DATOS
// ============================================
$usuario = 'admin';
$nuevaPassword = 'admin123';

try {
    $db = Database::getInstance()->getConnection();
    
    // Generar hash correcto
    $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
    
    // Actualizar en la base de datos
    $stmt = $db->prepare("UPDATE usuarios_admin SET password = ? WHERE usuario = ?");
    $resultado = $stmt->execute([$passwordHash, $usuario]);
    
    if ($resultado) {
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reseteado</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #1B4B5A, #0A1929);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container {
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    max-width: 500px;
                    text-align: center;
                }
                h1 { color: #2ED47A; margin-bottom: 20px; }
                .success-icon { font-size: 4rem; color: #2ED47A; margin-bottom: 20px; }
                .info-box {
                    background: #f5f5f5;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                    text-align: left;
                }
                .info-box strong { color: #1B4B5A; }
                .credential { 
                    background: white; 
                    padding: 10px; 
                    border-radius: 5px; 
                    border: 2px solid #3AAFA9;
                    margin: 5px 0;
                    font-family: monospace;
                    font-size: 1.1rem;
                }
                .btn {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #1B4B5A, #0A1929);
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    transition: all 0.3s ease;
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                }
                .warning {
                    background: #ffe6e6;
                    border: 2px solid #ff0000;
                    padding: 15px;
                    border-radius: 10px;
                    margin-top: 20px;
                    color: #cc0000;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='success-icon'>✅</div>
                <h1>¡Contraseña Reseteada!</h1>
                <p>La contraseña ha sido actualizada correctamente</p>
                
                <div class='info-box'>
                    <p><strong>Credenciales de acceso:</strong></p>
                    <p style='margin-top: 10px;'>Usuario:</p>
                    <div class='credential'>{$usuario}</div>
                    <p style='margin-top: 10px;'>Contraseña:</p>
                    <div class='credential'>{$nuevaPassword}</div>
                </div>
                
                <a href='index.php' class='btn'>🔐 Ir al Login</a>
                
                <div class='warning'>
                    <strong>⚠️ IMPORTANTE:</strong><br>
                    Elimina el archivo <code>reset_password.php</code> por seguridad
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Verificar que se guardó correctamente
        $stmt = $db->prepare("SELECT password FROM usuarios_admin WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar hash
        $verificacion = password_verify($nuevaPassword, $admin['password']);
        echo "<script>console.log('Hash guardado: " . substr($admin['password'], 0, 30) . "...');</script>";
        echo "<script>console.log('Verificación: " . ($verificacion ? 'OK ✅' : 'FALLO ❌') . "');</script>";
        
    } else {
        throw new Exception("No se pudo actualizar el usuario");
    }
    
} catch(Exception $e) {
    echo "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                padding: 50px;
                text-align: center;
            }
            .error {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                max-width: 500px;
                margin: 0 auto;
            }
            h1 { color: #F7464A; }
        </style>
    </head>
    <body>
        <div class='error'>
            <h1>❌ Error</h1>
            <p>{$e->getMessage()}</p>
        </div>
    </body>
    </html>
    ";
}
?>