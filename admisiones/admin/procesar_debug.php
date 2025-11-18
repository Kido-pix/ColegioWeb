<?php
/**
 * Archivo de depuración para identificar problemas en el formulario
 * Colócalo temporalmente como procesar_debug.php
 */

header('Content-Type: application/json');

echo json_encode([
    'POST' => $_POST,
    'FILES' => array_map(function($file) {
        return [
            'name' => $file['name'],
            'size' => $file['size'],
            'error' => $file['error'],
            'type' => $file['type']
        ];
    }, $_FILES),
    'campos_vacios' => array_filter($_POST, function($value) {
        return empty($value);
    }),
    'archivos_faltantes' => array_filter($_FILES, function($file) {
        return $file['error'] === UPLOAD_ERR_NO_FILE;
    })
], JSON_PRETTY_PRINT);