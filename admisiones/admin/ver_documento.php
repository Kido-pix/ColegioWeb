<?php
/**
 * Archivo para visualizar documentos desde la base de datos
 * Trinity School - Sistema de Admisiones 2025
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_logueado'])) {
    http_response_code(403);
    die('Acceso no autorizado');
}

require_once '../config/database.php'; 

// Validar parámetros
if (!isset($_GET['id']) || !isset($_GET['tipo'])) {
    http_response_code(400);
    die('Parámetros inválidos');
}

$solicitudId = (int)$_GET['id'];
$tipoDocumento = $_GET['tipo'];

// Tipos de documentos permitidos
$documentosPermitidos = [
    'partida_nacimiento',
    'dni_estudiante_doc',
    'dni_apoderado',
    'libreta_notas',
    'certificado_estudios',
    'foto_estudiante',
    'comprobante_pago'
];

if (!in_array($tipoDocumento, $documentosPermitidos)) {
    http_response_code(400);
    die('Tipo de documento inválido');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener el documento de la base de datos
    $sql = "SELECT 
                {$tipoDocumento} as contenido,
                {$tipoDocumento}_tipo as tipo_mime,
                {$tipoDocumento}_nombre as nombre_archivo
            FROM solicitudes_admision 
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$solicitudId]);
    $documento = $stmt->fetch();
    
    if (!$documento || empty($documento['contenido'])) {
        http_response_code(404);
        die('Documento no encontrado');
    }
    
    // Establecer headers apropiados
    header('Content-Type: ' . $documento['tipo_mime']);
    header('Content-Length: ' . strlen($documento['contenido']));
    
    // Si es PDF, mostrarlo en el navegador. Si es imagen, también.
    // Para descargar en lugar de mostrar, descomentar la siguiente línea:
    // header('Content-Disposition: attachment; filename="' . $documento['nombre_archivo'] . '"');
    
    // Para mostrar en el navegador:
    if (strpos($documento['tipo_mime'], 'image') !== false || 
        strpos($documento['tipo_mime'], 'pdf') !== false) {
        header('Content-Disposition: inline; filename="' . $documento['nombre_archivo'] . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . $documento['nombre_archivo'] . '"');
    }
    
    // Enviar el contenido del archivo
    echo $documento['contenido'];
    
} catch(PDOException $e) {
    http_response_code(500);
    die('Error al recuperar documento: ' . $e->getMessage());
}