<?php
/**
 * Configuración de Email - Trinity School
 * Sistema de Admisiones 2025
 */

class EmailConfig {
    // ============================================
    // CONFIGURACIÓN SMTP
    // ============================================
    
    // Para DESARROLLO LOCAL (Gmail)
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_SECURE = 'tls'; // tls o ssl
    const SMTP_AUTH = true;
    
    // Credenciales (CAMBIAR cuando subas a hosting)
    const SMTP_USERNAME = 'joe159julca357@gmail.com';
    const SMTP_PASSWORD = ''; // ← Aquí irá tu contraseña de aplicación
    
    // Remitente
    const FROM_EMAIL = 'joe159julca357@gmail.com';
    const FROM_NAME = 'Trinity School - Admisiones';
    
    // Email de notificaciones administrativas
    const ADMIN_EMAIL = 'julcadelacruzjoe@gmail.com';
    const ADMIN_NAME = 'Administrador Trinity School';
    
    // ============================================
    // CONFIGURACIÓN DE EMAILS
    // ============================================
    const CHARSET = 'UTF-8';
    const DEBUG = 0; // 0=sin debug, 1=mensajes cliente, 2=mensajes cliente y servidor, 3=todo
    
    // ============================================
    // PLANTILLAS
    // ============================================
    const LOGO_URL = 'https://trinityschool.edu.pe/img/logo.png'; // Cambiar por URL real
    const WEBSITE_URL = 'https://trinityschool.edu.pe';
    const PHONE = '(056) 123456';
    const ADDRESS = 'Jr. Educación 123, Chincha Alta, Ica';
}
?>