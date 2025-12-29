<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        $db = Database::getInstance()->getConnection();
        
        if ($_POST['accion'] === 'marcar_asistencia') {
            $solicitudId = (int)$_POST['solicitud_id'];
            $asistio = $_POST['asistio'];
            
            $stmt = $db->prepare("
                UPDATE solicitudes_admision 
                SET observaciones_entrevista = CONCAT(
                    COALESCE(observaciones_entrevista, ''), 
                    '\n\n[', NOW(), '] Asistencia registrada: ', ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$asistio, $solicitudId]);
            
            $_SESSION['success'] = 'Asistencia registrada correctamente';
        }
        
        header('Location: entrevistas.php' . (isset($_GET['filtro']) ? '?filtro=' . $_GET['filtro'] : ''));
        exit;
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Obtener filtro
$filtroFecha = $_GET['filtro'] ?? 'proximas';

try {
    $db = Database::getInstance()->getConnection();
    
    // Construir query según filtro
    $whereClause = "WHERE estado = 'Entrevista Agendada' AND fecha_entrevista IS NOT NULL";
    
    switch ($filtroFecha) {
        case 'hoy':
            $whereClause .= " AND DATE(fecha_entrevista) = CURDATE()";
            break;
        case 'semana':
            $whereClause .= " AND YEARWEEK(fecha_entrevista, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'proximas':
            $whereClause .= " AND fecha_entrevista >= NOW()";
            break;
        case 'pasadas':
            $whereClause .= " AND fecha_entrevista < NOW()";
            break;
        case 'todas':
            // Sin filtro adicional
            break;
    }
    
    // Obtener entrevistas
    $sql = "SELECT 
                id,
                codigo_postulante,
                CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
                nivel_postula,
                grado_postula,
                fecha_entrevista,
                celular_apoderado,
                email_apoderado,
                estado,
                resultado_entrevista
            FROM solicitudes_admision 
            $whereClause
            ORDER BY fecha_entrevista ASC";
    
    $stmt = $db->query($sql);
    $entrevistas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN DATE(fecha_entrevista) = CURDATE() THEN 1 ELSE 0 END) as hoy,
            SUM(CASE WHEN YEARWEEK(fecha_entrevista, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) as semana,
            SUM(CASE WHEN fecha_entrevista >= NOW() THEN 1 ELSE 0 END) as proximas
        FROM solicitudes_admision
        WHERE estado = 'Entrevista Agendada' AND fecha_entrevista IS NOT NULL
    ");
    $stats = $stmt->fetch();
    
} catch(PDOException $e) {
    $error = "Error al cargar entrevistas: " . $e->getMessage();
    $entrevistas = [];
    $stats = ['total' => 0, 'hoy' => 0, 'semana' => 0, 'proximas' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrevistas - Panel Administrativo</title>
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

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            flex-wrap: wrap;
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

        /* ENTREVISTAS GRID */
        .entrevistas-grid {
            display: grid;
            gap: 20px;
        }

        .entrevista-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            display: grid;
            grid-template-columns: 80px 1fr 200px 250px;
            gap: 25px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .entrevista-card:hover {
            border-color: var(--accent);
            transform: translateX(5px);
        }

        .entrevista-fecha {
            background: var(--secondary);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }

        .fecha-dia {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            line-height: 1;
        }

        .fecha-mes {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .fecha-hora {
            font-size: 0.9rem;
            color: var(--text);
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .entrevista-info h3 {
            font-size: 1.2rem;
            color: var(--text);
            margin-bottom: 8px;
        }

        .entrevista-codigo {
            color: var(--accent);
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .entrevista-details {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .entrevista-contacto {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .contacto-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .entrevista-actions {
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

        .btn-secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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

        @media (max-width: 1200px) {
            .entrevista-card {
                grid-template-columns: 1fr;
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
            <li><a href="verificar_pago.php"><i class="fas fa-money-check-alt"></i>Verificar Pagos</a></li>
            <li><a href="entrevistas.php" class="active"><i class="fas fa-calendar-alt"></i>Entrevistas</a></li>
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
            <h1>Gestión de Entrevistas</h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span>
                <span>Entrevistas</span>
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

        <!-- ESTADÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Agendadas</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['hoy']; ?></div>
                        <div class="stat-label">Entrevistas Hoy</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['semana']; ?></div>
                        <div class="stat-label">Esta Semana</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card accent">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['proximas']; ?></div>
                        <div class="stat-label">Próximas</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filters-bar">
            <a href="?filtro=hoy" class="btn btn-filter <?php echo $filtroFecha === 'hoy' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-day"></i>
                Hoy (<?php echo $stats['hoy']; ?>)
            </a>
            <a href="?filtro=semana" class="btn btn-filter <?php echo $filtroFecha === 'semana' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i>
                Esta Semana (<?php echo $stats['semana']; ?>)
            </a>
            <a href="?filtro=proximas" class="btn btn-filter <?php echo $filtroFecha === 'proximas' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                Próximas (<?php echo $stats['proximas']; ?>)
            </a>
            <a href="?filtro=pasadas" class="btn btn-filter <?php echo $filtroFecha === 'pasadas' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                Pasadas
            </a>
            <a href="?filtro=todas" class="btn btn-filter <?php echo $filtroFecha === 'todas' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                Todas
            </a>
        </div>

        <!-- LISTADO DE ENTREVISTAS -->
        <div class="entrevistas-grid">
            <?php if (count($entrevistas) > 0): ?>
                <?php foreach($entrevistas as $entrevista): ?>
                    <?php
                    $fechaObj = new DateTime($entrevista['fecha_entrevista']);
                    $dia = $fechaObj->format('d');
                    $mes = $fechaObj->format('M');
                    $hora = $fechaObj->format('h:i A');
                    ?>
                    <div class="entrevista-card">
                        <!-- FECHA -->
                        <div class="entrevista-fecha">
                            <div class="fecha-dia"><?php echo $dia; ?></div>
                            <div class="fecha-mes"><?php echo $mes; ?></div>
                            <div class="fecha-hora">
                                <i class="fas fa-clock"></i>
                                <?php echo $hora; ?>
                            </div>
                        </div>

                        <!-- INFO -->
                        <div class="entrevista-info">
                            <h3><?php echo htmlspecialchars($entrevista['nombre_completo']); ?></h3>
                            <div class="entrevista-codigo">
                                <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($entrevista['codigo_postulante']); ?>
                            </div>
                            <div class="entrevista-details">
                                <span><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($entrevista['nivel_postula'] . ' - ' . $entrevista['grado_postula']); ?></span>
                            </div>
                        </div>

                        <!-- CONTACTO -->
                        <div class="entrevista-contacto">
                            <div class="contacto-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($entrevista['celular_apoderado']); ?></span>
                            </div>
                            <div class="contacto-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($entrevista['email_apoderado']); ?></span>
                            </div>
                        </div>

                        <!-- ACCIONES -->
                        <div class="entrevista-actions">
                            <a href="ver_solicitud.php?id=<?php echo $entrevista['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>
                            <a href="https://wa.me/51<?php echo $entrevista['celular_apoderado']; ?>?text=Hola,%20le%20recordamos%20su%20entrevista%20en%20Trinity%20School" 
                               class="btn btn-success" target="_blank">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No hay entrevistas</h3>
                    <p>
                        <?php 
                        echo match($filtroFecha) {
                            'hoy' => 'No hay entrevistas programadas para hoy',
                            'semana' => 'No hay entrevistas para esta semana',
                            'proximas' => 'No hay entrevistas próximas agendadas',
                            'pasadas' => 'No hay entrevistas pasadas',
                            default => 'No hay entrevistas agendadas'
                        };
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>