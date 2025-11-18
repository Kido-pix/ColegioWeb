<?php
/**
 * Procesador de solicitudes - Versión simplificada para pruebas
 * Trinity School - Sistema de Admisiones 2025
 */

// Habilitar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla
ini_set('log_errors', 1);

// ============================================
// CLASE DATABASE
// ============================================
class Database {
    private static $instance = null;
    private $connection = null;
    
    private $host = 'localhost';
    private $dbname = 'trinity_admisiones';
    private $username = 'root';
    private $password = '';
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
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
        return $this->connection;
    }
}

// ============================================
// PROCESAR FORMULARIO
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['usuario'])) {
    
    try {
        // Validar que al menos los campos básicos existan
        $camposBasicos = [
            'nombres', 'apellido_paterno', 'apellido_materno',
            'fecha_nacimiento', 'dni_estudiante', 'sexo',
            'nivel_postula', 'grado_postula'
        ];
        
        $camposFaltantes = [];
        foreach ($camposBasicos as $campo) {
            if (empty($_POST[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            header('Content-Type: application/json');
            echo json_encode([
                'exito' => false,
                'mensaje' => 'Faltan campos obligatorios: ' . implode(', ', $camposFaltantes),
                'debug' => [
                    'campos_recibidos' => array_keys($_POST),
                    'archivos_recibidos' => array_keys($_FILES)
                ]
            ]);
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Generar código único
        $nivel = $_POST['nivel_postula'];
        $prefijo = match($nivel) {
            'Inicial' => 'INI',
            'Primaria' => 'PRI',
            'Secundaria' => 'SEC',
            default => 'GEN'
        };
        
        // Generar código manualmente si el procedimiento falla
        $año = date('Y');
        $stmt = $db->query("SELECT COUNT(*) + 1 as siguiente FROM solicitudes_admision WHERE codigo_postulante LIKE '{$año}{$prefijo}%'");
        $siguiente = $stmt->fetch()['siguiente'];
        $codigo = $año . $prefijo . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
        
        // Procesar archivos
        $archivosData = [];
        $archivosRequeridos = [
            'partida_nacimiento',
            'dni_estudiante_archivo',
            'dni_apoderado',
            'libreta_notas',
            'foto_estudiante',
            'comprobante_pago'
        ];
        
        foreach ($archivosRequeridos as $nombreCampo) {
            if (isset($_FILES[$nombreCampo]) && $_FILES[$nombreCampo]['error'] === UPLOAD_ERR_OK) {
                $archivo = $_FILES[$nombreCampo];
                $contenido = file_get_contents($archivo['tmp_name']);
                $tipoMime = mime_content_type($archivo['tmp_name']);
                $nombreOriginal = $archivo['name'];
                
                // Mapear nombres de campos
                $nombreColumna = ($nombreCampo === 'dni_estudiante_archivo') ? 'dni_estudiante_doc' : $nombreCampo;
                
                $archivosData[$nombreColumna] = [
                    'contenido' => $contenido,
                    'tipo' => $tipoMime,
                    'nombre' => $nombreOriginal
                ];
            }
        }
        
        // Construir INSERT
        $campos = [
            'codigo_postulante', 'nombre_estudiante', 'apellido_paterno', 'apellido_materno',
            'fecha_nacimiento', 'dni_estudiante', 'sexo', 'direccion', 'distrito',
            'nivel_educativo', 'grado', 'colegio_procedencia',
            'nombre_padre', 'celular_padre', 'nombre_madre', 'celular_madre',
            'apoderado_principal', 'email_apoderado', 'celular_apoderado',
            'estado', 'fecha_registro', 'ip_registro'
        ];
        
        $valores = [
            $codigo,
            $_POST['nombres'] ?? '',
            $_POST['apellido_paterno'] ?? '',
            $_POST['apellido_materno'] ?? '',
            $_POST['fecha_nacimiento'] ?? '',
            $_POST['dni_estudiante'] ?? '',
            $_POST['sexo'] ?? '',
            $_POST['direccion'] ?? '',
            $_POST['distrito'] ?? '',
            $_POST['nivel_postula'] ?? '',
            $_POST['grado_postula'] ?? '',
            $_POST['colegio_procedencia'] ?? '',
            $_POST['nombre_padre'] ?? '',
            $_POST['celular_padre'] ?? '',
            $_POST['nombre_madre'] ?? '',
            $_POST['celular_madre'] ?? '',
            $_POST['apoderado_principal'] ?? '',
            $_POST['email_apoderado'] ?? '',
            $_POST['celular_apoderado'] ?? '',
            'Pendiente Pago',
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR']
        ];
        
        // Agregar archivos
        foreach ($archivosData as $nombreColumna => $data) {
            $campos[] = $nombreColumna;
            $campos[] = $nombreColumna . '_tipo';
            $campos[] = $nombreColumna . '_nombre';
            
            $valores[] = $data['contenido'];
            $valores[] = $data['tipo'];
            $valores[] = $data['nombre'];
        }
        
        // Ejecutar INSERT
        $placeholders = array_fill(0, count($valores), '?');
        $sql = "INSERT INTO solicitudes_admision (" . implode(', ', $campos) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $db->prepare($sql);
        $resultado = $stmt->execute($valores);
        
        if ($resultado) {
            header('Content-Type: application/json');
            echo json_encode([
                'exito' => true,
                'mensaje' => 'Solicitud registrada exitosamente',
                'codigo' => $codigo
            ]);
        } else {
            throw new Exception('Error al insertar en base de datos');
        }
        
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Error: ' . $e->getMessage(),
            'debug' => [
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]
        ]);
    }
    
    exit;
}