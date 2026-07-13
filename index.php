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
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
             style="background-image:url('<?= e($src) ?>');">
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

            <!-- ---- Gauche : Texte & CTA ---- -->
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

            <!-- ---- Droite : Carrousel Cinématique ---- -->
            <div class="col-lg-6 d-none d-lg-flex justify-content-center align-items-center">
                <div class="cine-carousel">

                    <!-- Cadre principal -->
                    <div class="cine-frame">

                        <!-- Slides -->
                        <?php foreach ($carousel_images as $i => $src): ?>
                        <div class="cine-slide <?= $i === 0 ? 'active' : '' ?>" id="cineSlide<?= $i ?>">
                            <img src="<?= e($src) ?>" alt="Bénin Tourisme - Slide <?= $i+1 ?>">
                        </div>
                        <?php endforeach; ?>

                        <!-- Overlay gradient bas -->
                        <div class="cine-gradient"></div>

                        <!-- Info bas -->
                        <div class="cine-info">
                            <div class="cine-location">
                                <i class="fa-solid fa-location-dot"></i>
                                <span id="cineLabel">Bénin, Afrique de l'Ouest</span>
                            </div>
                            <div class="cine-counter">
                                <span id="cineCurrent">01</span>
                                <span class="cine-sep">/</span>
                                <span class="cine-total"><?= str_pad(count($carousel_images), 2, '0', STR_PAD_LEFT) ?></span>
                            </div>
                        </div>

                        <!-- Barre de progression -->
                        <div class="cine-progress">
                            <div class="cine-progress-bar" id="cineProgressBar"></div>
                        </div>

                        <!-- Flèches de navigation -->
                        <button class="cine-arrow cine-prev" onclick="cinePrev()" aria-label="Précédent">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button class="cine-arrow cine-next" onclick="cineNext()" aria-label="Suivant">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                        <!-- Pastille coin -->
                        <div class="cine-badge-corner">
                            <i class="fa-solid fa-camera"></i> Galerie
                        </div>
                    </div>

                    <!-- Labels / dots sous le cadre -->
                    <div class="cine-dots">
                        <?php foreach ($carousel_images as $i => $src): ?>
                        <button class="cine-dot <?= $i === 0 ? 'active' : '' ?>"
                                onclick="cineGo(<?= $i ?>)" aria-label="Slide <?= $i+1 ?>"></button>
                        <?php endforeach; ?>
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

.hero-slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transform: scale(1.06);
    transition: opacity 1.4s ease, transform 7s ease;
}
.hero-slide.active { opacity: 1; transform: scale(1); }

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        105deg,
        rgba(11,59,44,0.78) 0%,
        rgba(11,59,44,0.38) 48%,
        rgba(11,59,44,0.10) 100%
    );
}
.hero-content-wrapper { position: relative; z-index: 2; padding: 140px 0 100px; }

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

/* ---- Carrousel Cinématique (droite) ---- */
.cine-carousel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
}

.cine-frame {
    position: relative;
    width: 460px;
    height: 580px;
    border-radius: 24px;
    overflow: hidden;
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.08),
        0 40px 100px rgba(0,0,0,0.55),
        0 0 60px rgba(229,169,59,0.08);
    cursor: pointer;
    perspective: 1000px;
    background: #0a0a0a;
}

/* Slides */
.cine-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 1s ease, transform 0.9s cubic-bezier(0.25,0.8,0.25,1);
    transform: scale(1.04);
}
.cine-slide.active {
    opacity: 1;
    transform: scale(1);
}
.cine-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Gradient bas */
.cine-gradient {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 55%;
    background: linear-gradient(0deg, rgba(5,20,12,0.92) 0%, rgba(5,20,12,0.40) 60%, transparent 100%);
    z-index: 2;
    pointer-events: none;
}

