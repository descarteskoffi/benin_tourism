<?php
require_once 'header.php';

// Variables de gestion d'état
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;
$success = null;

// Dossier cible pour le téléversement des photos
$upload_dir = __DIR__ . '/../assets/img/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ----------------------------------------------------------------------------
// TRAITEMENT DU FORMULAIRE (AJOUT / MODIFICATION)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $nom_fr = trim($_POST['nom_fr']);
    $nom_en = trim($_POST['nom_en']);
    $region_fr = trim($_POST['region_fr']);
    $region_en = trim($_POST['region_en']);
    $categorie = trim($_POST['categorie']);
    $description_courte_fr = trim($_POST['description_courte_fr']);
    $description_courte_en = trim($_POST['description_courte_en']);
    $histoire_fr = trim($_POST['histoire_fr']);
    $histoire_en = trim($_POST['histoire_en']);
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : 0.0;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : 0.0;
    $horaires_fr = trim($_POST['horaires_fr']);
    $horaires_en = trim($_POST['horaires_en']);
    $tarif_fr = trim($_POST['tarif_fr']);
    $tarif_en = trim($_POST['tarif_en']);

    if (empty($nom_fr) || empty($nom_en) || empty($categorie)) {
        $error = "Les champs Nom (FR/EN) et Catégorie sont obligatoires.";
    } else {
        try {
            // Gestion du téléversement de la photo
            $photo_name = null;
            if (isset($_FILES['photo_principale']) && $_FILES['photo_principale']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['photo_principale']['tmp_name'];
                $file_name = basename($_FILES['photo_principale']['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Validation de l'extension
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($file_ext, $allowed_extensions)) {
                    $error = "Format de photo invalide. Extensions autorisées : JPG, JPEG, PNG, WEBP.";
                } else {
                    // Renommer le fichier pour éviter les doublons
                    $new_filename = uniqid('lieu_', true) . '.' . $file_ext;
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                        $photo_name = $new_filename;
                    } else {
                        $error = "Échec du transfert de la photo.";
                    }
                }
            }

            if (!$error) {
                if ($action === 'add') {
                    // Requête d'insertion
                    $sql = "INSERT INTO lieux 
                            (nom_fr, nom_en, region_fr, region_en, categorie, description_courte_fr, description_courte_en, histoire_fr, histoire_en, photo_principale, latitude, longitude, horaires_fr, horaires_en, tarif_fr, tarif_en) 
                            VALUES 
                            (:nom_fr, :nom_en, :region_fr, :region_en, :categorie, :description_courte_fr, :description_courte_en, :histoire_fr, :histoire_en, :photo_principale, :latitude, :longitude, :horaires_fr, :horaires_en, :tarif_fr, :tarif_en)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'nom_fr' => $nom_fr,
                        'nom_en' => $nom_en,
                        'region_fr' => $region_fr,
                        'region_en' => $region_en,
                        'categorie' => $categorie,
                        'description_courte_fr' => $description_courte_fr,
                        'description_courte_en' => $description_courte_en,
                        'histoire_fr' => $histoire_fr,
                        'histoire_en' => $histoire_en,
                        'photo_principale' => $photo_name ?: 'default.jpg',
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'horaires_fr' => $horaires_fr,
                        'horaires_en' => $horaires_en,
                        'tarif_fr' => $tarif_fr,
                        'tarif_en' => $tarif_en
                    ]);
                    $success = "Le lieu touristique a bien été ajouté.";
                    clear_cache();
                    $action = 'list';
                } else { // edit
                    // Requête de mise à jour
                    $sql = "UPDATE lieux SET 
                            nom_fr = :nom_fr, nom_en = :nom_en, region_fr = :region_fr, region_en = :region_en, 
                            categorie = :categorie, description_courte_fr = :description_courte_fr, description_courte_en = :description_courte_en, 
                            histoire_fr = :histoire_fr, histoire_en = :histoire_en, latitude = :latitude, longitude = :longitude, 
                            horaires_fr = :horaires_fr, horaires_en = :horaires_en, tarif_fr = :tarif_fr, tarif_en = :tarif_en";
                    
                    $params = [
                        'nom_fr' => $nom_fr,
                        'nom_en' => $nom_en,
                        'region_fr' => $region_fr,
                        'region_en' => $region_en,
                        'categorie' => $categorie,
                        'description_courte_fr' => $description_courte_fr,
                        'description_courte_en' => $description_courte_en,
                        'histoire_fr' => $histoire_fr,
                        'histoire_en' => $histoire_en,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'horaires_fr' => $horaires_fr,
                        'horaires_en' => $horaires_en,
                        'tarif_fr' => $tarif_fr,
                        'tarif_en' => $tarif_en,
                        'id' => $id
                    ];

                    if ($photo_name !== null) {
                        $sql .= ", photo_principale = :photo_principale";
                        $params['photo_principale'] = $photo_name;
                    }

                    $sql .= " WHERE id = :id";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = "Le lieu touristique a bien été modifié.";
                    clear_cache();
                    $action = 'list';
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données : " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------------------------------
// SUPPRESSION D'UN LIEU
// ----------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    try {
        // Optionnel : Supprimer le fichier image physique correspondant pour ne pas encombrer le disque
        $img_stmt = $pdo->prepare("SELECT photo_principale FROM lieux WHERE id = ?");
        $img_stmt->execute([$id]);
        $old_img = $img_stmt->fetchColumn();
        if ($old_img && $old_img !== 'default.jpg' && file_exists($upload_dir . $old_img)) {
            unlink($upload_dir . $old_img);
        }

        $stmt = $pdo->prepare("DELETE FROM lieux WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Le lieu a bien été supprimé.";
        clear_cache();
    } catch (PDOException $e) {
        $error = "Impossible de supprimer ce lieu (il est probablement lié à d'autres enregistrements).";
    }
    $action = 'list';
}

// ----------------------------------------------------------------------------
// AFFICHAGE
// ----------------------------------------------------------------------------
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 text-success"><i class="fa-solid fa-map-location-dot me-2"></i>Gestion des Lieux</h1>
    <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i> Ajouter un Lieu</a>
    <?php else: ?>
        <a href="?action=list" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Retour à la liste</a>
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
    $stmt = $pdo->query("SELECT * FROM lieux ORDER BY nom_fr ASC");
    $lieux = $stmt->fetchAll();
?>
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Photo</th>
                            <th>Nom (FR/EN)</th>
                            <th>Région (FR/EN)</th>
                            <th>Catégorie</th>
                            <th style="width: 150px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lieux)): ?>
                            <?php foreach ($lieux as $l): ?>
                                <tr>
                                    <td>
                                        <img src="<?= get_image_url($l['photo_principale'], $l['categorie']) ?>" class="rounded" style="width: 60px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= e($l['nom_fr']) ?></div>
                                        <small class="text-muted"><?= e($l['nom_en']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= e($l['region_fr']) ?></div>
                                        <small class="text-muted"><?= e($l['region_en']) ?></small>
                                    </td>
                                    <td><span class="badge bg-success"><?= e($l['categorie']) ?></span></td>
                                    <td class="text-end">
                                        <a href="?action=edit&id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Modifier">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>
                                        <a href="?action=delete&id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce lieu ?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Aucun lieu enregistré.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- VUE AJOUT / MODIFICATION -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $l = [
        'nom_fr' => '', 'nom_en' => '', 'region_fr' => '', 'region_en' => '',
        'categorie' => 'Culture', 'description_courte_fr' => '', 'description_courte_en' => '',
        'histoire_fr' => '', 'histoire_en' => '', 'photo_principale' => '',
        'latitude' => '', 'longitude' => '', 'horaires_fr' => '', 'horaires_en' => '',
        'tarif_fr' => '', 'tarif_en' => ''
    ];
    
    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        if ($res) {
            $l = $res;
        }
    }
