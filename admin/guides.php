<?php
require_once 'header.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;
$success = null;

$upload_dir = __DIR__ . '/../assets/img/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ----------------------------------------------------------------------------
// TRAITEMENT DU FORMULAIRE
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $nom = trim($_POST['nom']);
    $langues_fr = trim($_POST['langues_fr']);
    $langues_en = trim($_POST['langues_en']);
    $zones_fr = trim($_POST['zones_fr']);
    $zones_en = trim($_POST['zones_en']);
    $tarif_jour = (int)$_POST['tarif_jour'];
    $devise = trim($_POST['devise']);
    $telephone = trim($_POST['telephone']);
    $email = trim($_POST['email']);
    $disponible = isset($_POST['disponible']) ? 1 : 0;

    if (empty($nom) || empty($langues_fr) || empty($langues_en) || $tarif_jour <= 0) {
        $error = "Les champs Nom, Langues (FR/EN) et Tarif journalier sont obligatoires.";
    } else {
        try {
            // Photo Upload
            $photo_name = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['photo']['tmp_name'];
                $file_name = basename($_FILES['photo']['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($file_ext, $allowed)) {
                    $error = "Format de photo invalide (JPG, JPEG, PNG, WEBP).";
                } else {
                    $new_filename = uniqid('guide_', true) . '.' . $file_ext;
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                        $photo_name = $new_filename;
                    } else {
                        $error = "Échec du téléversement de la photo.";
                    }
                }
            }

            if (!$error) {
                if ($action === 'add') {
                    $sql = "INSERT INTO guides 
                            (nom, photo, langues_fr, langues_en, zones_fr, zones_en, tarif_jour, devise, telephone, email, disponible) 
                            VALUES 
                            (:nom, :photo, :langues_fr, :langues_en, :zones_fr, :zones_en, :tarif_jour, :devise, :telephone, :email, :disponible)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'nom' => $nom,
                        'photo' => $photo_name ?: 'default.jpg',
                        'langues_fr' => $langues_fr,
                        'langues_en' => $langues_en,
                        'zones_fr' => $zones_fr,
                        'zones_en' => $zones_en,
                        'tarif_jour' => $tarif_jour,
                        'devise' => $devise ?: 'FCFA',
                        'telephone' => $telephone,
                        'email' => $email,
                        'disponible' => $disponible
                    ]);
                    $success = "Le guide a été créé avec succès.";
                    $action = 'list';
                } else { // edit
                    $sql = "UPDATE guides SET 
                            nom = :nom, langues_fr = :langues_fr, langues_en = :langues_en, 
                            zones_fr = :zones_fr, zones_en = :zones_en, tarif_jour = :tarif_jour, 
                            devise = :devise, telephone = :telephone, email = :email, disponible = :disponible";
                    
                    $params = [
                        'nom' => $nom,
                        'langues_fr' => $langues_fr,
                        'langues_en' => $langues_en,
                        'zones_fr' => $zones_fr,
                        'zones_en' => $zones_en,
                        'tarif_jour' => $tarif_jour,
                        'devise' => $devise,
                        'telephone' => $telephone,
                        'email' => $email,
                        'disponible' => $disponible,
                        'id' => $id
                    ];

                    if ($photo_name !== null) {
                        $sql .= ", photo = :photo";
                        $params['photo'] = $photo_name;
                    }

                    $sql .= " WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = "Le guide a été mis à jour.";
                    $action = 'list';
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------------------------------
// SUPPRESSION
// ----------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    try {
        $img_stmt = $pdo->prepare("SELECT photo FROM guides WHERE id = ?");
        $img_stmt->execute([$id]);
        $old_img = $img_stmt->fetchColumn();
        if ($old_img && $old_img !== 'default.jpg' && file_exists($upload_dir . $old_img)) {
            unlink($upload_dir . $old_img);
        }

        $stmt = $pdo->prepare("DELETE FROM guides WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Guide supprimé de la base de données.";
    } catch (PDOException $e) {
        $error = "Erreur de suppression : ce guide est lié à des demandes de réservation.";
    }
    $action = 'list';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 text-success"><i class="fa-solid fa-car-side me-2"></i>Gestion des Guides</h1>
    <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i> Ajouter un Guide</a>
    <?php else: ?>
        <a href="?action=list" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Retour</a>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success shadow-sm"><i class="fa-solid fa-circle-check me-2"></i><?= e($success) ?></div>
<?php endif; ?>

<!-- VUE LISTE -->
<?php if ($action === 'list'): 
    $stmt = $pdo->query("SELECT * FROM guides ORDER BY nom ASC");
    $guides_list = $stmt->fetchAll();
?>
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Photo</th>
                            <th>Nom</th>
                            <th>Langues (FR/EN)</th>
                            <th>Zones (FR/EN)</th>
                            <th>Tarif journalier</th>
                            <th>Statut</th>
                            <th style="width: 150px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($guides_list)): ?>
                            <?php foreach ($guides_list as $g): ?>
                                <tr>
                                    <td>
                                        <img src="<?= get_image_url($g['photo'], 'guide') ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= e($g['nom']) ?></div>
                                        <small class="text-muted"><?= e($g['email']) ?> / <?= e($g['telephone']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= e($g['langues_fr']) ?></div>
                                        <small class="text-muted"><?= e($g['langues_en']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= e($g['zones_fr']) ?></div>
                                        <small class="text-muted"><?= e($g['zones_en']) ?></small>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?= number_format($g['tarif_jour'], 0, ',', ' ') ?> <?= e($g['devise']) ?>
                                    </td>
                                    <td>
                                        <?php if ($g['disponible']): ?>
                                            <span class="badge bg-success">Disponible</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Indisponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?action=edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>
                                        <a href="?action=delete&id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Voulez-vous supprimer ce chauffeur-guide ?')" >
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Aucun chauffeur-guide enregistré.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- VUE CONFIGURATION -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $g = [
        'nom' => '', 'langues_fr' => '', 'langues_en' => '', 'zones_fr' => '',
        'zones_en' => '', 'tarif_jour' => '', 'devise' => 'FCFA',
        'telephone' => '', 'email' => '', 'disponible' => 1, 'photo' => ''
    ];

    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM guides WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        if ($res) { $g = $res; }
    }
?>
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <h3 class="card-title h5 text-success mb-4 border-bottom pb-2">
                <i class="fa-solid fa-pen-to-square me-2"></i><?= $action === 'add' ? 'Créer un profil guide' : 'Modifier le profil guide' ?>
            </h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nom Complet *</label>
                        <input type="text" name="nom" class="form-control" value="<?= e($g['nom']) ?>" placeholder="Ex: Jean-Albert Dossou" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Disponibilité</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="disponible" value="1" id="flexSwitchCheckChecked" <?= $g['disponible'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted" for="flexSwitchCheckChecked">Disponible pour de nouvelles missions</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Langues (Français) *</label>
                        <input type="text" name="langues_fr" class="form-control" value="<?= e($g['langues_fr']) ?>" placeholder="Ex: Français, Fon, Goun" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Langues (Anglais) *</label>
                        <input type="text" name="langues_en" class="form-control" value="<?= e($g['langues_en']) ?>" placeholder="Ex: French, Fon, Goun (English basic)" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Zones couvertes (Français) *</label>
                        <input type="text" name="zones_fr" class="form-control" value="<?= e($g['zones_fr']) ?>" placeholder="Ex: Sud Bénin (Cotonou, Ouidah)" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Zones couvertes (Anglais) *</label>
                        <input type="text" name="zones_en" class="form-control" value="<?= e($g['zones_en']) ?>" placeholder="Ex: Southern Benin (Cotonou, Ouidah)" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tarif journalier *</label>
                        <input type="number" name="tarif_jour" class="form-control" value="<?= e($g['tarif_jour']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Devise</label>
                        <input type="text" name="devise" class="form-control" value="<?= e($g['devise']) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Numéro de téléphone</label>
                        <input type="tel" name="telephone" class="form-control" value="<?= e($g['telephone']) ?>" placeholder="Ex: +229 97 00 00 00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Adresse Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($g['email']) ?>" placeholder="Ex: guide@exemple.com">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Photo de profil</label>
                    <input type="file" name="photo" class="form-control">
                    <?php if (!empty($g['photo'])): ?>
                        <div class="mt-2 small text-muted">
                            Photo actuelle : <code><?= e($g['photo']) ?></code><br>
                            <img src="<?= get_image_url($g['photo'], 'guide') ?>" class="rounded mt-1" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <button type="submit" class="btn btn-success px-4 py-2"><i class="fa-solid fa-save me-1"></i> Enregistrer</button>
                    <a href="?action=list" class="btn btn-secondary px-4 py-2">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
