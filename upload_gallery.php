<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => false, 'uploaded_count' => 0];

try {
    $galleryDir = 'uploads';
    
    if (!is_dir($galleryDir)) {
        mkdir($galleryDir, 0755, true);
    }
    
    if (!isset($_FILES['photos'])) {
        $response['error'] = 'Nessuna foto ricevuta';
        echo json_encode($response);
        exit;
    }
    
    $uploadedFiles = $_FILES['photos'];
    $uploadedCount = 0;
    $errors = [];
    
    if (is_array($uploadedFiles['name'])) {
        for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
            if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = basename($uploadedFiles['name'][$i]);
                $fileTmp = $uploadedFiles['tmp_name'][$i];
                $fileSize = $uploadedFiles['size'][$i];
                $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                $maxSize = 5 * 1024 * 1024;
                
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = "Tipo file non supportato: $fileName";
                    continue;
                }
                
                if ($fileSize > $maxSize) {
                    $errors[] = "File troppo grande: $fileName";
                    continue;
                }
                
                $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fileName);
                $destination = $galleryDir . '/' . $newFileName;
                
                if (move_uploaded_file($fileTmp, $destination)) {
                    $uploadedCount++;
                } else {
                    $errors[] = "Errore nel salvataggio: $fileName";
                }
            } else {
                $errors[] = "Errore upload per $fileName";
            }
        }
    } else {
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
                } else {
                    $errors[] = "Errore nel salvataggio: $fileName";
                }
            } else {
                $errors[] = "File non valido: $fileName";
            }
        } else {
            $errors[] = "Errore upload";
        }
    }
    
    if ($uploadedCount > 0) {
        $response['success'] = true;
        $response['uploaded_count'] = $uploadedCount;
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
    } else {
        $response['error'] = 'Nessuna foto valida caricata. ' . implode('; ', $errors);
    }
    
} catch (Exception $e) {
    $response['error'] = 'Errore nel caricamento: ' . $e->getMessage();
}

echo json_encode($response);
?>