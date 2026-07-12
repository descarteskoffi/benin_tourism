<?php
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// Validation de l'identifiant du lieu
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: lieux.php');
    exit;
}

try {
    // Récupération des informations du lieu
    $stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
    $stmt->execute([$id]);
    $lieu = $stmt->fetch();

    if (!$lieu) {
        header('Location: lieux.php');
        exit;
    }

    // Récupération des photos de la galerie
    $photos_stmt = $pdo->prepare("SELECT * FROM lieux_photos WHERE lieu_id = ?");
    $photos_stmt->execute([$id]);
    $galerie = $photos_stmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur technique de chargement du lieu : " . $e->getMessage());
}

// SEO Metas
$page_title = db_trans($lieu, 'nom');
$page_desc = db_trans($lieu, 'description_courte');

require_once 'includes/header.php';

// Récupération des retours de session pour la planification
$plan_status = isset($_SESSION['plan_status']) ? $_SESSION['plan_status'] : null;
$plan_message = isset($_SESSION['plan_message']) ? $_SESSION['plan_message'] : null;
unset($_SESSION['plan_status'], $_SESSION['plan_message']);
?>

<!-- Messages flash de succès/erreur -->
<?php if ($plan_status === 'success'): ?>
    <div class="container mt-4">
        <div class="alert alert-success alert-dismissible fade show py-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= e($plan_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php elseif ($plan_status === 'error'): ?>
    <div class="container mt-4">
        <div class="alert alert-danger alert-dismissible fade show py-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= e($plan_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<!-- En-tête de la page de détails -->
<section class="lieu-detail-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-success text-decoration-none"><?= __('nav_home') ?></a></li>
                <li class="breadcrumb-item"><a href="lieux.php" class="text-success text-decoration-none"><?= __('nav_places') ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e(db_trans($lieu, 'nom')) ?></li>
            </ol>
        </nav>
        <h1 class="lieu-title my-3"><?= e(db_trans($lieu, 'nom')) ?></h1>
        <div class="fs-5 text-muted">
            <i class="fa-solid fa-location-dot text-accent me-2"></i>
            <?= e(db_trans($lieu, 'region')) ?> · <span class="badge bg-success"><?= e($lieu['categorie']) ?></span>
        </div>
    </div>
</section>

<!-- Section Principale -->
<section class="section-padding">
    <div class="container">
        <div class="row g-5">
            <!-- Colonne Gauche : Image, Histoire, Galerie -->
            <div class="col-lg-10 mx-auto">
                <!-- Image principale -->
                <div class="position-relative mb-4 rounded-3 overflow-hidden shadow-sm" style="max-height: 480px;">
                    <img src="<?= get_image_url($lieu['photo_principale'], $lieu['categorie']) ?>" class="w-100 h-100 object-fit-cover" alt="<?= e(db_trans($lieu, 'nom')) ?>" style="max-height: 480px; object-fit: cover;">
                </div>

                <!-- Histoire / Récit -->
                <div class="mb-5">
                    <h2 class="mb-4 text-success border-bottom pb-2"><i class="fa-solid fa-book-open me-2"></i><?= __('history_title') ?></h2>
                    <p class="fs-6 lh-lg text-secondary" style="white-space: pre-line;">
                        <?= e(db_trans($lieu, 'histoire')) ?>
                    </p>
                </div>

                <!-- Galerie Photos Supplémentaires -->
                <?php if (!empty($galerie)): ?>
                    <div class="mb-5">
                        <h2 class="mb-4 text-success border-bottom pb-2"><i class="fa-solid fa-images me-2"></i>Galerie Photos</h2>
                        <div class="row g-3">
                            <?php foreach ($galerie as $photo): ?>
                                <div class="col-md-4 col-6">
                                    <div class="gallery-thumbnail">
                                        <img src="<?= get_image_url($photo['chemin_photo'], $lieu['categorie']) ?>" alt="Photo de <?= e(db_trans($lieu, 'nom')) ?>" class="img-fluid rounded">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulaire de Planification Unifié -->
                <div class="card border-0 shadow-sm rounded-3 mt-5" id="planifier-form">
                    <div class="card-body p-4">
                        <h2 class="h3 text-success mb-4 pb-2 border-bottom">
                            <i class="fa-solid fa-map-location-dot me-2"></i>Planifier ma visite
                        </h2>
                        <p class="text-muted small mb-4">
                            Saisissez vos dates et coordonnées. Notre équipe vous contactera sous 24h par e-mail avec une proposition de guide local et d'hébergement.
                        </p>
                        <form method="POST" action="traitement/planifier_visite.php">
                            <input type="hidden" name="lieu_id" value="<?= $lieu['id'] ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date d'arrivée *</label>
                                    <input type="date" name="date_arrivee" id="date_arrivee" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date de départ *</label>
                                    <input type="date" name="date_depart" id="date_depart" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Nombre de personnes *</label>
                                    <input type="number" name="nb_personnes" class="form-control" value="1" min="1" max="20" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="besoin_hebergement" id="besoin_hebergement" value="1">
                                        <label class="form-check-label fw-bold" for="besoin_hebergement">Besoin d'un hébergement ?</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nom Complet *</label>
                                <input type="text" name="nom_client" class="form-control" placeholder="Ex: Jean Dupont" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Adresse Email *</label>
                                    <input type="email" name="email_client" class="form-control" placeholder="Ex: jean.dupont@exemple.com" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Numéro de Téléphone (avec indicatif) *</label>
                                    <input type="tel" name="telephone_client" class="form-control" placeholder="Ex: +229 97 00 00 00" required>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg py-3">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Soumettre ma demande
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const today = new Date().toISOString().split('T')[0];
                    const arrivalInput = document.getElementById('date_arrivee');
                    const departureInput = document.getElementById('date_depart');
                    
                    if (arrivalInput && departureInput) {
                        arrivalInput.min = today;
                        arrivalInput.addEventListener('change', function() {
                            departureInput.min = this.value;
                        });
                    }
                });
                </script>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
