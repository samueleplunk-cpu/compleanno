<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getCurrentRsvpEmail() {
    $sendEmailFile = 'send_email.php';
    
    if (!file_exists($sendEmailFile)) {
        return null;
    }
    
    $content = file_get_contents($sendEmailFile);
    
    // Pattern per estrarre l'email dalla variabile $to
    $pattern = '/\$to\s*=\s*["\']([^"\']+)["\']\s*;/';
    
    if (preg_match($pattern, $content, $matches)) {
        return $matches[1];
    }
    
    return null;
}

// Prova a leggere prima dal file di configurazione
$configFile = 'rsvp_email_config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if ($config && isset($config['rsvp_email'])) {
        echo json_encode([
            'success' => true,
            'email' => $config['rsvp_email'],
            'source' => 'config_file',
            'last_updated' => $config['last_updated'] ?? null
        ]);
        exit;
    }
}

// Altrimenti estrai dal file send_email.php
$currentEmail = getCurrentRsvpEmail();

if ($currentEmail) {
    echo json_encode([
        'success' => true,
        'email' => $currentEmail,
        'source' => 'send_email_file'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Impossibile determinare l\'email corrente'
    ]);
}
?>