<?php
// Démarrage de la session si non active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détection de la langue
$languages_allowed = ['fr', 'en'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $languages_allowed)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Par défaut la langue est le français 'fr'
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr';
}

$lang = $_SESSION['lang'];

// Chargement du fichier de langue
$lang_file = __DIR__ . '/../lang/' . $lang . '.php';
if (file_exists($lang_file)) {
    $translations = require $lang_file;
} else {
    $translations = [];
}

/**
 * Traduit une clé de texte statique
 */
function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

/**
 * Sécurise une chaîne pour l'affichage (XSS Protection)
 */
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Récupère un champ de base de données traduit (ex. nom_fr ou nom_en)
 */
function db_trans($row, $field) {
    global $lang;
    $translated_field = $field . '_' . $lang;
    if (isset($row[$translated_field])) {
        return $row[$translated_field];
    }
    // Fallback sur le français puis l'anglais
    if (isset($row[$field . '_fr'])) {
        return $row[$field . '_fr'];
    }
    if (isset($row[$field . '_en'])) {
        return $row[$field . '_en'];
    }
    // Fallback si le champ n'est pas traduit (ex. nom)
    return $row[$field] ?? '';
}

/**
 * Simule l'envoi d'un email en écrivant dans un fichier logs/mail.log
 */
function log_mail($to, $subject, $body) {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . '/mail.log';
    
    $date = date('Y-m-d H:i:s');
    $content = "==================================================\n";
    $content .= "DATE : $date\n";
    $content .= "TO : $to\n";
    $content .= "SUBJECT : $subject\n";
    $content .= "BODY :\n$body\n";
    $content .= "==================================================\n\n";
    
    // 1. Enregistrement dans le fichier log local pour le test
    $logged = file_put_contents($log_file, $content, FILE_APPEND) !== false;

    // 2. Vérification et chargement de la config SMTP
    $smtp_config = __DIR__ . '/../config/smtp.php';
    if (file_exists($smtp_config)) {
        require_once $smtp_config;
    }

    if (defined('SMTP_ENABLED') && SMTP_ENABLED === true) {
        // Envoi réel par SMTP via PHPMailer
        require_once __DIR__ . '/PHPMailer/Exception.php';
        require_once __DIR__ . '/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
        } catch (\Exception $e) {
            // Écrit l'erreur d'envoi SMTP dans le fichier log pour faciliter le débugging
            file_put_contents($log_file, "ERROR SENDING REAL MAIL: " . $e->getMessage() . "\n\n", FILE_APPEND);
        }
    } else {
        // 3. Fallback sur la fonction php native si SMTP n'est pas activé (utile en hébergement en ligne)
        $headers = "From: Benin Tourisme <no-reply@benintourisme.bj>\r\n";
        $headers .= "Reply-To: contact@benintourisme.bj\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        @mail($to, $subject, $body, $headers);
    }

    return $logged;
}

/**
 * Mapping des photos principales de la BDD vers les fichiers locaux dans assets/images/lieux/
 */
function get_image_url($img_name, $category = 'Culture') {
    // Mapping : nom du fichier BDD => fichier réel dans assets/images/lieux/
    $lieux_mapping = [
        'ganvie.jpg'         => 'assets/images/lieux/cité_ganvier.jpg',
        'abomey.jpg'         => 'assets/images/lieux/palais_abomey.jpg',
        'route_esclaves.jpg' => 'assets/images/lieux/porte_non_retour.jpg',
        'pendjari.jpg'       => 'assets/images/lieux/parc_national_pendjary.jpg',
        'grand_popo.jpg'     => 'assets/images/lieux/route_des_peches.jpg',
        'temple_pythons.jpg' => 'assets/images/lieux/temple_des_python.jpg',
    ];

    // 1. Chercher via le mapping lieux
    if (!empty($img_name) && isset($lieux_mapping[$img_name])) {
        $mapped = $lieux_mapping[$img_name];
        if (file_exists(__DIR__ . '/../' . $mapped)) {
            return $mapped;
        }
    }

    // 2. Chercher directement dans assets/images/lieux/
    if (!empty($img_name)) {
        $lieux_path = 'assets/images/lieux/' . $img_name;
        if (file_exists(__DIR__ . '/../' . $lieux_path)) {
            return $lieux_path;
        }
    }

    // 3. Chercher dans l'ancien dossier assets/img/
    if (!empty($img_name)) {
        $old_path = 'assets/img/' . $img_name;
        if (file_exists(__DIR__ . '/../' . $old_path)) {
            return $old_path;
        }
    }

    // 4. Fallback Unsplash par catégorie
    $placeholders = [
        'Culture'           => 'https://images.unsplash.com/photo-1599946347371-68eb71b16afc?auto=format&fit=crop&w=800&q=80',
        'Nature'            => 'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?auto=format&fit=crop&w=800&q=80',
        'Plage'             => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
        'Spiritualité'      => 'https://images.unsplash.com/photo-1609137144813-7d722ef2049e?auto=format&fit=crop&w=800&q=80',
        'Hôtel'             => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80',
        'Auberge'           => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=800&q=80',
        "Maison d'hôtes"    => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80',
        'guide'             => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=800&q=80',
    ];
    return $placeholders[$category] ?? $placeholders['Culture'];
}

/**
 * Récupère les données du cache fichier si elles existent et ne sont pas expirées (TTL en secondes)
 */
function get_cache($key, $ttl = 300) {
    $cache_dir = __DIR__ . '/../cache';
    if (!is_dir($cache_dir)) {
        return null;
    }
    $file = $cache_dir . '/' . md5($key) . '.cache';
    if (file_exists($file) && (time() - filemtime($file) < $ttl)) {
        $content = file_get_contents($file);
        return json_decode($content, true);
    }
    return null;
}

/**
 * Sauvegarde les données dans le cache fichier
 */
function set_cache($key, $data) {
    $cache_dir = __DIR__ . '/../cache';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0777, true);
    }
    $file = $cache_dir . '/' . md5($key) . '.cache';
    file_put_contents($file, json_encode($data));
}

/**
 * Supprime tous les fichiers de cache (appelé après des ajouts/modifications/suppressions)
 */
function clear_cache() {
    $cache_dir = __DIR__ . '/../cache';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*.cache');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}

