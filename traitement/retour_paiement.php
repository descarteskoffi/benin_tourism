<?php
require_once '../includes/fonctions.php';
require_once '../config/database.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'visite';

// FedaPay peut envoyer transaction_id ou status
$transaction_id = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

if (empty($token)) {
    header('Location: ../index.php');
    exit;
}

// Si pas de transaction_id, vérifier si la transaction est déjà enregistrée
if (empty($transaction_id)) {
    // Vérifier dans la base de données si le paiement a déjà été effectué
    try {
        if ($type === 'visite') {
            $stmt = $pdo->prepare("SELECT transaction_id, statut FROM demandes_visite WHERE token_paiement = ?");
            $stmt->execute([$token]);
            $result = $stmt->fetch();
            if ($result && $result['statut'] === 'payee') {
                $transaction_id = $result['transaction_id'];
            }
        } elseif ($type === 'hotel') {
            $stmt = $pdo->prepare("SELECT transaction_id, statut FROM reservations_hebergement WHERE token_paiement = ?");
            $stmt->execute([$token]);
            $result = $stmt->fetch();
            if ($result && $result['statut'] === 'payee') {
                $transaction_id = $result['transaction_id'];
            }
        } elseif ($type === 'guide') {
            $stmt = $pdo->prepare("SELECT transaction_id, statut FROM demandes_guide WHERE token_paiement = ?");
            $stmt->execute([$token]);
            $result = $stmt->fetch();
            if ($result && $result['statut'] === 'payee') {
                $transaction_id = $result['transaction_id'];
            }
        }
    } catch (PDOException $e) {
        // Ignorer l'erreur et rediriger vers la page de paiement
    }
}

// Si toujours pas de transaction_id, vérifier via webhook FedaPay ou extraire de l'URL
if (empty($transaction_id) && !empty($status)) {
    // FedaPay peut passer le statut dans l'URL après redirection
    // Générer un transaction_id temporaire basé sur le token et le timestamp
    $transaction_id = 'TXN_' . substr($token, 0, 8) . '_' . time();
}

// Si toujours rien, rediriger vers la page de paiement pour voir le statut
if (empty($transaction_id)) {
    header('Location: ../payer_visite.php?token=' . urlencode($token));
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

