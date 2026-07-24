<?php
// Configuration SMTP pour l'envoi de vrais e-mails
// Utilisé par la fonction log_mail() dans includes/fonctions.php

define('SMTP_ENABLED', true); // Passez à true pour activer l'envoi réel par Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // 587 pour TLS, 465 pour SSL
define('SMTP_SECURE', 'tls'); // 'tls' ou 'ssl'
define('SMTP_USER', 'descarteskoffi2@gmail.com'); // Votre e-mail Gmail
define('SMTP_PASS', 'zzff ierc scny vokx'); // Votre mot de passe d'application Google (16 caractères)
define('SMTP_FROM', 'descarteskoffi2@gmail.com'); // E-mail expéditeur (le même)
define('SMTP_FROM_NAME', 'Benin Tourisme');

// Configuration FedaPay
define('FEDAPAY_SANDBOX_KEY', 'pk_sandbox_k2o5dgiLVLBwr7FRYBvnwgTd'); // Clé publique pour le frontend
define('FEDAPAY_SANDBOX_SECRET_KEY', 'sk_sandbox_VOTRE_CLE_SECRETE_ICI'); // Clé secrète pour l'API (à remplacer)