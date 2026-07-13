<?php
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// SEO Metas
$page_title = __('nav_hotels');
$page_desc = __('hotels_subtitle');

// Récupération des hébergements avec Caching
$cache_key = "hebergements_list_" . $lang;
$hebergements = get_cache($cache_key, 300); // 5 minutes cache
if ($hebergements === null) {
    try {
        $stmt = $pdo->query("SELECT id, nom, type_fr, type_en, localite, quartier, prix_nuit, devise, description_fr, description_en, photo, email_contact FROM hebergements ORDER BY nom ASC");
        $hebergements = $stmt->fetchAll();
        set_cache($cache_key, $hebergements);
    } catch (PDOException $e) {
        $hebergements = [];
    }
}

// Variables pour les modals
$booking_success = isset($_SESSION['hotel_booking_success']) ? $_SESSION['hotel_booking_success'] : false;
$booking_email = isset($_SESSION['hotel_booking_email']) ? $_SESSION['hotel_booking_email'] : '';
$booking_hotel_name = isset($_SESSION['hotel_booking_name']) ? $_SESSION['hotel_booking_name'] : '';
$booking_error = isset($_SESSION['booking_status']) && $_SESSION['booking_status'] === 'error';
$booking_error_msg = isset($_SESSION['booking_message']) ? $_SESSION['booking_message'] : '';

// Nettoyage de la session après lecture
unset($_SESSION['booking_status'], $_SESSION['booking_message']);
unset($_SESSION['hotel_booking_success'], $_SESSION['hotel_booking_email'], $_SESSION['hotel_booking_name']);

require_once 'includes/header.php';
?>

<!-- En-tête -->
<section class="lieu-detail-header text-center">
    <div class="container">
        <h1 class="lieu-title"><?= __('hotels_title') ?></h1>
        <p class="lead text-muted col-md-8 mx-auto"><?= __('hotels_subtitle') ?></p>
    </div>
</section>

<!-- Section Hébergements et Formulaire -->
<section class="section-padding">
    <div class="container">

        <div class="row justify-content-center">
            <!-- Liste des Hébergements centrée et plus large -->
            <div class="col-lg-9">
                <div class="row g-4">
                    <?php if (!empty($hebergements)): ?>
                        <?php foreach ($hebergements as $hotel): ?>
                            <div class="col-12">
                                <div class="card custom-card flex-md-row">
                                    <div class="card-img-wrapper col-md-5" style="padding-top: 0; min-height: 220px;">
                                        <img src="<?= get_image_url($hotel['photo'], ($lang === 'en' ? $hotel['type_en'] : $hotel['type_fr'])) ?>" alt="<?= e($hotel['nom']) ?>" class="h-100 w-100 object-fit-cover" loading="lazy">
                                    </div>
                                    <div class="card-body-content col-md-7 d-flex flex-column justify-content-between p-4">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span class="badge bg-success mb-2"><?= e($lang === 'en' ? $hotel['type_en'] : $hotel['type_fr']) ?></span>
                                                <span class="text-danger fw-bold fs-5">
                                                    <?= number_format($hotel['prix_nuit'], 0, ',', ' ') ?> <?= e($hotel['devise']) ?><small class="text-muted fw-normal fs-6"> <?= __('per_night') ?></small>
                                                </span>
                                            </div>
                                            <h3 class="h4"><?= e($hotel['nom']) ?></h3>
                                            <p class="card-location text-muted mt-1">
                                                <i class="fa-solid fa-location-dot me-1"></i>
                                                <?= e($hotel['localite']) ?><?= $hotel['quartier'] ? ' (' . e($hotel['quartier']) . ')' : '' ?>
                                            </p>
                                            <p class="text-secondary small mt-2 mb-3">
                                                <?= e(db_trans($hotel, 'description')) ?>
                                            </p>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-custom btn-primary-custom w-100 py-3" onclick="selectHotel(<?= $hotel['id'] ?>, '<?= e(addslashes($hotel['nom'])) ?>', <?= $hotel['prix_nuit'] ?>)">
                                                <i class="fa-regular fa-calendar-check me-2"></i><?= __('book_now') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">Aucun hébergement trouvé dans la base de données.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de Réservation (Popup) -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bookingModalLabel"><i class="fa-solid fa-calendar-days me-2"></i>Réserver à : <span id="modal_hotel_title"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="traitement/reservation.php">
                <div class="modal-body p-4">
                    <input type="hidden" name="hebergement_id" id="hebergement_select">
                    <input type="hidden" id="modal_hotel_price">
                    
                    <!-- Nom client -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom Complet *</label>
                        <input type="text" name="nom_client" class="form-control" placeholder="Ex: Koffi Maxime" required>
                    </div>

                    <!-- Email & Téléphone -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Adresse Email *</label>
                            <input type="email" name="email_client" class="form-control" placeholder="nom@exemple.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="tel" name="telephone_client" class="form-control" placeholder="Ex: +229 97 00 00 00">
                        </div>
                    </div>

                    <!-- Type de chambre -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Type de chambre *</label>
                        <select name="type_chambre" id="type_chambre_select" class="form-select" onchange="calculatePrice()" required>
                            <option value="standard" data-mult="1.0">Chambre Standard (Tarif de base)</option>
                            <option value="confort" data-mult="1.5">Chambre Confort (+50%)</option>
                            <option value="suite" data-mult="2.2">Suite Privée (+120%)</option>
                        </select>
                    </div>

                    <!-- Dates d'Arrivée & Départ -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Date d'arrivée *</label>
                            <input type="date" name="date_arrivee" id="date_arrivee" class="form-control" onchange="calculatePrice()" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Date de départ *</label>
                            <input type="date" name="date_depart" id="date_depart" class="form-control" onchange="calculatePrice()" required>
                        </div>
                    </div>

                    <!-- Nb Personnes -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de personnes</label>
                        <input type="number" name="nb_personnes" class="form-control" value="1" min="1" max="10">
                    </div>

                    <!-- Message -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Demandes particulières (Optionnel)</label>
                        <textarea name="message" class="form-control" rows="2" placeholder="Précisez vos besoins particuliers (chambre double, lit bébé, etc.)"></textarea>
                    </div>

                    <!-- Bloc de Calcul Dynamique -->
                    <div class="p-3 bg-light rounded border border-success-subtle mt-4">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Nombre de nuits :</span>
                            <span><strong id="calc_nuits">1</strong> nuit(s)</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Prix de la nuitée (chambre choisie) :</span>
                            <span><strong id="calc_prix_unitaire">0</strong> FCFA</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total estimé :</span>
                            <span class="fs-5 fw-bold text-success"><span id="calc_total">0</span> FCFA</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-paper-plane me-1"></i>Confirmer ma demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts de sélection et calcul dynamique -->
