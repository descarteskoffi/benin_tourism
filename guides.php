<?php
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// SEO Metas
$page_title = __('nav_guides');
$page_desc = __('guides_subtitle');

$search_performed = false;
$search_results = [];

// Lecture session pour le modal de confirmation de booking
$booking_success = isset($_SESSION['guide_booking_success']) ? $_SESSION['guide_booking_success'] : false;
$booking_email   = isset($_SESSION['guide_booking_email'])   ? $_SESSION['guide_booking_email']   : '';
unset($_SESSION['guide_booking_success'], $_SESSION['guide_booking_email']);

// Récupération des critères de recherche et filtrage des chauffeurs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_search'])) {
    $search_performed = true;
    
    $passagers = isset($_POST['passagers']) ? (int)$_POST['passagers'] : 1;
    $bagages   = isset($_POST['bagages'])   ? (int)$_POST['bagages']   : 0;
    $gamme     = isset($_POST['gamme'])     ? trim($_POST['gamme'])     : '';
    
    // Données client transmises pour pré-remplissage du formulaire final
    $nom_client       = isset($_POST['nom_client'])       ? trim($_POST['nom_client'])       : '';
    $email_client     = isset($_POST['email_client'])     ? trim($_POST['email_client'])     : '';
    $telephone_client = isset($_POST['telephone_client']) ? trim($_POST['telephone_client']) : '';
    $date_debut       = isset($_POST['date_debut'])       ? trim($_POST['date_debut'])       : '';
    $date_fin         = isset($_POST['date_fin'])         ? trim($_POST['date_fin'])         : '';
    $destination      = isset($_POST['destination'])      ? trim($_POST['destination'])      : '';

    // Requête de filtrage des chauffeurs :
    //   - Capacité suffisante
    //   - Non déjà réservé (payé) sur la même période (chevauchement de dates)
    $sql = "SELECT id, nom, photo, langues_fr, langues_en, zones_fr, zones_en, tarif_jour, vehicule_modele, gamme, capacite_passagers, capacite_bagages 
            FROM guides 
            WHERE disponible = 1
              AND capacite_passagers >= :passagers
              AND capacite_bagages   >= :bagages
              AND id NOT IN (
                SELECT guide_id FROM demandes_guide
                WHERE statut = 'payee'
                  AND date_debut <= :date_fin
                  AND date_fin   >= :date_debut
              )";

    $params = [
        'passagers'  => $passagers,
        'bagages'    => $bagages,
        'date_debut' => !empty($date_debut) ? $date_debut : '1900-01-01',
        'date_fin'   => !empty($date_fin)   ? $date_fin   : '2100-12-31',
    ];

    if (!empty($gamme)) {
        $sql .= " AND gamme = :gamme";
        $params['gamme'] = $gamme;
    }

    $sql .= " LIMIT 3";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll();
    } catch (PDOException $e) {
        $search_results = [];
    }
}

// Variables pour les modals
$booking_success = isset($_SESSION['guide_booking_success']) ? $_SESSION['guide_booking_success'] : false;
$booking_email   = isset($_SESSION['guide_booking_email'])   ? $_SESSION['guide_booking_email']   : '';
$booking_error = isset($_SESSION['guide_status']) && $_SESSION['guide_status'] === 'error';
$booking_error_msg = isset($_SESSION['guide_message']) ? $_SESSION['guide_message'] : '';
unset($_SESSION['guide_booking_success'], $_SESSION['guide_booking_email']);
unset($_SESSION['guide_status'], $_SESSION['guide_message']);

require_once 'includes/header.php';
?>

