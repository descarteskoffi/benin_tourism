<?php
require_once '../includes/fonctions.php';
require_once '../config/database.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$transaction_id = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'visite';

if (empty($token) || empty($transaction_id)) {
    header('Location: ../index.php');
    exit;
}

try {
    if ($type === 'visite') {
        // Récupérer la demande
        $stmt = $pdo->prepare("SELECT d.*, l.nom_fr as lieu_nom FROM demandes_visite d JOIN lieux l ON d.lieu_id = l.id WHERE d.token_paiement = ?");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();

        if ($demande && $demande['statut'] === 'proposition_envoyee') {
            $update_stmt = $pdo->prepare("UPDATE demandes_visite SET statut = 'payee', transaction_id = ? WHERE token_paiement = ?");
            $update_stmt->execute([$transaction_id, $token]);

            // Email client
            $subject = "Confirmation de votre réservation de voyage - Bénin Tourisme";
            $body = "Bonjour " . $demande['nom_client'] . ",\n\n";
            $body .= "Votre paiement pour la visite de [" . $demande['lieu_nom'] . "] a bien été validé.\n\n";
            $body .= "ID de transaction FedaPay : $transaction_id\n\n";
            $body .= "Votre reçu est disponible en ligne à l'adresse suivante :\n";
            $body .= "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') . "/payer_visite.php?token=" . $token . "\n\n";
            $body .= "L'équipe Bénin Tourisme.";

            log_mail($demande['email_client'], $subject, $body);
        }

    } elseif ($type === 'hotel') {
        // Récupérer la demande
        $stmt = $pdo->prepare("SELECT r.*, h.nom as hotel_nom FROM reservations_hebergement r JOIN hebergements h ON r.hebergement_id = h.id WHERE r.token_paiement = ?");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();

        if ($demande && $demande['statut'] === 'proposition_envoyee') {
            $update_stmt = $pdo->prepare("UPDATE reservations_hebergement SET statut = 'payee', transaction_id = ? WHERE token_paiement = ?");
            $update_stmt->execute([$transaction_id, $token]);

            // Email client
            $subject = "Confirmation de votre réservation d'hébergement - Bénin Tourisme";
            $body = "Bonjour " . $demande['nom_client'] . ",\n\n";
            $body .= "Votre paiement pour l'hébergement [" . $demande['hotel_nom'] . "] a bien été validé.\n\n";
            $body .= "ID de transaction FedaPay : $transaction_id\n\n";
            $body .= "Votre reçu est disponible en ligne à l'adresse suivante :\n";
            $body .= "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') . "/payer_visite.php?token=" . $token . "\n\n";
            $body .= "L'équipe Bénin Tourisme.";

            log_mail($demande['email_client'], $subject, $body);
        }

    } elseif ($type === 'guide') {
        // Récupérer la demande
        $stmt = $pdo->prepare("SELECT d.*, g.nom as guide_nom FROM demandes_guide d JOIN guides g ON d.guide_id = g.id WHERE d.token_paiement = ?");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();

        if ($demande && $demande['statut'] === 'proposition_envoyee') {
            $update_stmt = $pdo->prepare("UPDATE demandes_guide SET statut = 'payee', transaction_id = ? WHERE token_paiement = ?");
            $update_stmt->execute([$transaction_id, $token]);

            // Email client
            $subject = "Confirmation de votre demande de chauffeur-guide - Bénin Tourisme";
            $body = "Bonjour " . $demande['nom_client'] . ",\n\n";
            $body .= "Votre paiement pour le chauffeur-guide [" . $demande['guide_nom'] . "] a bien été validé.\n\n";
            $body .= "ID de transaction FedaPay : $transaction_id\n\n";
            $body .= "Votre reçu est disponible en ligne à l'adresse suivante :\n";
            $body .= "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') . "/payer_visite.php?token=" . $token . "\n\n";
            $body .= "L'équipe Bénin Tourisme.";

            log_mail($demande['email_client'], $subject, $body);
        }
    }

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Redirection vers le reçu client
header('Location: ../payer_visite.php?token=' . $token);
exit;
