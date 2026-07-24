<?php
require_once '../includes/fonctions.php';
require_once '../config/database.php';
require_once '../config/smtp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$montant = isset($_POST['montant']) ? (int)$_POST['montant'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : 'Paiement Bénin Tourisme';

if (empty($token) || empty($type) || $montant <= 0) {
    die("Paramètres de paiement invalides.");
}

// Récupération de la demande selon le type
$demande = null;
try {
    if ($type === 'visite') {
        $stmt = $pdo->prepare("SELECT * FROM demandes_visite WHERE token_paiement = ? AND statut = 'proposition_envoyee'");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();
    } elseif ($type === 'hotel') {
        $stmt = $pdo->prepare("SELECT * FROM reservations_hebergement WHERE token_paiement = ? AND statut = 'proposition_envoyee'");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();
    } elseif ($type === 'guide') {
        $stmt = $pdo->prepare("SELECT * FROM demandes_guide WHERE token_paiement = ? AND statut = 'proposition_envoyee'");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();
    }

    if (!$demande) {
        die("Demande introuvable ou déjà payée.");
    }

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Construction de l'URL de callback
$protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$callback_url = $protocole . '://' . $host . $base_path . '/traitement/retour_paiement.php';

// Clé API FedaPay
$fedapay_key = defined('FEDAPAY_SANDBOX_SECRET_KEY') ? FEDAPAY_SANDBOX_SECRET_KEY : '';
$fedapay_mode = 'sandbox'; // Ou 'live' pour la production

if (empty($fedapay_key)) {
    die("Clé API FedaPay non configurée. Veuillez contacter l'administrateur.");
}

// Préparation des données client
$customer_phone = preg_replace("/[^0-9]/", "", $demande['telephone_client']);
if (substr($customer_phone, 0, 3) === '229') {
    $customer_phone = substr($customer_phone, 3);
} elseif (substr($customer_phone, 0, 2) === '00') {
    $customer_phone = substr($customer_phone, 2);
    if (substr($customer_phone, 0, 3) === '229') {
        $customer_phone = substr($customer_phone, 3);
    }
}

// Création de la transaction via l'API FedaPay
$fedapay_data = [
    'description' => $description,
    'amount' => $montant,
    'currency' => [
        'iso' => 'XOF'
    ],
    'callback_url' => $callback_url . '?token=' . urlencode($token) . '&type=' . urlencode($type),
    'customer' => [
        'firstname' => $demande['nom_client'],
        'lastname' => $demande['nom_client'],
        'email' => $demande['email_client'],
        'phone_number' => [
            'number' => $customer_phone,
            'country' => 'bj'
        ]
    ]
];

// Envoi de la requête à FedaPay
$ch = curl_init("https://api.fedapay.com/v1/transactions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fedapay_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $fedapay_key,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 && $http_code !== 201) {
    $error_data = json_decode($response, true);
    $error_message = isset($error_data['message']) ? $error_data['message'] : 'Erreur inconnue';
    die("Erreur lors de la création de la transaction FedaPay : " . $error_message . " (Code: $http_code)");
}

$result = json_decode($response, true);

if (!isset($result['v1/transaction']['token'])) {
    die("Erreur : Token de transaction FedaPay non reçu.");
}

// Récupération du token de la transaction
$transaction_token = $result['v1/transaction']['token'];

// Construction de l'URL de redirection FedaPay
$fedapay_checkout_url = "https://" . $fedapay_mode . ".fedapay.com/checkout/" . $transaction_token;

// Redirection vers FedaPay
header('Location: ' . $fedapay_checkout_url);
exit;
