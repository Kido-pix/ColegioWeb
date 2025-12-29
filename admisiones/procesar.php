<?php
// ============================================
// PROCESAMIENTO DE SOLICITUD DE ADMISIÓN
// Trinity School - Sistema de Admisiones 2025
// Versión 3.0 - PDO Completo
// ============================================

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

session_start();

require_once 'config/database.php';

try {
    $conn = Database::getInstance()->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // VALIDAR CAMPOS REQUERIDOS
    $campos_requeridos = [
        'nombres', 'apellido_paterno', 'apellido_materno',
        'dni_estudiante', 'fecha_nacimiento', 'sexo',
        'direccion', 'distrito',
        'nivel_postula', 'grado_postula',
        'apoderado_principal', 'celular_apoderado', 'email_apoderado'
    ];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            throw new Exception("El campo {$campo} es obligatorio");
        }
    }
    // ============================================
    // VALIDAR DNI DUPLICADO
    // ============================================
    $stmt = $conn->prepare("
        SELECT 
            codigo_postulante,
            CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo,
            estado,
            fecha_registro
        FROM solicitudes_admision 
        WHERE dni_estudiante = ?
    ");
    $stmt->execute([$_POST['dni_estudiante']]);
    $solicitud_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($solicitud_existente) {
        throw new Exception(
            "El DNI {$_POST['dni_estudiante']} ya está registrado. " .
            "Solicitud: {$solicitud_existente['codigo_postulante']} - " .
            "{$solicitud_existente['nombre_completo']} - " .
            "Estado: {$solicitud_existente['estado']} - " .
            "Registrado: " . date('d/m/Y', strtotime($solicitud_existente['fecha_registro']))
        );
    }
    // ============================================
    // GENERAR CÓDIGO ÚNICO
    // ============================================
    $anio = date('Y');
    $nivel_codigo = substr($_POST['nivel_postula'], 0, 1);
    
    do {
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $codigo_postulante = "ADM{$anio}{$nivel_codigo}{$random}";
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM solicitudes_admision WHERE codigo_postulante = ?");
        $stmt->execute([$codigo_postulante]);
        $existe = $stmt->fetchColumn();
    } while ($existe > 0);
    
    // ============================================
    // PROCESAR ARCHIVOS
    // ============================================
    $directorio_uploads = __DIR__ . '/uploads/';
    
    if (!file_exists($directorio_uploads)) {
        mkdir($directorio_uploads, 0755, true);
    }
    
    function procesarArchivo($nombre_campo, $codigo_postulante, $directorio_uploads, $requerido = true) {
        if (!isset($_FILES[$nombre_campo]) || $_FILES[$nombre_campo]['error'] === UPLOAD_ERR_NO_FILE) {
            if ($requerido) {
                throw new Exception("El archivo {$nombre_campo} es obligatorio");
            }
            return null;
        }
        
        $archivo = $_FILES[$nombre_campo];
        
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir {$nombre_campo}");
        }
        
        if ($archivo['size'] > 5242880) {
            throw new Exception("El archivo {$nombre_campo} excede 5MB");
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        $tipos_permitidos = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($mime_type, $tipos_permitidos)) {
            throw new Exception("El archivo {$nombre_campo} debe ser PDF, JPG o PNG");
        }
        
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombre_archivo = $codigo_postulante . '_' . $nombre_campo . '_' . time() . '.' . $extension;
        $ruta_destino = $directorio_uploads . $nombre_archivo;
        
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            throw new Exception("Error al guardar {$nombre_campo}");
        }
        
        return 'uploads/' . $nombre_archivo;
    }
    
    // Procesar archivos obligatorios
    $partida = procesarArchivo('partida_nacimiento', $codigo_postulante, $directorio_uploads, true);
    $dni_est = procesarArchivo('dni_estudiante_doc', $codigo_postulante, $directorio_uploads, true);
    $dni_apo = procesarArchivo('dni_apoderado', $codigo_postulante, $directorio_uploads, true);
    $libreta = procesarArchivo('libreta_notas', $codigo_postulante, $directorio_uploads, true);
    $certif = procesarArchivo('certificado_estudios', $codigo_postulante, $directorio_uploads, true);
    $compro = procesarArchivo('comprobante_pago', $codigo_postulante, $directorio_uploads, true);
    $foto = procesarArchivo('foto_estudiante', $codigo_postulante, $directorio_uploads, false);
    

    // INSERTAR EN BASE DE DATOS
    $sql = "INSERT INTO solicitudes_admision (
        codigo_postulante,
        nombres, apellido_paterno, apellido_materno,
        dni_estudiante, fecha_nacimiento, sexo,
        direccion, distrito,
        nivel_postula, grado_postula,
        colegio_procedencia,
        nombre_padre, ocupacion_padre, celular_padre, email_padre,
        nombre_madre, ocupacion_madre, celular_madre, email_madre,
        apoderado_principal, celular_apoderado, email_apoderado,
        nombre_apoderado, parentesco_apoderado, dni_apoderado_otro,
        tiene_hermanos, nombres_hermanos,
        necesidades_especiales, descripcion_necesidades,
        partida_nacimiento, dni_estudiante_doc, dni_apoderado,
        libreta_notas, certificado_estudios, foto_estudiante,
        comprobante_pago,
        fecha_registro, estado
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, NOW(), 'Pendiente Pago'
    )";
    
    $stmt = $conn->prepare($sql);
    
    $stmt->execute([
        $codigo_postulante,
        $_POST['nombres'],
        $_POST['apellido_paterno'],
        $_POST['apellido_materno'],
        $_POST['dni_estudiante'],
        $_POST['fecha_nacimiento'],
        $_POST['sexo'],
        $_POST['direccion'],
        $_POST['distrito'],
        $_POST['nivel_postula'],
        $_POST['grado_postula'],
        $_POST['colegio_procedencia'] ?? '',
        $_POST['nombre_padre'] ?? '',
        $_POST['ocupacion_padre'] ?? '',
        $_POST['celular_padre'] ?? '',
        $_POST['email_padre'] ?? '',
        $_POST['nombre_madre'] ?? '',
        $_POST['ocupacion_madre'] ?? '',
        $_POST['celular_madre'] ?? '',
        $_POST['email_madre'] ?? '',
        $_POST['apoderado_principal'],
        $_POST['celular_apoderado'],
        $_POST['email_apoderado'],
        $_POST['nombre_apoderado'] ?? '',
        $_POST['parentesco_apoderado'] ?? '',
        $_POST['dni_apoderado_otro'] ?? '',
        isset($_POST['tiene_hermanos']) ? 1 : 0,
        $_POST['nombres_hermanos'] ?? '',
        isset($_POST['necesidades_especiales']) ? 1 : 0,
        $_POST['descripcion_necesidades'] ?? '',
        $partida,
        $dni_est,
        $dni_apo,
        $libreta,
        $certif,
        $foto,
        $compro
    ]);
    
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Solicitud enviada correctamente',
        'codigo' => $codigo_postulante
    ]);
    
} catch (Exception $e) {
    error_log("Error en procesar.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => $e->getMessage()
    ]);
}
?>