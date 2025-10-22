<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => false];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $filePath = isset($input['file_path']) ? $input['file_path'] : '';
    $isDefault = isset($input['is_default']) ? $input['is_default'] : false;
    
    if (empty($filePath)) {
        $response['error'] = 'Percorso file non specificato';
        echo json_encode($response);
        exit;
    }
    
    if ($isDefault) {
        $realBasePath = realpath('img') . DIRECTORY_SEPARATOR;
        $realFilePath = realpath($filePath);
        
        if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
            $response['error'] = 'Percorso file default non valido';
            echo json_encode($response);
            exit;
        }
        
        if (!file_exists($realFilePath)) {
            $response['error'] = 'File default non trovato';
            echo json_encode($response);
            exit;
        }
        
        if (unlink($realFilePath)) {
            $response['success'] = true;
            $response['message'] = 'Immagine default eliminata con successo';
        } else {
            $response['error'] = 'Impossibile eliminare il file default';
        }
    } else {
        $realBasePath = realpath('uploads') . DIRECTORY_SEPARATOR;
        $realFilePath = realpath($filePath);
        
        if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
            $response['error'] = 'Percorso file non valido';
            echo json_encode($response);
            exit;
        }
        
        if (!file_exists($realFilePath)) {
            $response['error'] = 'File non trovato';
            echo json_encode($response);
            exit;
        }
        
        if (unlink($realFilePath)) {
            $response['success'] = true;
            $response['message'] = 'Foto eliminata con successo';
        } else {
            $response['error'] = 'Impossibile eliminare il file';
        }
    }
    
} catch (Exception $e) {
    $response['error'] = 'Errore nell\'eliminazione: ' . $e->getMessage();
}

echo json_encode($response);
?>