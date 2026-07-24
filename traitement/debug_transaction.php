<?php
/**
 * Script de débogage des transactions FedaPay
 * 
 * Permet de vérifier le statut d'une transaction directement via l'API FedaPay
 * Accès : http://localhost/benin_tourism/traitement/debug_transaction.php?transaction_id=XXX
 * 
 * ⚠️ SUPPRIMEZ CE FICHIER EN PRODUCTION !
 */

require_once '../config/smtp.php';
require_once '../config/database.php';

// Vérifier que la clé est configurée
if (!defined('FEDAPAY_SANDBOX_SECRET_KEY') || empty(FEDAPAY_SANDBOX_SECRET_KEY)) {
    die("Clé API FedaPay non configurée. Consultez CONFIGURATION_FEDAPAY.md");
}

$transaction_id = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Transaction FedaPay</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #007bff;
            color: white;
        }
        table tr:hover {
            background: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Transaction FedaPay</h1>
        
        <form method="GET">
            <div class="form-group">
                <label for="transaction_id">ID de Transaction FedaPay :</label>
                <input type="text" id="transaction_id" name="transaction_id" value="<?= htmlspecialchars($transaction_id) ?>" placeholder="Exemple : 12345 ou txn_xxx">
            </div>
            
            <div class="form-group">
                <label for="token">OU Token de Paiement (du site) :</label>
                <input type="text" id="token" name="token" value="<?= htmlspecialchars($token) ?>" placeholder="Token généré lors de la demande">
                <small style="color: #666;">Si vous avez le token, le système récupérera automatiquement l'ID de transaction</small>
            </div>
            
            <button type="submit">🔎 Rechercher</button>
        </form>
        
        <?php
        if (!empty($token) && empty($transaction_id)) {
            // Rechercher la transaction dans la base de données
            echo '<div class="result">';
            echo '<h3>📊 Recherche dans la base de données locale</h3>';
            
            try {
                // Recherche dans toutes les tables
                $found = false;
                
                // Demandes de visite
                $stmt = $pdo->prepare("SELECT 'visite' as type, transaction_id, statut, nom_client, email_client FROM demandes_visite WHERE token_paiement = ?");
                $stmt->execute([$token]);
                $result = $stmt->fetch();
                
                if (!$result) {
                    // Réservations d'hébergement
                    $stmt = $pdo->prepare("SELECT 'hotel' as type, transaction_id, statut, nom_client, email_client FROM reservations_hebergement WHERE token_paiement = ?");
                    $stmt->execute([$token]);
                    $result = $stmt->fetch();
                }
                
                if (!$result) {
                    // Demandes de guide
                    $stmt = $pdo->prepare("SELECT 'guide' as type, transaction_id, statut, nom_client, email_client FROM demandes_guide WHERE token_paiement = ?");
                    $stmt->execute([$token]);
                    $result = $stmt->fetch();
                }
                
                if ($result) {
                    echo '<table>';
                    echo '<tr><th>Champ</th><th>Valeur</th></tr>';
                    echo '<tr><td>Type</td><td>' . htmlspecialchars($result['type']) . '</td></tr>';
                    echo '<tr><td>ID Transaction FedaPay</td><td>' . htmlspecialchars($result['transaction_id'] ?: 'Non encore payée') . '</td></tr>';
                    echo '<tr><td>Statut</td><td>' . htmlspecialchars($result['statut']) . '</td></tr>';
                    echo '<tr><td>Client</td><td>' . htmlspecialchars($result['nom_client']) . '</td></tr>';
                    echo '<tr><td>Email</td><td>' . htmlspecialchars($result['email_client']) . '</td></tr>';
                    echo '</table>';
                    
                    if (!empty($result['transaction_id'])) {
                        $transaction_id = $result['transaction_id'];
                        $found = true;
                    }
                } else {
                    echo '<p style="color: #dc3545;">❌ Aucune demande trouvée avec ce token.</p>';
                }
            } catch (PDOException $e) {
                echo '<p style="color: #dc3545;">Erreur BDD : ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            
            echo '</div>';
        }
        
        if (!empty($transaction_id)) {
            echo '<div class="result">';
            echo '<h3>🌐 Requête à l\'API FedaPay</h3>';
            
            // Requête à l'API FedaPay
            $ch = curl_init("https://sandbox-api.fedapay.com/v1/transactions/" . urlencode($transaction_id));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . FEDAPAY_SANDBOX_SECRET_KEY,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if (!empty($curl_error)) {
                echo '<div class="error">';
                echo '<p>❌ Erreur cURL : ' . htmlspecialchars($curl_error) . '</p>';
                echo '</div>';
            } elseif ($http_code === 404) {
                echo '<div class="error">';
                echo '<p>❌ Transaction non trouvée sur FedaPay</p>';
                echo '<p>ID recherché : <code>' . htmlspecialchars($transaction_id) . '</code></p>';
                echo '</div>';
            } elseif ($http_code === 401) {
                echo '<div class="error">';
                echo '<p>❌ Erreur d\'authentification</p>';
                echo '<p>Vérifiez votre clé secrète FedaPay dans config/smtp.php</p>';
                echo '</div>';
            } elseif ($http_code === 200) {
                $data = json_decode($response, true);
                
                if (isset($data['v1/transaction'])) {
                    $transaction = $data['v1/transaction'];
                    
                    echo '<div class="success">';
                    echo '<h4>✅ Transaction trouvée</h4>';
                    echo '<table>';
                    echo '<tr><th>Propriété</th><th>Valeur</th></tr>';
                    echo '<tr><td>ID</td><td>' . htmlspecialchars($transaction['id']) . '</td></tr>';
                    echo '<tr><td>Référence</td><td>' . htmlspecialchars($transaction['reference'] ?? 'N/A') . '</td></tr>';
                    echo '<tr><td>Montant</td><td>' . number_format($transaction['amount'], 0, ',', ' ') . ' ' . ($transaction['currency']['iso'] ?? 'XOF') . '</td></tr>';
                    echo '<tr><td>Statut</td><td><strong>' . htmlspecialchars($transaction['status']) . '</strong></td></tr>';
                    echo '<tr><td>Description</td><td>' . htmlspecialchars($transaction['description'] ?? 'N/A') . '</td></tr>';
                    echo '<tr><td>Date création</td><td>' . htmlspecialchars($transaction['created_at'] ?? 'N/A') . '</td></tr>';
                    echo '<tr><td>Date mise à jour</td><td>' . htmlspecialchars($transaction['updated_at'] ?? 'N/A') . '</td></tr>';
                    
                    if (isset($transaction['customer'])) {
                        echo '<tr><td>Client Email</td><td>' . htmlspecialchars($transaction['customer']['email'] ?? 'N/A') . '</td></tr>';
                        echo '<tr><td>Client Nom</td><td>' . htmlspecialchars($transaction['customer']['firstname'] ?? '') . ' ' . htmlspecialchars($transaction['customer']['lastname'] ?? '') . '</td></tr>';
                    }
                    
                    echo '</table>';
                    echo '</div>';
                    
                    echo '<h4>📄 Réponse JSON complète :</h4>';
                    echo '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                }
            } else {
                echo '<div class="error">';
                echo '<p>❌ Erreur API FedaPay (Code HTTP : ' . $http_code . ')</p>';
                echo '<pre>' . htmlspecialchars($response) . '</pre>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
            <strong>⚠️ Attention :</strong> Ce fichier est uniquement pour le débogage. Supprimez-le avant la mise en production !
        </div>
    </div>
</body>
</html>
