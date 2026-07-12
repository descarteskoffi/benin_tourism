<?php
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/database.php';

// Vérification de la session admin
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php') {
    if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration | Bénin Tourisme</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --admin-primary: #127C54;
            --admin-dark: #0B3B2C;
            --admin-accent: #E5A93B;
            --sidebar-width: 260px;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f4f6f9;
        }
        .admin-navbar {
            background-color: var(--admin-primary);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-sidebar {
            width: var(--sidebar-width);
            background-color: #1e282c;
            color: #b8c7ce;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 60px;
            z-index: 100;
            transition: all 0.3s;
        }
        .admin-sidebar .nav-link {
            color: #b8c7ce;
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .admin-sidebar .nav-link:hover, 
        .admin-sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.05);
            border-left-color: var(--admin-accent);
        }
        .admin-content {
            margin-left: var(--sidebar-width);
            padding: 40px 30px;
            min-height: calc(100vh - 56px);
        }
        .card-stat {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            color: #fff;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        @media (max-width: 991px) {
            .admin-sidebar {
                left: calc(-1 * var(--sidebar-width));
            }
            .admin-content {
                margin-left: 0;
            }
            .admin-sidebar.show {
                left: 0;
            }
        }
    </style>
</head>
<body>

    <?php if ($current_page !== 'login.php'): ?>
    <!-- Navbar supérieure -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar sticky-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light d-lg-none me-2" type="button" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-user-shield me-2"></i> Bénin Tourisme & Services <span class="badge bg-warning text-dark text-uppercase fs-6">ADMIN</span>
            </a>
            
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3 d-none d-md-inline">
                    Bonjour, <strong><?= e($_SESSION['admin_username'] ?? 'Administrateur') ?></strong>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fa-solid fa-power-off me-1"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar de Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="d-flex flex-column h-100 justify-content-between">
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fa-solid fa-chart-line me-2"></i> Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'lieux.php' ? 'active' : '' ?>" href="lieux.php">
                        <i class="fa-solid fa-map-location-dot me-2"></i> Gérer les Lieux
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'hebergements.php' ? 'active' : '' ?>" href="hebergements.php">
                        <i class="fa-solid fa-hotel me-2"></i> Hébergements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'guides.php' ? 'active' : '' ?>" href="guides.php">
                        <i class="fa-solid fa-car-side me-2"></i> Chauffeurs-Guides
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'demandes.php' ? 'active' : '' ?>" href="demandes.php">
                        <i class="fa-solid fa-clipboard-list me-2"></i> Réservations & Messages
                    </a>
                </li>
            </ul>
            
            <ul class="nav flex-column mb-4 border-top border-secondary pt-3">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php" target="_blank">
                        <i class="fa-solid fa-globe me-2"></i> Voir le site public
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <main class="admin-content">
    <?php endif; ?>
