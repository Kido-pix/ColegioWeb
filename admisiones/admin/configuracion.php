<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

$db = Database::getInstance()->getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CAMBIAR CONTRASEÑA
    if (isset($_POST['accion']) && $_POST['accion'] === 'cambiar_password') {
        $password_actual = $_POST['password_actual'];
        $password_nueva = $_POST['password_nueva'];
        $password_confirmar = $_POST['password_confirmar'];
        
        try {
            // Verificar contraseña actual
            $stmt = $db->prepare("SELECT password FROM usuarios_admin WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $usuario = $stmt->fetch();
            
            if (!password_verify($password_actual, $usuario['password'])) {
                $_SESSION['error'] = "La contraseña actual es incorrecta";
            } elseif ($password_nueva !== $password_confirmar) {
                $_SESSION['error'] = "Las contraseñas nuevas no coinciden";
            } elseif (strlen($password_nueva) < 6) {
                $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres";
            } else {
                // Actualizar contraseña
                $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios_admin SET password = ? WHERE id = ?");
                $stmt->execute([$password_hash, $_SESSION['admin_id']]);
                
                // Registrar en log
                $stmt = $db->prepare("INSERT INTO log_actividades (usuario_admin, accion, detalles) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['admin_nombre'],
                    'Cambio de contraseña',
                    'Usuario cambió su contraseña'
                ]);
                
                $_SESSION['success'] = "✅ Contraseña actualizada correctamente";
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error al cambiar contraseña: " . $e->getMessage();
        }
        
        header('Location: configuracion.php');
        exit;
    }
    
    // CREAR USUARIO
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear_usuario') {
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $rol = $_POST['rol'];
        
        try {
            // Verificar si el usuario ya existe
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios_admin WHERE usuario = ? OR email = ?");
            $stmt->execute([$usuario, $email]);
            
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "El usuario o email ya existe";
            } elseif (strlen($password) < 6) {
                $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres";
            } else {
                // Crear usuario
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO usuarios_admin (nombre, usuario, email, password, rol, activo) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$nombre, $usuario, $email, $password_hash, $rol]);
                
                // Registrar en log
                $stmt = $db->prepare("INSERT INTO log_actividades (usuario_admin, accion, detalles) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['admin_nombre'],
                    'Crear usuario',
                    "Creó usuario: $nombre ($usuario)"
                ]);
                
                $_SESSION['success'] = "✅ Usuario creado correctamente";
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error al crear usuario: " . $e->getMessage();
        }
        
        header('Location: configuracion.php');
        exit;
    }
    
    // EDITAR USUARIO
    if (isset($_POST['accion']) && $_POST['accion'] === 'editar_usuario') {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $rol = $_POST['rol'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        try {
            $stmt = $db->prepare("
                UPDATE usuarios_admin 
                SET nombre = ?, email = ?, rol = ?, activo = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $email, $rol, $activo, $id]);
            
            // Registrar en log
            $stmt = $db->prepare("INSERT INTO log_actividades (usuario_admin, accion, detalles) VALUES (?, ?, ?)");
            $stmt->execute([
                $_SESSION['admin_nombre'],
                'Editar usuario',
                "Editó usuario ID: $id"
            ]);
            
            $_SESSION['success'] = "✅ Usuario actualizado correctamente";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error al editar usuario: " . $e->getMessage();
        }
        
        header('Location: configuracion.php');
        exit;
    }
    
    // ELIMINAR USUARIO
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_usuario') {
        $id = $_POST['id'];
        
        // No permitir eliminar al usuario actual
        if ($id == $_SESSION['admin_id']) {
            $_SESSION['error'] = "No puedes eliminar tu propio usuario";
        } else {
            try {
                $stmt = $db->prepare("DELETE FROM usuarios_admin WHERE id = ?");
                $stmt->execute([$id]);
                
                // Registrar en log
                $stmt = $db->prepare("INSERT INTO log_actividades (usuario_admin, accion, detalles) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['admin_nombre'],
                    'Eliminar usuario',
                    "Eliminó usuario ID: $id"
                ]);
                
                $_SESSION['success'] = "✅ Usuario eliminado correctamente";
            } catch(PDOException $e) {
                $_SESSION['error'] = "Error al eliminar usuario: " . $e->getMessage();
            }
        }
        
        header('Location: configuracion.php');
        exit;
    }
}

