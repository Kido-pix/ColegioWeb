<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Procesar exportación
if (isset($_GET['exportar'])) {
    try {
        $db = Database::getInstance()->getConnection();
        $tipoReporte = $_GET['exportar'];
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Estilos para encabezados
        $estiloEncabezado = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1B4B5A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        
        switch ($tipoReporte) {
            case 'todas':
                $sheet->setTitle('Todas las Solicitudes');
                
                // Encabezados
                $headers = ['Código', 'Nombre Completo', 'DNI', 'Nivel', 'Grado', 'Estado', 
                           'Fecha Registro', 'Celular', 'Email', 'Pago Verificado'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->applyFromArray($estiloEncabezado);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $col++;
                }
                
                // Datos
                $stmt = $db->query("
                    SELECT 
                        codigo_postulante,
                        CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
                        dni_estudiante,
                        nivel_postula,
                        grado_postula,
                        estado,
                        fecha_registro,
                        celular_apoderado,
                        email_apoderado,
                        IF(pago_verificado = 1, 'Sí', 'No') as pago_verificado
                    FROM solicitudes_admision
                    ORDER BY fecha_registro DESC
                ");
                
                $row = 2;
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $sheet->setCellValue('A' . $row, $data['codigo_postulante']);
                    $sheet->setCellValue('B' . $row, $data['nombre_completo']);
                    $sheet->setCellValue('C' . $row, $data['dni_estudiante']);
                    $sheet->setCellValue('D' . $row, $data['nivel_postula']);
                    $sheet->setCellValue('E' . $row, $data['grado_postula']);
                    $sheet->setCellValue('F' . $row, $data['estado']);
                    $sheet->setCellValue('G' . $row, date('d/m/Y H:i', strtotime($data['fecha_registro'])));
                    $sheet->setCellValue('H' . $row, $data['celular_apoderado']);
                    $sheet->setCellValue('I' . $row, $data['email_apoderado']);
                    $sheet->setCellValue('J' . $row, $data['pago_verificado']);
                    $row++;
                }
                
                $filename = 'Solicitudes_Todas_' . date('Y-m-d') . '.xlsx';
                break;
                
            case 'pagos':
                $sheet->setTitle('Pagos Verificados');
                
                // Encabezados
                $headers = ['Código', 'Nombre Completo', 'Nivel', 'Grado', 'Método Pago', 
                           'Número Operación', 'Fecha Pago', 'Monto'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->applyFromArray($estiloEncabezado);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $col++;
                }
                
                // Datos
                $stmt = $db->query("
                    SELECT 
                        codigo_postulante,
                        CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
                        nivel_postula,
                        grado_postula,
                        metodo_pago,
                        numero_operacion,
                        fecha_pago,
                        monto_pago
                    FROM solicitudes_admision
                    WHERE pago_verificado = 1
                    ORDER BY fecha_pago DESC
                ");
                
                $row = 2;
                $totalMonto = 0;
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $sheet->setCellValue('A' . $row, $data['codigo_postulante']);
                    $sheet->setCellValue('B' . $row, $data['nombre_completo']);
                    $sheet->setCellValue('C' . $row, $data['nivel_postula']);
                    $sheet->setCellValue('D' . $row, $data['grado_postula']);
                    $sheet->setCellValue('E' . $row, $data['metodo_pago']);
                    $sheet->setCellValue('F' . $row, $data['numero_operacion']);
                    $sheet->setCellValue('G' . $row, date('d/m/Y H:i', strtotime($data['fecha_pago'])));
                    $sheet->setCellValue('H' . $row, 'S/. ' . number_format($data['monto_pago'], 2));
                    $totalMonto += $data['monto_pago'];
                    $row++;
                }
                
                // Total
                $sheet->setCellValue('G' . $row, 'TOTAL:');
                $sheet->setCellValue('H' . $row, 'S/. ' . number_format($totalMonto, 2));
                $sheet->getStyle('G' . $row . ':H' . $row)->getFont()->setBold(true);
                
                $filename = 'Pagos_Verificados_' . date('Y-m-d') . '.xlsx';
                break;
                
            case 'entrevistas':
                $sheet->setTitle('Entrevistas');
                
                // Encabezados
                $headers = ['Código', 'Nombre Completo', 'Nivel', 'Grado', 'Fecha Entrevista', 
                           'Celular', 'Email', 'Estado'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->applyFromArray($estiloEncabezado);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $col++;
                }
                
                // Datos
                $stmt = $db->query("
                    SELECT 
                        codigo_postulante,
                        CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
                        nivel_postula,
                        grado_postula,
                        fecha_entrevista,
                        celular_apoderado,
                        email_apoderado,
                        estado
                    FROM solicitudes_admision
                    WHERE fecha_entrevista IS NOT NULL
                    ORDER BY fecha_entrevista ASC
                ");
                
                $row = 2;
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $sheet->setCellValue('A' . $row, $data['codigo_postulante']);
                    $sheet->setCellValue('B' . $row, $data['nombre_completo']);
                    $sheet->setCellValue('C' . $row, $data['nivel_postula']);
                    $sheet->setCellValue('D' . $row, $data['grado_postula']);
                    $sheet->setCellValue('E' . $row, date('d/m/Y H:i', strtotime($data['fecha_entrevista'])));
                    $sheet->setCellValue('F' . $row, $data['celular_apoderado']);
                    $sheet->setCellValue('G' . $row, $data['email_apoderado']);
                    $sheet->setCellValue('H' . $row, $data['estado']);
                    $row++;
                }
                
                $filename = 'Entrevistas_' . date('Y-m-d') . '.xlsx';
                break;
                
            case 'admitidos':
                $sheet->setTitle('Admitidos');
                
                // Encabezados
                $headers = ['Código', 'Nombre Completo', 'DNI', 'Nivel', 'Grado', 
                           'Celular', 'Email', 'Fecha Admisión'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->applyFromArray($estiloEncabezado);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $col++;
                }
                
                // Datos
                $stmt = $db->query("
                    SELECT 
                        codigo_postulante,
                        CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
                        dni_estudiante,
                        nivel_postula,
                        grado_postula,
                        celular_apoderado,
                        email_apoderado,
                        fecha_registro
                    FROM solicitudes_admision
                    WHERE estado = 'Admitido'
                    ORDER BY fecha_registro DESC
                ");
                
                $row = 2;
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $sheet->setCellValue('A' . $row, $data['codigo_postulante']);
                    $sheet->setCellValue('B' . $row, $data['nombre_completo']);
                    $sheet->setCellValue('C' . $row, $data['dni_estudiante']);
                    $sheet->setCellValue('D' . $row, $data['nivel_postula']);
                    $sheet->setCellValue('E' . $row, $data['grado_postula']);
                    $sheet->setCellValue('F' . $row, $data['celular_apoderado']);
                    $sheet->setCellValue('G' . $row, $data['email_apoderado']);
                    $sheet->setCellValue('H' . $row, date('d/m/Y', strtotime($data['fecha_registro'])));
                    $row++;
                }
                
                $filename = 'Admitidos_' . date('Y-m-d') . '.xlsx';
                break;
        }
        
        // Descargar archivo
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al generar reporte: " . $e->getMessage();
        header('Location: reportes.php');
        exit;
    }
}

