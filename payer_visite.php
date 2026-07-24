<?php
require_once 'includes/fonctions.php';
require_once 'config/database.php';
require_once 'config/smtp.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    header('Location: index.php');
    exit;
}

$demande = null;
$type_demande = ''; // 'visite', 'hotel', 'guide'

try {
    // 1. Recherche dans demandes_visite
    $stmt = $pdo->prepare("SELECT * FROM demandes_visite WHERE token_paiement = ?");
    $stmt->execute([$token]);
    $demande = $stmt->fetch();

    if ($demande) {
        $type_demande = 'visite';
    } else {
        // 2. Recherche dans reservations_hebergement
        $stmt = $pdo->prepare("SELECT * FROM reservations_hebergement WHERE token_paiement = ?");
        $stmt->execute([$token]);
        $demande = $stmt->fetch();

        if ($demande) {
            $type_demande = 'hotel';
        } else {
            // 3. Recherche dans demandes_guide
            $stmt = $pdo->prepare("SELECT * FROM demandes_guide WHERE token_paiement = ?");
            $stmt->execute([$token]);
            $demande = $stmt->fetch();

            if ($demande) {
                $type_demande = 'guide';
            }
        }
    }

    if (!$demande) {
        die("Proposition introuvable ou lien expiré.");
    }

    // Récupération des détails spécifiques selon le type
    $lieu = null;
    $guide = null;
    $hotel = null;
    $jours = 1;
    $tarif_guide = 0;
    $tarif_hotel = 0;

    if ($type_demande === 'visite') {
        // Récupérer le lieu
        $lieu_stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
        $lieu_stmt->execute([$demande['lieu_id']]);
        $lieu = $lieu_stmt->fetch();

        // Guide
        if (!empty($demande['guide_id'])) {
            $guide_stmt = $pdo->prepare("SELECT * FROM guides WHERE id = ?");
            $guide_stmt->execute([$demande['guide_id']]);
            $guide = $guide_stmt->fetch();
        }

        // Hôtel
        if (!empty($demande['hebergement_id'])) {
            $hotel_stmt = $pdo->prepare("SELECT * FROM hebergements WHERE id = ?");
            $hotel_stmt->execute([$demande['hebergement_id']]);
            $hotel = $hotel_stmt->fetch();
        }

        $date1 = new DateTime($demande['date_arrivee']);
        $date2 = new DateTime($demande['date_depart']);
        $jours = $date1->diff($date2)->days;
        if ($jours <= 0) $jours = 1;

        if ($guide) $tarif_guide = $guide['tarif_jour'] * $jours;
        if ($hotel) $tarif_hotel = $hotel['prix_nuit'] * $jours;

    } elseif ($type_demande === 'hotel') {
        // Récupérer l'hôtel
        $hotel_stmt = $pdo->prepare("SELECT * FROM hebergements WHERE id = ?");
        $hotel_stmt->execute([$demande['hebergement_id']]);
        $hotel = $hotel_stmt->fetch();

        $date1 = new DateTime($demande['date_arrivee']);
        $date2 = new DateTime($demande['date_depart']);
        $jours = $date1->diff($date2)->days;
        if ($jours <= 0) $jours = 1;

        // Calcul selon la catégorie de chambre
        $mult = 1.0;
        $room_type = isset($demande['type_chambre']) ? $demande['type_chambre'] : 'standard';
        if ($room_type === 'confort') $mult = 1.5;
        if ($room_type === 'suite') $mult = 2.2;

        if ($hotel) $tarif_hotel = ($hotel['prix_nuit'] * $mult) * $jours;

    } elseif ($type_demande === 'guide') {
        // Récupérer le guide
        $guide_stmt = $pdo->prepare("SELECT * FROM guides WHERE id = ?");
        $guide_stmt->execute([$demande['guide_id']]);
        $guide = $guide_stmt->fetch();

        $date1 = new DateTime($demande['date_debut']);
        $date2 = new DateTime($demande['date_fin']);
        $jours = $date1->diff($date2)->days;
        if ($jours <= 0) $jours = 1;

        if ($guide) $tarif_guide = $guide['tarif_jour'] * $jours;
    }

    $frais_service = 0;
    if ($type_demande === 'guide') {
        $frais_service = 3000;
    }

    $prix_total = $tarif_guide + $tarif_hotel + $frais_service;

    // Construction de l'URL absolue pour le callback
    $protocole   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host        = $_SERVER['HTTP_HOST'];
    $base_path   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $callback_base = $protocole . '://' . $host . $base_path;

    $fedapay_key = defined('FEDAPAY_SANDBOX_KEY') ? FEDAPAY_SANDBOX_KEY : 'pk_sandbox_MISSING_KEY';

} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}

$page_title = "Confirmation de réservation";
require_once 'includes/header.php';
?>

