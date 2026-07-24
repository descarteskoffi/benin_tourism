<?php
require_once __DIR__ . '/fonctions.php';
// Variables SEO par défaut si non définies sur la page
$page_title = isset($page_title) ? $page_title . ' | ' . __('site_title') : __('site_title');
$page_desc = isset($page_desc) ? $page_desc : __('hero_subtitle');
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($page_desc) ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- ============================================================
         STYLES NAVBAR — distincte de la Hero Section
         ============================================================ -->
    <style>
    .navbar {
        background-color: #0B3B2C; /* vert foncé, différent du #1a5c45 de la hero */
        box-shadow: 0 2px 12px rgba(0,0,0,0.25); /* légère ombre pour marquer la limite */
        padding-top: 14px;
        padding-bottom: 14px;
        z-index: 10;
        position: relative;
    }

    .navbar-brand .brand-text {
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .navbar-brand .text-accent {
        color: #E5A93B;
    }

    .navbar-brand .brand-icon {
        color: #E5A93B;
        margin-right: 8px;
    }

    .navbar .nav-link {
        color: rgba(255,255,255,0.85);
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .navbar .nav-link:hover,
    .navbar .nav-link.active {
        color: #E5A93B;
    }

    .navbar .btn-admin {
        border-color: rgba(255,255,255,0.4);
        color: #fff;
        font-size: 0.85rem;
    }

    .navbar .btn-admin:hover {
        background: #E5A93B;
        border-color: #E5A93B;
        color: #0B3B2C;
    }

    .navbar .lang-selector .nav-link {
        color: #fff;
    }
    </style>
</head>
<body>

    <!-- Barre de Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="brand-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                <span class="brand-text">BÉNIN <span class="text-accent">TOURISME</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php"><?= __('nav_home') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'lieux.php' || basename($_SERVER['PHP_SELF']) == 'lieu.php' ? 'active' : '' ?>" href="lieux.php"><?= __('nav_places') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'hebergements.php' ? 'active' : '' ?>" href="hebergements.php"><?= __('nav_hotels') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'guides.php' ? 'active' : '' ?>" href="guides.php"><?= __('nav_guides') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>" href="contact.php"><?= __('nav_contact') ?></a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-light btn-admin" href="admin/index.php">
                            <i class="fa-solid fa-user-shield me-1"></i> <?= __('nav_admin') ?>
                        </a>
                    </li>
                    <!-- Sélecteur de Langue -->
                    <li class="nav-item ms-lg-3 dropdown lang-selector">
                        <a class="nav-link dropdown-toggle text-uppercase btn btn-sm btn-dark text-white px-2 py-1" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-globe me-1"></i> <?= e($lang) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item <?= $lang === 'fr' ? 'active' : '' ?>" href="?lang=fr">
                                    <span class="flag-icon me-1">🇫🇷</span> <?= __('lang_switch_fr') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= $lang === 'en' ? 'active' : '' ?>" href="?lang=en">
                                    <span class="flag-icon me-1">🇬🇧</span> <?= __('lang_switch_en') ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</body>
</html>