<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getGalleryStats($directory) {
    $totalPhotos = 0;
    $totalSize = 0;
    $lastUpdate = null;
    
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $directory . '/' . $file;
                if (is_file($filePath) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $totalPhotos++;
                    $totalSize += filesize($filePath);
                    $fileTime = filemtime($filePath);
                    if (!$lastUpdate || $fileTime > $lastUpdate) {
                        $lastUpdate = $fileTime;
                    }
                }
            }
        }
    }
    
    return [
        'totalPhotos' => $totalPhotos,
        'totalSize' => round($totalSize / (1024 * 1024), 2) . ' MB',
        'lastUpdate' => $lastUpdate ? date('d/m/Y H:i', $lastUpdate) : '-'
    ];
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function getDefaultImages() {
    $defaultImages = [];
    $defaultDir = 'assets/images/default-gallery';
    
    // Crea la directory se non esiste
    if (!is_dir($defaultDir)) {
        mkdir($defaultDir, 0755, true);
    }
    
    // Array delle 4 immagini default
    $defaultFiles = ['default1.jpg', 'default2.jpg', 'default3.jpg', 'default4.jpg'];
    
    foreach ($defaultFiles as $defaultFile) {
        $filePath = $defaultDir . '/' . $defaultFile;
        if (file_exists($filePath)) {
            $defaultImages[] = [
                'name' => $defaultFile,
                'size' => formatFileSize(filesize($filePath)),
                'date' => date('d/m/Y H:i', filemtime($filePath)),
                'fullPath' => $filePath,
                'thumbnail' => $filePath,
                'isDefault' => true
            ];
        }
    }
    
    return $defaultImages;
}

$galleryDir = 'uploads/gallery';
$response = ['success' => false];

try {
    // Crea le directory se non esistono
    if (!is_dir($galleryDir)) {
        mkdir($galleryDir, 0755, true);
    }
    
    $defaultDir = 'assets/images/default-gallery';
    if (!is_dir($defaultDir)) {
        mkdir($defaultDir, 0755, true);
    }
    
    $photos = [];
    
    // Aggiungi immagini default
    $defaultImages = getDefaultImages();
    $photos = array_merge($photos, $defaultImages);
    
    // Aggiungi immagini caricate dagli utenti
    if (is_dir($galleryDir)) {
        $files = scandir($galleryDir);
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $galleryDir . '/' . $file;
                
                if (is_file($filePath)) {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $fileSize = filesize($filePath);
                        $fileTime = filemtime($filePath);
                        
                        $photos[] = [
                            'name' => $file,
                            'size' => formatFileSize($fileSize),
                            'date' => date('d/m/Y H:i', $fileTime),
                            'fullPath' => $filePath,
                            'thumbnail' => $filePath,
                            'isDefault' => false
                        ];
                    }
                }
            }
        }
    }
    
    // Ordina per data (più recenti prima)
    usort($photos, function($a, $b) {
        $timeA = $a['isDefault'] ? 0 : filemtime($a['fullPath']);
        $timeB = $b['isDefault'] ? 0 : filemtime($b['fullPath']);
        return $timeB - $timeA;
    });
    
    $response['success'] = true;
    $response['photos'] = $photos;
    $response['stats'] = getGalleryStats($galleryDir);
    
} catch (Exception $e) {
    $response['error'] = 'Errore nel caricamento della galleria: ' . $e->getMessage();
}

echo json_encode($response);
?>