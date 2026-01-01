<?php
/**
 * Clase Database - Patrón Singleton
 * Gestiona la conexión única a la base de datos
 * Trinity School - Sistema de Admisiones 2025
 */

class Database {
    private static $instance = null;
    private $conn;
    
    // Configuración de la base de datos
    private $host = 'localhost';
    private $dbname = 'trinity';
    private $username = 'root';
    private $password = '';
    
    /**
     * Constructor privado (patrón Singleton)
     * Previene la creación directa de objetos
     */
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch(PDOException $e) {
            // En producción, no mostrar el error completo
            if (ini_get('display_errors')) {
                die("Error de conexión a la base de datos: " . $e->getMessage());
            } else {
                die("Error de conexión a la base de datos. Contacte al administrador.");
            }
        }
    }
    
    /**
     * Previene la clonación del objeto (patrón Singleton)
     */
    private function __clone() {}
    
    /**
     * Previene la deserialización del objeto (patrón Singleton)
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar un singleton.");
    }
    
    /**
     * Obtiene la instancia única de Database
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtiene la conexión PDO
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Cierra la conexión
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>