<section class="lieu-detail-header text-center py-5">
    <div class="container">
        <h1 class="lieu-title text-success"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Votre Proposition de Voyage</h1>
        <p class="lead text-muted col-md-8 mx-auto">Veuillez vérifier les détails ci-dessous pour confirmer et finaliser votre paiement.</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        
        <?php if ($demande['statut'] === 'nouvelle'): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert alert-info text-center py-5 shadow-sm rounded-3">
                        <i class="fa-solid fa-spinner fa-spin fa-3x mb-3 text-info"></i>
                        <h4 class="fw-bold">Proposition en cours de préparation</h4>
                        <p class="mb-0 fs-5 mt-2">Notre équipe traite votre demande pour valider sa disponibilité. Un e-mail contenant le lien de paiement vous sera envoyé sous peu.</p>
                        <a href="index.php" class="btn btn-success mt-4 px-4 py-2"><i class="fa-solid fa-arrow-left me-1"></i> Retour à l'accueil</a>
                    </div>
                </div>
            </div>
            
        <?php elseif ($demande['statut'] === 'payee'): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                        <div class="bg-success text-white text-center py-5">
                            <i class="fa-solid fa-circle-check fa-4x mb-3"></i>
                            <h2 class="fw-bold">Paiement Confirmé !</h2>
                            <p class="mb-0 fs-5">Votre réservation est validée.</p>
                        </div>
                        <div class="card-body p-5">
                            <h4 class="fw-bold mb-4 text-success border-bottom pb-2"><i class="fa-solid fa-ticket me-2"></i>Votre Bon de Voyage (Voucher)</h4>
                            <div class="row g-4 mb-4">
                                <div class="col-sm-6">
                                    <span class="text-muted small d-block">CLIENT</span>
                                    <strong><?= e($demande['nom_client']) ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted small d-block">TYPE DE RÉSERVATION</span>
                                    <strong>
                                        <?php 
                                            if ($type_demande === 'visite') echo "Visite Complète + Chauffeur/Hôtel";
                                            elseif ($type_demande === 'hotel') echo "Hébergement Seul";
                                            elseif ($type_demande === 'guide') echo "Chauffeur-Guide Seul";
                                        ?>
                                    </strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted small d-block">PÉRIODE</span>
                                    <strong>
                                        <?php if ($type_demande === 'guide'): ?>
                                            Du <?= date('d/m/Y', strtotime($demande['date_debut'])) ?> au <?= date('d/m/Y', strtotime($demande['date_fin'])) ?> (<?= $jours ?> j)
                                        <?php else: ?>
                                            Du <?= date('d/m/Y', strtotime($demande['date_arrivee'])) ?> au <?= date('d/m/Y', strtotime($demande['date_depart'])) ?> (<?= $jours ?> j)
                                        <?php endif; ?>
                                    </strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted small d-block">ID TRANSACTION</span>
                                    <code class="text-dark fw-bold"><?= e($demande['transaction_id']) ?></code>
                                </div>
                            </div>
                            <div class="p-3 bg-light rounded border-start border-4 border-success small mb-4">
                                <strong>💡 Comment ça marche ?</strong> Présentez simplement ce bon (sur votre téléphone ou imprimé) lors de votre prise en charge ou à votre hôtel.
                            </div>
                            <div class="text-center">
                                <button class="btn btn-outline-success px-4" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Imprimer le Bon</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($demande['statut'] === 'proposition_envoyee'): ?>
            <div class="row g-5">
                <!-- Colonne Gauche : Récapitulatif et Propositions -->
                <div class="col-lg-8">
                    
                    <!-- Détail du Lieu si visite -->
                    <?php if ($type_demande === 'visite' && $lieu): ?>
                        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
                            <i class="fa-solid fa-map-location-dot fa-2x me-3 text-success"></i>
                            <div>
                                <h4 class="h6 mb-1 fw-bold">Destination principale</h4>
                                <p class="mb-0 small text-secondary">Planification organisée pour la découverte de : <strong><?= e(db_trans($lieu, 'nom')) ?></strong>.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Proposition Guide -->
                    <?php if ($guide): ?>
                        <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                            <div class="card-header bg-success text-white py-3">
                                <h3 class="h5 mb-0"><i class="fa-solid fa-car-side me-2"></i>Chauffeur-Guide Attribué</h3>
                            </div>
                            <div class="card-body p-4">
                                <div class="row align-items-center g-4">
                                    <div class="col-md-3 text-center">
                                        <img src="<?= get_image_url($guide['photo'], 'guide') ?>" alt="<?= e($guide['nom']) ?>" class="rounded-circle shadow-sm object-fit-cover" style="width: 120px; height: 120px;">
                                    </div>
                                    <div class="col-md-9">
                                        <h4 class="fw-bold mb-1"><?= e($guide['nom']) ?></h4>
                                        <p class="text-accent small mb-3"><i class="fa-solid fa-shield-halved me-1"></i>Certifié Bénin Tourisme</p>
                                        <ul class="list-unstyled mb-0 small text-secondary">
                                            <li class="mb-1"><strong><i class="fa-solid fa-language me-2 text-success"></i>Langues :</strong> <?= e(db_trans($guide, 'langues')) ?></li>
                                            <li><strong><i class="fa-solid fa-circle-info me-2 text-success"></i>Tarif journalier :</strong> <?= number_format($guide['tarif_jour'], 0, ',', ' ') ?> FCFA / jour</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Proposition Hôtel -->
                    <?php if ($hotel): ?>
                        <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                            <div class="card-header bg-success text-white py-3">
                                <h3 class="h5 mb-0"><i class="fa-solid fa-hotel me-2"></i>Hébergement Sélectionné</h3>
                            </div>
                            <div class="card-body p-4">
                                <div class="row align-items-center g-4">
                                    <div class="col-md-4">
                                        <img src="<?= get_image_url($hotel['photo'], ($lang === 'en' ? $hotel['type_en'] : $hotel['type_fr'])) ?>" alt="<?= e($hotel['nom']) ?>" class="rounded w-100 object-fit-cover" style="max-height: 140px;">
                                    </div>
                                    <div class="col-md-8">
                                        <span class="badge bg-success mb-2"><?= e($lang === 'en' ? $hotel['type_en'] : $hotel['type_fr']) ?></span>
                                        <h4 class="fw-bold mb-1"><?= e($hotel['nom']) ?></h4>
                                        <p class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($hotel['localite']) ?><?= $hotel['quartier'] ? ' (' . e($hotel['quartier']) . ')' : '' ?></p>
                                        <p class="small text-secondary mb-1"><?= number_format($hotel['prix_nuit'], 0, ',', ' ') ?> FCFA / nuit (Tarif de base)</p>
                                        <?php if ($type_demande === 'hotel' && !empty($demande['type_chambre'])): ?>
                                            <div class="small text-success fw-bold"><i class="fa-solid fa-bed me-1"></i>Chambre : <?= ucfirst(e($demande['type_chambre'])) ?></div>
                                            <div class="small text-dark">Tarif ajusté : <?= number_format($hotel['prix_nuit'] * $mult, 0, ',', ' ') ?> FCFA / nuit</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Colonne Droite : Total & Bouton de Paiement -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-3 sticky-top" style="top: 100px;">
                        <div class="card-body p-4">
                            <h3 class="h5 fw-bold mb-4 text-success border-bottom pb-2"><i class="fa-solid fa-calculator me-2"></i>Résumé du Paiement</h3>
                            
                            <ul class="list-unstyled mb-4">
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Type :</span>
                                    <strong>
                                        <?php 
                                            if ($type_demande === 'visite') echo "Visite Complète";
                                            elseif ($type_demande === 'hotel') echo "Hébergement Seul";
                                            elseif ($type_demande === 'guide') echo "Guide Seul";
                                        ?>
                                    </strong>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Durée :</span>
                                    <strong><?= $jours ?> jour(s)</strong>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Personnes :</span>
                                    <strong><?= isset($demande['nb_personnes']) ? $demande['nb_personnes'] : 1 ?></strong>
                                </li>
                                
                                <hr class="my-3">
                                
                                <?php if ($type_demande === 'guide' && $guide): 
                                    $loc_voiture = ($guide['tarif_jour'] * 0.6) * $jours;
                                    $prev_chauffeur = ($guide['tarif_jour'] * 0.4) * $jours;
                                ?>
                                    <li class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Location de voiture :</span>
                                        <span><?= number_format($loc_voiture, 0, ',', ' ') ?> FCFA</span>
                                    </li>
                                    <li class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Prestation Chauffeur :</span>
                                        <span><?= number_format($prev_chauffeur, 0, ',', ' ') ?> FCFA</span>
                                    </li>
                                    <li class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Frais de service du site :</span>
                                        <span><?= number_format($frais_service, 0, ',', ' ') ?> FCFA</span>
                                    </li>
                                <?php else: ?>
                                    <?php if ($guide): ?>
                                        <li class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Total Guide :</span>
                                            <span><?= number_format($tarif_guide, 0, ',', ' ') ?> FCFA</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($hotel): ?>
                                        <li class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Total Hébergement :</span>
                                            <span><?= number_format($tarif_hotel, 0, ',', ' ') ?> FCFA</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <hr class="my-3">
                                
                                <li class="d-flex justify-content-between mb-2 fs-5">
                                    <span class="fw-bold text-success">Total à payer :</span>
                                    <span class="fw-bold text-success"><?= number_format($prix_total, 0, ',', ' ') ?> FCFA</span>
                                </li>
                            </ul>

                            <!-- FedaPay - Redirection directe -->
                            <form id="payment-form" method="POST" action="traitement/creer_paiement.php">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($type_demande) ?>">
                                <input type="hidden" name="montant" value="<?= (int)$prix_total ?>">
                                <input type="hidden" name="description" value="Paiement Service Bénin Tourisme (<?= htmlspecialchars($type_demande) ?>)">
                                <button type="submit" class="btn btn-success btn-lg w-100 py-3 mt-3">
                                    <i class="fa-solid fa-credit-card me-2"></i>Confirmer & Payer avec FedaPay
                                </button>
                            </form>

                            <p class="text-center text-muted small mt-3 mb-0"><i class="fa-solid fa-lock me-1"></i>Sécurisé par FedaPay Sandbox</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
