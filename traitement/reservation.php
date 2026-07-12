<?php
require_once '../includes/fonctions.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../hebergements.php');
    exit;
}

// Nettoyage et récupération des données
$hebergement_id = isset($_POST['hebergement_id']) ? (int)$_POST['hebergement_id'] : 0;
$nom_client = isset($_POST['nom_client']) ? trim($_POST['nom_client']) : '';
$email_client = isset($_POST['email_client']) ? trim($_POST['email_client']) : '';
$telephone_client = isset($_POST['telephone_client']) ? trim($_POST['telephone_client']) : '';
$date_arrivee = isset($_POST['date_arrivee']) ? trim($_POST['date_arrivee']) : '';
$date_depart = isset($_POST['date_depart']) ? trim($_POST['date_depart']) : '';
$nb_personnes = isset($_POST['nb_personnes']) ? (int)$_POST['nb_personnes'] : 1;
$type_chambre = isset($_POST['type_chambre']) ? trim($_POST['type_chambre']) : 'standard';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation des données obligatoires
if ($hebergement_id <= 0 || empty($nom_client) || empty($email_client) || empty($date_arrivee) || empty($date_depart)) {
    $_SESSION['booking_status'] = 'error';
    $_SESSION['booking_message'] = 'Veuillez remplir tous les champs obligatoires (*) du formulaire.';
    header('Location: ../hebergements.php');
    exit;
}

// Validation des dates
if (strtotime($date_arrivee) >= strtotime($date_depart)) {
    $_SESSION['booking_status'] = 'error';
    $_SESSION['booking_message'] = 'La date de départ doit être postérieure à la date d\'arrivée.';
    header('Location: ../hebergements.php');
    exit;
}

// Validation de l'email
if (!filter_var($email_client, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['booking_status'] = 'error';
    $_SESSION['booking_message'] = 'L\'adresse email saisie est invalide.';
    header('Location: ../hebergements.php');
    exit;
}

try {
    // Vérifier que l'hébergement existe
    $hotel_stmt = $pdo->prepare("SELECT nom, email_contact FROM hebergements WHERE id = ?");
    $hotel_stmt->execute([$hebergement_id]);
    $hotel = $hotel_stmt->fetch();

    if (!$hotel) {
        $_SESSION['booking_status'] = 'error';
        $_SESSION['booking_message'] = 'L\'hébergement sélectionné est introuvable.';
        header('Location: ../hebergements.php');
        exit;
    }

    // Génération du token unique
    $token_paiement = bin2hex(random_bytes(32));

    // Insertion de la réservation dans la base
    $sql = "INSERT INTO reservations_hebergement 
            (hebergement_id, nom_client, email_client, telephone_client, date_arrivee, date_depart, nb_personnes, type_chambre, message, statut, token_paiement) 
            VALUES 
            (:hebergement_id, :nom_client, :email_client, :telephone_client, :date_arrivee, :date_depart, :nb_personnes, :type_chambre, :message, 'nouvelle', :token_paiement)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'hebergement_id' => $hebergement_id,
        'nom_client' => $nom_client,
        'email_client' => $email_client,
        'telephone_client' => $telephone_client,
        'date_arrivee' => $date_arrivee,
        'date_depart' => $date_depart,
        'nb_personnes' => $nb_personnes,
        'type_chambre' => $type_chambre,
        'message' => $message,
        'token_paiement' => $token_paiement
    ]);

    // Préparation de l'email de notification (simulé via log_mail)
    $email_hotel = !empty($hotel['email_contact']) ? $hotel['email_contact'] : 'resa@benintourisme.bj';
    
    $subject = "Nouvelle demande de réservation pour : " . $hotel['nom'];
    $body = "Bonjour,\n\n";
    $body .= "Une nouvelle demande de réservation a été enregistrée sur le site Bénin Tourisme & Services :\n\n";
    $body .= "--- INFORMATIONS CLIENT ---\n";
    $body .= "Nom : $nom_client\n";
    $body .= "Email : $email_client\n";
    $body .= "Téléphone : $telephone_client\n\n";
    $body .= "--- INFORMATIONS SÉJOUR ---\n";
    $body .= "Établissement : " . $hotel['nom'] . "\n";
    $body .= "Chambre : " . ucfirst($type_chambre) . "\n";
    $body .= "Date d'arrivée : $date_arrivee\n";
    $body .= "Date de départ : $date_depart\n";
    $body .= "Nombre de personnes : $nb_personnes\n";
    $body .= "Message / Demandes : $message\n\n";
    $body .= "Veuillez vous connecter au back-office d'administration pour traiter cette demande.\n";
    $body .= "L'équipe Bénin Tourisme.";

    // Envoi/Journalisation de l'email
    log_mail($email_hotel, $subject, $body); // Vers l'hôtel
    log_mail($email_client, "Confirmation de votre demande - Bénin Tourisme", "Bonjour $nom_client,\n\nVotre demande de réservation pour l'hébergement " . $hotel['nom'] . " (Chambre " . ucfirst($type_chambre) . ") du $date_arrivee au $date_depart a bien été reçue.\n\nNotre équipe validera la disponibilité et vous recontactera sous 24h par e-mail avec un lien de paiement pour confirmer votre réservation.\n\nCordialement,\nL'équipe Bénin Tourisme."); // Vers le client

    $_SESSION['booking_status'] = 'success';
    $_SESSION['booking_message'] = 'Votre demande de réservation a bien été enregistrée. Un retour vous sera fait par e-mail sous 24 heures.';

} catch (PDOException $e) {
    $_SESSION['booking_status'] = 'error';
    $_SESSION['booking_message'] = 'Erreur technique lors de l\'enregistrement : ' . $e->getMessage();
}

session_write_close();
header('Location: ../hebergements.php');
exit;
