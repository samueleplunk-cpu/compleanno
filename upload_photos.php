<?php
// upload_photos.php
header('Content-Type: application/json');

// Configurazione
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$uploadDir = 'uploads/gallery/';

// Crea la directory se non esiste
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$response = ['success' => false, 'message' => '', 'files' => []];

try {
    // Verifica che sia una richiesta POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metodo non consentito');
    }

    // Verifica che ci siano file
    if (empty($_FILES['photos'])) {
        throw new Exception('Nessun file selezionato');
    }

    $files = $_FILES['photos'];
    $uploadedFiles = [];

    // Gestione file multipli
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $fileTmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileError = $files['error'][$i];

        // Verifica errori di upload
        if ($fileError !== UPLOAD_ERR_OK) {
            throw new Exception("Errore nel caricamento del file: $fileName");
        }

        // Verifica dimensione file
        if ($fileSize > $maxFileSize) {
            throw new Exception("File troppo grande: $fileName (max 5MB)");
        }

        // Verifica tipo file
        $fileType = mime_content_type($fileTmpName);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Tipo file non supportato: $fileName");
        }

        // Genera nome file sicuro
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $safeFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $destination = $uploadDir . $safeFileName;

        // Sposta il file
        if (move_uploaded_file($fileTmpName, $destination)) {
            $uploadedFiles[] = [
                'original_name' => $fileName,
                'saved_name' => $safeFileName,
                'path' => $destination
            ];
        } else {
            throw new Exception("Errore nel salvataggio del file: $fileName");
        }
    }

    $response['success'] = true;
    $response['message'] = count($uploadedFiles) . ' file caricati con successo!';
    $response['files'] = $uploadedFiles;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>