/* Infos bas */
.cine-info {
    position: absolute;
    bottom: 52px; left: 24px; right: 24px;
    z-index: 3;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.cine-location {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    font-size: 0.88rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}
.cine-location i { color: #E5A93B; font-size: 0.9rem; }

.cine-counter {
    display: flex;
    align-items: baseline;
    gap: 3px;
    font-family: 'Outfit', sans-serif;
}
#cineCurrent {
    font-size: 2rem;
    font-weight: 800;
    color: #E5A93B;
    line-height: 1;
}
.cine-sep {
    font-size: 1rem;
    color: rgba(255,255,255,0.4);
    margin: 0 2px;
}
.cine-total {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.55);
    font-weight: 600;
}

/* Barre de progression */
.cine-progress {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 4px;
    background: rgba(255,255,255,0.12);
    z-index: 4;
}
.cine-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #E5A93B, #f5c842);
    width: 0%;
    transition: width 0.1s linear;
    border-radius: 0 2px 2px 0;
}

/* Flèches */
.cine-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    width: 44px; height: 44px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(8px);
    color: #fff;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    opacity: 0;
}
.cine-frame:hover .cine-arrow { opacity: 1; }
.cine-arrow:hover { background: #E5A93B; color: #0B3B2C; transform: translateY(-50%) scale(1.1); }
.cine-prev { left: 14px; }
.cine-next { right: 14px; }

/* Pastille coin */
.cine-badge-corner {
    position: absolute;
    top: 18px; left: 18px;
    z-index: 5;
    background: rgba(229,169,59,0.18);
    border: 1px solid rgba(229,169,59,0.45);
    color: #E5A93B;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 6px 13px;
    border-radius: 20px;
    backdrop-filter: blur(8px);
}

/* Dots sous le cadre */
.cine-dots {
    display: flex;
    gap: 8px;
    margin-top: 4px;
}
.cine-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.25);
    cursor: pointer;
    transition: all 0.35s ease;
    padding: 0;
}
.cine-dot.active { background: #E5A93B; width: 26px; border-radius: 4px; }

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

// ---- Carrousel Cinématique (droite) ----
(function () {
    var cineSlides = document.querySelectorAll('.cine-slide');
    var cineDots   = document.querySelectorAll('.cine-dot');
    var cineBar    = document.getElementById('cineProgressBar');
    var cineNum    = document.getElementById('cineCurrent');
    var cineLabel  = document.getElementById('cineLabel');
    var total      = cineSlides.length;
    var current    = 0;
    var timer      = null;
    var progTimer  = null;
    var DURATION   = 5500;   // ms par slide
    var elapsed    = 0;
    var TICK       = 50;     // ms par tick barre

    var labels = [
        'Bénin, Afrique de l\'Ouest',
        'Découvrez nos sites'
    ];

    function startProgress() {
        elapsed = 0;
        clearInterval(progTimer);
        progTimer = setInterval(function() {
            elapsed += TICK;
            var pct = Math.min((elapsed / DURATION) * 100, 100);
            if (cineBar) cineBar.style.width = pct + '%';
        }, TICK);
    }

    function cineActivate(n) {
        cineSlides[current].classList.remove('active');
        cineDots[current].classList.remove('active');

        current = ((n % total) + total) % total;

        cineSlides[current].classList.add('active');
        cineDots[current].classList.add('active');
        if (cineNum)   cineNum.textContent   = String(current + 1).padStart(2, '0');
        if (cineLabel) cineLabel.textContent = labels[current] || 'Bénin';

        startProgress();
    }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(function() { cineActivate(current + 1); }, DURATION);
    }

    window.cineGo   = function(n) { clearInterval(timer); cineActivate(n); startTimer(); };
    window.cineNext = function()  { clearInterval(timer); cineActivate(current + 1); startTimer(); };
    window.cinePrev = function()  { clearInterval(timer); cineActivate(current - 1); startTimer(); };

    startProgress();
    startTimer();
})();
</script>

<?php require_once 'includes/footer.php'; ?>
