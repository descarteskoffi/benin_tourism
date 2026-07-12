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
// TRAITEMENT SOUBOUMISSION FORMULAIRE
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $nom = trim($_POST['nom']);
    $type_fr = trim($_POST['type_fr']);
    $type_en = trim($_POST['type_en']);
    $localite = trim($_POST['localite']);
    $quartier = trim($_POST['quartier']);
    $prix_nuit = (int)$_POST['prix_nuit'];
    $devise = trim($_POST['devise']);
    $description_fr = trim($_POST['description_fr']);
    $description_en = trim($_POST['description_en']);
    $email_contact = trim($_POST['email_contact']);

    if (empty($nom) || empty($type_fr) || empty($type_en) || $prix_nuit <= 0 || empty($localite)) {
        $error = "Les champs Nom, Type (FR/EN), Localité et Prix par nuit sont obligatoires.";
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
                    $error = "Photo invalide (JPG, JPEG, PNG, WEBP uniquement).";
                } else {
                    $new_filename = uniqid('hotel_', true) . '.' . $file_ext;
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                        $photo_name = $new_filename;
                    } else {
                        $error = "Impossible d'enregistrer l'image.";
                    }
                }
            }

            if (!$error) {
                if ($action === 'add') {
                    $sql = "INSERT INTO hebergements 
                            (nom, type_fr, type_en, localite, quartier, prix_nuit, devise, description_fr, description_en, photo, email_contact) 
                            VALUES 
                            (:nom, :type_fr, :type_en, :localite, :quartier, :prix_nuit, :devise, :description_fr, :description_en, :photo, :email_contact)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'nom' => $nom,
                        'type_fr' => $type_fr,
                        'type_en' => $type_en,
                        'localite' => $localite,
                        'quartier' => $quartier,
                        'prix_nuit' => $prix_nuit,
                        'devise' => $devise ?: 'FCFA',
                        'description_fr' => $description_fr,
                        'description_en' => $description_en,
                        'photo' => $photo_name ?: 'default.jpg',
                        'email_contact' => $email_contact
                    ]);
                    $success = "Hébergement créé avec succès.";
                    $action = 'list';
                } else { // edit
                    $sql = "UPDATE hebergements SET 
                            nom = :nom, type_fr = :type_fr, type_en = :type_en, localite = :localite, 
                            quartier = :quartier, prix_nuit = :prix_nuit, devise = :devise, 
                            description_fr = :description_fr, description_en = :description_en, email_contact = :email_contact";
                    
                    $params = [
                        'nom' => $nom,
                        'type_fr' => $type_fr,
                        'type_en' => $type_en,
                        'localite' => $localite,
                        'quartier' => $quartier,
                        'prix_nuit' => $prix_nuit,
                        'devise' => $devise,
                        'description_fr' => $description_fr,
                        'description_en' => $description_en,
                        'email_contact' => $email_contact,
                        'id' => $id
                    ];

                    if ($photo_name !== null) {
                        $sql .= ", photo = :photo";
                        $params['photo'] = $photo_name;
                    }

                    $sql .= " WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = "Hébergement mis à jour.";
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
        // Suppression image physique
        $img_stmt = $pdo->prepare("SELECT photo FROM hebergements WHERE id = ?");
        $img_stmt->execute([$id]);
        $old_img = $img_stmt->fetchColumn();
        if ($old_img && $old_img !== 'default.jpg' && file_exists($upload_dir . $old_img)) {
            unlink($upload_dir . $old_img);
        }

        $stmt = $pdo->prepare("DELETE FROM hebergements WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Hébergement supprimé.";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : ce logement possède peut-être des réservations enregistrées.";
    }
    $action = 'list';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 text-success"><i class="fa-solid fa-hotel me-2"></i>Gestion des Hébergements</h1>
    <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i> Ajouter un Hébergement</a>
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

<!-- LISTE -->
<?php if ($action === 'list'): 
    $stmt = $pdo->query("SELECT * FROM hebergements ORDER BY nom ASC");
    $hotels = $stmt->fetchAll();
?>
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Image</th>
                            <th>Nom / Type</th>
                            <th>Localité</th>
                            <th>Prix / Nuit</th>
                            <th>Email de contact</th>
                            <th style="width: 150px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($hotels)): ?>
                            <?php foreach ($hotels as $h): ?>
                                <tr>
                                    <td>
                                        <img src="<?= get_image_url($h['photo'], $h['type_fr']) ?>" class="rounded" style="width: 60px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= e($h['nom']) ?></div>
                                        <small class="text-muted"><?= e($h['type_fr']) ?> (<?= e($h['type_en']) ?>)</small>
                                    </td>
                                    <td>
                                        <?= e($h['localite']) ?> <?= $h['quartier'] ? '<small class="text-muted">(' . e($h['quartier']) . ')</small>' : '' ?>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?= number_format($h['prix_nuit'], 0, ',', ' ') ?> <?= e($h['devise']) ?>
                                    </td>
                                    <td><?= e($h['email_contact']) ?></td>
                                    <td class="text-end">
                                        <a href="?action=edit&id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>
                                        <a href="?action=delete&id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cet hébergement ?')" >
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Aucun hébergement enregistré.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- AJOUT / EDITION -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $h = [
        'nom' => '', 'type_fr' => 'Hôtel', 'type_en' => 'Hotel', 'localite' => '',
        'quartier' => '', 'prix_nuit' => '', 'devise' => 'FCFA',
        'description_fr' => '', 'description_en' => '', 'photo' => '', 'email_contact' => ''
    ];

    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM hebergements WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        if ($res) { $h = $res; }
    }
