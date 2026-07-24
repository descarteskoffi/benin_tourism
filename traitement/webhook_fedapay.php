<?php
/**
 * Webhook FedaPay - Gestion des notifications de paiement
 * 
 * Ce fichier reçoit les notifications de FedaPay lorsqu'une transaction change de statut.
 * URL à configurer dans FedaPay : https://votresite.com/traitement/webhook_fedapay.php
 */

require_once '../includes/fonctions.php';
require_once '../config/database.php';

// Récupération du contenu de la requête
$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

// Log du webhook pour debug
file_put_contents('../logs/webhook_fedapay.log', date('Y-m-d H:i:s') . " - " . $payload . "\n", FILE_APPEND);

// Vérification de la signature (recommandé en production)
// $signature = $_SERVER['HTTP_X_FEDAPAY_SIGNATURE'] ?? '';
// Vérifier la signature ici avec votre secret FedaPay

if (!$event || !isset($event['entity'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Extraction des informations de la transaction
$entity = $event['entity'];
$event_type = $event['name'] ?? '';

// On ne traite que les transactions approuvées
if ($event_type !== 'transaction.approved') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => 'Event type not handled']);
    exit;
}

// Récupération des détails de la transaction
$transaction_id = $entity['id'] ?? '';
$transaction_reference = $entity['reference'] ?? '';
$amount = $entity['amount'] ?? 0;
$status = $entity['status'] ?? '';
$callback_url = $entity['callback_url'] ?? '';

if (empty($transaction_id) || $status !== 'approved') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid transaction data']);
    exit;
}

// Extraction du token et type depuis l'URL de callback
parse_str(parse_url($callback_url, PHP_URL_QUERY), $params);
$token = $params['token'] ?? '';
$type = $params['type'] ?? '';

if (empty($token) || empty($type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing token or type in callback URL']);
    exit;
}

// Mise à jour de la base de données
try {
    $pdo->beginTransaction();

    if ($type === 'visite') {
        $stmt = $pdo->prepare("SELECT d.*, l.nom_fr as lieu_nom FROM demandes_visite d LEFT JOIN lieux l ON d.lieu_id = l.id WHERE d.token_paiement = ?");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();

        if ($demande && $demande['statut'] === 'proposition_envoyee') {
            $update_stmt = $pdo->prepare("UPDATE demandes_visite SET statut = 'payee', transaction_id = ? WHERE token_paiement = ?");
            $update_stmt->execute([$transaction_id, $token]);

            // Email de confirmation
            $subject = "Confirmation de votre réservation de voyage - Bénin Tourisme";
            $body = "Bonjour " . $demande['nom_client'] . ",\n\n";
            $body .= "Votre paiement pour la visite de [" . $demande['lieu_nom'] . "] a bien été validé.\n\n";
            $body .= "ID de transaction FedaPay : $transaction_id\n\n";
            $body .= "Votre bon de voyage est disponible en ligne.\n\n";
            $body .= "L'équipe Bénin Tourisme.";

            log_mail($demande['email_client'], $subject, $body);
        }

    } elseif ($type === 'hotel') {
        $stmt = $pdo->prepare("SELECT r.*, h.nom as hotel_nom FROM reservations_hebergement r LEFT JOIN hebergements h ON r.hebergement_id = h.id WHERE r.token_paiement = ?");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();

        if ($demande && $demande['statut'] === 'proposition_envoyee') {
            $update_stmt = $pdo->prepare("UPDATE reservations_hebergement SET statut = 'payee', transaction_id = ? WHERE token_paiement = ?");
            $update_stmt->execute([$transaction_id, $token]);

            // Email de confirmation
            $subject = "Confirmation de votre réservation d'hébergement - Bénin Tourisme";
            $body = "Bonjour " . $demande['nom_client'] . ",\n\n";
            $body .= "Votre paiement pour l'hébergement [" . $demande['hotel_nom'] . "] a bien été validé.\n\n";
            $body .= "ID de transaction FedaPay : $transaction_id\n\n";
            $body .= "Votre bon de réservation est disponible en ligne.\n\n";
            $body .= "L'équipe Bénin Tourisme.";

            log_mail($demande['email_client'], $subject, $body);
        }

    } elseif ($type === 'guide') {
        $stmt = $pdo->prepare("SELECT d.*, g.nom as guide_nom FROM demandes_guide d LEFT JOIN guides g ON d.guide_id = g.id WHERE d.token_paiement = ?");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();

        if ($demande && $demande['statut'] === 'proposition_envoyee') {
            $update_stmt = $pdo->prepare("UPDATE demandes_guide SET statut = 'payee', transaction_id = ? WHERE token_paiement = ?");
            $update_stmt->execute([$transaction_id, $token]);

            // Email de confirmation
            $subject = "Confirmation de votre demande de chauffeur-guide - Bénin Tourisme";
            $body = "Bonjour " . $demande['nom_client'] . ",\n\n";
            $body .= "Votre paiement pour le chauffeur-guide [" . $demande['guide_nom'] . "] a bien été validé.\n\n";
            $body .= "ID de transaction FedaPay : $transaction_id\n\n";
            $body .= "Votre bon de réservation est disponible en ligne.\n\n";
            $body .= "L'équipe Bénin Tourisme.";

            log_mail($demande['email_client'], $subject, $body);
        }
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Payment processed successfully']);

} catch (PDOException $e) {
    $pdo->rollBack();
    file_put_contents('../logs/webhook_fedapay.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
