<?php
require_once 'includes/fonctions.php';
require_once 'config/database.php';

$page_title = __('nav_home');
$page_desc  = __('hero_subtitle');

// Images du carrousel (dossier statuts)
$carousel_images = [
    'assets/images/statuts/20455a024444236302d741aa7d7cfad7.jpg',
    'assets/images/statuts/598e64229ac8d9f73c92323efafa4d0c.jpg',
];

// 3 lieux vedettes depuis la base avec Caching
$cache_key = "index_lieux_vedette_" . $lang;
$lieux_vedette = get_cache($cache_key, 300); // 5 minutes cache
if ($lieux_vedette === null) {
    try {
        $stmt          = $pdo->query("SELECT id, nom_fr, nom_en, region_fr, region_en, categorie, description_courte_fr, description_courte_en, photo_principale, latitude, longitude FROM lieux ORDER BY id ASC LIMIT 3");
        $lieux_vedette = $stmt->fetchAll();
        set_cache($cache_key, $lieux_vedette);
    } catch (PDOException $e) {
        $lieux_vedette = [];
    }
}

require_once 'includes/header.php';
?>

<!-- ============================================================
     SECTION HERO  —  Carrousel plein écran
     ============================================================ -->
<section class="hero-section" id="hero">

    <!-- Slides de fond en rotation -->
    <div class="hero-carousel-bg" id="heroBg">
        <?php foreach ($carousel_images as $i => $src): ?>
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>">
            <div class="hero-slide-blur" style="background-image:url('<?= e($src) ?>');"></div>
            <img src="<?= e($src) ?>" alt="" class="hero-slide-img">
        </div>
        <?php endforeach; ?>
        <div class="hero-overlay"></div>
    </div>

    <!-- Indicateurs (dots) -->
    <div class="hero-dots">
        <?php foreach ($carousel_images as $i => $src): ?>
        <button class="hero-dot <?= $i === 0 ? 'active' : '' ?>"
                onclick="goToSlide(<?= $i ?>)" aria-label="Slide <?= $i+1 ?>"></button>
        <?php endforeach; ?>
    </div>

    <!-- Contenu : 2 colonnes -->
    <div class="container hero-content-wrapper">
        <div class="row align-items-center g-5">

            <!-- ---- Texte & CTA ---- -->
            <div class="col-lg-6 hero-text-col">
                <span class="hero-badge">
                    <i class="fa-solid fa-star me-1"></i>
                    Tourisme Authentique au Bénin
                </span>

                <h1 class="hero-title mt-3"><?= __('hero_title') ?></h1>
                <p class="hero-subtitle"><?= __('hero_subtitle') ?></p>

                <div class="d-flex gap-3 flex-wrap mt-4">
                    <a href="lieux.php" class="btn btn-hero">
                        <i class="fa-solid fa-compass me-2"></i><?= __('hero_cta') ?>
                    </a>
                    <a href="hebergements.php" class="btn btn-hero-outline">
                        <i class="fa-solid fa-bed me-2"></i>Hébergements
                    </a>
                </div>

                <!-- Stats -->
                <div class="hero-stats mt-5">
                    <div class="hero-stat">
                        <span class="stat-number">10+</span>
                        <span class="stat-label">Sites touristiques</span>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <span class="stat-number">5</span>
                        <span class="stat-label">Hébergements</span>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <span class="stat-number">4</span>
                        <span class="stat-label">Guides certifiés</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     3 SERVICES
     ============================================================ -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-title-container">
            <h2 class="section-title"><?= __('services_title') ?></h2>
            <p class="text-muted col-md-8 mx-auto"><?= __('services_subtitle') ?></p>
        </div>
        <div class="row g-4 mt-2">
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="fa-solid fa-map-marked-alt"></i></div>
                    <h3><?= __('nav_places') ?></h3>
                    <p class="text-muted my-3"><?= __('service_places_desc') ?></p>
                    <a href="lieux.php" class="btn btn-custom btn-primary-custom mt-2"><?= __('btn_explore') ?></a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="fa-solid fa-hotel"></i></div>
                    <h3><?= __('nav_hotels') ?></h3>
                    <p class="text-muted my-3"><?= __('service_hotels_desc') ?></p>
                    <a href="hebergements.php" class="btn btn-custom btn-primary-custom mt-2"><?= __('btn_explore') ?></a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="fa-solid fa-car-side"></i></div>
                    <h3><?= __('nav_guides') ?></h3>
                    <p class="text-muted my-3"><?= __('service_guides_desc') ?></p>
                    <a href="guides.php" class="btn btn-custom btn-primary-custom mt-2"><?= __('btn_explore') ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     LIEUX EN VEDETTE
     ============================================================ -->
