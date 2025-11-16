<?php
/**
 * =====================================================
 * PROCESAR FORMULARIO DE ADMISIONES
 * COLEGIO TRINITY SCHOOL
 * =====================================================
 */

// Iniciar sesión y configuración
session_start();
header('Content-Type: application/json; charset=utf-8');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'trinity_admisiones');
define('DB_USER', 'root');
define('DB_PASS', 'liznadine'); // En XAMPP por defecto es vacío

// Configuración de archivos
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// ============================================
// CLASE DE RESPUESTA
// ============================================
class Response {
    public static function success($message, $data = []) {
        echo json_encode([
            'exito' => true,
            'mensaje' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function error($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'exito' => false,
            'mensaje' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================================
// CLASE DE BASE DE DATOS
// ============================================
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            Response::error("Error de conexión a la base de datos: " . $e->getMessage(), 500);
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

// ============================================
// CLASE PARA MANEJO DE ARCHIVOS
// ============================================
class FileUploader {
    
    public static function validateFile($file) {
        // Verificar si hay error en la subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error al subir el archivo'];
        }
        
        // Verificar tamaño
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'El archivo excede el tamaño máximo de 5MB'];
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            return ['success' => false, 'message' => 'Formato de archivo no permitido'];
        }
        
        return ['success' => true];
    }
    
    public static function uploadFile($file, $prefix = 'doc') {
        $validation = self::validateFile($file);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Crear directorio si no existe
        if (!file_exists(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }
        
        // Generar nombre único
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
        $filepath = UPLOAD_DIR . $filename;
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath
            ];
        }
        
        return ['success' => false, 'message' => 'Error al guardar el archivo'];
    }
}

// ============================================
// CLASE PRINCIPAL DE ADMISIONES
// ============================================
class AdmisionesController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generar código de postulante único
     */
    private function generarCodigoPostulante($nivel) {
        $stmt = $this->db->prepare("CALL generar_codigo_postulante(:nivel, @codigo)");
        $stmt->execute(['nivel' => $nivel]);
        
        $result = $this->db->query("SELECT @codigo as codigo")->fetch();
        return $result['codigo'];
    }
    
    /**
     * Validar datos del formulario
     */
    private function validarDatos($data) {
        $errores = [];
        
        // Validar campos obligatorios
        $camposObligatorios = [
            'nombres', 'apellidos', 'fecha_nacimiento', 'dni_estudiante',
            'sexo', 'direccion', 'distrito', 'nivel_postula', 'grado_postula',
            'apoderado_principal', 'celular_apoderado', 'email_apoderado'
        ];
        
        foreach ($camposObligatorios as $campo) {
            if (empty($data[$campo])) {
                $errores[] = "El campo $campo es obligatorio";
            }
        }
        
        // Validar DNI (8 dígitos)
        if (!empty($data['dni_estudiante']) && !preg_match('/^\d{8}$/', $data['dni_estudiante'])) {
            $errores[] = "El DNI del estudiante debe tener 8 dígitos";
        }
        
        // Validar email
        if (!empty($data['email_apoderado']) && !filter_var($data['email_apoderado'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El email del apoderado no es válido";
        }
        
        // Validar celular (9 dígitos)
        if (!empty($data['celular_apoderado']) && !preg_match('/^\d{9}$/', $data['celular_apoderado'])) {
            $errores[] = "El celular debe tener 9 dígitos";
        }
        
        return $errores;
    }
    
    /**
     * Procesar documentos subidos
     */
    private function procesarDocumentos() {
        $documentos = [];
        $documentosRequeridos = [
            'doc_partida' => 'Partida de Nacimiento',
            'doc_dni_estudiante' => 'DNI del Estudiante',
            'doc_dni_apoderado' => 'DNI del Apoderado',
            'doc_libreta' => 'Libreta de Notas',
            'doc_foto' => 'Foto del Estudiante'
        ];
        
        foreach ($documentosRequeridos as $key => $nombre) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = FileUploader::uploadFile($_FILES[$key], $key);
                
                if (!$result['success']) {
                    Response::error("Error en $nombre: " . $result['message']);
                }
                
                $documentos[$key] = $result['filename'];
            } else {
                Response::error("Falta el documento: $nombre");
            }
        }
        
        // Documento opcional: Certificado
        if (isset($_FILES['doc_certificado']) && $_FILES['doc_certificado']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = FileUploader::uploadFile($_FILES['doc_certificado'], 'doc_certificado');
            if ($result['success']) {
                $documentos['doc_certificado'] = $result['filename'];
            }
        }
        
        return $documentos;
    }
    
