<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => false, 'uploaded_count' => 0];

try {
    $galleryDir = 'uploads/gallery';
    
    // Crea la directory se non esiste
    if (!is_dir($galleryDir)) {
        mkdir($galleryDir, 0755, true);
    }
    
    // Controlla se sono state inviate foto
    if (!isset($_FILES['photos'])) {
        $response['error'] = 'Nessuna foto ricevuta';
        echo json_encode($response);
        exit;
    }
    
    $uploadedFiles = $_FILES['photos'];
    $uploadedCount = 0;
    
    // Gestisci file multipli
    if (is_array($uploadedFiles['name'])) {
        for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
            if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = basename($uploadedFiles['name'][$i]);
                $fileTmp = $uploadedFiles['tmp_name'][$i];
                $fileSize = $uploadedFiles['size'][$i];
                $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Validazioni
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($fileType, $allowedTypes)) {
                    continue; // Salta file non supportati
                }
                
                if ($fileSize > $maxSize) {
                    continue; // Salta file troppo grandi
                }
                
                // Genera nome file univoco per evitare sovrascritture
                $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fileName);
                $destination = $galleryDir . '/' . $newFileName;
                
                if (move_uploaded_file($fileTmp, $destination)) {
                    $uploadedCount++;
                }
            }
        }
    } else {
        // Gestisci singolo file
        if ($uploadedFiles['error'] === UPLOAD_ERR_OK) {
            $fileName = basename($uploadedFiles['name']);
            $fileTmp = $uploadedFiles['tmp_name'];
            $fileSize = $uploadedFiles['size'];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            $maxSize = 5 * 1024 * 1024;
            
            if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fileName);
                $destination = $galleryDir . '/' . $newFileName;
                
                if (move_uploaded_file($fileTmp, $destination)) {
                    $uploadedCount = 1;
                }
            }
        }
    }
    
    if ($uploadedCount > 0) {
        $response['success'] = true;
        $response['uploaded_count'] = $uploadedCount;
    } else {
        $response['error'] = 'Nessuna foto valida caricata';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Errore nel caricamento: ' . $e->getMessage();
}

echo json_encode($response);
?>