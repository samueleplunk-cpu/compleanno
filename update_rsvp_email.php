<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

// Ricevi e valida l'email
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Indirizzo email non valido']);
    exit;
}

// Sanitizza l'email
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

// PRIMA SALVA NEL FILE DI CONFIGURAZIONE JSON
$config = [
    'rsvp_email' => $email,
    'last_updated' => date('c'),
    'updated_by' => 'admin_panel',
    'server_info' => [
        'server_name' => $_SERVER['SERVER_NAME'],
        'timestamp' => time()
    ]
];

$configSaved = file_put_contents('rsvp_email_config.json', json_encode($config, JSON_PRETTY_PRINT));

if ($configSaved === false) {
    echo json_encode(['success' => false, 'error' => 'Impossibile salvare la configurazione JSON']);
    exit;
}

// POI PROVA AD AGGIORNARE IL FILE PHP (opzionale, ma mantienilo per compatibilità)
$sendEmailFile = 'send_email.php';
$phpUpdated = false;

if (file_exists($sendEmailFile) && is_writable($sendEmailFile)) {
    $content = file_get_contents($sendEmailFile);
    
    if ($content !== false) {
        // Pattern per trovare la variabile $to
        $pattern = '/\$to\s*=\s*["\'][^"\']*["\']\s*;/';
        
        if (preg_match($pattern, $content)) {
            $newContent = preg_replace($pattern, '$to = "' . $email . '";', $content);
            
            if ($newContent !== $content && $newContent !== null) {
                $phpUpdated = (file_put_contents($sendEmailFile, $newContent, LOCK_EX) !== false);
            }
        }
    }
}

echo json_encode([
    'success' => true, 
    'message' => 'Email configurata con successo',
    'email' => $email,
    'config_saved' => true,
    'php_updated' => $phpUpdated,
    'timestamp' => date('c')
]);
?>