<!-- En-tête -->
<section class="lieu-detail-header text-center">
    <div class="container">
        <h1 class="lieu-title">Trouvez votre Chauffeur-Guide Privé</h1>
        <p class="lead text-muted col-md-8 mx-auto">Recherchez un chauffeur disponible selon votre itinéraire et réservez instantanément votre trajet.</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">

        <!-- ÉTAPE 1 : MOTEUR DE RECHERCHE -->
        <?php if (!$search_performed): ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-3 p-4">
                        <h3 class="h4 text-success border-bottom pb-2 mb-4">
                            <i class="fa-solid fa-magnifying-glass-location me-2"></i>Critères de recherche de votre trajet
                        </h3>
                        <form method="POST">
                            <input type="hidden" name="action_search" value="1">
                            
                            <!-- Coordonnées Client -->
                            <div class="mb-4 p-3 bg-light rounded">
                                <h4 class="h6 fw-bold mb-3"><i class="fa-solid fa-user me-2 text-success"></i>Vos Informations personnelles</h4>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nom Complet *</label>
                                    <input type="text" name="nom_client" class="form-control" placeholder="Ex: Koffi Maxime" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Adresse Email *</label>
                                        <input type="email" name="email_client" class="form-control" placeholder="nom@exemple.com" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Téléphone *</label>
                                        <input type="tel" name="telephone_client" class="form-control" placeholder="Ex: +229 97 00 00 00" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Critères trajet -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Destination / Itinéraire *</label>
                                <input type="text" name="destination" class="form-control" placeholder="Ex: Cotonou - Ouidah - Grand-Popo" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date de début *</label>
                                    <input type="date" name="date_debut" id="date_debut" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date de fin *</label>
                                    <input type="date" name="date_fin" id="date_fin" class="form-control" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Nombre de passagers *</label>
                                    <input type="number" name="passagers" class="form-control" value="1" min="1" max="10" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Nombre de bagages *</label>
                                    <input type="number" name="bagages" class="form-control" value="0" min="0" max="15" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Gamme de véhicule</label>
                                    <select name="gamme" class="form-select">
                                        <option value="">Toutes les gammes</option>
                                        <option value="Economique">Économique (ex: Citadine)</option>
                                        <option value="Confort">Confort (ex: Berline/SUV)</option>
                                        <option value="Premium">Premium (ex: Grand 4x4)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg py-3"><i class="fa-solid fa-search me-2"></i>Rechercher les chauffeurs disponibles</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <!-- ÉTAPE 2 : AFFICHAGE DES CHAUFFEURS CORRESPONDANTS -->
        <?php else: ?>
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h3 class="h4 text-success"><i class="fa-solid fa-user-check me-2"></i>Chauffeurs correspondants à votre recherche</h3>
                <a href="guides.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Modifier la recherche</a>
            </div>

            <div class="row g-4 justify-content-center">
                <?php if (!empty($search_results)): ?>
                    <?php foreach ($search_results as $guide): ?>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="position-relative" style="height: 250px;">
                                        <img src="<?= get_image_url($guide['photo'], 'guide') ?>" alt="<?= e($guide['nom']) ?>" class="w-100 h-100 object-fit-cover">
                                        <span class="position-absolute top-0 end-0 bg-success text-white px-3 py-1 m-2 rounded-pill small">
                                            <?= e($guide['gamme']) ?>
                                        </span>
                                    </div>
                                    <div class="p-4">
                                        <!-- Prénom uniquement -->
                                        <?php 
                                            $parts = explode(' ', $guide['nom']);
                                            $prenom = $parts[0];
                                        ?>
                                        <h4 class="fw-bold mb-1"><?= e($prenom) ?></h4>
                                        <p class="text-muted small mb-3"><i class="fa-solid fa-car me-1"></i> Véhicule : <strong><?= e($guide['vehicule_modele']) ?></strong></p>
                                        
                                        <ul class="list-unstyled small text-secondary mb-0">
                                            <li class="mb-2"><strong><i class="fa-solid fa-language text-success me-2"></i>Langues :</strong> <?= e($guide['langues_fr']) ?></li>
                                            <li class="mb-2"><strong><i class="fa-solid fa-users text-success me-2"></i>Capacité :</strong> <?= $guide['capacite_passagers'] ?> passagers max</li>
                                            <li class="mb-2"><strong><i class="fa-solid fa-suitcase text-success me-2"></i>Bagages :</strong> <?= $guide['capacite_bagages'] ?> bagages max</li>
                                            <li><strong><i class="fa-solid fa-dollar-sign text-success me-2"></i>Tarif :</strong> <?= number_format($guide['tarif_jour'], 0, ',', ' ') ?> FCFA / jour</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="p-4 bg-light border-top">
                                    <form method="POST" action="traitement/demande_guide.php">
                                        <input type="hidden" name="guide_id" value="<?= $guide['id'] ?>">
                                        <input type="hidden" name="nom_client" value="<?= e($nom_client) ?>">
                                        <input type="hidden" name="email_client" value="<?= e($email_client) ?>">
                                        <input type="hidden" name="telephone_client" value="<?= e($telephone_client) ?>">
                                        <input type="hidden" name="date_debut" value="<?= e($date_debut) ?>">
                                        <input type="hidden" name="date_fin" value="<?= e($date_fin) ?>">
                                        <input type="hidden" name="destination" value="<?= e($destination) ?>">
                                        
                                        <button type="submit" class="btn btn-success w-100 py-2"><i class="fa-solid fa-check me-2"></i>Confirmer ce chauffeur</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-md-8 text-center py-5">
                        <div class="alert alert-warning py-4 shadow-sm rounded-3">
                            <i class="fa-solid fa-triangle-exclamation fa-3x mb-3 text-warning"></i>
                            <h4 class="fw-bold">Aucun chauffeur disponible</h4>
                            <p class="mb-0">Aucun chauffeur ne correspond exactement à vos critères de passagers, bagages ou gamme de prix à ces dates. Essayez de modifier vos préférences.</p>
                            <a href="guides.php" class="btn btn-outline-success mt-4">Nouvelle recherche</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- Script pour configurer la date min de fin -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const debutInput = document.getElementById('date_debut');
    const finInput = document.getElementById('date_fin');
    
    if (debutInput && finInput) {
        debutInput.min = today;
        debutInput.addEventListener('change', function() {
            finInput.min = this.value;
        });
    }
});
</script>

