<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_logueado'])) {
    http_response_code(403);
    die('Acceso denegado');
}

// Verificar parámetros
if (!isset($_GET['id']) || !isset($_GET['tipo'])) {
    http_response_code(400);
    die('Parámetros inválidos');
}

$solicitudId = (int)$_GET['id'];
$tipoDocumento = $_GET['tipo'];
$descargar = isset($_GET['download']) && $_GET['download'] == 1;

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

// Conectar a BD
require_once '../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener RUTA del documento (no el contenido)
    $stmt = $db->prepare("SELECT {$tipoDocumento} FROM solicitudes_admision WHERE id = ?");
    $stmt->execute([$solicitudId]);
    $resultado = $stmt->fetch();
    
    if (!$resultado || empty($resultado[$tipoDocumento])) {
        http_response_code(404);
        die('Documento no encontrado en la base de datos');
    }
    
    // La ruta está guardada como: uploads/nombrearchivo.pdf
    $rutaRelativa = $resultado[$tipoDocumento];
    
    // Construir ruta absoluta desde admin/
    $rutaArchivo = '../' . $rutaRelativa;
    
    // Verificar que el archivo existe físicamente
    if (!file_exists($rutaArchivo)) {
        http_response_code(404);
        die('Archivo no encontrado: ' . htmlspecialchars($rutaRelativa));
    }
    
    // Obtener tipo MIME real del archivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $rutaArchivo);
    finfo_close($finfo);
    
    // Obtener nombre del archivo
    $nombreArchivo = basename($rutaArchivo);
    
    // Configurar headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($rutaArchivo));
    
    if ($descargar) {
        // Forzar descarga
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    } else {
        // Mostrar en navegador (PDFs e imágenes)
        header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
    }
    
    // Prevenir caché
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Limpiar buffer de salida
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Enviar archivo
    readfile($rutaArchivo);
    exit;
    
} catch(Exception $e) {
    http_response_code(500);
    error_log('Error en ver_documento.php: ' . $e->getMessage());
    die('Error al cargar documento');
}
?>