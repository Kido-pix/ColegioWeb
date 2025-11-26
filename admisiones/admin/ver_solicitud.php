<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: solicitudes.php');
    exit;
}

$solicitudId = (int)$_GET['id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener datos completos de la solicitud
    $stmt = $db->prepare("
        SELECT 
            id, codigo_postulante, fecha_registro, estado,
            nombres, apellido_paterno, apellido_materno,
            fecha_nacimiento, dni_estudiante, sexo, lugar_nacimiento,
            direccion, distrito, provincia,
            nivel_postula, grado_postula, colegio_procedencia, tipo_colegio, promedio_anterior,
            nombre_padre, dni_padre, celular_padre, email_padre, ocupacion_padre,
            nombre_madre, dni_madre, celular_madre, email_madre, ocupacion_madre,
            apoderado_principal, nombre_apoderado, parentesco_apoderado,
            celular_apoderado, email_apoderado,
            tiene_hermanos, nombres_hermanos,
            necesidades_especiales, descripcion_necesidades,
            pago_verificado, fecha_pago, monto_pago, metodo_pago, numero_operacion,
            fecha_entrevista, observaciones_entrevista, resultado_entrevista,
            partida_nacimiento, partida_nacimiento_tipo, partida_nacimiento_nombre,
            dni_estudiante_doc, dni_estudiante_doc_tipo, dni_estudiante_doc_nombre,
            dni_apoderado, dni_apoderado_tipo, dni_apoderado_nombre,
            libreta_notas, libreta_notas_tipo, libreta_notas_nombre,
            certificado_estudios, certificado_estudios_tipo, certificado_estudios_nombre,
            foto_estudiante, foto_estudiante_tipo, foto_estudiante_nombre,
            comprobante_pago, comprobante_pago_tipo, comprobante_pago_nombre,
            ip_registro, ultima_modificacion
        FROM solicitudes_admision 
        WHERE id = ?
    ");
    $stmt->execute([$solicitudId]);
    $solicitud = $stmt->fetch();
    
    if (!$solicitud) {
        $_SESSION['error'] = 'Solicitud no encontrada';
        header('Location: solicitudes.php');
        exit;
    }
    
    // Procesar actualización de estado si se envió formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        
        switch ($accion) {
            case 'cambiar_estado':
                $nuevoEstado = $_POST['nuevo_estado'];
                $observaciones = $_POST['observaciones'] ?? '';
                
                $stmt = $db->prepare("
                    UPDATE solicitudes_admision 
                    SET estado = ?,
                        observaciones_entrevista = CONCAT(COALESCE(observaciones_entrevista, ''), '\n\n[', NOW(), '] ', ?)
                    WHERE id = ?
                ");
                $stmt->execute([$nuevoEstado, $observaciones, $solicitudId]);
                
                // Registrar en log
                $stmt = $db->prepare("
                    INSERT INTO log_actividades (usuario_id, accion, solicitud_id, detalles)
                    VALUES (?, 'cambio_estado', ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['admin_id'],
                    $solicitudId,
                    "Estado cambiado a: $nuevoEstado. $observaciones"
                ]);
                
                $_SESSION['success'] = 'Estado actualizado correctamente';
                break;
                
            case 'verificar_pago':
                $metodoPago = $_POST['metodo_pago'];
                $numeroOperacion = $_POST['numero_operacion'] ?? '';
                
                $stmt = $db->prepare("
                    UPDATE solicitudes_admision 
                    SET pago_verificado = 1,
                        fecha_pago = NOW(),
                        metodo_pago = ?,
                        numero_operacion = ?,
                        estado = 'Pago Verificado'
                    WHERE id = ?
                ");
                $stmt->execute([$metodoPago, $numeroOperacion, $solicitudId]);
                
                // Registrar en log
                $stmt = $db->prepare("
                    INSERT INTO log_actividades (usuario_id, accion, solicitud_id, detalles)
                    VALUES (?, 'verificacion_pago', ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['admin_id'],
                    $solicitudId,
                    "Pago verificado. Método: $metodoPago"
                ]);
                
                $_SESSION['success'] = 'Pago verificado correctamente';
                break;
                
            case 'agendar_entrevista':
                $fechaEntrevista = $_POST['fecha_entrevista'];
                $observaciones = $_POST['observaciones'] ?? '';
                
                $stmt = $db->prepare("
                    UPDATE solicitudes_admision 
                    SET fecha_entrevista = ?,
                        observaciones_entrevista = CONCAT(COALESCE(observaciones_entrevista, ''), '\n\n[', NOW(), '] Entrevista agendada: ', ?),
                        estado = 'Entrevista Agendada'
                    WHERE id = ?
                ");
                $stmt->execute([$fechaEntrevista, $observaciones, $solicitudId]);
                
                // Registrar en log
                $stmt = $db->prepare("
                    INSERT INTO log_actividades (usuario_id, accion, solicitud_id, detalles)
                    VALUES (?, 'agendar_entrevista', ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['admin_id'],
                    $solicitudId,
                    "Entrevista agendada para: $fechaEntrevista"
                ]);
                
                $_SESSION['success'] = 'Entrevista agendada correctamente';
                break;
                
            case 'resultado_entrevista':
                $resultado = $_POST['resultado'];
                $observaciones = $_POST['observaciones'];
                
                $nuevoEstado = $resultado === 'Aprobado' ? 'Admitido' : 'Rechazado';
                
                $stmt = $db->prepare("
                    UPDATE solicitudes_admision 
                    SET resultado_entrevista = ?,
                        estado = ?,
                        observaciones_entrevista = CONCAT(COALESCE(observaciones_entrevista, ''), '\n\n[', NOW(), '] Resultado: ', ?)
                    WHERE id = ?
                ");
                $stmt->execute([$resultado, $nuevoEstado, $observaciones, $solicitudId]);
                
                // Registrar en log
                $stmt = $db->prepare("
                    INSERT INTO log_actividades (usuario_id, accion, solicitud_id, detalles)
                    VALUES (?, 'resultado_entrevista', ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['admin_id'],
                    $solicitudId,
                    "Resultado de entrevista: $resultado"
                ]);
                
                $_SESSION['success'] = 'Resultado registrado correctamente';
                break;
        }
        
        // Recargar datos actualizados
        $stmt = $db->prepare("SELECT * FROM solicitudes_admision WHERE id = ?");
        $stmt->execute([$solicitudId]);
        $solicitud = $stmt->fetch();
    }
    
    // Obtener historial de actividades
    $stmt = $db->prepare("
        SELECT l.*, u.nombre_completo 
        FROM log_actividades l
        LEFT JOIN usuarios_admin u ON l.usuario_id = u.id
        WHERE l.solicitud_id = ?
        ORDER BY l.fecha DESC
        LIMIT 10
    ");
    $stmt->execute([$solicitudId]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error al cargar la solicitud: " . $e->getMessage();
    header('Location: solicitudes.php');
    exit;
}

// Función helper para verificar si existe un documento
function tieneDocumento($solicitud, $campo) {
    return !empty($solicitud[$campo]) || !empty($solicitud[$campo . '_nombre']);
}

// Calcular edad
$fechaNac = new DateTime($solicitud['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $hoy->diff($fechaNac)->y;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Solicitud - <?php echo htmlspecialchars($solicitud['codigo_postulante']); ?></title>
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
            --light: #F5F7FA;
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

        /* HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-left h1 {
            font-size: 1.8rem;
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

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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

        /* MAIN CARD */
        .solicitud-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--secondary);
        }

        .card-header-left h2 {
            font-size: 1.5rem;
            color: var(--text);
            margin-bottom: 5px;
        }

        .codigo-grande {
            font-size: 1.1rem;
            color: var(--accent);
            font-weight: 600;
        }

        .badge {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge.pendiente { background: rgba(255, 182, 72, 0.15); color: var(--warning); }
        .badge.verificado { background: rgba(58, 175, 169, 0.15); color: var(--accent); }
        .badge.agendado { background: rgba(46, 212, 122, 0.15); color: var(--success); }
        .badge.admitido { background: rgba(46, 212, 122, 0.15); color: var(--success); }
        .badge.rechazado { background: rgba(247, 70, 74, 0.15); color: var(--danger); }

        /* TABS */
        .tabs {
            display: flex;
            gap: 5px;
            padding: 20px 30px 0;
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .tab-btn:hover {
            color: var(--text);
            background: var(--hover);
        }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        /* TAB CONTENT */
        .tab-content {
            display: none;
            padding: 30px;
        }

        .tab-content.active {
            display: block;
        }

        /* DATA SECTIONS */
        .data-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.2rem;
            color: var(--text);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .data-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .data-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-value {
            font-size: 1rem;
            color: var(--text);
            font-weight: 500;
        }

        .data-value.empty {
            color: var(--text-secondary);
            font-style: italic;
        }

        /* DOCUMENTOS GRID */
        .documentos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .documento-card {
            background: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .documento-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
        }

        .doc-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .doc-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 10px;
        }

        .doc-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .doc-status.uploaded {
            background: rgba(46, 212, 122, 0.2);
            color: var(--success);
        }

        .doc-status.missing {
            background: rgba(247, 70, 74, 0.2);
            color: var(--danger);
        }

        .doc-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--card-bg);
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn-icon:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        /* FORMS */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* HISTORIAL */
        .historial-item {
            padding: 15px;
            background: var(--secondary);
            border-left: 3px solid var(--accent);
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .historial-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .historial-accion {
            font-weight: 600;
            color: var(--text);
        }

        .historial-fecha {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .historial-usuario {
            font-size: 0.9rem;
            color: var(--accent);
            margin-bottom: 5px;
        }

        .historial-detalles {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 {
            font-size: 1.3rem;
            color: var(--text);
        }

        .btn-close {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        /* FOTO ESTUDIANTE */
        .foto-estudiante {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent);
            margin: 0 auto 20px;
            display: block;
        }

        .foto-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--secondary);
            border: 4px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--text-secondary);
            margin: 0 auto 20px;
        }

        /* INFO DESTACADA */
        .info-destacada {
            background: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-destacada-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            text-align: center;
        }

        .info-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .data-grid {
                grid-template-columns: 1fr;
            }

            .documentos-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
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
            <li><a href="solicitudes.php" class="active"><i class="fas fa-file-alt"></i>Solicitudes</a></li>
            <li><a href="verificar_pago.php"><i class="fas fa-money-check-alt"></i>Verificar Pagos</a></li>            <li><a href="entrevistas.php"><i class="fas fa-calendar-alt"></i>Entrevistas</a></li>
            <li><a href="reportes.php"><i class="fas fa-chart-bar"></i>Reportes</a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i>Configuración</a></li>
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
            <div class="header-left">
                <h1>Solicitud de Admisión</h1>
                <div class="breadcrumb">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <span>/</span>
                    <a href="solicitudes.php">Solicitudes</a>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($solicitud['codigo_postulante']); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <a href="solicitudes.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
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

        <!-- TARJETA PRINCIPAL -->
        <div class="solicitud-card">
            <!-- HEADER DE LA TARJETA -->
            <div class="card-header">
                <div class="card-header-left">
                    <h2><?php echo htmlspecialchars($solicitud['nombres'] . ' ' . $solicitud['apellido_paterno'] . ' ' . $solicitud['apellido_materno']); ?></h2>
                    <span class="codigo-grande">
                        <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($solicitud['codigo_postulante']); ?>
                    </span>
                </div>
                <div>
                    <span class="badge <?php 
                        echo match($solicitud['estado']) {
                            'Pendiente Pago' => 'pendiente',
                            'Documentos Completos', 'Pago Verificado' => 'verificado',
                            'Entrevista Agendada' => 'agendado',
                            'Admitido' => 'admitido',
                            'Rechazado' => 'rechazado',
                            default => 'pendiente'
                        };
                    ?>">
                        <?php echo htmlspecialchars($solicitud['estado']); ?>
                    </span>
                </div>
            </div>

            <!-- TABS -->
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('info-general')">
                    <i class="fas fa-user"></i> Información General
                </button>
                <button class="tab-btn" onclick="switchTab('documentos')">
                    <i class="fas fa-folder"></i> Documentos
                </button>
                <button class="tab-btn" onclick="switchTab('pago')">
                    <i class="fas fa-money-check-alt"></i> Pago
                </button>
                <button class="tab-btn" onclick="switchTab('entrevista')">
                    <i class="fas fa-calendar-check"></i> Entrevista
                </button>
                <button class="tab-btn" onclick="switchTab('historial')">
                    <i class="fas fa-history"></i> Historial
                </button>
            </div>

            <!-- TAB: INFORMACIÓN GENERAL -->
            <div class="tab-content active" id="tab-info-general">
                <!-- Foto del estudiante -->
                <?php if (tieneDocumento($solicitud, 'foto_estudiante')): ?>
                    <img src="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=foto_estudiante" 
                         alt="Foto del estudiante" class="foto-estudiante">
                <?php else: ?>
                    <div class="foto-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>

                <!-- Información destacada -->
                <div class="info-destacada">
                    <div class="info-destacada-grid">
                        <div class="info-item">
                            <span class="info-icon">🎓</span>
                            <div class="info-label">Nivel y Grado</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($solicitud['nivel_postula'] . ' - ' . $solicitud['grado_postula']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">🎂</span>
                            <div class="info-label">Edad</div>
                            <div class="info-value"><?php echo $edad; ?> años</div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">📅</span>
                            <div class="info-label">Fecha de Registro</div>
                            <div class="info-value">
                                <?php echo date('d/m/Y', strtotime($solicitud['fecha_registro'])); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">📞</span>
                            <div class="info-label">Contacto</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($solicitud['celular_apoderado']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos del Estudiante -->
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i>
                        Datos del Estudiante
                    </h3>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">Nombres</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['nombres']); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Apellido Paterno</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['apellido_paterno']); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Apellido Materno</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['apellido_materno']); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">DNI</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['dni_estudiante']); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Fecha de Nacimiento</span>
                            <span class="data-value"><?php echo date('d/m/Y', strtotime($solicitud['fecha_nacimiento'])); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Sexo</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['sexo']); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Lugar de Nacimiento</span>
                            <span class="data-value <?php echo empty($solicitud['lugar_nacimiento']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['lugar_nacimiento']) ? htmlspecialchars($solicitud['lugar_nacimiento']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item" style="grid-column: 1 / -1;">
                            <span class="data-label">Dirección</span>
                            <span class="data-value">
                                <?php echo htmlspecialchars($solicitud['direccion'] . ', ' . $solicitud['distrito'] . ', ' . $solicitud['provincia']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Información Académica -->
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-graduation-cap"></i>
                        Información Académica
                    </h3>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">Nivel al que Postula</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['nivel_postula']); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Grado</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['grado_postula']); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Colegio de Procedencia</span>
                            <span class="data-value <?php echo empty($solicitud['colegio_procedencia']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['colegio_procedencia']) ? htmlspecialchars($solicitud['colegio_procedencia']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Tipo de Colegio</span>
                            <span class="data-value <?php echo empty($solicitud['tipo_colegio']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['tipo_colegio']) ? htmlspecialchars($solicitud['tipo_colegio']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Promedio Anterior</span>
                            <span class="data-value <?php echo empty($solicitud['promedio_anterior']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['promedio_anterior']) ? htmlspecialchars($solicitud['promedio_anterior']) : 'No especificado'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Datos del Padre -->
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-male"></i>
                        Datos del Padre
                    </h3>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">Nombre Completo</span>
                            <span class="data-value <?php echo empty($solicitud['nombre_padre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['nombre_padre']) ? htmlspecialchars($solicitud['nombre_padre']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">DNI</span>
                            <span class="data-value <?php echo empty($solicitud['dni_padre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['dni_padre']) ? htmlspecialchars($solicitud['dni_padre']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Celular</span>
                            <span class="data-value <?php echo empty($solicitud['celular_padre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['celular_padre']) ? htmlspecialchars($solicitud['celular_padre']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Email</span>
                            <span class="data-value <?php echo empty($solicitud['email_padre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['email_padre']) ? htmlspecialchars($solicitud['email_padre']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Ocupación</span>
                            <span class="data-value <?php echo empty($solicitud['ocupacion_padre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['ocupacion_padre']) ? htmlspecialchars($solicitud['ocupacion_padre']) : 'No especificado'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Datos de la Madre -->
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-female"></i>
                        Datos de la Madre
                    </h3>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">Nombre Completo</span>
                            <span class="data-value <?php echo empty($solicitud['nombre_madre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['nombre_madre']) ? htmlspecialchars($solicitud['nombre_madre']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">DNI</span>
                            <span class="data-value <?php echo empty($solicitud['dni_madre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['dni_madre']) ? htmlspecialchars($solicitud['dni_madre']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Celular</span>
                            <span class="data-value <?php echo empty($solicitud['celular_madre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['celular_madre']) ? htmlspecialchars($solicitud['celular_madre']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Email</span>
                            <span class="data-value <?php echo empty($solicitud['email_madre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['email_madre']) ? htmlspecialchars($solicitud['email_madre']) : 'No especificado'; ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Ocupación</span>
                            <span class="data-value <?php echo empty($solicitud['ocupacion_madre']) ? 'empty' : ''; ?>">
                                <?php echo !empty($solicitud['ocupacion_madre']) ? htmlspecialchars($solicitud['ocupacion_madre']) : 'No especificado'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Datos del Apoderado -->
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-user-shield"></i>
                        Apoderado Principal
                    </h3>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">Apoderado</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['apoderado_principal']); ?></span>
                        </div>
                        <?php if ($solicitud['apoderado_principal'] === 'Otro'): ?>
                            <div class="data-item">
                                <span class="data-label">Nombre del Apoderado</span>
                                <span class="data-value"><?php echo htmlspecialchars($solicitud['nombre_apoderado']); ?></span>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Parentesco</span>
                                <span class="data-value"><?php echo htmlspecialchars($solicitud['parentesco_apoderado']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="data-item">
                            <span class="data-label">Celular</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['celular_apoderado']); ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Email</span>
                            <span class="data-value"><?php echo htmlspecialchars($solicitud['email_apoderado']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <?php if ($solicitud['tiene_hermanos'] || $solicitud['necesidades_especiales']): ?>
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Información Adicional
                    </h3>
                    <div class="data-grid">
                        <?php if ($solicitud['tiene_hermanos']): ?>
                            <div class="data-item" style="grid-column: 1 / -1;">
                                <span class="data-label">Hermanos en Trinity School</span>
                                <span class="data-value"><?php echo htmlspecialchars($solicitud['nombres_hermanos']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($solicitud['necesidades_especiales']): ?>
                            <div class="data-item" style="grid-column: 1 / -1;">
                                <span class="data-label">Necesidades Especiales</span>
                                <span class="data-value"><?php echo htmlspecialchars($solicitud['descripcion_necesidades']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Acciones -->
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-cogs"></i>
                        Acciones
                    </h3>
                    <button class="btn btn-primary" onclick="openModal('modalCambiarEstado')">
                        <i class="fas fa-exchange-alt"></i> Cambiar Estado
                    </button>
                </div>
            </div>

            <!-- TAB: DOCUMENTOS -->
            <div class="tab-content" id="tab-documentos">
                <div class="documentos-grid">
                    <!-- Partida de Nacimiento -->
                    <div class="documento-card">
                        <span class="doc-icon">📄</span>
                        <div class="doc-name">Partida de Nacimiento</div>
                        <?php if (tieneDocumento($solicitud, 'partida_nacimiento')): ?>
                            <span class="doc-status uploaded">✓ Subido</span>
                            <div class="doc-actions">
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=partida_nacimiento" 
                                   target="_blank" class="btn-icon" title="Ver documento">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=partida_nacimiento&download=1" 
                                   class="btn-icon" title="Descargar">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <span class="doc-status missing">✗ No subido</span>
                        <?php endif; ?>
                    </div>

                    <!-- DNI Estudiante -->
                    <div class="documento-card">
                        <span class="doc-icon">🪪</span>
                        <div class="doc-name">DNI del Estudiante</div>
                        <?php if (tieneDocumento($solicitud, 'dni_estudiante_doc')): ?>
                            <span class="doc-status uploaded">✓ Subido</span>
                            <div class="doc-actions">
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=dni_estudiante_doc" 
                                   target="_blank" class="btn-icon" title="Ver documento">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=dni_estudiante_doc&download=1" 
                                   class="btn-icon" title="Descargar">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <span class="doc-status missing">✗ No subido</span>
                        <?php endif; ?>
                    </div>

                    <!-- DNI Apoderado -->
                    <div class="documento-card">
                        <span class="doc-icon">🪪</span>
                        <div class="doc-name">DNI del Apoderado</div>
                        <?php if (tieneDocumento($solicitud, 'dni_apoderado')): ?>
                            <span class="doc-status uploaded">✓ Subido</span>
                            <div class="doc-actions">
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=dni_apoderado" 
                                   target="_blank" class="btn-icon" title="Ver documento">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=dni_apoderado&download=1" 
                                   class="btn-icon" title="Descargar">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <span class="doc-status missing">✗ No subido</span>
                        <?php endif; ?>
                    </div>

                    <!-- Libreta de Notas -->
                    <div class="documento-card">
                        <span class="doc-icon">📚</span>
                        <div class="doc-name">Libreta de Notas</div>
                        <?php if (tieneDocumento($solicitud, 'libreta_notas')): ?>
                            <span class="doc-status uploaded">✓ Subido</span>
                            <div class="doc-actions">
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=libreta_notas" 
                                   target="_blank" class="btn-icon" title="Ver documento">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=libreta_notas&download=1" 
                                   class="btn-icon" title="Descargar">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <span class="doc-status missing">✗ No subido</span>
                        <?php endif; ?>
                    </div>

                    <!-- Certificado de Estudios -->
                    <div class="documento-card">
                        <span class="doc-icon">📜</span>
                        <div class="doc-name">Certificado de Estudios</div>
                        <?php if (tieneDocumento($solicitud, 'certificado_estudios')): ?>
                            <span class="doc-status uploaded">✓ Subido</span>
                            <div class="doc-actions">
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=certificado_estudios" 
                                   target="_blank" class="btn-icon" title="Ver documento">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=certificado_estudios&download=1" 
                                   class="btn-icon" title="Descargar">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <span class="doc-status missing">✗ No subido</span>
                        <?php endif; ?>
                    </div>

                    <!-- Foto del Estudiante -->
                    <div class="documento-card">
                        <span class="doc-icon">📸</span>
                        <div class="doc-name">Foto del Estudiante</div>
                        <?php if (tieneDocumento($solicitud, 'foto_estudiante')): ?>
                            <span class="doc-status uploaded">✓ Subido</span>
                            <div class="doc-actions">
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=foto_estudiante" 
                                   target="_blank" class="btn-icon" title="Ver documento">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=foto_estudiante&download=1" 
                                   class="btn-icon" title="Descargar">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <span class="doc-status missing">✗ No subido</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB: PAGO -->
            <div class="tab-content" id="tab-pago">
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-money-bill-wave"></i>
                        Información de Pago
                    </h3>
                    
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">Estado del Pago</span>
                            <span class="data-value">
                                <?php if ($solicitud['pago_verificado']): ?>
                                    <span class="badge verificado">✓ Verificado</span>
                                <?php else: ?>
                                    <span class="badge pendiente">⏳ Pendiente</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="data-item">
                            <span class="data-label">Monto</span>
                            <span class="data-value">S/. <?php echo number_format($solicitud['monto_pago'], 2); ?></span>
                        </div>
                        
                        <?php if ($solicitud['pago_verificado']): ?>
                            <div class="data-item">
                                <span class="data-label">Fecha de Pago</span>
                                <span class="data-value">
                                    <?php echo $solicitud['fecha_pago'] ? date('d/m/Y H:i', strtotime($solicitud['fecha_pago'])) : 'No registrado'; ?>
                                </span>
                            </div>
                            
                            <div class="data-item">
                                <span class="data-label">Método de Pago</span>
                                <span class="data-value">
                                    <?php echo htmlspecialchars($solicitud['metodo_pago'] ?? 'No especificado'); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($solicitud['numero_operacion'])): ?>
                                <div class="data-item">
                                    <span class="data-label">Número de Operación</span>
                                    <span class="data-value"><?php echo htmlspecialchars($solicitud['numero_operacion']); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Comprobante de Pago -->
                    <?php if (tieneDocumento($solicitud, 'comprobante_pago')): ?>
                        <div style="margin-top: 30px;">
                            <h4 style="color: var(--text); margin-bottom: 15px;">Comprobante de Pago</h4>
                            <div class="documento-card" style="max-width: 400px;">
                                <span class="doc-icon">💳</span>
                                <div class="doc-name">Comprobante de Pago</div>
                                <span class="doc-status uploaded">✓ Subido</span>
                                <div class="doc-actions">
                                    <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=comprobante_pago" 
                                       target="_blank" class="btn-icon" title="Ver comprobante">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=comprobante_pago&download=1" 
                                       class="btn-icon" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 30px; padding: 20px; background: rgba(247, 70, 74, 0.1); border-left: 4px solid var(--danger); border-radius: 8px;">
                            <strong style="color: var(--danger);">⚠️ Comprobante de pago no subido</strong>
                            <p style="color: var(--text-secondary); margin-top: 8px;">El postulante aún no ha subido el comprobante de pago.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Botón para verificar pago -->
                    <?php if (!$solicitud['pago_verificado'] && tieneDocumento($solicitud, 'comprobante_pago')): ?>
                        <div style="margin-top: 30px;">
                            <button class="btn btn-success" onclick="openModal('modalVerificarPago')">
                                <i class="fas fa-check-circle"></i> Verificar Pago
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB: ENTREVISTA -->
            <div class="tab-content" id="tab-entrevista">
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-check"></i>
                        Información de Entrevista
                    </h3>
                    
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">Estado de Entrevista</span>
                            <span class="data-value">
                                <span class="badge <?php 
                                    echo match($solicitud['resultado_entrevista']) {
                                        'Aprobado' => 'admitido',
                                        'Rechazado' => 'rechazado',
                                        default => 'pendiente'
                                    };
                                ?>">
                                    <?php echo htmlspecialchars($solicitud['resultado_entrevista']); ?>
                                </span>
                            </span>
                        </div>
                        
                        <?php if (!empty($solicitud['fecha_entrevista'])): ?>
                            <div class="data-item">
                                <span class="data-label">Fecha de Entrevista</span>
                                <span class="data-value">
                                    <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_entrevista'])); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($solicitud['observaciones_entrevista'])): ?>
                        <div style="margin-top: 20px;">
                            <h4 style="color: var(--text); margin-bottom: 10px;">Observaciones</h4>
                            <div style="padding: 15px; background: var(--secondary); border-radius: 8px; white-space: pre-wrap; color: var(--text-secondary);">
                                <?php echo htmlspecialchars($solicitud['observaciones_entrevista']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Acciones de Entrevista -->
                    <div style="margin-top: 30px; display: flex; gap: 15px;">
                        <?php if (empty($solicitud['fecha_entrevista']) && $solicitud['pago_verificado']): ?>
                            <button class="btn btn-primary" onclick="openModal('modalAgendarEntrevista')">
                                <i class="fas fa-calendar-plus"></i> Agendar Entrevista
                            </button>
                        <?php endif; ?>

                        <?php if (!empty($solicitud['fecha_entrevista']) && $solicitud['resultado_entrevista'] === 'Pendiente'): ?>
                            <button class="btn btn-success" onclick="openModal('modalResultadoEntrevista')">
                                <i class="fas fa-clipboard-check"></i> Registrar Resultado
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB: HISTORIAL -->
            <div class="tab-content" id="tab-historial">
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-history"></i>
                        Historial de Actividades
                    </h3>
                    
                    <?php if (count($historial) > 0): ?>
                        <?php foreach($historial as $actividad): ?>
                            <div class="historial-item">
                                <div class="historial-header">
                                    <span class="historial-accion">
                                        <?php 
                                        $accionTexto = match($actividad['accion']) {
                                            'cambio_estado' => '🔄 Cambio de Estado',
                                            'verificacion_pago' => '💰 Verificación de Pago',
                                            'agendar_entrevista' => '📅 Entrevista Agendada',
                                            'resultado_entrevista' => '✅ Resultado de Entrevista',
                                            default => htmlspecialchars($actividad['accion'])
                                        };
                                        echo $accionTexto;
                                        ?>
                                    </span>
                                    <span class="historial-fecha">
                                        <?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?>
                                    </span>
                                </div>
                                <div class="historial-usuario">
                                    Por: <?php echo htmlspecialchars($actividad['nombre_completo'] ?? 'Sistema'); ?>
                                </div>
                                <?php if (!empty($actividad['detalles'])): ?>
                                    <div class="historial-detalles">
                                        <?php echo htmlspecialchars($actividad['detalles']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No hay actividades registradas aún</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- MODALES -->

    <!-- Modal: Cambiar Estado -->
    <div class="modal" id="modalCambiarEstado">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cambiar Estado</h3>
                <button class="btn-close" onclick="closeModal('modalCambiarEstado')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="cambiar_estado">
                
                <div class="form-group">
                    <label>Nuevo Estado</label>
                    <select name="nuevo_estado" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <option value="Pendiente Pago">Pendiente Pago</option>
                        <option value="Pago Verificado">Pago Verificado</option>
                        <option value="Entrevista Agendada">Entrevista Agendada</option>
                        <option value="Admitido">Admitido</option>
                        <option value="Rechazado">Rechazado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="4" 
                              placeholder="Observaciones sobre el cambio de estado..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalCambiarEstado')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Verificar Pago -->
    <div class="modal" id="modalVerificarPago">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Verificar Pago</h3>
                <button class="btn-close" onclick="closeModal('modalVerificarPago')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="verificar_pago">
                
                <div class="form-group">
                    <label>Método de Pago</label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <option value="Yape">Yape</option>
                        <option value="Plin">Plin</option>
                        <option value="Transferencia">Transferencia Bancaria</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Número de Operación (opcional)</label>
                    <input type="text" name="numero_operacion" class="form-control" 
                           placeholder="Número de operación o referencia">
                </div>
                
                <div style="padding: 15px; background: rgba(46, 212, 122, 0.1); border-radius: 8px; margin-bottom: 20px;">
                    <strong style="color: var(--success);">✓ Confirmar Verificación</strong>
                    <p style="color: var(--text-secondary); margin-top: 8px; font-size: 0.9rem;">
                        Al verificar, el estado cambiará a "Pago Verificado" y se registrará la fecha de pago.
                    </p>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalVerificarPago')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Verificar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Agendar Entrevista -->
    <div class="modal" id="modalAgendarEntrevista">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Agendar Entrevista</h3>
                <button class="btn-close" onclick="closeModal('modalAgendarEntrevista')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="agendar_entrevista">
                
                <div class="form-group">
                    <label>Fecha y Hora de la Entrevista</label>
                    <input type="datetime-local" name="fecha_entrevista" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="4" 
                              placeholder="Detalles sobre la entrevista, lugar, etc..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalAgendarEntrevista')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Agendar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Resultado de Entrevista -->
    <div class="modal" id="modalResultadoEntrevista">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Registrar Resultado de Entrevista</h3>
                <button class="btn-close" onclick="closeModal('modalResultadoEntrevista')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="resultado_entrevista">
                
                <div class="form-group">
                    <label>Resultado</label>
                    <select name="resultado" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <option value="Aprobado">✓ Aprobado</option>
                        <option value="Rechazado">✗ Rechazado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Observaciones de la Entrevista</label>
                    <textarea name="observaciones" class="form-control" rows="5" required
                              placeholder="Detalles sobre cómo fue la entrevista, motivos de la decisión, etc..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalResultadoEntrevista')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Resultado
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funciones para las tabs
        function switchTab(tabName) {
            // Remover clase active de todos los botones y contenidos
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Agregar clase active al botón clickeado y su contenido
            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        // Funciones para modales
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>