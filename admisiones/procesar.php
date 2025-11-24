<?php
/**
 * PROCESAR SOLICITUD DE ADMISIÓN - TRINITY SCHOOL
 * Sistema de procesamiento de formularios de admisión
 */

// Clase Database (Singleton Pattern)
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                'mysql:host=localhost;dbname=trinity_admisiones;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Solo procesar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Método no permitido'
    ]);
    exit;
}

// Función para validar y procesar archivos
function procesarArchivo($nombre_campo, $obligatorio = true) {
    if (!isset($_FILES[$nombre_campo]) || $_FILES[$nombre_campo]['error'] === UPLOAD_ERR_NO_FILE) {
        if ($obligatorio) {
            return ['error' => true, 'mensaje' => "El archivo {$nombre_campo} es obligatorio"];
        }
        return ['error' => false, 'contenido' => null, 'tipo' => null, 'nombre' => null];
    }
    
    $archivo = $_FILES[$nombre_campo];
    
    // Validar errores de carga
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['error' => true, 'mensaje' => "Error al subir {$nombre_campo}"];
    }
    
    // Validar tamaño (máximo 5MB)
    if ($archivo['size'] > 5242880) {
        return ['error' => true, 'mensaje' => "El archivo {$nombre_campo} excede el tamaño máximo de 5MB"];
    }
    
    // Validar tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($tipo_mime, $tipos_permitidos)) {
        return ['error' => true, 'mensaje' => "Tipo de archivo no permitido para {$nombre_campo}. Solo PDF, JPG o PNG"];
    }
    
    // Leer contenido del archivo
    $contenido = file_get_contents($archivo['tmp_name']);
    
    return [
        'error' => false,
        'contenido' => $contenido,
        'tipo' => $tipo_mime,
        'nombre' => $archivo['name']
    ];
}

// Generar código de postulante
function generarCodigoPostulante($db, $nivel) {
    try {
        // Intentar usar el procedimiento almacenado
        $stmt = $db->prepare("CALL generar_codigo_postulante(?, @codigo)");
        $stmt->execute([$nivel]);
        $result = $db->query("SELECT @codigo as codigo")->fetch();
        
        if ($result && !empty($result['codigo'])) {
            return $result['codigo'];
        }
    } catch (Exception $e) {
        // Si falla el procedimiento, generar manualmente
    }
    
    // Generar código manualmente
    $anio = date('Y');
    $prefijo = match($nivel) {
        'Inicial' => 'INI',
        'Primaria' => 'PRI',
        'Secundaria' => 'SEC',
        default => 'GEN'
    };
    
    // Obtener último correlativo
    $stmt = $db->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING(codigo_postulante, 8) AS UNSIGNED)), 0) + 1 as siguiente
        FROM solicitudes_admision
        WHERE codigo_postulante LIKE ?
    ");
    $stmt->execute([$anio . $prefijo . '%']);
    $result = $stmt->fetch();
    $correlativo = $result['siguiente'];
    
    return $anio . $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
}

