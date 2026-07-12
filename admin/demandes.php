<?php
require_once 'header.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$error = null;
$success = null;

// ----------------------------------------------------------------------------
// ACTIONS : ENVOI DE PROPOSITION / CHANGER STATUT / SUPPRIMER
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send_proposal' && $id > 0) {
    $guide_id = isset($_POST['guide_id']) ? (int)$_POST['guide_id'] : 0;
    $hebergement_id = isset($_POST['hebergement_id']) && $_POST['hebergement_id'] !== '' ? (int)$_POST['hebergement_id'] : null;

    if ($guide_id <= 0) {
        $error = "Veuillez attribuer un chauffeur-guide.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE demandes_visite SET statut = 'proposition_envoyee', guide_id = :guide_id, hebergement_id = :hebergement_id WHERE id = :id");
            $stmt->execute([
                'guide_id' => $guide_id,
                'hebergement_id' => $hebergement_id,
                'id' => $id
            ]);

            $q_stmt = $pdo->prepare("SELECT d.*, l.nom_fr as lieu_nom FROM demandes_visite d JOIN lieux l ON d.lieu_id = l.id WHERE d.id = ?");
            $q_stmt->execute([$id]);
            $demande = $q_stmt->fetch();

            // Génération du lien de paiement dynamique (fonctionne en local ET en ligne)
            $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
            $lien_paiement = $protocole . '://' . $host . $base_path . '/payer_visite.php?token=' . $demande['token_paiement'];
            
            $subject = "Votre proposition de voyage au Bénin est prête !";
            $body = "Bonjour " . $demande['nom_client'] . ",\n\n";
            $body .= "Nous avons traité votre demande de planification pour votre visite de : " . $demande['lieu_nom'] . ".\n\n";
            $body .= "Notre équipe vous a sélectionné un chauffeur-guide local qualifié (et un hébergement si demandé).\n\n";
            $body .= "Veuillez consulter les détails de notre proposition et procéder au paiement par FedaPay pour confirmer votre voyage en cliquant sur le lien ci-dessous :\n";
            $body .= "$lien_paiement\n\n";
            $body .= "À très bientôt au Bénin !\nL'équipe Bénin Tourisme.";

            log_mail($demande['email_client'], $subject, $body);

            $success = "La proposition a bien été envoyée au client. L'e-mail a été enregistré dans logs/mail.log.";
            $action = 'list';
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

