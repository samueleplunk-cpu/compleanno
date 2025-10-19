<?php
// === CONFIGURAZIONE EMAIL RSVP ===
$to = getRsvpEmailFromConfig();
if (!$to) {
    $to = "test@test.it";
    error_log("RSVP: Usando email di fallback: " . $to);
}
// === FINE CONFIGURAZIONE ===

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ricevi i dati dal form
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $adults = htmlspecialchars($_POST['adults']);
    $children = htmlspecialchars($_POST['children']);
    $message_text = htmlspecialchars($_POST['message']);
    
    error_log("RSVP Ricevuto: $name, $email, Adulti: $adults, Bambini: $children, Destinazione: $to");

    // Oggetto dell'email
    $subject = "Conferma Partecipazione Battesimo - " . $name;
    
    // Corpo dell'email AGGIORNATO
    $email_content = "CONFERMA DI PARTECIPAZIONE AL BATTESIMO\n\n";
    $email_content .= "============================================\n";
    $email_content .= "NOME: $name\n";
    $email_content .= "EMAIL: $email\n";
    $email_content .= "TELEFONO: $phone\n";
    $email_content .= "NUMERO ADULTI: $adults\n";
    $email_content .= "NUMERO BAMBINI: $children\n";
    $email_content .= "MESSAGGIO: " . ($message_text ? $message_text : "Nessun messaggio") . "\n";
    $email_content .= "============================================\n\n";
    $email_content .= "Data e ora: " . date('d/m/Y H:i:s') . "\n";
    $email_content .= "Sito web: " . $_SERVER['HTTP_HOST'] . "\n";
    
    // HEADERS MIGLIORATI per evitare SPAM
    $headers = "From: \"Sito Battesimo\" <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Reply-To: \"$name\" <$email>\r\n";
    $headers .= "Return-Path: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 3\r\n"; // Normal priority
    $headers .= "X-AntiAbuse: This is a legitimate email from website form\r\n";
    
    // Invio email
    if (mail($to, $subject, $email_content, $headers, "-fnoreply@" . $_SERVER['HTTP_HOST'])) {
        error_log("RSVP SUCCESS: Email inviata a: " . $to);
        
        // Salva nel localStorage per compatibilità con admin
        echo "<script>
            if (typeof saveRSVPToLocalStorage === 'function') {
                const formData = new FormData();
                formData.append('name', '$name');
                formData.append('email', '$email');
                formData.append('phone', '$phone');
                formData.append('adults', '$adults');
                formData.append('children', '$children');
                formData.append('message', '$message_text');
                saveRSVPToLocalStorage(formData);
            }
        </script>";
        
        // Reindirizza alla pagina di ringraziamento
        header('Location: grazie.html');
        exit;
    } else {
        error_log("RSVP ERROR: Invio fallito a: " . $to);
        header('Location: errore.html');
        exit;
    }
} else {
    // Se qualcuno accede direttamente a questo file
    header('Location: index.html');
    exit;
}

/**
 * Funzione per ottenere l'email RSVP dalla configurazione JSON
 */
function getRsvpEmailFromConfig() {
    $configFile = 'rsvp_email_config.json';
    
    if (!file_exists($configFile)) {
        error_log("RSVP: File configurazione non trovato");
        return null;
    }
    
    $configContent = file_get_contents($configFile);
    if ($configContent === false) {
        error_log("RSVP: Impossibile leggere file configurazione");
        return null;
    }
    
    $config = json_decode($configContent, true);
    if ($config === null) {
        error_log("RSVP: JSON configurazione non valido");
        return null;
    }
    
    if (isset($config['rsvp_email']) && filter_var($config['rsvp_email'], FILTER_VALIDATE_EMAIL)) {
        return $config['rsvp_email'];
    }
    
    error_log("RSVP: Email non valida nella configurazione");
    return null;
}
?>