    /**
     * Guardar solicitud en la base de datos
     */
    public function guardarSolicitud() {
        try {
            // Validar datos
            $errores = $this->validarDatos($_POST);
            if (!empty($errores)) {
                Response::error(implode(', ', $errores));
            }
            
            // Procesar documentos
            $documentos = $this->procesarDocumentos();
            
            // Generar código de postulante
            $codigo = $this->generarCodigoPostulante($_POST['nivel_postula']);
            
            // Preparar datos para insertar
            $sql = "INSERT INTO solicitudes_admision (
                codigo_postulante, nombres, apellidos, fecha_nacimiento, dni_estudiante,
                sexo, lugar_nacimiento, direccion, distrito, provincia,
                nivel_postula, grado_postula, colegio_procedencia, tipo_colegio, promedio_anterior,
                nombre_padre, dni_padre, celular_padre, email_padre, ocupacion_padre,
                nombre_madre, dni_madre, celular_madre, email_madre, ocupacion_madre,
                apoderado_principal, nombre_apoderado, parentesco_apoderado, dni_apoderado,
                celular_apoderado, email_apoderado,
                tiene_hermanos, nombres_hermanos, necesidades_especiales, descripcion_necesidades,
                doc_partida, doc_dni_estudiante, doc_dni_apoderado, doc_libreta, doc_certificado, doc_foto,
                estado, ip_registro
            ) VALUES (
                :codigo_postulante, :nombres, :apellidos, :fecha_nacimiento, :dni_estudiante,
                :sexo, :lugar_nacimiento, :direccion, :distrito, :provincia,
                :nivel_postula, :grado_postula, :colegio_procedencia, :tipo_colegio, :promedio_anterior,
                :nombre_padre, :dni_padre, :celular_padre, :email_padre, :ocupacion_padre,
                :nombre_madre, :dni_madre, :celular_madre, :email_madre, :ocupacion_madre,
                :apoderado_principal, :nombre_apoderado, :parentesco_apoderado, :dni_apoderado,
                :celular_apoderado, :email_apoderado,
                :tiene_hermanos, :nombres_hermanos, :necesidades_especiales, :descripcion_necesidades,
                :doc_partida, :doc_dni_estudiante, :doc_dni_apoderado, :doc_libreta, :doc_certificado, :doc_foto,
                'Documentos Completos', :ip_registro
            )";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind de parámetros
            $params = [
                'codigo_postulante' => $codigo,
                'nombres' => $_POST['nombres'],
                'apellidos' => $_POST['apellidos'],
                'fecha_nacimiento' => $_POST['fecha_nacimiento'],
                'dni_estudiante' => $_POST['dni_estudiante'],
                'sexo' => $_POST['sexo'],
                'lugar_nacimiento' => $_POST['lugar_nacimiento'] ?? null,
                'direccion' => $_POST['direccion'],
                'distrito' => $_POST['distrito'],
                'provincia' => $_POST['provincia'] ?? 'Chincha',
                'nivel_postula' => $_POST['nivel_postula'],
                'grado_postula' => $_POST['grado_postula'],
                'colegio_procedencia' => $_POST['colegio_procedencia'] ?? null,
                'tipo_colegio' => $_POST['tipo_colegio'] ?? null,
                'promedio_anterior' => $_POST['promedio_anterior'] ?? null,
                'nombre_padre' => $_POST['nombre_padre'] ?? null,
                'dni_padre' => $_POST['dni_padre'] ?? null,
                'celular_padre' => $_POST['celular_padre'] ?? null,
                'email_padre' => $_POST['email_padre'] ?? null,
                'ocupacion_padre' => $_POST['ocupacion_padre'] ?? null,
                'nombre_madre' => $_POST['nombre_madre'] ?? null,
                'dni_madre' => $_POST['dni_madre'] ?? null,
                'celular_madre' => $_POST['celular_madre'] ?? null,
                'email_madre' => $_POST['email_madre'] ?? null,
                'ocupacion_madre' => $_POST['ocupacion_madre'] ?? null,
                'apoderado_principal' => $_POST['apoderado_principal'],
                'nombre_apoderado' => $_POST['nombre_apoderado'] ?? null,
                'parentesco_apoderado' => $_POST['parentesco_apoderado'] ?? null,
                'dni_apoderado' => $_POST['dni_apoderado'] ?? null,
                'celular_apoderado' => $_POST['celular_apoderado'],
                'email_apoderado' => $_POST['email_apoderado'],
                'tiene_hermanos' => isset($_POST['tiene_hermanos']) ? 1 : 0,
                'nombres_hermanos' => $_POST['nombres_hermanos'] ?? null,
                'necesidades_especiales' => isset($_POST['necesidades_especiales']) ? 1 : 0,
                'descripcion_necesidades' => $_POST['descripcion_necesidades'] ?? null,
                'doc_partida' => $documentos['doc_partida'],
                'doc_dni_estudiante' => $documentos['doc_dni_estudiante'],
                'doc_dni_apoderado' => $documentos['doc_dni_apoderado'],
                'doc_libreta' => $documentos['doc_libreta'],
                'doc_certificado' => $documentos['doc_certificado'] ?? null,
                'doc_foto' => $documentos['doc_foto'],
                'ip_registro' => $_SERVER['REMOTE_ADDR']
            ];
            
            $stmt->execute($params);
            
            // Enviar email de confirmación (opcional)
            $this->enviarEmailConfirmacion($codigo, $_POST['email_apoderado'], $_POST['nombres'], $_POST['apellidos']);
            
            Response::success('Solicitud enviada correctamente', [
                'codigo' => $codigo
            ]);
            
        } catch(PDOException $e) {
            Response::error('Error al procesar la solicitud: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Enviar email de confirmación
     */
    private function enviarEmailConfirmacion($codigo, $email, $nombres, $apellidos) {
        $asunto = "Confirmación de Solicitud de Admisión - Trinity School";
        $mensaje = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #8B1538; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f5f5f5; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .codigo { font-size: 24px; font-weight: bold; color: #8B1538; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Colegio Trinity School</h1>
                    <p>Proceso de Admisión 2025</p>
                </div>
                <div class='content'>
                    <h2>¡Solicitud Recibida!</h2>
                    <p>Estimado(a) Apoderado(a),</p>
                    <p>Hemos recibido correctamente la solicitud de admisión para:</p>
                    <p><strong>Estudiante:</strong> $nombres $apellidos</p>
                    <p><strong>Código de Postulante:</strong> <span class='codigo'>$codigo</span></p>
                    <p>Guarde este código para futuras consultas.</p>
                    <p>En breve nos pondremos en contacto con usted para coordinar la entrevista.</p>
                </div>
                <div class='footer'>
                    <p>Colegio Trinity School - Chincha Alta</p>
                    <p>📞 (056) 123456 | ✉️ admisiones@trinityschool.edu.pe</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: admisiones@trinityschool.edu.pe" . "\r\n";
        
        // Nota: Para que funcione el envío de emails, necesitas configurar sendmail en XAMPP
        // o usar una librería como PHPMailer
        @mail($email, $asunto, $mensaje, $headers);
    }
}

// ============================================
// EJECUCIÓN
// ============================================

// Verificar que es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

// Procesar solicitud
$controller = new AdmisionesController();
$controller->guardarSolicitud();