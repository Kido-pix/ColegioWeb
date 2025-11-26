<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

// Procesar verificación de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        $db = Database::getInstance()->getConnection();
        
        if ($_POST['accion'] === 'verificar_pago') {
            $solicitudId = (int)$_POST['solicitud_id'];
            $metodoPago = $_POST['metodo_pago'];
            $numeroOperacion = $_POST['numero_operacion'] ?? '';
            $observaciones = $_POST['observaciones'] ?? '';
            
            // Actualizar solicitud
            $stmt = $db->prepare("
                UPDATE solicitudes_admision 
                SET pago_verificado = 1,
                    fecha_pago = NOW(),
                    metodo_pago = ?,
                    numero_operacion = ?,
                    estado = 'Pago Verificado',
                    observaciones_entrevista = CONCAT(
                        COALESCE(observaciones_entrevista, ''), 
                        '\n\n[', NOW(), '] Pago verificado por ', ?, 
                        IF(? != '', CONCAT('. Obs: ', ?), '')
                    )
                WHERE id = ?
            ");
            
            $stmt->execute([
                $metodoPago, 
                $numeroOperacion, 
                $_SESSION['admin_nombre'],
                $observaciones,
                $observaciones,
                $solicitudId
            ]);
            
            // Registrar en log
            $stmt = $db->prepare("
                INSERT INTO log_actividades (usuario_id, accion, solicitud_id, detalles)
                VALUES (?, 'verificacion_pago', ?, ?)
            ");
            $stmt->execute([
                $_SESSION['admin_id'],
                $solicitudId,
                "Pago verificado. Método: $metodoPago. " . ($observaciones ? "Obs: $observaciones" : "")
            ]);
            
            $_SESSION['success'] = 'Pago verificado correctamente';
            
        } elseif ($_POST['accion'] === 'rechazar_pago') {
            $solicitudId = (int)$_POST['solicitud_id'];
            $motivo = $_POST['motivo_rechazo'];
            
            // Actualizar observaciones
            $stmt = $db->prepare("
                UPDATE solicitudes_admision 
                SET observaciones_entrevista = CONCAT(
                    COALESCE(observaciones_entrevista, ''), 
                    '\n\n[', NOW(), '] ⚠️ PAGO RECHAZADO por ', ?, ': ', ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['admin_nombre'], $motivo, $solicitudId]);
            
            // Registrar en log
            $stmt = $db->prepare("
                INSERT INTO log_actividades (usuario_id, accion, solicitud_id, detalles)
                VALUES (?, 'rechazo_pago', ?, ?)
            ");
            $stmt->execute([
                $_SESSION['admin_id'],
                $solicitudId,
                "Pago rechazado. Motivo: $motivo"
            ]);
            
            $_SESSION['warning'] = 'Pago rechazado. Se notificó al postulante.';
        }
        
        header('Location: verificar_pago.php');
        exit;
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error al procesar: " . $e->getMessage();
    }
}

// Obtener filtros
$filtroEstado = $_GET['filtro'] ?? 'pendientes';

try {
    $db = Database::getInstance()->getConnection();
    
    // Construir query según filtro
    $whereClause = match($filtroEstado) {
        'verificados' => "WHERE pago_verificado = 1",
        'todos' => "",
        default => "WHERE estado = 'Pendiente Pago' AND comprobante_pago IS NOT NULL"
    };
    
    // Obtener solicitudes
    $sql = "SELECT 
                id,
                codigo_postulante,
                CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
                nivel_postula,
                grado_postula,
                celular_apoderado,
                email_apoderado,
                fecha_registro,
                estado,
                pago_verificado,
                fecha_pago,
                metodo_pago,
                monto_pago,
                comprobante_pago IS NOT NULL as tiene_comprobante
            FROM solicitudes_admision 
            $whereClause
            ORDER BY fecha_registro DESC";
    
    $stmt = $db->query($sql);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'Pendiente Pago' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN pago_verificado = 1 THEN 1 ELSE 0 END) as verificados,
            SUM(CASE WHEN pago_verificado = 1 THEN monto_pago ELSE 0 END) as monto_recaudado
        FROM solicitudes_admision
        WHERE comprobante_pago IS NOT NULL
    ");
    $stats = $stmt->fetch();
    
} catch(PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
    $solicitudes = [];
    $stats = ['total' => 0, 'pendientes' => 0, 'verificados' => 0, 'monto_recaudado' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Pagos - Panel Administrativo</title>
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

        .alert-warning {
            background: rgba(255, 182, 72, 0.1);
            border-left: 4px solid var(--warning);
            color: var(--warning);
        }

        .alert-error {
            background: rgba(247, 70, 74, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 25px;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card.primary::before { background: var(--primary); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.success::before { background: var(--success); }
        .stat-card.accent::before { background: var(--accent); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.primary .stat-icon { background: var(--primary); }
        .stat-card.warning .stat-icon { background: var(--warning); }
        .stat-card.success .stat-icon { background: var(--success); }
        .stat-card.accent .stat-icon { background: var(--accent); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* FILTERS */
        .filters-bar {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
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

        .btn-filter {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-filter.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-filter:hover:not(.active) {
            background: var(--hover);
        }

        /* PAGOS GRID */
        .pagos-grid {
            display: grid;
            gap: 20px;
        }

        .pago-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            display: grid;
            grid-template-columns: 1fr 200px 250px;
            gap: 25px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .pago-card:hover {
            border-color: var(--accent);
            transform: translateX(5px);
        }

        .pago-info h3 {
            font-size: 1.2rem;
            color: var(--text);
            margin-bottom: 8px;
        }

        .pago-codigo {
            color: var(--accent);
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .pago-details {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .pago-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pago-detail-item i {
            width: 16px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge.pendiente { background: rgba(255, 182, 72, 0.15); color: var(--warning); }
        .badge.verificado { background: rgba(46, 212, 122, 0.15); color: var(--success); }

        /* COMPROBANTE PREVIEW */
        .comprobante-preview {
            position: relative;
            width: 200px;
            height: 250px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--border);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .comprobante-preview:hover {
            border-color: var(--accent);
            transform: scale(1.02);
        }

        .comprobante-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .comprobante-preview .view-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .comprobante-preview:hover .view-overlay {
            opacity: 1;
        }

        .view-overlay i {
            font-size: 2rem;
            color: white;
        }

        .sin-comprobante {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .sin-comprobante i {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        /* ACTIONS */
        .pago-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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

        /* IMAGE VIEWER MODAL */
        .image-viewer {
            max-width: 90vw;
            max-height: 90vh;
        }

        .image-viewer img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 8px;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            color: var(--text);
            margin-bottom: 10px;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .pago-card {
                grid-template-columns: 1fr;
            }

            .comprobante-preview {
                width: 100%;
                max-width: 300px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
            <li><a href="verificar_pago.php" class="active"><i class="fas fa-money-check-alt"></i>Verificar Pagos</a></li>
            <li><a href="entrevistas.php"><i class="fas fa-calendar-alt"></i>Entrevistas</a></li>
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
            <h1>Verificación de Pagos</h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span>
                <span>Verificar Pagos</span>
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

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php 
                echo htmlspecialchars($_SESSION['warning']); 
                unset($_SESSION['warning']);
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

        <!-- ESTADÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total con Comprobante</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['pendientes']; ?></div>
                        <div class="stat-label">Pendientes de Verificar</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['verificados']; ?></div>
                        <div class="stat-label">Pagos Verificados</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card accent">
                <div class="stat-header">
                    <div>
                        <div class="stat-value">S/. <?php echo number_format($stats['monto_recaudado'], 2); ?></div>
                        <div class="stat-label">Total Recaudado</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filters-bar">
            <div class="filter-group">
                <a href="?filtro=pendientes" class="btn btn-filter <?php echo $filtroEstado === 'pendientes' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    Pendientes (<?php echo $stats['pendientes']; ?>)
                </a>
                <a href="?filtro=verificados" class="btn btn-filter <?php echo $filtroEstado === 'verificados' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    Verificados (<?php echo $stats['verificados']; ?>)
                </a>
                <a href="?filtro=todos" class="btn btn-filter <?php echo $filtroEstado === 'todos' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    Todos
                </a>
            </div>
        </div>

        <!-- LISTADO DE PAGOS -->
        <div class="pagos-grid">
            <?php if (count($solicitudes) > 0): ?>
                <?php foreach($solicitudes as $solicitud): ?>
                    <div class="pago-card">
                        <!-- INFO -->
                        <div class="pago-info">
                            <h3><?php echo htmlspecialchars($solicitud['nombre_completo']); ?></h3>
                            <div class="pago-codigo">
                                <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($solicitud['codigo_postulante']); ?>
                            </div>
                            <div class="pago-details">
                                <div class="pago-detail-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo htmlspecialchars($solicitud['nivel_postula'] . ' - ' . $solicitud['grado_postula']); ?></span>
                                </div>
                                <div class="pago-detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('d/m/Y', strtotime($solicitud['fecha_registro'])); ?></span>
                                </div>
                                <div class="pago-detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($solicitud['celular_apoderado']); ?></span>
                                </div>
                                <div class="pago-detail-item">
                                    <i class="fas fa-money-bill"></i>
                                    <span>S/. <?php echo number_format($solicitud['monto_pago'], 2); ?></span>
                                </div>
                                <div style="margin-top: 10px;">
                                    <span class="badge <?php echo $solicitud['pago_verificado'] ? 'verificado' : 'pendiente'; ?>">
                                        <?php echo $solicitud['pago_verificado'] ? '✓ Verificado' : '⏳ Pendiente'; ?>
                                    </span>
                                    <?php if ($solicitud['pago_verificado'] && !empty($solicitud['fecha_pago'])): ?>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 5px;">
                                            Verificado: <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_pago'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- COMPROBANTE -->
                        <div class="comprobante-preview" onclick="viewComprobante(<?php echo $solicitud['id']; ?>)">
                            <?php if ($solicitud['tiene_comprobante']): ?>
                                <img src="ver_documento.php?id=<?php echo $solicitud['id']; ?>&tipo=comprobante_pago" 
                                     alt="Comprobante de pago"
                                     onerror="this.parentElement.innerHTML='<div class=\'sin-comprobante\'><i class=\'fas fa-file-image\'></i><span>Error al cargar</span></div>'">
                                <div class="view-overlay">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            <?php else: ?>
                                <div class="sin-comprobante">
                                    <i class="fas fa-file-excel"></i>
                                    <span>Sin comprobante</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- ACCIONES -->
                        <div class="pago-actions">
                            <?php if (!$solicitud['pago_verificado']): ?>
                                <button class="btn btn-success" onclick="openVerificarModal(<?php echo $solicitud['id']; ?>, '<?php echo htmlspecialchars($solicitud['nombre_completo']); ?>', '<?php echo htmlspecialchars($solicitud['codigo_postulante']); ?>')">
                                    <i class="fas fa-check"></i> Verificar Pago
                                </button>
                                <button class="btn btn-danger" onclick="openRechazarModal(<?php echo $solicitud['id']; ?>, '<?php echo htmlspecialchars($solicitud['nombre_completo']); ?>')">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            <?php endif; ?>
                            <a href="ver_solicitud.php?id=<?php echo $solicitud['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No hay pagos para mostrar</h3>
                    <p>
                        <?php 
                        echo match($filtroEstado) {
                            'pendientes' => 'No hay pagos pendientes de verificación',
                            'verificados' => 'No hay pagos verificados aún',
                            default => 'No hay solicitudes con comprobantes de pago'
                        };
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL: VERIFICAR PAGO -->
    <div class="modal" id="modalVerificarPago">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Verificar Pago</h3>
                <button class="btn-close" onclick="closeModal('modalVerificarPago')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="verificar_pago">
                <input type="hidden" name="solicitud_id" id="verificar_solicitud_id">
                
                <div style="padding: 15px; background: var(--secondary); border-radius: 8px; margin-bottom: 20px;">
                    <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 5px;">Postulante:</div>
                    <div style="color: var(--text); font-weight: 600;" id="verificar_nombre"></div>
                    <div style="color: var(--accent); font-size: 0.9rem; margin-top: 3px;" id="verificar_codigo"></div>
                </div>
                
                <div class="form-group">
                    <label>Método de Pago <span style="color: var(--danger);">*</span></label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <option value="Yape">Yape</option>
                        <option value="Plin">Plin</option>
                        <option value="Transferencia">Transferencia Bancaria</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Número de Operación</label>
                    <input type="text" name="numero_operacion" class="form-control" 
                           placeholder="Opcional - número de operación o referencia">
                </div>
                
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3" 
                              placeholder="Observaciones adicionales (opcional)"></textarea>
                </div>
                
                <div style="padding: 15px; background: rgba(46, 212, 122, 0.1); border-radius: 8px; margin-bottom: 20px;">
                    <strong style="color: var(--success);">✓ Confirmar Verificación</strong>
                    <p style="color: var(--text-secondary); margin-top: 8px; font-size: 0.9rem;">
                        Al verificar, el estado cambiará a "Pago Verificado" y se enviará una notificación al postulante.
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

    <!-- MODAL: RECHAZAR PAGO -->
    <div class="modal" id="modalRechazarPago">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rechazar Pago</h3>
                <button class="btn-close" onclick="closeModal('modalRechazarPago')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="rechazar_pago">
                <input type="hidden" name="solicitud_id" id="rechazar_solicitud_id">
                
                <div style="padding: 15px; background: var(--secondary); border-radius: 8px; margin-bottom: 20px;">
                    <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 5px;">Postulante:</div>
                    <div style="color: var(--text); font-weight: 600;" id="rechazar_nombre"></div>
                </div>
                
                <div class="form-group">
                    <label>Motivo del Rechazo <span style="color: var(--danger);">*</span></label>
                    <textarea name="motivo_rechazo" class="form-control" rows="4" required
                              placeholder="Especifique el motivo por el cual se rechaza el comprobante de pago..."></textarea>
                </div>
                
                <div style="padding: 15px; background: rgba(247, 70, 74, 0.1); border-radius: 8px; margin-bottom: 20px;">
                    <strong style="color: var(--danger);">⚠️ Importante</strong>
                    <p style="color: var(--text-secondary); margin-top: 8px; font-size: 0.9rem;">
                        El postulante será notificado sobre el rechazo y deberá enviar un nuevo comprobante válido.
                    </p>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalRechazarPago')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Rechazar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: VER COMPROBANTE -->
    <div class="modal" id="modalViewComprobante">
        <div class="modal-content image-viewer">
            <div class="modal-header">
                <h3>Comprobante de Pago</h3>
                <button class="btn-close" onclick="closeModal('modalViewComprobante')">&times;</button>
            </div>
            <div style="text-align: center;">
                <img id="comprobanteImage" src="" alt="Comprobante" style="max-width: 100%; border-radius: 8px;">
            </div>
        </div>
    </div>

    <script>
        // Abrir modal de verificar
        function openVerificarModal(id, nombre, codigo) {
            document.getElementById('verificar_solicitud_id').value = id;
            document.getElementById('verificar_nombre').textContent = nombre;
            document.getElementById('verificar_codigo').textContent = codigo;
            document.getElementById('modalVerificarPago').classList.add('active');
        }

        // Abrir modal de rechazar
        function openRechazarModal(id, nombre) {
            document.getElementById('rechazar_solicitud_id').value = id;
            document.getElementById('rechazar_nombre').textContent = nombre;
            document.getElementById('modalRechazarPago').classList.add('active');
        }

        // Ver comprobante en modal
        function viewComprobante(id) {
            const img = document.getElementById('comprobanteImage');
            img.src = `ver_documento.php?id=${id}&tipo=comprobante_pago`;
            document.getElementById('modalViewComprobante').classList.add('active');
        }

        // Cerrar modal
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

        // Cerrar modal con ESC
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