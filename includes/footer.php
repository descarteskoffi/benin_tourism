<?php
require_once __DIR__ . '/fonctions.php';
?>
    <!-- Pied de Page -->
    <footer class="footer mt-auto py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Info Section -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="footer-title">BÉNIN TOURISME & SERVICES</h5>
                    <p class="footer-text mt-3">
                        <?= __('footer_text') ?>
                    </p>
                    <div class="social-icons mt-3">
                        <a href="#" class="me-2 text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-2 text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-2 text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="me-2 text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-3 col-md-6 offset-lg-1">
                    <h5 class="footer-title"><?= __('nav_home') ?></h5>
                    <ul class="list-unstyled footer-links mt-3">
                        <li><a href="index.php"><i class="fas fa-chevron-right me-2 text-accent"></i><?= __('nav_home') ?></a></li>
                        <li><a href="lieux.php"><i class="fas fa-chevron-right me-2 text-accent"></i><?= __('nav_places') ?></a></li>
                        <li><a href="hebergements.php"><i class="fas fa-chevron-right me-2 text-accent"></i><?= __('nav_hotels') ?></a></li>
                        <li><a href="guides.php"><i class="fas fa-chevron-right me-2 text-accent"></i><?= __('nav_guides') ?></a></li>
                        <li><a href="contact.php"><i class="fas fa-chevron-right me-2 text-accent"></i><?= __('nav_contact') ?></a></li>
                    </ul>
                </div>
                
                <!-- Contact Details -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="footer-title">Contact</h5>
                    <ul class="list-unstyled footer-contact mt-3 text-white">
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-accent"></i>
                            Avenue Jean-Paul II, Cotonou, Bénin
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2 text-accent"></i>
                            +229 21 30 00 01 / +229 97 00 00 01
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2 text-accent"></i>
                            contact@benintourisme.bj
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="footer-divider my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start text-white">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= __('site_title') ?>. <?= __('copyright') ?></p>
                </div>
                <div class="col-md-6 text-center text-md-end text-white-50 mt-2 mt-md-0">
                    <small>Version 2.0 (PHP/MySQL) · Design Premium</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