<script>
function selectHotel(id, name, prixNuit) {
    document.getElementById('hebergement_select').value = id;
    document.getElementById('modal_hotel_title').innerText = name;
    document.getElementById('modal_hotel_price').value = prixNuit;
    
    // Reset type de chambre à standard
    document.getElementById('type_chambre_select').value = 'standard';
    
    // Configurer la date min de départ
    const today = new Date().toISOString().split('T')[0];
    const arrivalInput = document.getElementById('date_arrivee');
    const departureInput = document.getElementById('date_depart');
    
    if (arrivalInput && departureInput) {
        arrivalInput.min = today;
        arrivalInput.addEventListener('change', function() {
            departureInput.min = this.value;
        });
    }

    calculatePrice();
    
    // Ouvrir le modal Bootstrap
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    bookingModal.show();
}

function calculatePrice() {
    const basePrice = parseFloat(document.getElementById('modal_hotel_price').value) || 0;
    const roomSelect = document.getElementById('type_chambre_select');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const multiplier = parseFloat(selectedOption.getAttribute('data-mult')) || 1.0;
    
    const pricePerNight = basePrice * multiplier;
    
    const arrivalDate = document.getElementById('date_arrivee').value;
    const departureDate = document.getElementById('date_depart').value;
    
    let nights = 0;
    if (arrivalDate && departureDate) {
        const d1 = new Date(arrivalDate);
        const d2 = new Date(departureDate);
        const diffTime = Math.abs(d2 - d1);
        nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }
    
    if (nights <= 0) nights = 1; // min 1 nuit pour le calcul du tarif
    
    const total = pricePerNight * nights;
    
    document.getElementById('calc_nuits').innerText = nights;
    document.getElementById('calc_prix_unitaire').innerText = new Intl.NumberFormat().format(pricePerNight);
    document.getElementById('calc_total').innerText = new Intl.NumberFormat().format(total);
}
</script>

<!-- Modal de confirmation de réservation hébergement -->
<?php if ($booking_success): ?>
<div class="modal fade" id="bookingSuccessModal" tabindex="-1" aria-labelledby="bookingSuccessLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="bg-success text-white text-center py-5 px-4">
                <div style="font-size: 4rem;">✅</div>
                <h4 class="fw-bold mt-3 mb-1">Demande enregistrée !</h4>
                <p class="mb-0 opacity-75">Votre réservation pour <?php echo e($booking_hotel_name); ?> a été prise en compte.</p>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fs-6 mb-3">
                    Un e-mail de confirmation a été envoyé à :
                </p>
                <div class="alert alert-success fw-bold fs-5 mb-3">
                    <i class="fa-solid fa-envelope me-2"></i><?php echo e($booking_email); ?>
                </div>
                <p class="text-muted small mb-4">
                    Notre équipe validera la disponibilité et vous recontactera sous 24 heures avec un lien de paiement sécurisé pour confirmer votre réservation.
                </p>
                <button type="button" class="btn btn-success btn-lg px-5" data-bs-dismiss="modal">
                    <i class="fa-solid fa-check me-1"></i> Parfait !
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('bookingSuccessModal'));
    modal.show();
});
</script>
<?php endif; ?>

<!-- Modal d'erreur -->
<?php if ($booking_error): ?>
<div class="modal fade" id="bookingErrorModal" tabindex="-1" aria-labelledby="bookingErrorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="bg-danger text-white text-center py-5 px-4">
                <div style="font-size: 4rem;">❌</div>
                <h4 class="fw-bold mt-3 mb-1">Erreur !</h4>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fs-6 mb-4">
                    <?php echo e($booking_error_msg ?: __('booking_error')); ?>
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
    const modal = new bootstrap.Modal(document.getElementById('bookingErrorModal'));
    modal.show();
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
