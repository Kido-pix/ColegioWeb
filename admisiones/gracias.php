<?php
// Obtener código de postulante de la URL
$codigo = isset($_GET['codigo']) ? htmlspecialchars($_GET['codigo']) : '2025INI0001';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Enviada - Colegio Trinity School</title>
    
    <link rel="stylesheet" href="../css/Estilo.css">
    <link rel="stylesheet" href="../css/darkmode.css">
    <link rel="stylesheet" href="css/admisiones.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .gracias-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 50px;
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
        }
        
        .gracias-box {
            max-width: 900px;
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .check-icon {
            width: 100px;
            height: 100px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease;
        }
        
        .check-icon::before {
            content: '✓';
            font-size: 60px;
            color: white;
            font-weight: bold;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .gracias-box h1 {
            color: #8B1538;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .codigo-postulante {
            background: linear-gradient(135deg, #8B1538, #1B4B5A);
            color: white;
            padding: 25px 35px;
            border-radius: 15px;
            margin: 30px 0;
            display: inline-block;
        }
        
        .codigo-postulante strong {
            font-size: 2.2rem;
            display: block;
            margin-top: 10px;
            letter-spacing: 3px;
        }
        
        .siguientes-pasos {
            background: #f8f9fa;
            padding: 35px;
            border-radius: 12px;
            margin: 30px 0;
            text-align: left;
        }
        
        .siguientes-pasos h3 {
            color: #8B1538;
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.8rem;
        }
        
        .paso-siguiente {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .paso-siguiente:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        
        .paso-numero {
            width: 45px;
            height: 45px;
            background: #8B1538;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .paso-info h4 {
            color: #333;
            margin-bottom: 8px;
            font-size: 1.2rem;
        }
        
        .paso-info p {
            color: #666;
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
        }

        .tiempo-estimado {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(139, 21, 56, 0.1);
            color: #8B1538;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .nota-importante {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px 25px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: left;
        }

        .nota-importante strong {
            color: #8B1538;
            display: block;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .nota-importante p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }
        
        .contacto-info {
            background: #e8f5e9;
            border-left: 4px solid #28a745;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: left;
        }
        
        .contacto-info p {
            margin: 8px 0;
            color: #333;
            font-size: 1rem;
        }

        .contacto-info strong {
            color: #28a745;
            font-size: 1.1rem;
        }
        
        .btn-grupo {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 35px;
        }
        
        .btn-principal, .btn-secundario {
            padding: 16px 35px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-principal {
            background: #8B1538;
            color: white;
        }
        
        .btn-principal:hover {
            background: #1B4B5A;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-secundario {
            background: white;
            color: #8B1538;
            border: 2px solid #8B1538;
        }
        
        .btn-secundario:hover {
            background: #8B1538;
            color: white;
        }

        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 193, 7, 0.2);
            color: #f57c00;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            font-size: 0.95rem;
        }

        .icono-tiempo {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @media (max-width: 768px) {
            .gracias-box {
                padding: 30px 20px;
            }
            
            .gracias-box h1 {
                font-size: 2rem;
            }
            
            .codigo-postulante strong {
                font-size: 1.8rem;
            }
            
            .btn-grupo {
                flex-direction: column;
            }

            .paso-siguiente {
                flex-direction: column;
                text-align: center;
            }
        }

        body.dark-mode .gracias-container {
            background: linear-gradient(135deg, #0f0f10 0%, #1a1a1c 100%);
        }

        body.dark-mode .gracias-box {
            background: #1a1a1c;
        }

        body.dark-mode .gracias-box h1 {
            color: #D64A4A;
        }

        body.dark-mode .siguientes-pasos {
            background: #232325;
        }

        body.dark-mode .paso-info h4 {
            color: #e8e8e9;
        }

        body.dark-mode .paso-info p {
            color: #a8a8aa;
        }

        body.dark-mode .nota-importante {
            background: rgba(255, 193, 7, 0.1);
        }

        body.dark-mode .nota-importante strong {
            color: #D64A4A;
        }

        body.dark-mode .nota-importante p {
            color: #a8a8aa;
        }

        body.dark-mode .contacto-info {
            background: rgba(40, 167, 69, 0.1);
        }

        body.dark-mode .contacto-info p {
            color: #e8e8e9;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="../index.html" class="logo-link">
                    <img src="../img/logo.png" alt="Logo Colegio Trinity School" />
                    <div class="logo-text">
                        <h1>Colegio<br>Trinity School</h1>
                        <p>Chincha Alta</p>
                    </div>
                </a>
            </div>
        </nav>
    </header>

    <div class="gracias-container">
        <div class="gracias-box">
            <div class="check-icon"></div>
            
            <h1>¡Solicitud Enviada Correctamente!</h1>
            <p style="font-size: 1.15rem; color: #666; margin-bottom: 15px;">
                Gracias por tu interés en formar parte de la familia Trinity School
            </p>

            <div class="estado-badge">
                <span class="icono-tiempo">⏳</span>
                Estado: Pendiente de Verificación de Pago
            </div>
            
            <div class="codigo-postulante">
                <span style="font-size: 1rem;">Tu código de postulante es:</span>
                <strong id="codigoPostulante"><?php echo $codigo; ?></strong>
            </div>
            
            <p style="color: #666; margin-bottom: 30px; font-size: 1.05rem;">
                <strong>📧 Importante:</strong> Hemos enviado un email de confirmación a tu correo electrónico.<br>
                Guarda tu código de postulante para futuras consultas.
            </p>

            <div class="nota-importante">
                <strong>✅ Tu pago y documentos están siendo revisados</strong>
                <p>Has completado exitosamente tu solicitud y enviado tu comprobante de pago de S/. 150.00. Nuestro equipo administrativo verificará tu pago y documentos en las próximas 24 horas hábiles.</p>
            </div>
            
            <!-- SIGUIENTES PASOS -->
            <div class="siguientes-pasos">
                <h3>📋 ¿Qué Sigue Ahora?</h3>
                
                <div class="paso-siguiente">
                    <div class="paso-numero">1</div>
                    <div class="paso-info">
                        <h4>Verificación de Pago</h4>
                        <p>Nuestro equipo verificará tu comprobante de pago y los documentos enviados.</p>
                        <span class="tiempo-estimado">⏱️ 24 horas hábiles</span>
                    </div>
                </div>
                
                <div class="paso-siguiente">
                    <div class="paso-numero">2</div>
                    <div class="paso-info">
                        <h4>Notificación por Email</h4>
                        <p>Recibirás un correo confirmando que tu pago fue verificado exitosamente.</p>
                        <span class="tiempo-estimado">📧 Email automático</span>
                    </div>
                </div>
                
                <div class="paso-siguiente">
                    <div class="paso-numero">3</div>
                    <div class="paso-info">
                        <h4>Coordinación de Entrevista</h4>
                        <p>Nos contactaremos contigo vía WhatsApp o email para agendar tu entrevista de admisión.</p>
                        <span class="tiempo-estimado">📞 Próximos 2-3 días</span>
                    </div>
                </div>
                
                <div class="paso-siguiente">
                    <div class="paso-numero">4</div>
                    <div class="paso-info">
                        <h4>Entrevista Personal</h4>
                        <p>Entrevista con el apoderado y estudiante para conocer sus expectativas y presentar nuestro proyecto educativo.</p>
                        <span class="tiempo-estimado">🎯 Según disponibilidad</span>
                    </div>
                </div>
            </div>
            
            <!-- INFORMACIÓN DE CONTACTO -->
            <div class="contacto-info">
                <p><strong>📞 ¿Tienes alguna consulta?</strong></p>
                <p>📱 WhatsApp: 987 654 321</p>
                <p>📧 Email: admisiones@trinityschool.edu.pe</p>
                <p>🕐 Horario de atención: Lunes a Viernes 8:00 AM - 5:00 PM</p>
                <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #c8e6c9;">
                    <strong>Código de referencia:</strong> <?php echo $codigo; ?>
                </p>
            </div>
            
            <!-- BOTONES DE ACCIÓN -->
            <div class="btn-grupo">
                <a href="../index.html" class="btn-principal">Volver al Inicio</a>
                <a href="https://wa.me/51987654321?text=Hola,%20soy%20el%20postulante%20<?php echo urlencode($codigo); ?>,%20quisiera%20consultar%20sobre%20mi%20solicitud" 
                   class="btn-secundario" target="_blank">
                    Contactar por WhatsApp
                </a>
            </div>
        </div>
    </div>

    <script src="../js/darkmode.js"></script>
</body>
</html>