<section class="section-padding">
    <div class="container">
        <div class="section-title-container">
            <h2 class="section-title"><?= __('featured_places') ?></h2>
            <p class="text-muted col-md-8 mx-auto"><?= __('featured_places_subtitle') ?></p>
        </div>
        <div class="row g-4 mt-2">
            <?php if (!empty($lieux_vedette)): ?>
                <?php foreach ($lieux_vedette as $lieu): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="custom-card">
                            <div class="card-img-wrapper">
                                <span class="card-badge"><?= e($lieu['categorie']) ?></span>
                                <img src="<?= e(get_image_url($lieu['photo_principale'], $lieu['categorie'])) ?>"
                                     alt="<?= e(db_trans($lieu, 'nom')) ?>" loading="lazy">
                            </div>
                            <div class="card-body-content">
                                <div class="card-location">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?= e(db_trans($lieu, 'region')) ?>
                                </div>
                                <h4 class="mb-3">
                                    <a href="lieu.php?id=<?= $lieu['id'] ?>" class="card-title-link">
                                        <?= e(db_trans($lieu, 'nom')) ?>
                                    </a>
                                </h4>
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
                <div class="col-12 text-center">
                    <div class="alert alert-info">Veuillez importer la base de données MySQL.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     CTA FINAL
     ============================================================ -->
<section class="section-padding cta-section text-white text-center">
    <div class="container py-2">
        <h2 class="mb-3 text-white" style="font-family:'Playfair Display',serif; font-size:2.5rem;">
            Prêt à vivre une expérience authentique ?
        </h2>
        <p class="mb-5 mx-auto col-md-7" style="color:rgba(255,255,255,0.8); font-size:1.1rem;">
            Réservez dès maintenant et planifiez votre itinéraire avec l'un de nos chauffeurs-guides pour découvrir toute la richesse du Bénin.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="hebergements.php" class="btn btn-custom btn-accent-custom px-5 py-3">
                <i class="fa-solid fa-bed me-2"></i>Réserver un Hôtel
            </a>
            <a href="guides.php" class="btn btn-hero-outline px-5 py-3">
                <i class="fa-solid fa-user-tie me-2"></i>Trouver un Guide
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     STYLES HERO (isolés)
     ============================================================ -->
<style>
.hero-section {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    color: #fff;
    padding: 0;
}
.hero-carousel-bg { position: absolute; inset: 0; z-index: 0; }

/* ---------------------------------------------------------------
   MODIFIÉ : le slide contient désormais 2 couches
   1) .hero-slide-blur  -> fond flou (cover) qui comble les bords
   2) .hero-slide-img   -> l'image réelle, jamais recadrée (contain)
   --------------------------------------------------------------- */
.hero-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transform: scale(1.06);
    transition: opacity 1.4s ease, transform 7s ease;
    overflow: hidden;
}
.hero-slide.active { opacity: 1; transform: scale(1); }

.hero-slide-blur {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    filter: blur(30px) brightness(0.55);
    transform: scale(1.2); /* évite de voir les bords flous/transparents */
}

.hero-slide-img {
    position: relative;
    z-index: 1;
    width: 100%;
    height: 100%;
    object-fit: contain;   /* l'image entière est toujours visible, jamais coupée */
    object-position: center;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        105deg,
        rgba(11,59,44,0.55) 0%,
        rgba(11,59,44,0.25) 48%,
        rgba(11,59,44,0.05) 100%
    );
    z-index: 2;
}
.hero-content-wrapper { position: relative; z-index: 3; padding: 140px 0 100px; }
.hero-text-col { margin: 0 auto; }

