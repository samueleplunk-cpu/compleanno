<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => false];

try {
    $oldImagePath = $_POST['old_image_path'] ?? '';
    $newImageFile = $_FILES['new_image'] ?? null;
    
    if (empty($oldImagePath) || !$newImageFile) {
        $response['error'] = 'Dati insufficienti';
        echo json_encode($response);
        exit;
    }
    
    $realBasePath = realpath('img') . DIRECTORY_SEPARATOR;
    $realOldPath = realpath($oldImagePath);
    
    if ($realOldPath === false || strpos($realOldPath, $realBasePath) !== 0) {
        $response['error'] = 'Percorso immagine default non valido';
        echo json_encode($response);
        exit;
    }
    
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 5 * 1024 * 1024;
    
    $fileType = strtolower(pathinfo($newImageFile['name'], PATHINFO_EXTENSION));
    $fileSize = $newImageFile['size'];
    
    if (!in_array($fileType, $allowedTypes)) {
        $response['error'] = 'Tipo file non supportato';
        echo json_encode($response);
        exit;
    }
    
    if ($fileSize > $maxSize) {
        $response['error'] = 'File troppo grande (max 5MB)';
        echo json_encode($response);
        exit;
    }
    
    if (!unlink($realOldPath)) {
        $response['error'] = 'Impossibile eliminare il vecchio file';
        echo json_encode($response);
        exit;
    }
    
    $newFileName = basename($oldImagePath);
    $destination = dirname($oldImagePath) . '/' . $newFileName;
    
    if (move_uploaded_file($newImageFile['tmp_name'], $destination)) {
        $response['success'] = true;
        $response['message'] = 'Immagine default sostituita con successo';
    } else {
        $response['error'] = 'Errore nel salvataggio del nuovo file';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Errore nella sostituzione: ' . $e->getMessage();
}

echo json_encode($response);
?>