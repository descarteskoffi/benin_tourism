<?php
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// SEO Metas 
$page_title = __('nav_contact');
$page_desc = __('contact_subtitle');

// Variables pour les modals
$contact_success = isset($_SESSION['contact_success']) ? $_SESSION['contact_success'] : false;
$contact_email = isset($_SESSION['contact_email']) ? $_SESSION['contact_email'] : '';
$contact_error = isset($_SESSION['contact_status']) && $_SESSION['contact_status'] === 'error';
$contact_error_msg = isset($_SESSION['contact_message']) ? $_SESSION['contact_message'] : '';

// Nettoyage session
unset($_SESSION['contact_status'], $_SESSION['contact_message']);
unset($_SESSION['contact_success'], $_SESSION['contact_email']);

require_once 'includes/header.php';
?>

<!-- En-tête -->
<section class="lieu-detail-header text-center">
    <div class="container">
        <h1 class="lieu-title"><?= __('nav_contact') ?></h1>
        <p class="lead text-muted col-md-8 mx-auto"><?= __('contact_subtitle') ?></p>
    </div>
</section>

<!-- Section Présentation & Formulaire -->
<section class="section-padding">
    <div class="container">
   <!-- div -->
        <div class="row g-5 align-items-stretch">
            <!-- Présentation "À propos" (Colonne Gauche) -->
            <div class="col-lg-6 d-flex flex-column justify-content-between">
                <div>
                    <h2 class="mb-4 text-success"><i class="fa-solid fa-circle-info me-2"></i><?= __('about_title') ?></h2>
                    <p class="fs-5 text-secondary lh-lg mb-4">
                        <?= __('about_text') ?>
                    </p>
                    <p class="text-muted">
                        Que vous soyez un touriste international préparant votre premier safari au Parc de la Pendjari, un membre de la diaspora béninoise souhaitant redécouvrir le patrimoine des Palais Royaux d'Abomey, ou un voyageur d'affaires de passage à Cotonou, nous avons le service adapté à vos besoins.
                    </p>
                </div>
                
                <!-- Infos Directes -->
                <div class="mt-4 p-4 rounded bg-light border-start border-4 border-success">
                    <h4 class="h5 mb-3 text-success"><i class="fa-solid fa-headset me-2"></i>Assistance 24/7</h4>
                    <p class="mb-2"><strong>Téléphone :</strong> +229 21 30 00 01</p>
                    <p class="mb-2"><strong>WhatsApp :</strong> +229 97 00 00 01</p>
                    <p class="mb-0"><strong>E-mail :</strong> support@benintourisme.bj</p>
                </div>
            </div>

            <!-- Formulaire de Contact (Colonne Droite) -->
            <div class="col-lg-6">
                <div class="form-card h-100">
                    <h3 class="mb-4 text-success border-bottom pb-2">
                        <i class="fa-solid fa-envelope-open-text me-2"></i><?= __('contact_title') ?>
                    </h3>
                    
                    <form method="POST" action="traitement/contact.php">
                        <!-- Nom Complet -->
                        <div class="mb-3">
                            <label class="form-label fw-bold"><?= __('client_name') ?> *</label>
                            <input type="text" name="nom" class="form-control" placeholder="Votre nom et prénom" required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label fw-bold"><?= __('client_email') ?> *</label>
                            <input type="email" name="email" class="form-control" placeholder="votre.email@exemple.com" required>
                        </div>

                        <!-- Sujet -->
                        <div class="mb-3">
                            <label class="form-label fw-bold"><?= __('contact_subject') ?> *</label>
                            <input type="text" name="sujet" class="form-control" placeholder="Le motif de votre message" required>
                        </div>

                        <!-- Message -->
                        <div class="mb-4">
                            <label class="form-label fw-bold"><?= __('contact_message') ?> *</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Saisissez votre message ici..." required></textarea>
                        </div>

                        <!-- Submit -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-custom btn-accent-custom py-3 fs-6">
                                <i class="fa-solid fa-paper-plane me-2"></i><?= __('btn_send_message') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de confirmation de message contact -->
<?php if ($contact_success): ?>
<div class="modal fade" id="contactSuccessModal" tabindex="-1" aria-labelledby="contactSuccessLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="bg-success text-white text-center py-5 px-4">
                <div style="font-size: 4rem;">✅</div>
                <h4 class="fw-bold mt-3 mb-1">Message envoyé !</h4>
                <p class="mb-0 opacity-75">Nous avons bien reçu votre demande.</p>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fs-6 mb-3">
                    Un accusé de réception a été envoyé à :
                </p>
                <div class="alert alert-success fw-bold fs-5 mb-3">
                    <i class="fa-solid fa-envelope me-2"></i><?php echo e($contact_email); ?>
                </div>
                <p class="text-muted small mb-4">
                    Notre équipe vous répondra sous 24 heures pour vous aider.
                </p>
                <button type="button" class="btn btn-success btn-lg px-5" data-bs-dismiss="modal">
                    <i class="fa-solid fa-check me-1"></i> Merci !
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('contactSuccessModal'));
    modal.show();
});
</script>
<?php endif; ?>

<!-- Modal d'erreur -->
<?php if ($contact_error): ?>
<div class="modal fade" id="contactErrorModal" tabindex="-1" aria-labelledby="contactErrorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="bg-danger text-white text-center py-5 px-4">
                <div style="font-size: 4rem;">❌</div>
                <h4 class="fw-bold mt-3 mb-1">Erreur !</h4>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fs-6 mb-4">
                    <?php echo e($contact_error_msg ?: __('booking_error')); ?>
                </p>
                <button type="button" class="btn btn-danger btn-lg px-5" data-bs-dismiss="modal">
                    <i class="fa-solid fa-redo me-1"></i> Réessayer
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('contactErrorModal'));
    modal.show();
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
