<?php
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/database.php';

// Si déjà connecté, redirection directe vers l'index admin
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE nom_utilisateur = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['mot_de_passe_hash'])) {
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_username'] = $admin['nom_utilisateur'];
                header('Location: index.php');
                exit;
            } else {
                $error = "Identifiant ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion à la base de données : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

require_once 'header.php';
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <a href="../index.php" class="text-decoration-none">
                    <h2 class="text-success fw-bold"><i class="fa-solid fa-map-location-dot me-2"></i>BÉNIN TOURISME</h2>
                </a>
                <p class="text-muted">Espace d'Administration Sécurisé</p>
            </div>
            
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h3 class="card-title h5 text-center mb-4"><i class="fa-solid fa-lock me-2 text-success"></i>Connexion</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 small" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php">
                        <!-- Nom d'utilisateur -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nom d'utilisateur</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
                            </div>
                        </div>
                        
                        <!-- Mot de passe -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-key"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>
                        
                        <!-- Submit -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success py-2 fw-bold text-uppercase">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Se connecter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="../index.php" class="text-success small text-decoration-none">
                    <i class="fa-solid fa-arrow-left me-1"></i> Retour au site public
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