<!-- Modal de confirmation de réservation chauffeur -->
<?php if ($booking_success): ?>
<div class="modal fade" id="bookingSuccessModal" tabindex="-1" aria-labelledby="bookingSuccessLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="bg-success text-white text-center py-5 px-4">
                <div style="font-size: 4rem;">✅</div>
                <h4 class="fw-bold mt-3 mb-1">Demande enregistrée !</h4>
                <p class="mb-0 opacity-75">Votre chauffeur-guide vous attend.</p>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fs-6 mb-3">
                    Un e-mail récapitulatif avec le <strong>lien de paiement sécurisé</strong> (FedaPay) et le détail de votre facture a été envoyé à :
                </p>
                <div class="alert alert-success fw-bold fs-5 mb-3">
                    <i class="fa-solid fa-envelope me-2"></i><?= e($booking_email) ?>
                </div>
                <p class="text-muted small mb-4">
                    Ouvrez cet e-mail et cliquez sur le lien pour confirmer et payer votre réservation via Mobile Money (MTN / Moov) ou Carte Bancaire.
                </p>
                <button type="button" class="btn btn-success btn-lg px-5" data-bs-dismiss="modal">
                    <i class="fa-solid fa-check me-1"></i> Compris, je vais vérifier
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('bookingSuccessModal'));
    modal.show();
});
</script>
<?php endif; ?>

<!-- Modal d'erreur -->
<?php if ($booking_error): ?>
<div class="modal fade" id="bookingErrorModal" tabindex="-1" aria-labelledby="bookingErrorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="bg-danger text-white text-center py-5 px-4">
                <div style="font-size: 4rem;">❌</div>
                <h4 class="fw-bold mt-3 mb-1">Erreur !</h4>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fs-6 mb-4">
                    <?php echo e($booking_error_msg); ?>
                </p>
                <button type="button" class="btn btn-danger btn-lg px-5" data-bs-dismiss="modal">
                    <i class="fa-solid fa-redo me-1"></i> Réessayer
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('bookingErrorModal'));
    modal.show();
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
