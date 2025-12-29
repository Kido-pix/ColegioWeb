<?php
// ============================================
// GENERADOR DE SOLICITUDES DE PRUEBA
// Trinity School - Sistema de Admisiones
// ============================================

session_start();

// Solo permitir acceso si estás logueado como admin
if (!isset($_SESSION['admin_logueado'])) {
    die("Acceso denegado. Debes iniciar sesión como administrador.");
}

require_once '../config/database.php';

// ============================================
// CONFIGURACIÓN
// ============================================
$cantidad = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 10;
$cantidad = min($cantidad, 50); // Máximo 50 a la vez

// ============================================
// DATOS DE EJEMPLO
// ============================================
$nombres = [
    'Juan', 'María', 'Carlos', 'Ana', 'Luis', 'Sofia', 'Diego', 'Valentina',
    'Miguel', 'Isabella', 'José', 'Camila', 'Pedro', 'Lucía', 'Antonio',
    'Martina', 'Javier', 'Emma', 'Manuel', 'Mía', 'Rafael', 'Paula',
    'Fernando', 'Julia', 'Ricardo', 'Laura', 'Andrés', 'Carolina'
];

$apellidos = [
    'García', 'Rodríguez', 'Martínez', 'López', 'González', 'Pérez',
    'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez',
    'Díaz', 'Cruz', 'Morales', 'Reyes', 'Jiménez', 'Hernández',
    'Ruiz', 'Mendoza', 'Castillo', 'Vargas', 'Romero', 'Silva'
];

$niveles = ['Inicial', 'Primaria', 'Secundaria'];

$gradosPorNivel = [
    'Inicial' => ['3 años', '4 años', '5 años'],
    'Primaria' => ['1° grado', '2° grado', '3° grado', '4° grado', '5° grado', '6° grado'],
    'Secundaria' => ['1° año', '2° año', '3° año', '4° año', '5° año']
];

$estados = [
    'Pendiente Pago',
    'Pago Verificado',
    'Entrevista Agendada',
    'Admitido',
    'Rechazado'
];

$distritos = [
    'Chincha Alta', 'Chincha Baja', 'Pueblo Nuevo', 'Sunampe',
    'Tambo de Mora', 'Alto Larán', 'Grocio Prado'
];