if ($action === 'proposer_hotel' && $id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE reservations_hebergement SET statut = 'proposition_envoyee' WHERE id = ?");
        $stmt->execute([$id]);

        $q_stmt = $pdo->prepare("SELECT r.*, h.nom as hotel_nom FROM reservations_hebergement r JOIN hebergements h ON r.hebergement_id = h.id WHERE r.id = ?");
        $q_stmt->execute([$id]);
        $res = $q_stmt->fetch();

        $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
        $lien_paiement = $protocole . '://' . $host . $base_path . '/payer_visite.php?token=' . $res['token_paiement'];

        $subject = "Votre demande d'hébergement - Lien de paiement";
        $body = "Bonjour " . $res['nom_client'] . ",\n\n";
        $body .= "Nous vous confirmons la disponibilité de l'hébergement [" . $res['hotel_nom'] . "] pour votre séjour du " . date('d/m/Y', strtotime($res['date_arrivee'])) . " au " . date('d/m/Y', strtotime($res['date_depart'])) . ".\n\n";
        $body .= "Veuillez finaliser votre réservation en payant par FedaPay via le lien suivant :\n";
        $body .= "$lien_paiement\n\n";
        $body .= "Cordialement,\nL'équipe Bénin Tourisme.";

        log_mail($res['email_client'], $subject, $body);
        $success = "La disponibilité a été confirmée et le lien de paiement a été envoyé par e-mail.";
        $action = 'list';
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

if ($action === 'refuser_hotel' && $id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE reservations_hebergement SET statut = 'annulee' WHERE id = ?");
        $stmt->execute([$id]);

        $q_stmt = $pdo->prepare("SELECT r.*, h.nom as hotel_nom FROM reservations_hebergement r JOIN hebergements h ON r.hebergement_id = h.id WHERE r.id = ?");
        $q_stmt->execute([$id]);
        $res = $q_stmt->fetch();

        $subject = "Votre demande de réservation d'hébergement - Indisponible";
        $body = "Bonjour " . $res['nom_client'] . ",\n\n";
        $body .= "Nous vous remercions pour votre intérêt pour l'hébergement [" . $res['hotel_nom'] . "].\n\n";
        $body .= "Malheureusement, après vérification, cet établissement n'est pas disponible pour vos dates de séjour du " . date('d/m/Y', strtotime($res['date_arrivee'])) . " au " . date('d/m/Y', strtotime($res['date_depart'])) . ".\n\n";
        $body .= "N'hésitez pas à soumettre une nouvelle demande pour un autre établissement sur notre site.\n\n";
        $body .= "Cordialement,\nL'équipe Bénin Tourisme.";

        log_mail($res['email_client'], $subject, $body);
        $success = "La demande a été marquée comme indisponible et un e-mail a été envoyé au client.";
        $action = 'list';
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

if ($action === 'status' && $id > 0 && !empty($type) && !empty($status)) {
    try {
        if ($type === 'hotel') {
            $stmt = $pdo->prepare("UPDATE reservations_hebergement SET statut = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = "Le statut de la réservation d'hébergement a été mis à jour.";
        } elseif ($type === 'guide') {
            $stmt = $pdo->prepare("UPDATE demandes_guide SET statut = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = "Le statut de la demande de chauffeur-guide a été mis à jour.";
        } elseif ($type === 'visite') {
            $stmt = $pdo->prepare("UPDATE demandes_visite SET statut = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = "Le statut de la planification a été mis à jour.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la mise à jour du statut : " . $e->getMessage();
    }
}

if ($action === 'delete' && $id > 0 && !empty($type)) {
    try {
        if ($type === 'hotel') {
            $stmt = $pdo->prepare("DELETE FROM reservations_hebergement WHERE id = ?");
            $stmt->execute([$id]);
            $success = "La réservation d'hébergement a été supprimée.";
        } elseif ($type === 'guide') {
            $stmt = $pdo->prepare("DELETE FROM demandes_guide WHERE id = ?");
            $stmt->execute([$id]);
            $success = "La demande de guide a été supprimée.";
        } elseif ($type === 'contact') {
            $stmt = $pdo->prepare("DELETE FROM messages_contact WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Le message de contact a été supprimé.";
        } elseif ($type === 'visite') {
            $stmt = $pdo->prepare("DELETE FROM demandes_visite WHERE id = ?");
            $stmt->execute([$id]);
            $success = "La demande de visite unifiée a été supprimée.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DES LISTES
// ----------------------------------------------------------------------------
try {
    // 1. Demandes de visite unifiées
    $visites_stmt = $pdo->query("
        SELECT d.*, l.nom_fr as lieu_nom, g.nom as guide_nom, h.nom as hotel_nom 
        FROM demandes_visite d 
        JOIN lieux l ON d.lieu_id = l.id 
        LEFT JOIN guides g ON d.guide_id = g.id 
        LEFT JOIN hebergements h ON d.hebergement_id = h.id 
        ORDER BY d.date_creation DESC
    ");
    $visites = $visites_stmt->fetchAll();

    // 2. Réservations hébergements classiques
    $bookings_stmt = $pdo->query("
        SELECT r.*, h.nom AS hotel_nom, h.localite AS hotel_ville 
        FROM reservations_hebergement r 
        JOIN hebergements h ON r.hebergement_id = h.id 
        ORDER BY r.date_demande DESC
    ");
    $bookings = $bookings_stmt->fetchAll();

    // 3. Demandes guides classiques
    $guides_stmt = $pdo->query("
        SELECT d.*, g.nom AS guide_nom 
        FROM demandes_guide d 
        JOIN guides g ON d.guide_id = g.id 
        ORDER BY d.date_demande DESC
    ");
    $guides_demandes = $guides_stmt->fetchAll();

    // 4. Messages
    $messages_stmt = $pdo->query("SELECT * FROM messages_contact ORDER BY date_envoi DESC");
    $messages = $messages_stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Erreur de chargement : " . $e->getMessage();
    $visites = [];
    $bookings = [];
    $guides_demandes = [];
    $messages = [];
}
?>

<div class="mb-4">
    <h1 class="h2 text-success"><i class="fa-solid fa-clipboard-list me-2"></i>Réservations & Messages</h1>
    <p class="text-muted">Consultez et traitez les demandes de réservation unifiées, hébergements, guides et messages.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success shadow-sm"><i class="fa-solid fa-circle-check me-2"></i><?= e($success) ?></div>
<?php endif; ?>

<!-- SI ACTION = TRAITER VISITE -->
<?php if ($action === 'traiter_visite' && $id > 0): ?>
    <?php
    try {
        $stmt = $pdo->prepare("SELECT d.*, l.nom_fr as lieu_nom FROM demandes_visite d JOIN lieux l ON d.lieu_id = l.id WHERE d.id = ?");
        $stmt->execute([$id]);
        $dem = $stmt->fetch();

        // Récupérer les guides disponibles
        $guides_avail = $pdo->query("SELECT id, nom, tarif_jour FROM guides WHERE disponible = 1 ORDER BY nom ASC")->fetchAll();

        // Récupérer les hôtels
        $hotels_avail = $pdo->query("SELECT id, nom, localite, prix_nuit FROM hebergements ORDER BY nom ASC")->fetchAll();

    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
    ?>
    <div class="card border-0 shadow-sm rounded-3 mb-5">
        <div class="card-body p-4">
            <h3 class="card-title h5 text-success mb-4 border-bottom pb-2">
                <i class="fa-solid fa-user-check me-2"></i>Attribuer Guide & Hébergement pour la visite de: <?= e($dem['lieu_nom']) ?>
            </h3>
            
            <form method="POST" action="?action=send_proposal&id=<?= $id ?>">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Attribuer un Chauffeur-Guide *</label>
                        <select name="guide_id" class="form-select" required>
                            <option value="">-- Sélectionner un guide --</option>
                            <?php foreach ($guides_avail as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= e($g['nom']) ?> (<?= number_format($g['tarif_jour'], 0, ',', ' ') ?> FCFA/j)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($dem['besoin_hebergement']): ?>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Attribuer un Hébergement *</label>
                            <select name="hebergement_id" class="form-select" required>
                                <option value="">-- Sélectionner un hôtel --</option>
                                <?php foreach ($hotels_avail as $h): ?>
                                    <option value="<?= $h['id'] ?>"><?= e($h['nom']) ?> (<?= e($h['localite']) ?> - <?= number_format($h['prix_nuit'], 0, ',', ' ') ?> FCFA/nuit)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Hébergement</label>
                            <input type="text" class="form-control" value="Non demandé par le client" disabled>
                            <input type="hidden" name="hebergement_id" value="">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 border-top pt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-paper-plane me-1"></i> Envoyer la proposition</button>
                    <a href="?action=list" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

<!-- SI LISTE STANDARD -->
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 py-3">
            <ul class="nav nav-tabs card-header-tabs" id="demandesTab" role="tablist">
                <!-- Onglet Planifications (Visites) -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-success" id="visites-tab" data-bs-toggle="tab" data-bs-target="#visites-content" type="button" role="tab" aria-controls="visites-content" aria-selected="true">
                        <i class="fa-solid fa-map-location-dot me-1"></i> Planification de Visites (<?= count($visites) ?>)
                    </button>
                </li>
                <!-- Onglet Hébergements -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-success" id="hotels-tab" data-bs-toggle="tab" data-bs-target="#hotels-content" type="button" role="tab" aria-controls="hotels-content" aria-selected="false">
                        <i class="fa-solid fa-bed me-1"></i> Hébergements Classiques (<?= count($bookings) ?>)
                    </button>
                </li>
                <!-- Onglet Guides -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-success" id="guides-tab" data-bs-toggle="tab" data-bs-target="#guides-content" type="button" role="tab" aria-controls="guides-content" aria-selected="false">
                        <i class="fa-solid fa-car-side me-1"></i> Chauffeurs-Guides Classiques (<?= count($guides_demandes) ?>)
                    </button>
                </li>
                <!-- Onglet Messages -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-success" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages-content" type="button" role="tab" aria-controls="messages-content" aria-selected="false">
                        <i class="fa-solid fa-envelope me-1"></i> Messages (<?= count($messages) ?>)
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-0">
            <div class="tab-content" id="demandesTabContent">
                
                <!-- 1. CONTENU : PLANIFICATIONS DE VISITES UNIFIÉES -->
                <div class="tab-pane fade show active p-3" id="visites-content" role="tabpanel" aria-labelledby="visites-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Lieu / Dates</th>
                                    <th>Attribution</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($visites)): ?>
                                    <?php foreach ($visites as $v): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($v['nom_client']) ?></div>
                                                <small class="text-muted d-block"><i class="fa-solid fa-envelope me-1"></i><?= e($v['email_client']) ?></small>
                                                <small class="text-muted d-block"><i class="fa-solid fa-phone me-1"></i><?= e($v['telephone_client']) ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-success"><?= e($v['lieu_nom']) ?></div>
                                                <small class="text-muted d-block">Du <?= date('d/m/Y', strtotime($v['date_arrivee'])) ?> au <?= date('d/m/Y', strtotime($v['date_depart'])) ?></small>
                                                <small class="text-muted d-block">Personnes : <?= $v['nb_personnes'] ?> | Hôtel requis : <?= $v['besoin_hebergement'] ? 'Oui' : 'Non' ?></small>
                                            </td>
                                            <td>
                                                <div><strong>Guide :</strong> <?= $v['guide_nom'] ? e($v['guide_nom']) : '<span class="text-warning">Non attribué</span>' ?></div>
                                                <div><strong>Hôtel :</strong> <?= $v['hotel_nom'] ? e($v['hotel_nom']) : '<span class="text-muted">Non attribué</span>' ?></div>
                                            </td>
                                            <td>
                                                <?php if ($v['statut'] === 'nouvelle'): ?>
                                                    <span class="badge bg-danger">Nouvelle</span>
                                                <?php elseif ($v['statut'] === 'proposition_envoyee'): ?>
                                                    <span class="badge bg-warning text-dark">Proposition envoyée</span>
                                                <?php elseif ($v['statut'] === 'payee'): ?>
                                                    <span class="badge bg-success">Payée (FedaPay)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Annulée</span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($v['transaction_id'])): ?>
                                                    <small class="d-block text-muted mt-1">TransID : <code><?= e($v['transaction_id']) ?></code></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($v['statut'] === 'nouvelle'): ?>
                                                    <a href="?action=traiter_visite&id=<?= $v['id'] ?>" class="btn btn-sm btn-success me-1">
                                                        <i class="fa-solid fa-user-check me-1"></i> Traiter
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <div class="btn-group me-1">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        Statut
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="?action=status&type=visite&id=<?= $v['id'] ?>&status=nouvelle">Nouvelle</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=visite&id=<?= $v['id'] ?>&status=proposition_envoyee">Proposition envoyée</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=visite&id=<?= $v['id'] ?>&status=payee">Payée</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=visite&id=<?= $v['id'] ?>&status=annulee">Annulée</a></li>
                                                    </ul>
                                                </div>
                                                
                                                <a href="?action=delete&type=visite&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette planification ?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Aucune planification de visite reçue.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. CONTENU : HÉBERGEMENTS -->
                <div class="tab-pane fade p-3" id="hotels-content" role="tabpanel" aria-labelledby="hotels-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Établissement</th>
                                    <th>Arrivée / Départ</th>
                                    <th>Personnes</th>
                                    <th>Statut / Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($bookings)): ?>
                                    <?php foreach ($bookings as $b): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($b['nom_client']) ?></div>
                                                <div class="small text-muted"><i class="fa-solid fa-envelope me-1"></i><?= e($b['email_client']) ?></div>
                                                <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i><?= e($b['telephone_client']) ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= e($b['hotel_nom']) ?></div>
                                                <small class="text-muted d-block"><?= e($b['hotel_ville']) ?></small>
                                                <?php if (!empty($b['type_chambre'])): ?>
                                                    <span class="badge bg-success text-white mt-1" style="font-size: 0.75rem;">Chambre : <?= ucfirst(e($b['type_chambre'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>Du <?= date('d/m/Y', strtotime($b['date_arrivee'])) ?></div>
                                                <div>au <?= date('d/m/Y', strtotime($b['date_depart'])) ?></div>
                                            </td>
                                            <td class="text-center fw-bold"><?= $b['nb_personnes'] ?></td>
                                            <td>
                                                <div class="btn-group mb-2">
                                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <?php if ($b['statut'] === 'nouvelle'): ?>
                                                            <span class="badge bg-danger">Nouvelle</span>
                                                        <?php elseif ($b['statut'] === 'proposition_envoyee'): ?>
                                                            <span class="badge bg-warning text-dark">Proposition envoyée</span>
                                                        <?php elseif ($b['statut'] === 'payee'): ?>
                                                            <span class="badge bg-success">Payée (FedaPay)</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Annulée</span>
                                                        <?php endif; ?>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="?action=status&type=hotel&id=<?= $b['id'] ?>&status=nouvelle">Nouvelle</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=hotel&id=<?= $b['id'] ?>&status=proposition_envoyee">Proposition envoyée</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=hotel&id=<?= $b['id'] ?>&status=payee">Payée</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=hotel&id=<?= $b['id'] ?>&status=annulee">Annulée</a></li>
                                                    </ul>
                                                </div>
                                                
                                                <?php if ($b['statut'] === 'nouvelle'): ?>
                                                    <div class="mt-1 d-flex gap-1 mb-2">
                                                        <a href="?action=proposer_hotel&id=<?= $b['id'] ?>" class="btn btn-sm btn-success flex-grow-1">
                                                            <i class="fa-solid fa-check me-1"></i> Disponible
                                                        </a>
                                                        <a href="?action=refuser_hotel&id=<?= $b['id'] ?>" class="btn btn-sm btn-danger flex-grow-1" onclick="return confirm('Déclarer cet hébergement indisponible ?')">
                                                            <i class="fa-solid fa-xmark me-1"></i> Indisponible
                                                        </a>
                                                    </div>
                                                <?php endif; ?>

                                                <a href="?action=delete&type=hotel&id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette réservation ?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Aucune réservation d'hébergement reçue.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- 3. CONTENU : CHAUFFEURS-GUIDES -->
                <div class="tab-pane fade p-3" id="guides-content" role="tabpanel" aria-labelledby="guides-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Guide</th>
                                    <th>Période</th>
                                    <th>Destination</th>
                                    <th>Statut / Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($guides_demandes)): ?>
                                    <?php foreach ($guides_demandes as $gd): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($gd['nom_client']) ?></div>
                                                <div class="small text-muted"><i class="fa-solid fa-envelope me-1"></i><?= e($gd['email_client']) ?></div>
                                                <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i><?= e($gd['telephone_client']) ?></div>
                                            </td>
                                            <td class="fw-bold"><?= e($gd['guide_nom']) ?></td>
                                            <td>
                                                <div>Du <?= date('d/m/Y', strtotime($gd['date_debut'])) ?></div>
                                                <div>au <?= date('d/m/Y', strtotime($gd['date_fin'])) ?></div>
                                            </td>
                                            <td><?= e($gd['destination']) ?: '-' ?></td>
                                            <td>
                                                <div class="btn-group mb-2">
                                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <?php if ($gd['statut'] === 'nouvelle'): ?>
                                                            <span class="badge bg-danger">Nouvelle</span>
                                                        <?php elseif ($gd['statut'] === 'proposition_envoyee'): ?>
                                                            <span class="badge bg-warning text-dark">Proposition envoyée</span>
                                                        <?php elseif ($gd['statut'] === 'payee'): ?>
                                                            <span class="badge bg-success">Payée (FedaPay)</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Annulée</span>
                                                        <?php endif; ?>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="?action=status&type=guide&id=<?= $gd['id'] ?>&status=nouvelle">Nouvelle</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=guide&id=<?= $gd['id'] ?>&status=proposition_envoyee">Proposition envoyée</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=guide&id=<?= $gd['id'] ?>&status=payee">Payée</a></li>
                                                        <li><a class="dropdown-item" href="?action=status&type=guide&id=<?= $gd['id'] ?>&status=annulee">Annulée</a></li>
                                                    </ul>
                                                </div>

                                                <a href="?action=delete&type=guide&id=<?= $gd['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette demande de guide ?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Aucune demande de chauffeur-guide reçue.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- 4. CONTENU : MESSAGES DE CONTACT -->
                <div class="tab-pane fade p-3" id="messages-content" role="tabpanel" aria-labelledby="messages-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 250px;">Date & Expéditeur</th>
                                    <th>Sujet</th>
                                    <th>Message</th>
                                    <th style="width: 120px;" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($messages)): ?>
                                    <?php foreach ($messages as $m): ?>
                                        <tr>
                                            <td>
                                                <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($m['date_envoi'])) ?></div>
                                                <div class="fw-bold"><?= e($m['nom']) ?></div>
                                                <div class="small text-muted"><?= e($m['email']) ?></div>
                                            </td>
                                            <td class="fw-bold"><?= e($m['sujet']) ?></td>
                                            <td>
                                                <div class="small text-secondary" style="max-width: 400px; white-space: pre-wrap;"><?= e($m['message']) ?></div>
                                            </td>
                                            <td class="text-end">
                                                <a href="?action=delete&type=contact&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce message ?')">
                                                    <i class="fa-solid fa-trash me-1"></i> Supprimer
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Aucun message de contact reçu.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