// Obtener estadísticas
try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'Pendiente Pago' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN pago_verificado = 1 THEN 1 ELSE 0 END) as pagos_verificados,
            SUM(CASE WHEN estado = 'Entrevista Agendada' THEN 1 ELSE 0 END) as entrevistas,
            SUM(CASE WHEN estado = 'Admitido' THEN 1 ELSE 0 END) as admitidos,
            SUM(CASE WHEN pago_verificado = 1 THEN monto_pago ELSE 0 END) as total_recaudado
        FROM solicitudes_admision
    ");
    $stats = $stmt->fetch();
    
} catch(PDOException $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
    $stats = ['total' => 0, 'pendientes' => 0, 'pagos_verificados' => 0, 'entrevistas' => 0, 'admitidos' => 0, 'total_recaudado' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Panel Administrativo</title>
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

        .alert-error {
            background: rgba(247, 70, 74, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
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
        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
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
        .stat-card.success .stat-icon { background: var(--success); }
        .stat-card.warning .stat-icon { background: var(--warning); }
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

        /* REPORTES GRID */
        .reportes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .reporte-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .reporte-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .reporte-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .reporte-card.primary .reporte-icon { background: var(--primary); }
        .reporte-card.success .reporte-icon { background: var(--success); }
        .reporte-card.warning .reporte-icon { background: var(--warning); }
        .reporte-card.accent .reporte-icon { background: var(--accent); }

        .reporte-card h3 {
            font-size: 1.3rem;
            color: var(--text);
            margin-bottom: 10px;
        }

        .reporte-card p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .btn-exportar {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            background: var(--success);
            color: white;
        }

        .btn-exportar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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

            .reportes-grid {
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
            <li><a href="entrevistas.php"><i class="fas fa-calendar-alt"></i>Entrevistas</a></li>
            <li><a href="reportes.php" class="active"><i class="fas fa-chart-bar"></i>Reportes</a></li>
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
            <h1>Reportes y Estadísticas</h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span>
                <span>Reportes</span>
            </div>
        </div>

        <!-- ALERTAS -->
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
                        <div class="stat-label">Total Solicitudes</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['pagos_verificados']; ?></div>
                        <div class="stat-label">Pagos Verificados</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['entrevistas']; ?></div>
                        <div class="stat-label">Entrevistas Agendadas</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card accent">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['admitidos']; ?></div>
                        <div class="stat-label">Admitidos</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card success" style="grid-column: 1 / -1;">
                <div class="stat-header">
                    <div>
                        <div class="stat-value">S/. <?php echo number_format($stats['total_recaudado'], 2); ?></div>
                        <div class="stat-label">Total Recaudado</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- TÍTULO REPORTES -->
        <h2 style="font-size: 1.5rem; color: var(--text); margin: 40px 0 20px 0;">
            <i class="fas fa-download"></i> Exportar Reportes a Excel
        </h2>

        <!-- REPORTES DISPONIBLES -->
        <div class="reportes-grid">
            <!-- Reporte: Todas las Solicitudes -->
            <div class="reporte-card primary">
                <div class="reporte-icon">
                    <i class="fas fa-file-excel"></i>
                </div>
                <h3>Todas las Solicitudes</h3>
                <p>Exporta todas las solicitudes con información completa de postulantes</p>
                <a href="?exportar=todas" class="btn-exportar">
                    <i class="fas fa-download"></i> Descargar Excel
                </a>
            </div>

            <!-- Reporte: Pagos Verificados -->
            <div class="reporte-card success">
                <div class="reporte-icon">
                    <i class="fas fa-money-check-alt"></i>
                </div>
                <h3>Pagos Verificados</h3>
                <p>Lista de todos los pagos verificados con métodos y montos</p>
                <a href="?exportar=pagos" class="btn-exportar">
                    <i class="fas fa-download"></i> Descargar Excel
                </a>
            </div>

            <!-- Reporte: Entrevistas -->
            <div class="reporte-card warning">
                <div class="reporte-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Entrevistas Agendadas</h3>
                <p>Calendario completo de entrevistas con datos de contacto</p>
                <a href="?exportar=entrevistas" class="btn-exportar">
                    <i class="fas fa-download"></i> Descargar Excel
                </a>
            </div>

            <!-- Reporte: Admitidos -->
            <div class="reporte-card accent">
                <div class="reporte-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3>Estudiantes Admitidos</h3>
                <p>Listado completo de estudiantes admitidos para matrícula</p>
                <a href="?exportar=admitidos" class="btn-exportar">
                    <i class="fas fa-download"></i> Descargar Excel
                </a>
            </div>
        </div>
    </main>
</body>
</html>