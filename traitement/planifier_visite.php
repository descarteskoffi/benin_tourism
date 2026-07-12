<?php
require_once '../includes/fonctions.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../lieux.php');
    exit;
}

// Récupération et nettoyage des données
$lieu_id = isset($_POST['lieu_id']) ? (int)$_POST['lieu_id'] : 0;
$nom_client = isset($_POST['nom_client']) ? trim($_POST['nom_client']) : '';
$email_client = isset($_POST['email_client']) ? trim($_POST['email_client']) : '';
$telephone_client = isset($_POST['telephone_client']) ? trim($_POST['telephone_client']) : '';
$date_arrivee = isset($_POST['date_arrivee']) ? trim($_POST['date_arrivee']) : '';
$date_depart = isset($_POST['date_depart']) ? trim($_POST['date_depart']) : '';
$nb_personnes = isset($_POST['nb_personnes']) ? (int)$_POST['nb_personnes'] : 1;
$besoin_hebergement = isset($_POST['besoin_hebergement']) ? 1 : 0;

// Validation des données requises
if ($lieu_id <= 0 || empty($nom_client) || empty($email_client) || empty($telephone_client) || empty($date_arrivee) || empty($date_depart)) {
    $_SESSION['plan_status'] = 'error';
    $_SESSION['plan_message'] = 'Veuillez remplir tous les champs obligatoires (*) du formulaire.';
    session_write_close();
    header('Location: ../lieu.php?id=' . $lieu_id);
    exit;
}

// Validation des dates
if (strtotime($date_arrivee) >= strtotime($date_depart)) {
    $_SESSION['plan_status'] = 'error';
    $_SESSION['plan_message'] = 'La date de départ doit être postérieure à la date d\'arrivée.';
    session_write_close();
    header('Location: ../lieu.php?id=' . $lieu_id);
    exit;
}

// Validation de l'adresse email
if (!filter_var($email_client, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['plan_status'] = 'error';
    $_SESSION['plan_message'] = 'L\'adresse email saisie est invalide.';
    session_write_close();
    header('Location: ../lieu.php?id=' . $lieu_id);
    exit;
}

try {
    // Vérification de l'existence du lieu
    $lieu_stmt = $pdo->prepare("SELECT id FROM lieux WHERE id = ?");
    $lieu_stmt->execute([$lieu_id]);
    if (!$lieu_stmt->fetch()) {
        $_SESSION['plan_status'] = 'error';
        $_SESSION['plan_message'] = 'Le lieu sélectionné est introuvable.';
        header('Location: ../lieux.php');
        exit;
    }

    // Génération d'un token sécurisé unique pour le futur paiement
    $token_paiement = bin2hex(random_bytes(32));

    // Insertion en BDD
    $sql = "INSERT INTO demandes_visite 
            (lieu_id, nom_client, email_client, telephone_client, date_arrivee, date_depart, nb_personnes, besoin_hebergement, statut, token_paiement) 
            VALUES 
            (:lieu_id, :nom_client, :email_client, :telephone_client, :date_arrivee, :date_depart, :nb_personnes, :besoin_hebergement, 'nouvelle', :token_paiement)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'lieu_id' => $lieu_id,
        'nom_client' => $nom_client,
        'email_client' => $email_client,
        'telephone_client' => $telephone_client,
        'date_arrivee' => $date_arrivee,
        'date_depart' => $date_depart,
        'nb_personnes' => $nb_personnes,
        'besoin_hebergement' => $besoin_hebergement,
        'token_paiement' => $token_paiement
    ]);

    $_SESSION['plan_status'] = 'success';
    $_SESSION['plan_message'] = 'Votre demande de planification de visite a bien été enregistrée. Notre équipe vous répondra par e-mail dans un délai de 24 heures avec une proposition.';

} catch (Exception $e) {
    $_SESSION['plan_status'] = 'error';
    $_SESSION['plan_message'] = 'Erreur technique lors de l\'enregistrement de votre demande : ' . $e->getMessage();
}

session_write_close();
header('Location: ../lieu.php?id=' . $lieu_id);
exit;
