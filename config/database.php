<?php
$hote = 'localhost';
$base = 'tourisme';
$utilisateur = 'root';
$motdepasse = '';

try {
    $pdo = new PDO("mysql:host=$hote;dbname=$base;charset=utf8mb4",
        $utilisateur, $motdepasse, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
} catch (PDOException $e) {
    // Si la base n'est pas encore créée en local, on affiche un message d'erreur clair
    die('Erreur de connexion à la base de données : ' . $e->getMessage() . '. Veuillez vérifier que le serveur MySQL est démarré et que la base de données "' . $base . '" a été importée avec le schéma SQL.');
}
