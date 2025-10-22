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
    $defaultDir = 'img';
    
    $defaultFiles = [
        'gallery-default-1.jpg',
        'gallery-default-2.jpg', 
        'gallery-default-3.jpg',
        'gallery-default-4.jpg'
    ];
    
    foreach ($defaultFiles as $index => $defaultFile) {
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
        } else {
            $placeholderPath = createDefaultImage($defaultDir, $defaultFile, $index + 1);
            if ($placeholderPath) {
                $defaultImages[] = [
                    'name' => $defaultFile,
                    'size' => '0.5 MB',
                    'date' => date('d/m/Y H:i'),
                    'fullPath' => $placeholderPath,
                    'thumbnail' => $placeholderPath,
                    'isDefault' => true
                ];
            }
        }
    }
    
    return $defaultImages;
}

function createDefaultImage($dir, $filename, $number) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $filePath = $dir . '/' . $filename;
    $image = imagecreate(400, 400);
    
    $colors = [
        imagecolorallocate($image, 255, 182, 193),
        imagecolorallocate($image, 173, 216, 230),
        imagecolorallocate($image, 255, 255, 153),
        imagecolorallocate($image, 152, 251, 152)
    ];
    
    $backgroundColor = $colors[($number - 1) % count($colors)];
    $textColor = imagecolorallocate($image, 0, 0, 0);
    
    imagefill($image, 0, 0, $backgroundColor);
    
    $text = "Foto $number";
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $x = (400 - $textWidth) / 2;
    $y = (400 - $textHeight) / 2;
    
    imagestring($image, $font, $x, $y, $text, $textColor);
    
    if (imagejpeg($image, $filePath, 80)) {
        imagedestroy($image);
        return $filePath;
    }
    
    imagedestroy($image);
    return false;
}

$galleryDir = 'uploads';
$response = ['success' => false];

try {
    if (!is_dir($galleryDir)) {
        mkdir($galleryDir, 0755, true);
    }
    
    if (!is_dir('img')) {
        mkdir('img', 0755, true);
    }
    
    $photos = [];
    
    $defaultImages = getDefaultImages();
    $photos = array_merge($photos, $defaultImages);
    
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
    
    usort($photos, function($a, $b) {
        if ($a['isDefault'] && !$b['isDefault']) return -1;
        if (!$a['isDefault'] && $b['isDefault']) return 1;
        
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