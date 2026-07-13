<?php
require_once '../includes/fonctions.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../contact.php');
    exit;
}

// Nettoyage des données
$nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$sujet = isset($_POST['sujet']) ? trim($_POST['sujet']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
    $_SESSION['contact_status'] = 'error';
    $_SESSION['contact_message'] = 'Veuillez remplir tous les champs obligatoires du formulaire.';
    header('Location: ../contact.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['contact_status'] = 'error';
    $_SESSION['contact_message'] = 'L\'adresse email saisie est invalide.';
    header('Location: ../contact.php');
    exit;
}

try {
    // Insertion en base de données
    $sql = "INSERT INTO messages_contact (nom, email, sujet, message) VALUES (:nom, :email, :sujet, :message)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nom' => $nom,
        'email' => $email,
        'sujet' => $sujet,
        'message' => $message
    ]);

    // Envoi d'un email de notification à l'administrateur
    $admin_email = 'admin@benintourisme.bj';
    $email_subject = "Nouveau message de contact : " . $sujet;
    
    $email_body = "Bonjour,\n\n";
    $email_body .= "Un nouveau message a été envoyé depuis le formulaire de contact de Bénin Tourisme & Services :\n\n";
    $email_body .= "Nom de l'expéditeur : $nom\n";
    $email_body .= "E-mail de l'expéditeur : $email\n";
    $email_body .= "Sujet : $sujet\n\n";
    $email_body .= "--- MESSAGE ---\n";
    $email_body .= "$message\n\n";
    $email_body .= "Vous pouvez consulter et gérer ce message sur le tableau de bord de l'administration.\n";
    $email_body .= "L'équipe Bénin Tourisme.";

    log_mail($admin_email, $email_subject, $email_body);

    // Email de courtoisie à l'expéditeur
    $client_body = "Bonjour $nom,\n\n";
    $client_body .= "Nous avons bien reçu votre message concernant le sujet : \"$sujet\".\n";
    $client_body .= "Notre équipe l'étudie et reviendra vers vous rapidement.\n\n";
    $client_body .= "Cordialement,\nL'équipe Bénin Tourisme.";
    
    log_mail($email, "Accusé de réception de votre message - Bénin Tourisme", $client_body);

    $_SESSION['contact_status'] = 'success';
    $_SESSION['contact_message'] = 'Votre message a bien été transmis. Nous vous répondrons rapidement.';
    // Variables pour le modal de succès
    $_SESSION['contact_success'] = true;
    $_SESSION['contact_email'] = $email;

} catch (PDOException $e) {
    $_SESSION['contact_status'] = 'error';
    $_SESSION['contact_message'] = 'Erreur technique lors de la transmission du message : ' . $e->getMessage();
}

header('Location: ../contact.php');
exit;
