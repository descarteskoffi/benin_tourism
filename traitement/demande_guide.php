<?php
require_once '../includes/fonctions.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../guides.php');
    exit;
}

// Nettoyage des données
$guide_id = isset($_POST['guide_id']) ? (int)$_POST['guide_id'] : 0;
$nom_client = isset($_POST['nom_client']) ? trim($_POST['nom_client']) : '';
$email_client = isset($_POST['email_client']) ? trim($_POST['email_client']) : '';
$telephone_client = isset($_POST['telephone_client']) ? trim($_POST['telephone_client']) : '';
$date_debut = isset($_POST['date_debut']) ? trim($_POST['date_debut']) : '';
$date_fin = isset($_POST['date_fin']) ? trim($_POST['date_fin']) : '';
$destination = isset($_POST['destination']) ? trim($_POST['destination']) : '';

// Validation des données obligatoires
if ($guide_id <= 0 || empty($nom_client) || empty($email_client) || empty($date_debut) || empty($date_fin)) {
    $_SESSION['guide_status'] = 'error';
    $_SESSION['guide_message'] = 'Veuillez remplir tous les champs obligatoires (*) du formulaire.';
    header('Location: ../guides.php');
    exit;
}

// Validation des dates
if (strtotime($date_debut) >= strtotime($date_fin)) {
    $_SESSION['guide_status'] = 'error';
    $_SESSION['guide_message'] = 'La date de fin doit être postérieure à la date de début.';
    header('Location: ../guides.php');
    exit;
}

// Validation de l'email
if (!filter_var($email_client, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['guide_status'] = 'error';
    $_SESSION['guide_message'] = 'L\'adresse email saisie est invalide.';
    header('Location: ../guides.php');
    exit;
}

try {
    // Vérification que le guide existe
    $guide_stmt = $pdo->prepare("SELECT nom, email, tarif_jour, vehicule_modele, gamme FROM guides WHERE id = ? AND disponible = 1");
    $guide_stmt->execute([$guide_id]);
    $guide = $guide_stmt->fetch();

    if (!$guide) {
        $_SESSION['guide_status'] = 'error';
        $_SESSION['guide_message'] = 'Le chauffeur-guide sélectionné n\'est pas disponible ou introuvable.';
        header('Location: ../guides.php');
        exit;
    }

    // Génération du token unique
    $token_paiement = bin2hex(random_bytes(32));

    // Pour l'instant booking, on passe le statut directement à 'proposition_envoyee'
    $sql = "INSERT INTO demandes_guide 
            (guide_id, nom_client, email_client, telephone_client, date_debut, date_fin, destination, statut, token_paiement) 
            VALUES 
            (:guide_id, :nom_client, :email_client, :telephone_client, :date_debut, :date_fin, :destination, 'proposition_envoyee', :token_paiement)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'guide_id' => $guide_id,
        'nom_client' => $nom_client,
        'email_client' => $email_client,
        'telephone_client' => $telephone_client,
        'date_debut' => $date_debut,
        'date_fin' => $date_fin,
        'destination' => $destination,
        'token_paiement' => $token_paiement
    ]);

    // Calculs de tarification détaillés
    $date1 = new DateTime($date_debut);
    $date2 = new DateTime($date_fin);
    $jours = $date1->diff($date2)->days;
    if ($jours <= 0) $jours = 1;

    // Répartition de la facture
    $prix_voiture_jour = $guide['tarif_jour'] * 0.6; // 60%
    $prix_chauffeur_jour = $guide['tarif_jour'] * 0.4; // 40%
    $frais_service = 3000; // frais de service fixes du site

    $total_voiture = $prix_voiture_jour * $jours;
    $total_chauffeur = $prix_chauffeur_jour * $jours;
    $total_global = ($guide['tarif_jour'] * $jours) + $frais_service;

    // Construction du lien de paiement dynamique
    $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    $lien_paiement = $protocole . '://' . $host . $base_path . '/payer_visite.php?token=' . $token_paiement;

    // Prénom du chauffeur
    $parts = explode(' ', $guide['nom']);
    $prenom_guide = $parts[0];

    // Notification par email
    $subject = "Confirmation de votre trajet - Chauffeur : " . $prenom_guide;
    $body = "Bonjour $nom_client,\n\n";
    $body .= "Votre demande de chauffeur-guide a bien été enregistrée pour votre trajet : $destination.\n\n";
    $body .= "Voici le détail de votre facture :\n";
    $body .= "---------------------------------------------------------\n";
    $body .= "- Location de voiture (" . $guide['vehicule_modele'] . " - " . $guide['gamme'] . ") :\n";
    $body .= "  " . number_format($prix_voiture_jour, 0, ',', ' ') . " FCFA / jour x $jours jours = " . number_format($total_voiture, 0, ',', ' ') . " FCFA\n";
    $body .= "- Prestation Chauffeur (" . $guide['nom'] . ") :\n";
    $body .= "  " . number_format($prix_chauffeur_jour, 0, ',', ' ') . " FCFA / jour x $jours jours = " . number_format($total_chauffeur, 0, ',', ' ') . " FCFA\n";
    $body .= "- Frais de service de la plateforme Bénin Tourisme :\n";
    $body .= "  " . number_format($frais_service, 0, ',', ' ') . " FCFA\n";
    $body .= "---------------------------------------------------------\n";
    $body .= "MONTANT TOTAL : " . number_format($total_global, 0, ',', ' ') . " FCFA\n\n";
    $body .= "Pour valider et procéder au paiement sécurisé via FedaPay (Mobile Money MTN / Moov ou Carte Bancaire), veuillez cliquer sur le lien ci-dessous :\n";
    $body .= "$lien_paiement\n\n";
    $body .= "Bon voyage !\nL'équipe Bénin Tourisme.";

    log_mail($email_client, $subject, $body);

    // Sauvegarder les variables de session pour afficher le modal de succès sur la page guides.php
    $_SESSION['guide_booking_success'] = true;
    $_SESSION['guide_booking_email'] = $email_client;

    session_write_close();
    header('Location: ../guides.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['guide_status'] = 'error';
    $_SESSION['guide_message'] = 'Erreur technique lors du traitement de la réservation : ' . $e->getMessage();
    session_write_close();
    header('Location: ../guides.php');
    exit;
}
