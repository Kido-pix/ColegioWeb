<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_logueado']) || $_SESSION['admin_logueado'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Incluir conexión
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    // Total de solicitudes
    $stmt = $conn->query("SELECT COUNT(*) FROM solicitudes_admision");
    $total = (int)$stmt->fetchColumn();
    
    // Pendientes de pago
    $stmt = $conn->query("SELECT COUNT(*) FROM solicitudes_admision WHERE estado = 'Pendiente Pago'");
    $pendientes = (int)$stmt->fetchColumn();
    
    // Pagos verificados
    $stmt = $conn->query("SELECT COUNT(*) FROM solicitudes_admision WHERE estado = 'Pago Verificado'");
    $verificados = (int)$stmt->fetchColumn();
    
    // Admitidos
    $stmt = $conn->query("SELECT COUNT(*) FROM solicitudes_admision WHERE estado = 'Admitido'");
    $admitidos = (int)$stmt->fetchColumn();
    
    // Solicitudes de hoy
    $stmt = $conn->query("SELECT COUNT(*) FROM solicitudes_admision WHERE DATE(fecha_registro) = CURDATE()");
    $hoy = (int)$stmt->fetchColumn();
    
    // Solicitudes de esta semana
    $stmt = $conn->query("SELECT COUNT(*) FROM solicitudes_admision WHERE YEARWEEK(fecha_registro, 1) = YEARWEEK(CURDATE(), 1)");
    $semana = (int)$stmt->fetchColumn();
    
    // Respuesta JSON
    echo json_encode([
        'success' => true,
        'total' => $total,
        'pendientes' => $pendientes,
        'verificados' => $verificados,
        'admitidos' => $admitidos,
        'hoy' => $hoy,
        'semana' => $semana,
        'timestamp' => time()
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener estadísticas',
        'message' => $e->getMessage()
    ]);
}
?>