?>
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <h3 class="card-title h5 text-success mb-4 border-bottom pb-2">
                <i class="fa-solid fa-pen-to-square me-2"></i><?= $action === 'add' ? 'Ajouter un nouveau lieu' : 'Modifier le lieu' ?>
            </h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Noms bilingues -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nom (Français) *</label>
                        <input type="text" name="nom_fr" class="form-control" value="<?= e($l['nom_fr']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nom (Anglais) *</label>
                        <input type="text" name="nom_en" class="form-control" value="<?= e($l['nom_en']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Régions bilingues -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Région (Français)</label>
                        <input type="text" name="region_fr" class="form-control" value="<?= e($l['region_fr']) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Région (Anglais)</label>
                        <input type="text" name="region_en" class="form-control" value="<?= e($l['region_en']) ?>">
                    </div>
                    <!-- Catégorie -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Catégorie *</label>
                        <select name="categorie" class="form-select" required>
                            <option value="Culture" <?= $l['categorie'] === 'Culture' ? 'selected' : '' ?>>Culture</option>
                            <option value="Nature" <?= $l['categorie'] === 'Nature' ? 'selected' : '' ?>>Nature</option>
                            <option value="Plage" <?= $l['categorie'] === 'Plage' ? 'selected' : '' ?>>Plage</option>
                            <option value="Spiritualité" <?= $l['categorie'] === 'Spiritualité' ? 'selected' : '' ?>>Spiritualité</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Descriptions courtes bilingues -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Description Courte (Français) *</label>
                        <textarea name="description_courte_fr" class="form-control" rows="2" maxlength="255" required><?= e($l['description_courte_fr']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Description Courte (Anglais) *</label>
                        <textarea name="description_courte_en" class="form-control" rows="2" maxlength="255" required><?= e($l['description_courte_en']) ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <!-- Histoires bilingues -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Histoire / Récit Détaillé (Français)</label>
                        <textarea name="histoire_fr" class="form-control" rows="6"><?= e($l['histoire_fr']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Histoire / Récit Détaillé (Anglais)</label>
                        <textarea name="histoire_en" class="form-control" rows="6"><?= e($l['histoire_en']) ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <!-- Coordonnées GPS -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Latitude (GPS)</label>
                        <input type="number" step="0.0000001" name="latitude" class="form-control" value="<?= e($l['latitude']) ?>" placeholder="Ex: 6.467822">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Longitude (GPS)</label>
                        <input type="number" step="0.0000001" name="longitude" class="form-control" value="<?= e($l['longitude']) ?>" placeholder="Ex: 2.417258">
                    </div>
                </div>

                <div class="row">
                    <!-- Horaires bilingues -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Horaires (Français)</label>
                        <input type="text" name="horaires_fr" class="form-control" value="<?= e($l['horaires_fr']) ?>" placeholder="Ex: 08h00 - 18h00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Horaires (Anglais)</label>
                        <input type="text" name="horaires_en" class="form-control" value="<?= e($l['horaires_en']) ?>" placeholder="Ex: 8:00 AM - 6:00 PM">
                    </div>
                </div>

                <div class="row">
                    <!-- Tarifs bilingues -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tarifs (Français)</label>
                        <input type="text" name="tarif_fr" class="form-control" value="<?= e($l['tarif_fr']) ?>" placeholder="Ex: 5 000 FCFA">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tarifs (Anglais)</label>
                        <input type="text" name="tarif_en" class="form-control" value="<?= e($l['tarif_en']) ?>" placeholder="Ex: 5,000 FCFA">
                    </div>
                </div>

                <!-- Téléversement de l'image -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Photo principale</label>
                    <input type="file" name="photo_principale" class="form-control">
                    <?php if (!empty($l['photo_principale'])): ?>
                        <div class="mt-2 small text-muted">
                            Photo actuelle : <code><?= e($l['photo_principale']) ?></code>
                            <br>
                            <img src="<?= get_image_url($l['photo_principale'], $l['categorie']) ?>" class="rounded mt-1" style="width: 100px; height: 60px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Submit -->
                <div>
                    <button type="submit" class="btn btn-success px-4 py-2"><i class="fa-solid fa-save me-1"></i> Enregistrer</button>
                    <a href="?action=list" class="btn btn-secondary px-4 py-2">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
