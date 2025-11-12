<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// LOG DETTAGLIATO
$log = "=== DELETE REQUEST ===\n";
$log .= "Time: " . date('Y-m-d H:i:s') . "\n";
$log .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$log .= "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";

$raw_input = file_get_contents('php://input');
$log .= "Raw input: " . $raw_input . "\n";

$input = json_decode($raw_input, true);
$log .= "JSON decode error: " . json_last_error_msg() . "\n";
$log .= "Parsed input: " . print_r($input, true) . "\n";

$response = ['success' => false, 'debug_log' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metodo non consentito');
    }
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON invalido: ' . json_last_error_msg());
    }
    
    if (!$input) {
        throw new Exception('Nessun dato ricevuto');
    }
    
    $filePath = $input['file_path'] ?? '';
    $isDefault = $input['is_default'] ?? false;
    
    $log .= "File path: " . $filePath . "\n";
    $log .= "Is default: " . ($isDefault ? 'true' : 'false') . "\n";
    
    $response['debug_log']['received_file_path'] = $filePath;
    $response['debug_log']['received_is_default'] = $isDefault;
    
    if (empty($filePath)) {
        throw new Exception('Percorso file vuoto');
    }
    
    // VERIFICA FILE
    $log .= "File exists: " . (file_exists($filePath) ? 'YES' : 'NO') . "\n";
    $log .= "File is writable: " . (is_writable($filePath) ? 'YES' : 'NO') . "\n";
    
    $response['debug_log']['file_exists'] = file_exists($filePath);
    $response['debug_log']['file_writable'] = is_writable($filePath);
    $response['debug_log']['file_path_used'] = $filePath;
    
    if (!file_exists($filePath)) {
        throw new Exception("File non trovato: " . $filePath);
    }
    
    if (!is_writable($filePath)) {
        throw new Exception("File non scrivibile: " . $filePath);
    }
    
    // TENTATIVO DI ELIMINAZIONE
    $log .= "Attempting to delete...\n";
    
    if (unlink($filePath)) {
        $log .= "DELETE SUCCESS\n";
        $log .= "File exists after delete: " . (file_exists($filePath) ? 'YES' : 'NO') . "\n";
        
        $response['success'] = true;
        $response['message'] = 'File eliminato con successo';
        $response['debug_log']['deletion_result'] = 'success';
        $response['debug_log']['file_exists_after'] = file_exists($filePath);
    } else {
        $lastError = error_get_last();
        $errorMsg = $lastError ? $lastError['message'] : 'Unknown error';
        $log .= "DELETE FAILED: " . $errorMsg . "\n";
        throw new Exception("Errore durante eliminazione: " . $errorMsg);
    }
    
} catch (Exception $e) {
    $log .= "EXCEPTION: " . $e->getMessage() . "\n";
    $response['error'] = $e->getMessage();
    $response['debug_log']['exception'] = $e->getMessage();
}

// Salva il log
file_put_contents('delete_debug.log', $log, FILE_APPEND);
$response['debug_log']['full_log'] = $log;

echo json_encode($response);
?>