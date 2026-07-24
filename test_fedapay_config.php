<?php
/**
 * Script de test de configuration FedaPay
 * 
 * Ce fichier vérifie que votre configuration FedaPay est correcte.
 * Accédez à : http://localhost/benin_tourism/test_fedapay_config.php
 * 
 * ⚠️ SUPPRIMEZ CE FICHIER EN PRODUCTION !
 */

require_once 'config/smtp.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Configuration FedaPay</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
            border-bottom: 3px solid #28a745;
            padding-bottom: 10px;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #ccc;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .code {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            word-break: break-all;
        }
        .icon {
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test de Configuration FedaPay</h1>
        
        <h2>1. Vérification des Constantes PHP</h2>
        
        <?php
        // Test de la clé publique
        if (defined('FEDAPAY_SANDBOX_KEY') && !empty(FEDAPAY_SANDBOX_KEY)) {
            echo '<div class="test-item success">';
            echo '<span class="icon">✅</span>';
            echo '<strong>Clé Publique Sandbox :</strong> Configurée<br>';
            echo '<div class="code">' . htmlspecialchars(FEDAPAY_SANDBOX_KEY) . '</div>';
            echo '</div>';
        } else {
            echo '<div class="test-item error">';
            echo '<span class="icon">❌</span>';
            echo '<strong>Clé Publique Sandbox :</strong> NON CONFIGURÉE';
            echo '</div>';
        }
        
        // Test de la clé secrète
        if (defined('FEDAPAY_SANDBOX_SECRET_KEY') && !empty(FEDAPAY_SANDBOX_SECRET_KEY)) {
            $secret_key = FEDAPAY_SANDBOX_SECRET_KEY;
            if (strpos($secret_key, 'VOTRE_CLE') !== false || strpos($secret_key, 'XXX') !== false) {
                echo '<div class="test-item warning">';
                echo '<span class="icon">⚠️</span>';
                echo '<strong>Clé Secrète Sandbox :</strong> Valeur par défaut détectée<br>';
                echo 'Vous devez remplacer cette valeur par votre vraie clé secrète FedaPay.';
                echo '</div>';
            } else {
                echo '<div class="test-item success">';
                echo '<span class="icon">✅</span>';
                echo '<strong>Clé Secrète Sandbox :</strong> Configurée<br>';
                echo '<div class="code">' . substr($secret_key, 0, 20) . '...</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="test-item error">';
            echo '<span class="icon">❌</span>';
            echo '<strong>Clé Secrète Sandbox :</strong> NON CONFIGURÉE<br>';
            echo 'Ajoutez <code>define(\'FEDAPAY_SANDBOX_SECRET_KEY\', \'sk_sandbox_...\');</code> dans config/smtp.php';
            echo '</div>';
        }
        ?>
        
        <h2>2. Vérification des Extensions PHP</h2>
        
        <?php
        // Test de cURL
        if (extension_loaded('curl')) {
            echo '<div class="test-item success">';
            echo '<span class="icon">✅</span>';
            echo '<strong>Extension cURL :</strong> Activée';
            echo '</div>';
        } else {
            echo '<div class="test-item error">';
            echo '<span class="icon">❌</span>';
            echo '<strong>Extension cURL :</strong> NON ACTIVÉE<br>';
            echo 'L\'extension cURL est requise pour communiquer avec l\'API FedaPay.';
            echo '</div>';
        }
        
        // Test de JSON
        if (extension_loaded('json')) {
            echo '<div class="test-item success">';
            echo '<span class="icon">✅</span>';
            echo '<strong>Extension JSON :</strong> Activée';
            echo '</div>';
        } else {
            echo '<div class="test-item error">';
            echo '<span class="icon">❌</span>';
            echo '<strong>Extension JSON :</strong> NON ACTIVÉE';
            echo '</div>';
        }
        ?>
        
        <h2>3. Test de Connexion à l'API FedaPay</h2>
        
        <?php
        if (defined('FEDAPAY_SANDBOX_SECRET_KEY') && 
            !empty(FEDAPAY_SANDBOX_SECRET_KEY) && 
            strpos(FEDAPAY_SANDBOX_SECRET_KEY, 'VOTRE_CLE') === false &&
            extension_loaded('curl')) {
            
            // Test simple de connexion
            $ch = curl_init("https://sandbox-api.fedapay.com/v1/transactions");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . FEDAPAY_SANDBOX_SECRET_KEY,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($http_code === 200 || $http_code === 401) {
                if ($http_code === 401) {
                    echo '<div class="test-item error">';
                    echo '<span class="icon">❌</span>';
                    echo '<strong>Connexion API :</strong> Clé d\'authentification invalide<br>';
                    echo 'Code HTTP : ' . $http_code . '<br>';
                    echo 'Vérifiez que votre clé secrète est correcte sur votre tableau de bord FedaPay.';
                    echo '</div>';
                } else {
                    echo '<div class="test-item success">';
                    echo '<span class="icon">✅</span>';
                    echo '<strong>Connexion API :</strong> Succès<br>';
                    echo 'Code HTTP : ' . $http_code;
                    echo '</div>';
                }
            } else {
                echo '<div class="test-item error">';
                echo '<span class="icon">❌</span>';
                echo '<strong>Connexion API :</strong> Échec<br>';
                echo 'Code HTTP : ' . $http_code . '<br>';
                if (!empty($curl_error)) {
                    echo 'Erreur cURL : ' . htmlspecialchars($curl_error);
                }
                echo '</div>';
            }
        } else {
            echo '<div class="test-item warning">';
            echo '<span class="icon">⚠️</span>';
            echo '<strong>Test de Connexion :</strong> Impossible<br>';
            echo 'Complétez d\'abord la configuration ci-dessus.';
            echo '</div>';
        }
        ?>
        
        <h2>4. Vérification des Fichiers</h2>
        
        <?php
        $files_to_check = [
            'traitement/creer_paiement.php' => 'Création de paiement',
            'traitement/retour_paiement.php' => 'Retour de paiement',
            'traitement/webhook_fedapay.php' => 'Webhook FedaPay',
            'logs/webhook_fedapay.log' => 'Logs webhook'
        ];
        
        foreach ($files_to_check as $file => $description) {
            if (file_exists($file)) {
                echo '<div class="test-item success">';
                echo '<span class="icon">✅</span>';
                echo '<strong>' . htmlspecialchars($description) . ' :</strong> Fichier présent';
                echo '</div>';
            } else {
                echo '<div class="test-item error">';
                echo '<span class="icon">❌</span>';
                echo '<strong>' . htmlspecialchars($description) . ' :</strong> Fichier manquant<br>';
                echo '<code>' . htmlspecialchars($file) . '</code>';
                echo '</div>';
            }
        }
        ?>
        
        <h2>5. URLs de Configuration</h2>
        
        <div class="test-item">
            <strong>URL de Callback :</strong>
            <div class="code">
                <?php
                $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                echo htmlspecialchars($protocole . '://' . $host . $base_path . '/traitement/retour_paiement.php');
                ?>
            </div>
        </div>
        
        <div class="test-item">
            <strong>URL Webhook (à configurer sur FedaPay) :</strong>
            <div class="code">
                <?php
                echo htmlspecialchars($protocole . '://' . $host . $base_path . '/traitement/webhook_fedapay.php');
                ?>
            </div>
        </div>
        
        <h2>📚 Prochaines Étapes</h2>
        
        <div class="test-item" style="border-left-color: #17a2b8;">
            <ol>
                <li>Si des erreurs apparaissent ci-dessus, corrigez-les d'abord</li>
                <li>Obtenez votre clé secrète depuis : <a href="https://sandbox.fedapay.com" target="_blank">https://sandbox.fedapay.com</a></li>
                <li>Mettez à jour <code>config/smtp.php</code> avec votre vraie clé secrète</li>
                <li>Configurez le webhook dans votre tableau de bord FedaPay (optionnel)</li>
                <li>Testez un paiement depuis votre site</li>
                <li><strong>⚠️ SUPPRIMEZ CE FICHIER DE TEST AVANT LA MISE EN PRODUCTION !</strong></li>
            </ol>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 5px; border-left: 4px solid #007bff;">
            <strong>📖 Documentation :</strong> Consultez <code>CONFIGURATION_FEDAPAY.md</code> pour plus de détails.
        </div>
    </div>
</body>
</html>
