<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_logueado']) && $_SESSION['admin_logueado'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../procesar.php';
    
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, completa todos los campos';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM usuarios_admin WHERE usuario = ? AND activo = 1");
            $stmt->execute([$usuario]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Login exitoso
                $_SESSION['admin_logueado'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                $_SESSION['admin_nombre'] = $admin['nombre_completo'];
                $_SESSION['admin_rol'] = $admin['rol'];
                
                // Actualizar último acceso
                $updateStmt = $db->prepare("UPDATE usuarios_admin SET ultimo_acceso = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel Administrativo Trinity School</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
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
        }

        .login-card {
            background: rgba(26, 32, 44, 0.95);
            border: 1px solid rgba(58, 175, 169, 0.2);
            border-radius: 20px;
            padding: 50px 40px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1B4B5A, #3AAFA9);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 20px;
        }

        .logo-text h1 {
            font-size: 1.8rem;
            color: #E8EAED;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .logo-text p {
            color: #9AA0A6;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            color: #E8EAED;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9AA0A6;
            font-size: 1.1rem;
        }

        .form-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            background: rgba(45, 55, 72, 0.5);
            border: 1px solid rgba(58, 175, 169, 0.2);
            border-radius: 12px;
            color: #E8EAED;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #3AAFA9;
            background: rgba(45, 55, 72, 0.8);
            box-shadow: 0 0 0 3px rgba(58, 175, 169, 0.1);
        }

        .form-input::placeholder {
            color: #9AA0A6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(247, 70, 74, 0.1);
            border: 1px solid rgba(247, 70, 74, 0.3);
            color: #F7464A;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1B4B5A, #3AAFA9);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(58, 175, 169, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(58, 175, 169, 0.2);
            color: #9AA0A6;
            font-size: 0.85rem;
        }

        .login-footer a {
            color: #3AAFA9;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 500px) {
            .login-card {
                padding: 40px 30px;
            }

            .logo {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .logo-text h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="logo-text">
                    <h1>Trinity School</h1>
                    <p>Panel Administrativo</p>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="usuario">Usuario</label>
                    <div class="input-group">
                        <i class="input-icon fas fa-user"></i>
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            class="form-input" 
                            placeholder="Ingresa tu usuario"
                            required
                            autocomplete="username"
                            value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="input-group">
                        <i class="input-icon fas fa-lock"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <div class="login-footer">
                <p>
                    ¿Problemas para acceder? 
                    <a href="mailto:admin@trinityschool.edu.pe">Contáctanos</a>
                </p>
                <p style="margin-top: 15px;">
                    <a href="../index.php">
                        <i class="fas fa-arrow-left"></i> Volver al sitio web
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus en el campo de usuario al cargar
        document.getElementById('usuario').focus();
    </script>
</body>
</html>