/* ---------------------------------------------------------------
   DESKTOP : l'image passe à droite (toujours en fond), texte à gauche
   --------------------------------------------------------------- */
@media (min-width: 992px) {
    /* Fond derrière le texte, pour la moitié gauche (vert allégé) */
    .hero-section { background: #1a5c45; }

    /* L'image occupe uniquement la moitié droite */
    .hero-carousel-bg {
        left: 50%;
        width: 50%;
        right: 0;
    }

    /* Suppression complète du dégradé sur desktop */
    .hero-overlay {
        display: none;
    }

    /* Texte cantonné à la moitié gauche, non centré */
    .hero-text-col { margin: 0; }

    /* Affichage complet de l'image (sans rognage) */
    .hero-slide-blur { display: none; }
    .hero-slide-img {
        object-fit: contain;
        object-position: center;
    }
}

/* Badge */
.hero-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(229,169,59,0.15);
    border: 1px solid rgba(229,169,59,0.45);
    color: #E5A93B;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    backdrop-filter: blur(6px);
}

/* Titre */
.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2.4rem, 4.5vw, 3.8rem);
    font-weight: 800;
    line-height: 1.12;
    color: #fff;
    letter-spacing: -0.02em;
}

.hero-subtitle {
    font-size: clamp(1rem, 1.8vw, 1.18rem);
    color: rgba(255,255,255,0.82);
    max-width: 520px;
    line-height: 1.75;
    font-weight: 300;
    margin-top: 18px;
    margin-bottom: 0;
}

/* Bouton outline blanc */
.btn-hero-outline {
    display: inline-flex;
    align-items: center;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    padding: 14px 32px;
    border-radius: 50px;
    border: 1.5px solid rgba(255,255,255,0.5);
    color: #fff;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(6px);
    transition: all 0.4s ease;
    text-decoration: none;
}
.btn-hero-outline:hover { background:#fff; color:#0B3B2C; border-color:#fff; transform:translateY(-2px); }

/* Stats */
.hero-stats {
    display: flex;
    align-items: center;
    gap: 28px;
    padding-top: 22px;
    border-top: 1px solid rgba(255,255,255,0.12);
}
.hero-stat { display: flex; flex-direction: column; }
.stat-number { font-size: 1.85rem; font-weight: 800; color: #E5A93B; line-height: 1; }
.stat-label  { font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
.hero-stat-divider { width: 1px; height: 40px; background: rgba(255,255,255,0.15); flex-shrink: 0; }

/* Dots Hero fond (ancien) */
.hero-dots { position: absolute; bottom: 28px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; z-index: 5; }
.hero-dot {
    width: 8px; height: 8px; border-radius: 50%;
    border: none; background: rgba(255,255,255,0.30);
    cursor: pointer; transition: all 0.35s ease; padding: 0;
}
.hero-dot.active { background: #E5A93B; width: 28px; border-radius: 4px; }

/* CTA Final */
.cta-section {
    background: linear-gradient(135deg, #0B3B2C 0%, #127C54 100%);
    position: relative; overflow: hidden;
}
.cta-section::before {
    content: '';
    position: absolute; inset: 0;
    background: url('assets/images/statuts/598e64229ac8d9f73c92323efafa4d0c.jpg') center/cover no-repeat;
    opacity: 0.07;
}
.cta-section .container { position: relative; z-index: 1; }
</style>

<!-- ============================================================
     JS CARROUSEL HERO
     ============================================================ -->
<script>
// ---- Carrousel de fond (hero background) ----
(function () {
    var slides  = document.querySelectorAll('.hero-slide');
    var dots    = document.querySelectorAll('.hero-dot');
    var total   = slides.length;
    var current = 0;
    var timer   = null;

    function bgActivate(n) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = n % total;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    window.goToSlide = function(n) {
        clearInterval(timer);
        bgActivate(n);
        timer = setInterval(function() { bgActivate((current + 1) % total); }, 5500);
    };
    timer = setInterval(function() { bgActivate((current + 1) % total); }, 5500);
})();

</script>

<?php require_once 'includes/footer.php'; ?>