$colegios = [
    'I.E. San José',
    'I.E. Abraham Valdelomar',
    'Colegio Particular Santa Rosa',
    'I.E. José María Arguedas',
    'Colegio San Luis Gonzaga',
    'I.E. Melitón Carvajal',
    'No aplica'
];

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Generador de Solicitudes de Prueba</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', -apple-system, sans-serif;
                background: #0F1419;
                color: #E8EAED;
                padding: 40px;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: #1A202C;
                border: 1px solid #2D3748;
                border-radius: 12px;
                padding: 40px;
            }
            h1 {
                color: #3AAFA9;
                margin-bottom: 10px;
            }
            .subtitle {
                color: #9AA0A6;
                margin-bottom: 30px;
            }
            .success {
                background: rgba(46, 212, 122, 0.1);
                border-left: 4px solid #2ED47A;
                padding: 15px;
                margin-bottom: 10px;
                border-radius: 4px;
            }
            .info {
                background: rgba(58, 175, 169, 0.1);
                border-left: 4px solid #3AAFA9;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin: 30px 0;
            }
            .stat-card {
                background: #2D3748;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
            }
            .stat-value {
                font-size: 2rem;
                font-weight: 700;
                color: #3AAFA9;
            }
            .stat-label {
                font-size: 0.9rem;
                color: #9AA0A6;
                margin-top: 5px;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: #3AAFA9;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin-top: 20px;
                transition: all 0.3s ease;
            }
            .btn:hover {
                background: #2ED47A;
                transform: translateY(-2px);
            }
            .btn-secondary {
                background: #1B4B5A;
            }
            .btn-secondary:hover {
                background: #2D3748;
            }
            .form-group {
                margin: 20px 0;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #E8EAED;
                font-weight: 500;
            }
            .form-group input {
                width: 100%;
                padding: 12px;
                background: #2D3748;
                border: 1px solid #3D4852;
                border-radius: 8px;
                color: #E8EAED;
                font-size: 1rem;
            }
            code {
                background: #2D3748;
                padding: 2px 6px;
                border-radius: 4px;
                font-family: monospace;
            }
        </style>
    </head>
    <body>
        <div class='container'>";
    
    if (isset($_GET['generar'])) {
        // ============================================
        // GENERAR SOLICITUDES
        // ============================================
        
        echo "<h1>🎲 Generando Solicitudes...</h1>";
        echo "<p class='subtitle'>Creando {$cantidad} solicitudes de prueba</p>";
        
        $generadas = 0;
        $errores = 0;
        
        for ($i = 0; $i < $cantidad; $i++) {
            // Generar datos aleatorios
            $nombre = $nombres[array_rand($nombres)];
            $apellidoPaterno = $apellidos[array_rand($apellidos)];
            $apellidoMaterno = $apellidos[array_rand($apellidos)];
            
            // DNI único (8 dígitos aleatorios)
            $dni = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            
            // Verificar que no exista
            $stmt = $db->prepare("SELECT COUNT(*) FROM solicitudes_admision WHERE dni_estudiante = ?");
            $stmt->execute([$dni]);
            if ($stmt->fetchColumn() > 0) {
                $errores++;
                continue; // Saltar si ya existe
            }
            
            // Fecha de nacimiento (entre 3 y 17 años)
            $edad = rand(3, 17);
            $fechaNac = date('Y-m-d', strtotime("-{$edad} years"));
            
            // Nivel y grado
            $nivel = $niveles[array_rand($niveles)];
            $grado = $gradosPorNivel[$nivel][array_rand($gradosPorNivel[$nivel])];
            
            // Estado aleatorio con probabilidades
            $random = rand(1, 100);
            if ($random <= 30) {
                $estado = 'Pendiente Pago';
            } elseif ($random <= 60) {
                $estado = 'Pago Verificado';
            } elseif ($random <= 80) {
                $estado = 'Entrevista Agendada';
            } elseif ($random <= 95) {
                $estado = 'Admitido';
            } else {
                $estado = 'Rechazado';
            }
            
            // Generar código único
            $anio = date('Y');
            $nivelCodigo = substr($nivel, 0, 1);
            do {
                $randomNum = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $codigo = "ADM{$anio}{$nivelCodigo}{$randomNum}";
                $stmt = $db->prepare("SELECT COUNT(*) FROM solicitudes_admision WHERE codigo_postulante = ?");
                $stmt->execute([$codigo]);
                $existe = $stmt->fetchColumn();
            } while ($existe > 0);
            
            // Datos de contacto
            $celular = '9' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $email = strtolower($apellidoPaterno . rand(1, 999)) . '@gmail.com';
            
            // Fecha de registro aleatoria (últimos 3 meses)
            $diasAtras = rand(0, 90);
            $fechaRegistro = date('Y-m-d H:i:s', strtotime("-{$diasAtras} days"));
            
            // Insertar en BD
            $sql = "INSERT INTO solicitudes_admision (
                codigo_postulante, nombres, apellido_paterno, apellido_materno,
                dni_estudiante, fecha_nacimiento, sexo,
                direccion, distrito,
                nivel_postula, grado_postula,
                colegio_procedencia,
                apoderado_principal, celular_apoderado, email_apoderado,
                estado, pago_verificado, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $resultado = $stmt->execute([
                $codigo,
                $nombre,
                $apellidoPaterno,
                $apellidoMaterno,
                $dni,
                $fechaNac,
                rand(0, 1) ? 'Masculino' : 'Femenino',
                'Calle ' . rand(100, 999) . ', ' . $distritos[array_rand($distritos)],
                $distritos[array_rand($distritos)],
                $nivel,
                $grado,
                $colegios[array_rand($colegios)],
                rand(0, 1) ? 'Padre' : 'Madre',
                $celular,
                $email,
                $estado,
                $estado !== 'Pendiente Pago' ? 1 : 0,
                $fechaRegistro
            ]);
            
            if ($resultado) {
                $generadas++;
                echo "<div class='success'>✅ Solicitud {$generadas}: <code>{$codigo}</code> - {$nombre} {$apellidoPaterno} ({$nivel} - {$grado}) - Estado: {$estado}</div>";
            } else {
                $errores++;
            }
        }
        
        // Obtener estadísticas actualizadas
        $stmt = $db->query("SELECT COUNT(*) as total FROM solicitudes_admision");
        $totalGeneral = $stmt->fetch()['total'];
        
        $stmt = $db->query("
            SELECT estado, COUNT(*) as cantidad 
            FROM solicitudes_admision 
            GROUP BY estado
        ");
        $porEstado = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        echo "<div class='info'>
            <strong>📊 Resumen de Generación:</strong><br>
            ✅ Generadas exitosamente: <strong>{$generadas}</strong><br>
            ❌ Errores: <strong>{$errores}</strong><br>
            📈 Total en sistema: <strong>{$totalGeneral}</strong>
        </div>";
        
        echo "<div class='stats'>";
        foreach ($porEstado as $estado => $cantidad) {
            echo "<div class='stat-card'>
                <div class='stat-value'>{$cantidad}</div>
                <div class='stat-label'>{$estado}</div>
            </div>";
        }
        echo "</div>";
        
        echo "<a href='dashboard.php' class='btn'>Ver Dashboard</a>";
        echo "<a href='generar_solicitudes.php' class='btn btn-secondary'>Generar Más</a>";
        
    } else {
        // ============================================
        // FORMULARIO
        // ============================================
        
        echo "<h1>🎲 Generador de Solicitudes de Prueba</h1>";
        echo "<p class='subtitle'>Crea solicitudes automáticamente para probar el sistema</p>";
        
        echo "<div class='info'>
            <strong>ℹ️ Información:</strong><br>
            Este script genera solicitudes con datos aleatorios pero realistas:
            <ul style='margin-top: 10px; margin-left: 20px;'>
                <li>Nombres y apellidos peruanos comunes</li>
                <li>DNIs únicos de 8 dígitos</li>
                <li>Códigos de postulante únicos</li>
                <li>Estados distribuidos aleatoriamente</li>
                <li>Fechas de registro de los últimos 3 meses</li>
                <li>Niveles y grados válidos</li>
            </ul>
        </div>";
        
        // Estadísticas actuales
        $stmt = $db->query("SELECT COUNT(*) as total FROM solicitudes_admision");
        $totalActual = $stmt->fetch()['total'];
        
        echo "<div class='stats'>";
        echo "<div class='stat-card'>
            <div class='stat-value'>{$totalActual}</div>
            <div class='stat-label'>Solicitudes Actuales</div>
        </div>";
        echo "</div>";
        
        echo "<form method='GET' action='generar_solicitudes.php'>
            <div class='form-group'>
                <label for='cantidad'>¿Cuántas solicitudes quieres generar?</label>
                <input type='number' id='cantidad' name='cantidad' value='10' min='1' max='50' required>
                <small style='color: #9AA0A6; display: block; margin-top: 5px;'>Máximo 50 por vez</small>
            </div>
            <input type='hidden' name='generar' value='1'>
            <button type='submit' class='btn'>🎲 Generar Solicitudes</button>
            <a href='dashboard.php' class='btn btn-secondary'>Volver al Dashboard</a>
        </form>";
    }
    
    echo "</div></body></html>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f44336; color: white; padding: 20px; margin: 20px; border-radius: 10px;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>