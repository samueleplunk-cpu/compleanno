<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestione preflight CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_POST['action'] === 'save') {
    $data = $_POST['data'];
    $version = $_POST['version'];
    
    // DEBUG: Log per tracciare il salvataggio
    error_log("🔄 MOBILE SYNC - Salvataggio dati. Versione: " . $version);
    error_log("📊 Dati ricevuti: " . substr($data, 0, 100) . "...");
    
    // Salva in un file JSON
    $saveData = [
        'data' => $data,
        'version' => $version,
        'timestamp' => time(),
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    $result = file_put_contents('mobile_data.json', json_encode($saveData));
    
    if ($result !== false) {
        error_log("✅ MOBILE SYNC - Dati salvati con successo");
        echo json_encode([
            'success' => true, 
            'message' => 'Dati salvati correttamente',
            'version' => $version,
            'saved_keys' => count(json_decode($data, true))
        ]);
    } else {
        error_log("❌ MOBILE SYNC - Errore nel salvataggio");
        echo json_encode(['success' => false, 'error' => 'Impossibile salvare i dati']);
    }
    exit;
}

if ($_POST['action'] === 'load') {
    if (file_exists('mobile_data.json')) {
        $content = file_get_contents('mobile_data.json');
        $data = json_decode($content, true);
        
        // DEBUG
        error_log("📱 MOBILE SYNC - Caricamento dati per dispositivo mobile");
        error_log("📱 Versione server: " . $data['version']);
        
        echo json_encode([
            'success' => true,
            'data' => $data['data'],
            'version' => $data['version'],
            'timestamp' => $data['timestamp']
        ]);
    } else {
        error_log("📱 MOBILE SYNC - Nessun dato disponibile sul server");
        echo json_encode([
            'success' => false, 
            'error' => 'No data available',
            'version' => 0
        ]);
    }
    exit;
}

// Nuova azione: get_version - per controllare rapidamente se ci sono aggiornamenti
if ($_POST['action'] === 'get_version') {
    if (file_exists('mobile_data.json')) {
        $content = file_get_contents('mobile_data.json');
        $data = json_decode($content, true);
        echo json_encode([
            'success' => true,
            'version' => $data['version'],
            'timestamp' => $data['timestamp']
        ]);
    } else {
        echo json_encode(['success' => false, 'version' => 0]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>