?>
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <h3 class="card-title h5 text-success mb-4 border-bottom pb-2">
                <i class="fa-solid fa-pen-to-square me-2"></i><?= $action === 'add' ? 'Créer un hébergement' : 'Modifier l\'hébergement' ?>
            </h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nom de l'établissement *</label>
                        <input type="text" name="nom" class="form-control" value="<?= e($h['nom']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Email de contact pour réservations</label>
                        <input type="email" name="email_contact" class="form-control" value="<?= e($h['email_contact']) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Type (Français) *</label>
                        <select name="type_fr" class="form-select" required>
                            <option value="Hôtel" <?= $h['type_fr'] === 'Hôtel' ? 'selected' : '' ?>>Hôtel</option>
                            <option value="Auberge" <?= $h['type_fr'] === 'Auberge' ? 'selected' : '' ?>>Auberge</option>
                            <option value="Maison d'hôtes" <?= $h['type_fr'] === "Maison d'hôtes" ? 'selected' : '' ?>>Maison d'hôtes</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Type (Anglais) *</label>
                        <select name="type_en" class="form-select" required>
                            <option value="Hotel" <?= $h['type_en'] === 'Hotel' ? 'selected' : '' ?>>Hotel</option>
                            <option value="Inn" <?= $h['type_en'] === 'Inn' ? 'selected' : '' ?>>Inn</option>
                            <option value="Guest house" <?= $h['type_en'] === 'Guest house' ? 'selected' : '' ?>>Guest house</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Localité (Ville) *</label>
                        <input type="text" name="localite" class="form-control" value="<?= e($h['localite']) ?>" placeholder="Ex: Cotonou" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Quartier</label>
                        <input type="text" name="quartier" class="form-control" value="<?= e($h['quartier']) ?>" placeholder="Ex: Haie Vive">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Prix par nuit *</label>
                        <input type="number" name="prix_nuit" class="form-control" value="<?= e($h['prix_nuit']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Devise</label>
                        <input type="text" name="devise" class="form-control" value="<?= e($h['devise']) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Description (Français)</label>
                        <textarea name="description_fr" class="form-control" rows="4"><?= e($h['description_fr']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Description (Anglais)</label>
                        <textarea name="description_en" class="form-control" rows="4"><?= e($h['description_en']) ?></textarea>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Photo de l'établissement</label>
                    <input type="file" name="photo" class="form-control">
                    <?php if (!empty($h['photo'])): ?>
                        <div class="mt-2 small text-muted">
                            Photo actuelle : <code><?= e($h['photo']) ?></code><br>
                            <img src="<?= get_image_url($h['photo'], $h['type_fr']) ?>" class="rounded mt-1" style="width: 100px; height: 60px; object-fit: cover;">
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