try {
    // Obtener conexión a la base de datos
    $db = Database::getInstance()->getConnection();
    
    // Validar campos obligatorios
    $campos_obligatorios = [
        'nombres', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento', 
        'dni_estudiante', 'sexo', 'direccion', 'distrito',
        'nivel_postula', 'grado_postula',
        'nombre_padre', 'celular_padre',
        'nombre_madre', 'celular_madre',
        'apoderado_principal', 'celular_apoderado', 'email_apoderado'
    ];
    
    $campos_faltantes = [];
    foreach ($campos_obligatorios as $campo) {
        if (empty($_POST[$campo])) {
            $campos_faltantes[] = $campo;
        }
    }
    
    if (!empty($campos_faltantes)) {
        throw new Exception("Faltan campos obligatorios: " . implode(', ', $campos_faltantes));
    }
    
    // Procesar archivos OBLIGATORIOS
    $archivos_obligatorios = [
        'partida_nacimiento',
        'dni_estudiante_doc',
        'dni_apoderado',
        'libreta_notas',
        'certificado_estudios',
        'comprobante_pago'
    ];
    
    // Procesar archivos OPCIONALES
    $archivos_opcionales = [
        'foto_estudiante'
    ];
    
    $archivos_procesados = [];
    
    // Procesar archivos obligatorios
    foreach ($archivos_obligatorios as $archivo) {
        $resultado = procesarArchivo($archivo, true);
        if ($resultado['error']) {
            throw new Exception($resultado['mensaje']);
        }
        $archivos_procesados[$archivo] = $resultado;
    }
    
    // Procesar archivos opcionales
    foreach ($archivos_opcionales as $archivo) {
        $resultado = procesarArchivo($archivo, false);
        if ($resultado['error']) {
            throw new Exception($resultado['mensaje']);
        }
        $archivos_procesados[$archivo] = $resultado;
    }
    
    // Generar código de postulante
    $codigo_postulante = generarCodigoPostulante($db, $_POST['nivel_postula']);
    
    // Preparar datos para inserción
    $datos = [
        'codigo_postulante' => $codigo_postulante,
        
        // Datos del Estudiante
        'nombres' => trim($_POST['nombres']),
        'apellido_paterno' => trim($_POST['apellido_paterno']),
        'apellido_materno' => trim($_POST['apellido_materno']),
        'fecha_nacimiento' => $_POST['fecha_nacimiento'],
        'dni_estudiante' => $_POST['dni_estudiante'],
        'sexo' => $_POST['sexo'],
        'lugar_nacimiento' => $_POST['lugar_nacimiento'] ?? null,
        
        // Dirección
        'direccion' => trim($_POST['direccion']),
        'distrito' => trim($_POST['distrito']),
        'provincia' => $_POST['provincia'] ?? 'Chincha',
        
        // Información Académica
        'nivel_postula' => $_POST['nivel_postula'],
        'grado_postula' => $_POST['grado_postula'],
        'colegio_procedencia' => $_POST['colegio_procedencia'] ?? null,
        'tipo_colegio' => $_POST['tipo_colegio'] ?? null,
        'promedio_anterior' => !empty($_POST['promedio_anterior']) ? floatval($_POST['promedio_anterior']) : null,
        
        // Datos del Padre
        'nombre_padre' => trim($_POST['nombre_padre']),
        'dni_padre' => $_POST['dni_padre'] ?? null,
        'celular_padre' => $_POST['celular_padre'],
        'email_padre' => $_POST['email_padre'] ?? null,
        'ocupacion_padre' => $_POST['ocupacion_padre'] ?? null,
        
        // Datos de la Madre
        'nombre_madre' => trim($_POST['nombre_madre']),
        'dni_madre' => $_POST['dni_madre'] ?? null,
        'celular_madre' => $_POST['celular_madre'],
        'email_madre' => $_POST['email_madre'] ?? null,
        'ocupacion_madre' => $_POST['ocupacion_madre'] ?? null,
        
        // Apoderado Principal
        'apoderado_principal' => $_POST['apoderado_principal'],
        'nombre_apoderado' => $_POST['nombre_apoderado'] ?? null,
        'parentesco_apoderado' => $_POST['parentesco_apoderado'] ?? null,
        'celular_apoderado' => $_POST['celular_apoderado'],
        'email_apoderado' => $_POST['email_apoderado'],
        
        // Información Adicional
        'tiene_hermanos' => isset($_POST['tiene_hermanos']) ? 1 : 0,
        'nombres_hermanos' => $_POST['nombres_hermanos'] ?? null,
        'necesidades_especiales' => isset($_POST['necesidades_especiales']) ? 1 : 0,
        'descripcion_necesidades' => $_POST['descripcion_necesidades'] ?? null,
        
        // Documentos
        'partida_nacimiento' => $archivos_procesados['partida_nacimiento']['contenido'],
        'partida_nacimiento_tipo' => $archivos_procesados['partida_nacimiento']['tipo'],
        'partida_nacimiento_nombre' => $archivos_procesados['partida_nacimiento']['nombre'],
        
        'dni_estudiante_doc' => $archivos_procesados['dni_estudiante_doc']['contenido'],
        'dni_estudiante_doc_tipo' => $archivos_procesados['dni_estudiante_doc']['tipo'],
        'dni_estudiante_doc_nombre' => $archivos_procesados['dni_estudiante_doc']['nombre'],
        
        'dni_apoderado' => $archivos_procesados['dni_apoderado']['contenido'],
        'dni_apoderado_tipo' => $archivos_procesados['dni_apoderado']['tipo'],
        'dni_apoderado_nombre' => $archivos_procesados['dni_apoderado']['nombre'],
        
        'libreta_notas' => $archivos_procesados['libreta_notas']['contenido'],
        'libreta_notas_tipo' => $archivos_procesados['libreta_notas']['tipo'],
        'libreta_notas_nombre' => $archivos_procesados['libreta_notas']['nombre'],
        
        'certificado_estudios' => $archivos_procesados['certificado_estudios']['contenido'],
        'certificado_estudios_tipo' => $archivos_procesados['certificado_estudios']['tipo'],
        'certificado_estudios_nombre' => $archivos_procesados['certificado_estudios']['nombre'],
        
        'foto_estudiante' => $archivos_procesados['foto_estudiante']['contenido'],
        'foto_estudiante_tipo' => $archivos_procesados['foto_estudiante']['tipo'],
        'foto_estudiante_nombre' => $archivos_procesados['foto_estudiante']['nombre'],
        
        'comprobante_pago' => $archivos_procesados['comprobante_pago']['contenido'],
        'comprobante_pago_tipo' => $archivos_procesados['comprobante_pago']['tipo'],
        'comprobante_pago_nombre' => $archivos_procesados['comprobante_pago']['nombre'],
        
        // Información de pago
        'monto_pago' => 150.00,
        
        // Estado y control
        'estado' => 'Pendiente Pago',
        'ip_registro' => $_SERVER['REMOTE_ADDR']
    ];
    
    // Preparar SQL de inserción
    $campos = array_keys($datos);
    $placeholders = array_fill(0, count($campos), '?');
    
    $sql = "INSERT INTO solicitudes_admision (" . implode(', ', $campos) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(array_values($datos));
    
    // Respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'exito' => true,
        'mensaje' => '¡Solicitud enviada exitosamente!',
        'codigo_postulante' => $codigo_postulante,
        'debug' => [
            'campos_recibidos' => array_keys($_POST),
            'archivos_recibidos' => array_keys($_FILES)
        ]
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => $e->getMessage(),
        'debug' => [
            'campos_recibidos' => array_keys($_POST),
            'archivos_recibidos' => array_keys($_FILES)
        ]
    ]);
}