// Obtener usuarios
try {
    $stmt = $db->query("SELECT * FROM usuarios_admin ORDER BY nombre ASC");
    $usuarios = $stmt->fetchAll();
} catch(PDOException $e) {
    $usuarios = [];
    $error = "Error al cargar usuarios: " . $e->getMessage();
}

// Obtener últimas actividades
try {
    $stmt = $db->query("
        SELECT * FROM log_actividades 
        ORDER BY fecha_hora DESC 
        LIMIT 50
    ");
    $actividades = $stmt->fetchAll();
} catch(PDOException $e) {
    $actividades = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Panel Administrativo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1B4B5A;
            --secondary: #0A1929;
            --accent: #3AAFA9;
            --success: #2ED47A;
            --warning: #FFB648;
            --danger: #F7464A;
            --dark: #0F1419;
            --text: #E8EAED;
            --text-secondary: #9AA0A6;
            --border: #2D3748;
            --card-bg: #1A202C;
            --hover: #2D3748;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--dark);
            color: var(--text);
            line-height: 1.6;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: var(--secondary);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sidebar-logo img {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--primary);
            padding: 8px;
        }

        .sidebar-logo-text h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
        }

        .sidebar-logo-text p {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 15px;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .sidebar-menu a:hover {
            background: var(--hover);
            color: var(--text);
        }

        .sidebar-menu a.active {
            background: var(--primary);
            color: white;
        }

        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid var(--border);
            background: var(--secondary);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--card-bg);
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
        }

        .user-details p {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
        }

        .user-details small {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .btn-logout {
            width: 100%;
            padding: 10px;
            background: transparent;
            color: var(--danger);
            border: 1px solid var(--danger);
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: var(--danger);
            color: white;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--text);
            margin-bottom: 8px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .breadcrumb a {
            color: var(--accent);
            text-decoration: none;
        }

        /* ALERTS */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(46, 212, 122, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(247, 70, 74, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        /* TABS */
        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid var(--border);
            margin-bottom: 30px;
        }

        .tab-btn {
            padding: 12px 25px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .tab-btn:hover {
            color: var(--text);
        }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* CARDS */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 1.3rem;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* FORM */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 12px 15px;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-checkbox input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* BUTTONS */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #143d49;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #25b563;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d93d41;
        }

        /* TABLE */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--dark);
        }

        th {
            padding: 15px;
            text-align: left;
            color: var(--text);
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
        }

        tbody tr:hover {
            background: var(--hover);
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(46, 212, 122, 0.2);
            color: var(--success);
        }

        .badge-danger {
            background: rgba(247, 70, 74, 0.2);
            color: var(--danger);
        }

        .badge-primary {
            background: rgba(27, 75, 90, 0.3);
            color: var(--accent);
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            color: var(--text);
        }

        .btn-close {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-close:hover {
            color: var(--danger);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../img/logo.png" alt="Trinity School">
                <div class="sidebar-logo-text">
                    <h3>Trinity School</h3>
                    <p>Panel Administrativo</p>
                </div>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <li><a href="solicitudes.php"><i class="fas fa-file-alt"></i>Solicitudes</a></li>
            <li><a href="verificar_pago.php"><i class="fas fa-money-check-alt"></i>Verificar Pagos</a></li>
            <li><a href="entrevistas.php"><i class="fas fa-calendar-alt"></i>Entrevistas</a></li>
            <li><a href="reportes.php"><i class="fas fa-chart-bar"></i>Reportes</a></li>
            <li><a href="configuracion.php" class="active"><i class="fas fa-cog"></i>Configuración</a></li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_nombre'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <p><?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></p>
                    <small><?php echo htmlspecialchars($_SESSION['admin_rol']); ?></small>
                </div>
            </div>
            <form method="POST" action="logout.php">
                <button type="submit" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HEADER -->
        <div class="page-header">
            <h1>Configuración del Sistema</h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span>
                <span>Configuración</span>
            </div>
        </div>

        <!-- ALERTAS -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo htmlspecialchars($_SESSION['success']); 
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error']); 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="cambiarTab('usuarios')">
                <i class="fas fa-users"></i> Usuarios
            </button>
            <button class="tab-btn" onclick="cambiarTab('password')">
                <i class="fas fa-key"></i> Cambiar Contraseña
            </button>
            <button class="tab-btn" onclick="cambiarTab('actividades')">
                <i class="fas fa-history"></i> Log de Actividades
            </button>
        </div>

        <!-- TAB: USUARIOS -->
        <div id="tab-usuarios" class="tab-content active">
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-users"></i>
                    Gestión de Usuarios
                    <button class="btn btn-success" onclick="abrirModal('modalCrear')" style="margin-left: auto;">
                        <i class="fas fa-plus"></i> Crear Usuario
                    </button>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?php echo htmlspecialchars($usuario['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($usuario['activo']): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($usuario['id'] != $_SESSION['admin_id']): ?>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('¿Eliminar este usuario?')">
                                            <input type="hidden" name="accion" value="eliminar_usuario">
                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: CAMBIAR CONTRASEÑA -->
        <div id="tab-password" class="tab-content">
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-key"></i>
                    Cambiar Contraseña
                </div>

                <form method="POST" style="max-width: 500px;">
                    <input type="hidden" name="accion" value="cambiar_password">

                    <div class="form-group">
                        <label class="form-label">Contraseña Actual *</label>
                        <input type="password" name="password_actual" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nueva Contraseña *</label>
                        <input type="password" name="password_nueva" class="form-input" required minlength="6">
                        <small style="color: var(--text-secondary);">Mínimo 6 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmar Nueva Contraseña *</label>
                        <input type="password" name="password_confirmar" class="form-input" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cambiar Contraseña
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB: LOG DE ACTIVIDADES -->
        <div id="tab-actividades" class="tab-content">
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-history"></i>
                    Últimas 50 Actividades
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actividades as $actividad): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($actividad['fecha_hora'])); ?></td>
                                <td><?php echo htmlspecialchars($actividad['usuario_admin']); ?></td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?php echo htmlspecialchars($actividad['accion']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($actividad['detalles']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL CREAR USUARIO -->
    <div id="modalCrear" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h3>
                <button class="btn-close" onclick="cerrarModal('modalCrear')">&times;</button>
            </div>

            <form method="POST">
                <input type="hidden" name="accion" value="crear_usuario">

                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre de Usuario *</label>
                    <input type="text" name="usuario" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label">Rol *</label>
                    <select name="rol" class="form-select" required>
                        <option value="Administrador">Administrador</option>
                        <option value="Asistente">Asistente</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Crear Usuario
                    </button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModal('modalCrear')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR USUARIO -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Editar Usuario</h3>
                <button class="btn-close" onclick="cerrarModal('modalEditar')">&times;</button>
            </div>

            <form method="POST" id="formEditar">
                <input type="hidden" name="accion" value="editar_usuario">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="edit_email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Rol *</label>
                    <select name="rol" id="edit_rol" class="form-select" required>
                        <option value="Administrador">Administrador</option>
                        <option value="Asistente">Asistente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="activo" id="edit_activo">
                        <span>Usuario Activo</span>
                    </label>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModal('modalEditar')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // CAMBIAR TABS
        function cambiarTab(tab) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Mostrar tab seleccionado
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.classList.add('active');
        }

        // MODALES
        function abrirModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function cerrarModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // EDITAR USUARIO
        function editarUsuario(usuario) {
            document.getElementById('edit_id').value = usuario.id;
            document.getElementById('edit_nombre').value = usuario.nombre;
            document.getElementById('edit_email').value = usuario.email;
            document.getElementById('edit_rol').value = usuario.rol;
            document.getElementById('edit_activo').checked = usuario.activo == 1;
            abrirModal('modalEditar');
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>