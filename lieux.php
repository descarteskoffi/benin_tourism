<?php
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// SEO Metas
$page_title = __('nav_places');
$page_desc = __('service_places_desc');

// Récupération des filtres depuis l'URL
$cat_filter = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';
$region_filter = isset($_GET['region']) ? trim($_GET['region']) : '';

// Paramètres de pagination
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

try {
    $categories = ['Culture', 'Nature', 'Plage', 'Spiritualité'];

    // 1. Récupération dynamique des régions (avec Cache)
    $cache_regions_key = "db_regions";
    $db_regions = get_cache($cache_regions_key, 3600); // 1 heure de cache
    if ($db_regions === null) {
        $regions_stmt = $pdo->query("SELECT DISTINCT region_fr, region_en FROM lieux ORDER BY region_fr ASC");
        $db_regions = $regions_stmt->fetchAll();
        set_cache($cache_regions_key, $db_regions);
    }

    // 2. Construction de la requête SQL filtrée
    $where_clauses = ["1=1"];
    $params = [];

    if ($cat_filter !== '') {
        $where_clauses[] = "categorie = :categorie";
        $params['categorie'] = $cat_filter;
    }

    if ($region_filter !== '') {
        $where_clauses[] = "(region_fr = :region OR region_en = :region)";
        $params['region'] = $region_filter;
    }

    $where_sql = implode(" AND ", $where_clauses);

    // 3. Récupération du nombre total pour la pagination (avec Cache)
    $cache_total_key = "lieux_total_" . md5($where_sql . serialize($params));
    $total_count = get_cache($cache_total_key, 300);
    if ($total_count === null) {
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM lieux WHERE $where_sql");
        $count_stmt->execute($params);
        $total_count = (int)$count_stmt->fetchColumn();
        set_cache($cache_total_key, $total_count);
    }

    $total_pages = ceil($total_count / $limit);
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $offset = ($page - 1) * $limit;
    }

    // 4. Récupération des lieux paginés (avec Cache)
    $cache_lieux_key = "lieux_list_" . md5($where_sql . serialize($params) . "_page_" . $page . "_lang_" . $lang);
    $lieux = get_cache($cache_lieux_key, 300); // 5 minutes cache
    if ($lieux === null) {
        $sql = "SELECT id, nom_fr, nom_en, region_fr, region_en, categorie, description_courte_fr, description_courte_en, photo_principale, latitude, longitude FROM lieux WHERE $where_sql ORDER BY nom_fr ASC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        // Liaison des paramètres spécifiques de pagination (obligatoire avec PDO::PARAM_INT)
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->execute();
        $lieux = $stmt->fetchAll();
        set_cache($cache_lieux_key, $lieux);
    }

} catch (PDOException $e) {
    $categories = [];
    $db_regions = [];
    $lieux = [];
    $total_pages = 0;
    $total_count = 0;
}

require_once 'includes/header.php';
?>

<!-- En-tête de page -->
<section class="lieu-detail-header text-center">
    <div class="container">
        <h1 class="lieu-title"><?= __('nav_places') ?></h1>
        <p class="lead text-muted col-md-8 mx-auto"><?= __('service_places_desc') ?></p>
    </div>
</section>

<!-- Section Filtres & Liste -->
<section class="section-padding">
    <div class="container">
        <!-- Formulaire de filtrage GET -->
        <div class="form-card mb-5">
            <form method="GET" action="lieux.php" class="row g-3 align-items-end">
                <!-- Filtre Catégorie -->
                <div class="col-md-5">
                    <label class="form-label fw-bold"><?= __('filter_category') ?></label>
                    <select name="categorie" class="form-select">
                        <option value=""><?= __('filter_all') ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $cat_filter === $cat ? 'selected' : '' ?>>
                                <?= e($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Filtre Région -->
                <div class="col-md-5">
                    <label class="form-label fw-bold"><?= __('filter_region') ?></label>
                    <select name="region" class="form-select">
                        <option value=""><?= __('filter_all') ?></option>
                        <?php foreach ($db_regions as $reg): ?>
                            <?php 
                            // Affiche la région selon la langue mais conserve la valeur d'origine pour la recherche
                            $display_region = ($lang === 'en') ? $reg['region_en'] : $reg['region_fr'];
                            $value_region = $reg['region_fr']; // On utilise la version FR comme clé d'identification
                            ?>
                            <option value="<?= e($value_region) ?>" <?= ($region_filter === $value_region) ? 'selected' : '' ?>>
                                <?= e($display_region) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Boutons -->
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-custom btn-primary-custom py-3">
                        <i class="fa-solid fa-filter me-2"></i><?= __('filter_btn') ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des Lieux -->
        <div class="row g-4">
            <?php if (!empty($lieux)): ?>
                <?php foreach ($lieux as $lieu): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="custom-card">
                            <div class="card-img-wrapper">
                                <span class="card-badge"><?= e($lieu['categorie']) ?></span>
                                <img src="<?= get_image_url($lieu['photo_principale'], $lieu['categorie']) ?>" alt="<?= e(db_trans($lieu, 'nom')) ?>" loading="lazy">
                            </div>
                            <div class="card-body-content">
                                <div class="card-location">
                                    <i class="fa-solid fa-location-dot text-accent me-1"></i>
                                    <?= e(db_trans($lieu, 'region')) ?>
                                </div>
                                <h3 class="h4 mb-3">
                                    <a href="lieu.php?id=<?= $lieu['id'] ?>" class="card-title-link">
                                        <?= e(db_trans($lieu, 'nom')) ?>
                                    </a>
                                </h3>
                                <p class="card-text-desc flex-grow-1">
                                    <?= e(db_trans($lieu, 'description_courte')) ?>
                                </p>
                                <div class="d-flex justify-content-center align-items-center mt-auto">
                                    <a href="lieu.php?id=<?= $lieu['id'] ?>" class="btn btn-custom btn-primary-custom">
                                        <?= __('btn_explore') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="alert alert-warning py-4">
                        <i class="fa-solid fa-circle-info fa-2x mb-3 text-warning"></i>
                        <p class="mb-0 fs-5"><?= __('no_places_found') ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php
        $query_params = $_GET;
        unset($query_params['page']);
        $base_query_string = http_build_query($query_params);
        ?>
        <?php if ($total_pages > 1): ?>
            <div class="row mt-5">
                <div class="col-12 d-flex justify-content-center">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-custom gap-2">
                            <!-- Page précédente -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($base_query_string) ? '&' . $base_query_string : '' ?>" aria-label="Previous">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            </li>

                            <!-- Liste des pages -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($base_query_string) ? '&' . $base_query_string : '' ?>">
                                        <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Page suivante -->
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($base_query_string) ? '&' . $base_query_string : '' ?>" aria-label="Next">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Styles Pagination de Prestige -->
<style>
.pagination-custom .page-item .page-link {
    background: #ffffff;
    border: 1px solid rgba(11, 59, 44, 0.15);
    color: #0B3B2C;
    font-family: 'Outfit', sans-serif;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.pagination-custom .page-item.active .page-link {
    background: #0B3B2C;
    border-color: #0B3B2C;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(11, 59, 44, 0.18);
}
.pagination-custom .page-item .page-link:hover {
    background: #E5A93B;
    border-color: #E5A93B;
    color: #0B3B2C;
}
.pagination-custom .page-item.disabled .page-link {
    background: #f8f9fa;
    color: #6c757d;
    border-color: rgba(11, 59, 44, 0.08);
}
</style>

<?php require_once 'includes/footer.php'; ?>
