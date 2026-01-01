<?php
/**
 * Clase Emailer - Gestión de envío de correos
 * Trinity School - Sistema de Admisiones 2025
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'email.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Ajustar ruta si es necesario

class Emailer {
    
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }
    
    /**
     * Configura los parámetros SMTP
     */
    private function configurarSMTP() {
        try {
            // Configuración del servidor
            $this->mail->isSMTP();
            $this->mail->Host = EmailConfig::SMTP_HOST;
            $this->mail->SMTPAuth = EmailConfig::SMTP_AUTH;
            $this->mail->Username = EmailConfig::SMTP_USERNAME;
            $this->mail->Password = EmailConfig::SMTP_PASSWORD;
            $this->mail->SMTPSecure = EmailConfig::SMTP_SECURE;
            $this->mail->Port = EmailConfig::SMTP_PORT;
            
            // Configuración del mensaje
            $this->mail->CharSet = EmailConfig::CHARSET;
            $this->mail->SMTPDebug = EmailConfig::DEBUG;
            
            // Remitente por defecto
            $this->mail->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
            
        } catch (Exception $e) {
            error_log("Error configurando SMTP: " . $e->getMessage());
        }
    }
    
    /**
     * EMAIL TIPO 1: Confirmación al Postulante
     */
    public function enviarConfirmacionPostulante($datos) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($datos['email'], $datos['nombre_completo']);
            
            $this->mail->Subject = '✅ Solicitud de Admisión Recibida - ' . $datos['codigo'];
            $this->mail->isHTML(true);
            $this->mail->Body = $this->plantillaConfirmacionPostulante($datos);
            $this->mail->AltBody = strip_tags($this->mail->Body);
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email enviado correctamente'];
            
        } catch (Exception $e) {
            error_log("Error enviando email al postulante: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * EMAIL TIPO 2: Notificación al Administrador
     */
    public function enviarNotificacionAdmin($datos) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress(EmailConfig::ADMIN_EMAIL, EmailConfig::ADMIN_NAME);
            
            $this->mail->Subject = '🔔 Nueva Solicitud de Admisión - ' . $datos['codigo'];
            $this->mail->isHTML(true);
            $this->mail->Body = $this->plantillaNotificacionAdmin($datos);
            $this->mail->AltBody = strip_tags($this->mail->Body);
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Notificación enviada'];
            
        } catch (Exception $e) {
            error_log("Error enviando notificación al admin: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * EMAIL TIPO 3: Cambio de Estado
     */
    public function enviarCambioEstado($datos) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($datos['email'], $datos['nombre_completo']);
            
            $asunto = $this->obtenerAsuntoPorEstado($datos['estado']);
            $this->mail->Subject = $asunto . ' - ' . $datos['codigo'];
            $this->mail->isHTML(true);
            $this->mail->Body = $this->plantillaCambioEstado($datos);
            $this->mail->AltBody = strip_tags($this->mail->Body);
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email enviado correctamente'];
            
        } catch (Exception $e) {
            error_log("Error enviando cambio de estado: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * EMAIL TIPO 4: Email Personalizado (para recuperación de contraseña, etc)
     */
    public function enviarEmailPersonalizado($destinatario, $asunto, $cuerpoHTML) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($destinatario);
            $this->mail->Subject = $asunto;
            $this->mail->isHTML(true);
            $this->mail->Body = $cuerpoHTML;
            $this->mail->AltBody = strip_tags($cuerpoHTML);
            
            $resultado = $this->mail->send();
            
            return [
                'success' => $resultado,
                'message' => $resultado ? 'Email enviado correctamente' : 'Error al enviar email'
            ];
        } catch (Exception $e) {
            error_log("Error enviando email personalizado: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->mail->ErrorInfo
            ];
        }
    }

    /**
     * Obtener asunto según el estado
     */
    private function obtenerAsuntoPorEstado($estado) {
        return match($estado) {
            'Pago Verificado' => '✅ Pago Verificado',
            'Entrevista Agendada' => '📅 Entrevista Agendada',
            'Admitido' => '🎉 ¡Felicitaciones! Solicitud Admitida',
            'Rechazado' => '❌ Resultado de Solicitud',
            default => '📧 Actualización de Solicitud'
        };
    }
    
    /**
     * PLANTILLA 1: Confirmación al Postulante
     */
    private function plantillaConfirmacionPostulante($datos) {
        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Solicitud</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #8B1538 0%, #6B0F2A 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">Trinity School</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Proceso de Admisiones 2025</p>
                        </td>
                    </tr>
                    
                    <!-- Icono de éxito -->
                    <tr>
                        <td style="padding: 30px; text-align: center;">
                            <div style="width: 80px; height: 80px; background-color: #4CAF50; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                                <span style="color: white; font-size: 48px;">✓</span>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Contenido principal -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <h2 style="color: #333333; font-size: 24px; margin: 0 0 20px 0; text-align: center;">¡Solicitud Recibida!</h2>
                            
                            <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Estimado/a <strong>' . htmlspecialchars($datos['nombre_completo']) . '</strong>,
                            </p>
                            
                            <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hemos recibido exitosamente su solicitud de admisión para el nivel de <strong>' . htmlspecialchars($datos['nivel']) . '</strong>.
                            </p>
                            
                            <!-- Código de postulante destacado -->
                            <div style="background-color: #f8f9fa; border-left: 4px solid #8B1538; padding: 20px; margin: 20px 0; border-radius: 5px;">
                                <p style="color: #666666; margin: 0 0 10px 0; font-size: 14px;">Su código de postulante es:</p>
                                <p style="color: #8B1538; font-size: 28px; font-weight: bold; margin: 0; letter-spacing: 2px;">' . htmlspecialchars($datos['codigo']) . '</p>
                                <p style="color: #999999; margin: 10px 0 0 0; font-size: 13px;">Guarde este código para consultas futuras</p>
                            </div>
                            
                            <h3 style="color: #333333; font-size: 18px; margin: 30px 0 15px 0;">📋 Próximos Pasos:</h3>
                            
                            <ol style="color: #666666; font-size: 15px; line-height: 1.8; padding-left: 20px;">
                                <li>Nuestro equipo verificará su pago en un plazo máximo de <strong>24 horas hábiles</strong></li>
                                <li>Una vez verificado, recibirá un correo de confirmación</li>
                                <li>Posteriormente le contactaremos para agendar una entrevista</li>
                            </ol>
                            
                            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;">
                                <p style="color: #856404; margin: 0; font-size: 14px;">
                                    <strong>⚠️ Importante:</strong> Si tiene alguna consulta, puede comunicarse con nosotros indicando su código de postulante.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Información de contacto -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px 40px; border-top: 1px solid #e0e0e0;">
                            <h3 style="color: #333333; font-size: 16px; margin: 0 0 15px 0;">Datos de Contacto</h3>
                            <p style="color: #666666; font-size: 14px; line-height: 1.6; margin: 5px 0;">
                                📞 Teléfono: ' . EmailConfig::PHONE . '<br>
                                📧 Email: ' . EmailConfig::FROM_EMAIL . '<br>
                                📍 Dirección: ' . EmailConfig::ADDRESS . '
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #333333; padding: 20px; text-align: center;">
                            <p style="color: #ffffff; font-size: 13px; margin: 0; opacity: 0.8;">
                                © 2025 Trinity School - Chincha Alta. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * PLANTILLA 2: Notificación al Administrador
     */
    private function plantillaNotificacionAdmin($datos) {
        $urlVer = 'http://localhost/Trinity/admisiones/admin/ver_solicitud.php?id=' . $datos['id'];
        
        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden;">
                    
                    <tr>
                        <td style="background-color: #1B4B5A; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">🔔 Nueva Solicitud</h1>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #333333; font-size: 20px; margin: 0 0 20px 0;">Detalles del Postulante</h2>
                            
                            <table width="100%" style="border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #666; width: 40%;"><strong>Código:</strong></td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #333;"><strong>' . htmlspecialchars($datos['codigo']) . '</strong></td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #666;"><strong>Estudiante:</strong></td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #333;">' . htmlspecialchars($datos['nombre_completo']) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #666;"><strong>DNI:</strong></td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #333;">' . htmlspecialchars($datos['dni']) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #666;"><strong>Nivel:</strong></td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #333;">' . htmlspecialchars($datos['nivel']) . ' - ' . htmlspecialchars($datos['grado']) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #666;"><strong>Apoderado:</strong></td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #333;">' . htmlspecialchars($datos['apoderado']) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #666;"><strong>Contacto:</strong></td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #333;">' . htmlspecialchars($datos['email']) . '<br>' . htmlspecialchars($datos['celular']) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; color: #666;"><strong>Fecha:</strong></td>
                                    <td style="padding: 10px; color: #333;">' . date('d/m/Y H:i', strtotime($datos['fecha'])) . '</td>
                                </tr>
                            </table>
                            
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="' . $urlVer . '" style="display: inline-block; background-color: #1B4B5A; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                                    Ver Solicitud Completa
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="color: #666666; font-size: 13px; margin: 0;">
                                Panel Administrativo - Trinity School
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * PLANTILLA 3: Cambio de Estado
     */
    private function plantillaCambioEstado($datos) {
        $colorEstado = match($datos['estado']) {
            'Pago Verificado' => '#3AAFA9',
            'Entrevista Agendada' => '#FFB648',
            'Admitido' => '#2ED47A',
            'Rechazado' => '#F7464A',
            default => '#1B4B5A'
        };
        
        $mensajeEstado = $this->obtenerMensajePorEstado($datos['estado'], $datos);
        
        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0;">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden;">
                    
                    <tr>
                        <td style="background-color: ' . $colorEstado . '; padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Trinity School</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Actualización de Solicitud</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Estimado/a <strong>' . htmlspecialchars($datos['nombre_completo']) . '</strong>,
                            </p>
                            
                            <div style="background-color: ' . $colorEstado . '20; border-left: 4px solid ' . $colorEstado . '; padding: 20px; margin: 20px 0; border-radius: 5px;">
                                <p style="color: #333; font-size: 18px; font-weight: bold; margin: 0 0 10px 0;">Estado Actual:</p>
                                <p style="color: ' . $colorEstado . '; font-size: 24px; font-weight: bold; margin: 0;">' . htmlspecialchars($datos['estado']) . '</p>
                            </div>
                            
                            ' . $mensajeEstado . '
                            
                            <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
                                <p style="color: #666; font-size: 14px; margin: 0 0 5px 0;"><strong>Código de Postulante:</strong></p>
                                <p style="color: #333; font-size: 18px; font-weight: bold; margin: 0;">' . htmlspecialchars($datos['codigo']) . '</p>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; border-top: 1px solid #e0e0e0;">
                            <p style="color: #666666; font-size: 14px; line-height: 1.6; margin: 0 0 15px 0;">
                                Para cualquier consulta, contáctenos a:
                            </p>
                            <p style="color: #666666; font-size: 14px; line-height: 1.6; margin: 0;">
                                📞 ' . EmailConfig::PHONE . '<br>
                                📧 ' . EmailConfig::FROM_EMAIL . '
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="background-color: #333333; padding: 20px; text-align: center;">
                            <p style="color: #ffffff; font-size: 13px; margin: 0; opacity: 0.8;">
                                © 2025 Trinity School. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Obtener mensaje personalizado según estado
     */
    private function obtenerMensajePorEstado($estado, $datos) {
        return match($estado) {
            'Pago Verificado' => '
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 15px 0;">
                    Nos complace informarle que hemos <strong>verificado exitosamente su pago</strong>.
                </p>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0;">
                    En breve nos pondremos en contacto con usted para coordinar la fecha de la entrevista personal.
                </p>',
            
            'Entrevista Agendada' => '
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 15px 0;">
                    Su entrevista ha sido agendada para:
                </p>
                <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <p style="color: #856404; font-size: 16px; margin: 0;"><strong>📅 Fecha:</strong> ' . (isset($datos['fecha_entrevista']) ? htmlspecialchars($datos['fecha_entrevista']) : 'Por confirmar') . '</p>
                    <p style="color: #856404; font-size: 16px; margin: 10px 0 0 0;"><strong>🕐 Hora:</strong> ' . (isset($datos['hora_entrevista']) ? htmlspecialchars($datos['hora_entrevista']) : 'Por confirmar') . '</p>
                </div>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0;">
                    Por favor, asista puntualmente con el apoderado principal.
                </p>',
            
            'Admitido' => '
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 15px 0;">
                    ¡Felicitaciones! 🎉 Nos complace informarle que su solicitud ha sido <strong>ADMITIDA</strong>.
                </p>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 15px 0;">
                    En los próximos días recibirá información sobre:
                </p>
                <ul style="color: #666666; font-size: 15px; line-height: 1.8; margin: 0 0 15px 20px;">
                    <li>Proceso de matrícula</li>
                    <li>Documentación requerida</li>
                    <li>Costos y formas de pago</li>
                    <li>Fecha de inicio de clases</li>
                </ul>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0;">
                    ¡Bienvenido a la familia Trinity School!
                </p>',
            
            'Rechazado' => '
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 15px 0;">
                    Lamentamos informarle que en esta oportunidad su solicitud no ha sido aprobada.
                </p>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0;">
                    Agradecemos su interés en Trinity School y le deseamos mucho éxito en su búsqueda educativa.
                </p>',
            
            default => '
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0;">
                    El estado de su solicitud ha sido actualizado. Para más información, comuníquese con nosotros.
                </p>'
        };
    }
}
?>