<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header('Location: index.php');
    exit;
}

require_once '../procesar.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Inicializar variables con valores por defecto
    $totalSolicitudes = 0;
    $pendientesPago = 0;
    $pagosVerificados = 0;
    $entrevistasAgendadas = 0;
    $admitidos = 0;
    $rechazados = 0;
    $inicial = 0;
    $primaria = 0;
    $secundaria = 0;
    $solicitudesHoy = 0;
    $solicitudesSemana = 0;
    $ultimasSolicitudes = [];
    
    // Total de solicitudes
    $stmt = $db->query("SELECT COUNT(*) as total FROM solicitudes_admision");
    $result = $stmt->fetch();
    $totalSolicitudes = $result ? (int)$result['total'] : 0;
    
    // Solicitudes por estado
    $stmt = $db->query("
        SELECT 
            estado,
            COUNT(*) as cantidad
        FROM solicitudes_admision
        GROUP BY estado
    ");
    $estadisticas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $pendientesPago = isset($estadisticas['Pendiente Pago']) ? (int)$estadisticas['Pendiente Pago'] : 0;
    $pagosVerificados = isset($estadisticas['Pago Verificado']) ? (int)$estadisticas['Pago Verificado'] : 0;
    $entrevistasAgendadas = isset($estadisticas['Entrevista Agendada']) ? (int)$estadisticas['Entrevista Agendada'] : 0;
    $admitidos = isset($estadisticas['Admitido']) ? (int)$estadisticas['Admitido'] : 0;
    $rechazados = isset($estadisticas['Rechazado']) ? (int)$estadisticas['Rechazado'] : 0;
    
    // Solicitudes por nivel educativo
    $stmt = $db->query("
        SELECT 
            nivel_postula,
            COUNT(*) as cantidad
        FROM solicitudes_admision
        GROUP BY nivel_postula
    ");
    $porNivel = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $inicial = isset($porNivel['Inicial']) ? (int)$porNivel['Inicial'] : 0;
    $primaria = isset($porNivel['Primaria']) ? (int)$porNivel['Primaria'] : 0;
    $secundaria = isset($porNivel['Secundaria']) ? (int)$porNivel['Secundaria'] : 0;
    
    // Últimas 5 solicitudes
    $stmt = $db->query("
        SELECT 
            codigo_postulante,
            CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
            nivel_postula as nivel_educativo,
            grado_postula as grado,
            estado,
            fecha_registro
        FROM solicitudes_admision
        ORDER BY fecha_registro DESC
        LIMIT 5
    ");
    $ultimasSolicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($ultimasSolicitudes)) {
        $ultimasSolicitudes = [];
    }
    
    // Solicitudes de hoy
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM solicitudes_admision 
        WHERE DATE(fecha_registro) = CURDATE()
    ");
    $result = $stmt->fetch();
    $solicitudesHoy = $result ? (int)$result['total'] : 0;
    
    // Solicitudes de esta semana
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM solicitudes_admision 
        WHERE YEARWEEK(fecha_registro, 1) = YEARWEEK(CURDATE(), 1)
    ");
    $result = $stmt->fetch();
    $solicitudesSemana = $result ? (int)$result['total'] : 0;
    
} catch(PDOException $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
    // Mantener valores por defecto en caso de error
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Administrativo Trinity School</title>
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
            padding: 0;
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
            margin-bottom: 10px;
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
            margin-bottom: 2px;
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
            font-size: 1.1rem;
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
            font-size: 1rem;
            color: white;
        }

        .user-details {
            flex: 1;
            overflow: hidden;
        }

        .user-details p {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        }

        .header {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--text);
            margin-bottom: 5px;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
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
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
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
        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.info::before { background: var(--accent); }

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
        .stat-card.success .stat-icon { background: var(--success); }
        .stat-card.warning .stat-icon { background: var(--warning); }
        .stat-card.info .stat-icon { background: var(--accent); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* CONTENT GRID */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 25px;
            border-radius: 12px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .card-header h3 {
            font-size: 1.2rem;
            color: var(--text);
            font-weight: 600;
        }

        .card-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-primary { background: rgba(27, 75, 90, 0.2); color: var(--primary); }
        .badge-success { background: rgba(46, 212, 122, 0.2); color: var(--success); }

        /* PROGRESS BARS */
        .progress-item {
            margin-bottom: 20px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .progress-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
        }

        .progress-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--accent);
        }

        .progress-bar-container {
            height: 10px;
            background: var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }

        .progress-bar.inicial { background: linear-gradient(90deg, #3AAFA9, #2ED47A); }
        .progress-bar.primaria { background: linear-gradient(90deg, #1B4B5A, #3AAFA9); }
        .progress-bar.secundaria { background: linear-gradient(90deg, #0A1929, #1B4B5A); }

        /* TABLE */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--secondary);
        }

        thead th {
            padding: 12px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            color: var(--text);
        }

        tbody tr:hover {
            background: var(--hover);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge.pendiente { background: rgba(255, 182, 72, 0.2); color: var(--warning); }
        .badge.verificado { background: rgba(58, 175, 169, 0.2); color: var(--accent); }
        .badge.agendado { background: rgba(58, 175, 169, 0.2); color: var(--accent); }
        .badge.admitido { background: rgba(46, 212, 122, 0.2); color: var(--success); }
        .badge.rechazado { background: rgba(247, 70, 74, 0.2); color: var(--danger); }

        /* QUICK ACTIONS */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-btn {
            padding: 15px 20px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .action-btn:hover {
            border-color: var(--accent);
            transform: translateY(-3px);
            background: var(--hover);
        }

        .action-btn i {
            font-size: 1.2rem;
            color: var(--accent);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* ACTIVITY ITEM */
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-name {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 5px;
        }

        .activity-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
        }

        .activity-time {
            color: var(--text-secondary);
            font-size: 0.75rem;
            margin-top: 5px;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i>Dashboard</a></li>
            <li><a href="solicitudes.php"><i class="fas fa-file-alt"></i>Solicitudes</a></li>
            <li><a href="verificar_pago.php"><i class="fas fa-money-check-alt"></i>Verificar Pagos</a></li>
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
            <form method="POST" action="logout.php" style="margin: 0;">
                <button type="submit" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>👋 Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></h1>
            <p>Resumen del proceso de admisiones 2025</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $totalSolicitudes; ?></div>
                        <div class="stat-label">Total Solicitudes</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="fas fa-calendar-day"></i> <?php echo $solicitudesHoy; ?> hoy
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $pendientesPago; ?></div>
                        <div class="stat-label">Pendientes de Pago</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="fas fa-exclamation-circle"></i> Requieren atención
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $pagosVerificados; ?></div>
                        <div class="stat-label">Pagos Verificados</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="fas fa-chart-line"></i> <?php echo $solicitudesSemana; ?> esta semana
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $admitidos; ?></div>
                        <div class="stat-label">Admitidos</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="fas fa-graduation-cap"></i> Proceso completado
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="solicitudes.php" class="action-btn">
                <i class="fas fa-list"></i>
                Ver Todas las Solicitudes
            </a>
            <a href="verificar_pago.php" class="action-btn">
                <i class="fas fa-money-check-alt"></i>
                Verificar Pagos
            </a>
            <a href="reportes.php" class="action-btn">
                <i class="fas fa-download"></i>
                Generar Reporte
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Chart Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Solicitudes por Nivel Educativo</h3>
                    <span class="card-badge badge-primary">Total: <?php echo $totalSolicitudes; ?></span>
                </div>
                <div class="chart-container">
                    <?php if ($totalSolicitudes > 0): ?>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Inicial</span>
                                <span class="progress-value"><?php echo $inicial; ?> (<?php echo round(($inicial/$totalSolicitudes)*100); ?>%)</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar inicial" style="width: <?php echo ($inicial/$totalSolicitudes)*100; ?>%"></div>
                            </div>
                        </div>

                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Primaria</span>
                                <span class="progress-value"><?php echo $primaria; ?> (<?php echo round(($primaria/$totalSolicitudes)*100); ?>%)</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar primaria" style="width: <?php echo ($primaria/$totalSolicitudes)*100; ?>%"></div>
                            </div>
                        </div>

                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Secundaria</span>
                                <span class="progress-value"><?php echo $secundaria; ?> (<?php echo round(($secundaria/$totalSolicitudes)*100); ?>%)</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar secundaria" style="width: <?php echo ($secundaria/$totalSolicitudes)*100; ?>%"></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No hay solicitudes registradas aún</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Actividad Reciente</h3>
                    <span class="card-badge badge-success"><?php echo $solicitudesHoy; ?> hoy</span>
                </div>
                <div>
                    <?php if (count($ultimasSolicitudes) > 0): ?>
                        <?php foreach($ultimasSolicitudes as $solicitud): ?>
                            <div class="activity-item">
                                <div class="activity-name">
                                    <?php echo htmlspecialchars($solicitud['nombre_completo']); ?>
                                </div>
                                <div class="activity-details">
                                    <span><?php echo $solicitud['nivel_educativo'] . ' - ' . $solicitud['grado']; ?></span>
                                    <span class="badge <?php 
                                        echo match($solicitud['estado']) {
                                            'Pendiente Pago' => 'pendiente',
                                            'Pago Verificado' => 'verificado',
                                            'Entrevista Agendada' => 'agendado',
                                            'Admitido' => 'admitido',
                                            'Rechazado' => 'rechazado',
                                            default => 'pendiente'
                                        };
                                    ?>"><?php echo $solicitud['estado']; ?></span>
                                </div>
                                <div class="activity-time">
                                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_registro'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No hay actividad reciente</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Submissions Table -->
        <div class="card">
            <div class="card-header">
                <h3>Últimas Solicitudes</h3>
                <a href="solicitudes.php" style="color: var(--accent); text-decoration: none; font-size: 0.9rem;">
                    Ver todas <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="table-container">
                <?php if (count($ultimasSolicitudes) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Postulante</th>
                                <th>Nivel</th>
                                <th>Grado</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ultimasSolicitudes as $solicitud): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($solicitud['codigo_postulante']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($solicitud['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($solicitud['nivel_educativo']); ?></td>
                                    <td><?php echo htmlspecialchars($solicitud['grado']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($solicitud['estado']) {
                                                'Pendiente Pago' => 'pendiente',
                                                'Pago Verificado' => 'verificado',
                                                'Entrevista Agendada' => 'agendado',
                                                'Admitido' => 'admitido',
                                                'Rechazado' => 'rechazado',
                                                default => 'pendiente'
                                            };
                                        ?>">
                                            <?php echo htmlspecialchars($solicitud['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_registro'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No hay solicitudes registradas</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Animar barras de progreso
        window.addEventListener('load', function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
</body>
</html>