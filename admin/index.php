<?php
require_once 'header.php';

// Initialisation des compteurs
$count_lieux = 0;
$count_hebergements = 0;
$count_guides = 0;
$count_booking_new = 0;
$count_guide_new = 0;
$count_messages = 0;

$recent_bookings = [];
$recent_guides = [];

try {
    // Lieux
    $count_lieux = $pdo->query("SELECT COUNT(*) FROM lieux")->fetchColumn();
    // Hébergements
    $count_hebergements = $pdo->query("SELECT COUNT(*) FROM hebergements")->fetchColumn();
    // Guides
    $count_guides = $pdo->query("SELECT COUNT(*) FROM guides")->fetchColumn();
    // Nouvelles réservations hébergements
    $count_booking_new = $pdo->query("SELECT COUNT(*) FROM reservations_hebergement WHERE statut = 'nouvelle'")->fetchColumn();
    // Nouvelles demandes guides
    $count_guide_new = $pdo->query("SELECT COUNT(*) FROM demandes_guide WHERE statut = 'nouvelle'")->fetchColumn();
    // Messages
    $count_messages = $pdo->query("SELECT COUNT(*) FROM messages_contact")->fetchColumn();

    // Récupération des 5 dernières réservations hébergements
    $recent_bookings_stmt = $pdo->query("
        SELECT r.*, h.nom AS hotel_nom 
        FROM reservations_hebergement r 
        JOIN hebergements h ON r.hebergement_id = h.id 
        ORDER BY r.date_demande DESC 
        LIMIT 5
    ");
    $recent_bookings = $recent_bookings_stmt->fetchAll();

    // Récupération des 5 dernières demandes guides
    $recent_guides_stmt = $pdo->query("
        SELECT d.*, g.nom AS guide_nom 
        FROM demandes_guide d 
        JOIN guides g ON d.guide_id = g.id 
        ORDER BY d.date_demande DESC 
        LIMIT 5
    ");
    $recent_guides = $recent_guides_stmt->fetchAll();

} catch (PDOException $e) {
    // Gestion silencieuse ou affichage d'erreur en cas de tables manquantes
    $db_error = $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 text-success"><i class="fa-solid fa-gauge me-2"></i>Tableau de bord</h1>
    <span class="badge bg-secondary p-2"><?= date('d F Y') ?></span>
</div>

<?php if (isset($db_error)): ?>
    <div class="alert alert-danger shadow-sm">
        <i class="fa-solid fa-circle-exclamation me-2"></i> Une erreur est survenue lors de l'accès à la base de données : <?= e($db_error) ?>. Veuillez vous assurer que vous avez importé le fichier `sql/schema.sql`.
    </div>
<?php endif; ?>

<!-- Grille des statistiques -->
<div class="row g-4 mb-5">
    <!-- Lieux -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-stat bg-success p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 text-uppercase mb-1 small">Lieux touristiques</h6>
                    <h2 class="fw-bold mb-0"><?= $count_lieux ?></h2>
                </div>
                <div class="fs-1"><i class="fa-solid fa-map-location-dot"></i></div>
            </div>
            <a href="lieux.php" class="text-white text-decoration-none mt-3 d-flex justify-content-between align-items-center small">
                <span>Gérer les lieux</span> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <!-- Hébergements -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-stat bg-info p-3">
            <div class="d-flex justify-content-between align-items-center text-white">
                <div>
                    <h6 class="text-white-50 text-uppercase mb-1 small">Hébergements</h6>
                    <h2 class="fw-bold mb-0"><?= $count_hebergements ?></h2>
                </div>
                <div class="fs-1"><i class="fa-solid fa-hotel"></i></div>
            </div>
            <a href="hebergements.php" class="text-white text-decoration-none mt-3 d-flex justify-content-between align-items-center small">
                <span>Gérer les hôtels</span> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Guides -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-stat bg-warning p-3 text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-dark-50 text-uppercase mb-1 small">Chauffeurs-Guides</h6>
                    <h2 class="fw-bold mb-0"><?= $count_guides ?></h2>
                </div>
                <div class="fs-1 text-dark-50"><i class="fa-solid fa-car-side"></i></div>
            </div>
            <a href="guides.php" class="text-dark text-decoration-none mt-3 d-flex justify-content-between align-items-center small">
                <span>Gérer les guides</span> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Nouvelles demandes -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-stat bg-danger p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 text-uppercase mb-1 small">Nouvelles demandes</h6>
                    <h2 class="fw-bold mb-0"><?= ($count_booking_new + $count_guide_new) ?></h2>
                </div>
                <div class="fs-1"><i class="fa-solid fa-bell"></i></div>
            </div>
            <a href="demandes.php" class="text-white text-decoration-none mt-3 d-flex justify-content-between align-items-center small">
                <span>Traiter les demandes</span> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Dernières demandes d'hébergement -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-success fw-bold"><i class="fa-solid fa-bed me-2"></i>Réservations Récentes</h5>
                <a href="demandes.php" class="btn btn-sm btn-outline-success">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Hôtel</th>
                                <th>Arrivée</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_bookings)): ?>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= e($booking['nom_client']) ?></div>
                                            <small class="text-muted"><?= e($booking['email_client']) ?></small>
                                        </td>
                                        <td><?= e($booking['hotel_nom']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($booking['date_arrivee'])) ?></td>
                                        <td>
                                            <?php if ($booking['statut'] === 'nouvelle'): ?>
                                                <span class="badge bg-danger">Nouvelle</span>
                                            <?php elseif ($booking['statut'] === 'traitee'): ?>
                                                <span class="badge bg-success">Traitée</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Annulée</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Aucune réservation récente.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernières demandes de guide -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-success fw-bold"><i class="fa-solid fa-car-side me-2"></i>Demandes de Guides Récentes</h5>
                <a href="demandes.php" class="btn btn-sm btn-outline-success">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Guide</th>
                                <th>Début</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_guides)): ?>
                                <?php foreach ($recent_guides as $demande): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= e($demande['nom_client']) ?></div>
                                            <small class="text-muted"><?= e($demande['email_client']) ?></small>
                                        </td>
                                        <td><?= e($demande['guide_nom']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($demande['date_debut'])) ?></td>
                                        <td>
                                            <?php if ($demande['statut'] === 'nouvelle'): ?>
                                                <span class="badge bg-danger">Nouvelle</span>
                                            <?php elseif ($demande['statut'] === 'traitee'): ?>
                                                <span class="badge bg-success">Traitée</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Annulée</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Aucune demande de guide récente.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
