<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

// Parámetros de paginación y filtros
$registrosPorPagina = 15;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Filtros
$filtroNivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtroBusqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

try {
    $db = Database::getInstance()->getConnection();
    
    // Construir query con filtros
    $whereConditions = [];
    $params = [];
    
    if (!empty($filtroNivel)) {
$whereConditions[] = "nivel_postula = ?";
        $params[] = $filtroNivel;
    }
    
    if (!empty($filtroEstado)) {
        $whereConditions[] = "estado = ?";
        $params[] = $filtroEstado;
    }
    
    if (!empty($filtroBusqueda)) {
        $whereConditions[] = "(codigo_postulante LIKE ? OR CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) LIKE ? OR dni_estudiante LIKE ?)";
        $busquedaParam = "%{$filtroBusqueda}%";
        $params[] = $busquedaParam;
        $params[] = $busquedaParam;
        $params[] = $busquedaParam;
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Contar total de registros
    $sqlCount = "SELECT COUNT(*) as total FROM solicitudes_admision $whereClause";
    $stmtCount = $db->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRegistros = $stmtCount->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $registrosPorPagina);
    
    // Obtener solicitudes
    $sql = "SELECT 
                id,
                codigo_postulante,
                CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
                dni_estudiante,
                nivel_postula as nivel_educativo,
                grado_postula as grado,
                estado,
                fecha_registro,
                email_apoderado,
                celular_apoderado
            FROM solicitudes_admision 
            $whereClause
            ORDER BY fecha_registro DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($sql);
    $params[] = $registrosPorPagina;
    $params[] = $offset;
    $stmt->execute($params);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Asegurar que $solicitudes siempre sea un array
    if (!is_array($solicitudes)) {
        $solicitudes = [];
    }
    
} catch(PDOException $e) {
    $error = "Error al cargar solicitudes: " . $e->getMessage();
    $solicitudes = [];
    $totalRegistros = 0;
    $totalPaginas = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes - Panel Administrativo</title>
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
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
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

        /* FILTERS */
        .filters-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            padding: 12px 15px;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            background: var(--secondary);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .filters-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 24px;
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
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #2A5A6A;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--hover);
        }

        /* STATS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-mini {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-mini-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .stat-mini-icon.primary { background: rgba(27, 75, 90, 0.2); color: var(--primary); }
        .stat-mini-icon.warning { background: rgba(255, 182, 72, 0.2); color: var(--warning); }
        .stat-mini-icon.success { background: rgba(46, 212, 122, 0.2); color: var(--success); }

        .stat-mini-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2px;
        }

        .stat-mini-content p {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* TABLE */
        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
        }

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
            padding: 15px 20px;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tbody td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            color: var(--text);
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: var(--hover);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge.pendiente { background: rgba(255, 182, 72, 0.15); color: var(--warning); }
        .badge.verificado { background: rgba(58, 175, 169, 0.15); color: var(--accent); }
        .badge.agendado { background: rgba(46, 212, 122, 0.15); color: var(--success); }
        .badge.admitido { background: rgba(46, 212, 122, 0.15); color: var(--success); }
        .badge.rechazado { background: rgba(247, 70, 74, 0.15); color: var(--danger); }

        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon:hover {
            background: var(--hover);
            color: var(--accent);
            border-color: var(--accent);
        }

        /* PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 25px;
            border-top: 1px solid var(--border);
        }

        .pagination a,
        .pagination span {
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background: var(--hover);
            border-color: var(--accent);
        }

        .pagination .active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            color: var(--text);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-secondary);
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

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.85rem;
            }

            thead th,
            tbody td {
                padding: 12px;
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
        <div class="page-header">
            <h1>Solicitudes de Admisión</h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span>
                <span>Solicitudes</span>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-icon primary">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-mini-content">
                    <h3><?php echo $totalRegistros; ?></h3>
                    <p>Total Solicitudes</p>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-mini-content">
                    <h3><?php 
                        try {
                            $stmt = $db->query("SELECT COUNT(*) as total FROM solicitudes_admision WHERE estado = 'Pendiente Pago'");
                            echo $stmt->fetch()['total'];
                        } catch(PDOException $e) {
                            echo '0';
                        }
                    ?></h3>
                    <p>Pendientes</p>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-mini-content">
                    <h3><?php 
                        try {
                            $stmt = $db->query("SELECT COUNT(*) as total FROM solicitudes_admision WHERE estado = 'Admitido'");
                            echo $stmt->fetch()['total'];
                        } catch(PDOException $e) {
                            echo '0';
                        }
                    ?></h3>
                    <p>Admitidos</p>
                </div>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="filters-card">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>Buscar</label>
                        <input type="text" name="busqueda" class="form-control" 
                               placeholder="Código, nombre o DNI..." 
                               value="<?php echo htmlspecialchars($filtroBusqueda); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nivel</label>
                        <select name="nivel" class="form-control">
                            <option value="">Todos los niveles</option>
                            <option value="Inicial" <?php echo $filtroNivel === 'Inicial' ? 'selected' : ''; ?>>Inicial</option>
                            <option value="Primaria" <?php echo $filtroNivel === 'Primaria' ? 'selected' : ''; ?>>Primaria</option>
                            <option value="Secundaria" <?php echo $filtroNivel === 'Secundaria' ? 'selected' : ''; ?>>Secundaria</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="">Todos los estados</option>
                            <option value="Pendiente Pago" <?php echo $filtroEstado === 'Pendiente Pago' ? 'selected' : ''; ?>>Pendiente Pago</option>
                            <option value="Pago Verificado" <?php echo $filtroEstado === 'Pago Verificado' ? 'selected' : ''; ?>>Pago Verificado</option>
                            <option value="Entrevista Agendada" <?php echo $filtroEstado === 'Entrevista Agendada' ? 'selected' : ''; ?>>Entrevista Agendada</option>
                            <option value="Admitido" <?php echo $filtroEstado === 'Admitido' ? 'selected' : ''; ?>>Admitido</option>
                            <option value="Rechazado" <?php echo $filtroEstado === 'Rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                </div>
                <div class="filters-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="solicitudes.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-header">
                <h3>Listado de Solicitudes (<?php echo $totalRegistros; ?>)</h3>
            </div>
            <div class="table-container">
                <?php if (count($solicitudes) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Postulante</th>
                                <th>DNI</th>
                                <th>Nivel</th>
                                <th>Grado</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($solicitudes as $solicitud): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($solicitud['codigo_postulante']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($solicitud['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($solicitud['dni_estudiante']); ?></td>
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
                                    <td>
                                        <a href="ver_solicitud.php?id=<?php echo $solicitud['id']; ?>" 
                                           class="btn-icon" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- PAGINATION -->
                    <?php if ($totalPaginas > 1): ?>
                        <div class="pagination">
                            <?php if ($paginaActual > 1): ?>
                                <a href="?pagina=<?php echo $paginaActual - 1; ?>&nivel=<?php echo $filtroNivel; ?>&estado=<?php echo $filtroEstado; ?>&busqueda=<?php echo $filtroBusqueda; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <?php if ($i == $paginaActual): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?pagina=<?php echo $i; ?>&nivel=<?php echo $filtroNivel; ?>&estado=<?php echo $filtroEstado; ?>&busqueda=<?php echo $filtroBusqueda; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($paginaActual < $totalPaginas): ?>
                                <a href="?pagina=<?php echo $paginaActual + 1; ?>&nivel=<?php echo $filtroNivel; ?>&estado=<?php echo $filtroEstado; ?>&busqueda=<?php echo $filtroBusqueda; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No se encontraron solicitudes</h3>
                        <p>No hay solicitudes que coincidan con los filtros aplicados</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>