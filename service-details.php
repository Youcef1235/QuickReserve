<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Récupérer l'ID du service
$serviceId = intval($_GET['id'] ?? 0);

if ($serviceId <= 0) {
    setAlert('danger', 'Service non trouvé.');
    redirect('services.php');
}

// Récupérer les détails du service
$sql = "SELECT s.*, u.name as provider_name, u.email as provider_email, u.phone as provider_phone 
        FROM services s 
        JOIN users u ON s.provider_id = u.id 
        WHERE s.id = :id AND s.status = 'active'";
$service = fetchOne($sql, ['id' => $serviceId]);

if (!$service) {
    setAlert('danger', 'Service non trouvé.');
    redirect('services.php');
}

// Récupérer les avis sur le service
$sql = "SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.service_id = :service_id 
        ORDER BY r.created_at DESC";
$reviews = fetchAll($sql, ['service_id' => $serviceId]);

// Calculer la note moyenne
$averageRating = 0;
if (!empty($reviews)) {
    $totalRating = 0;
    foreach ($reviews as $review) {
        $totalRating += $review['rating'];
    }
    $averageRating = $totalRating / count($reviews);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['name']); ?> - QuickReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <img src="<?php echo !empty($service['image']) ? $service['image'] : 'assets/img/default-service.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($service['name']); ?>">
                    <div class="card-body">
                        <h1 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h1>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= round($averageRating)): ?>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star text-warning"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="ms-1">(<?php echo count($reviews); ?> avis)</span>
                            </div>
                            <span class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($service['type'])); ?></span>
                        </div>
                        
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                        
                        <h5 class="mt-4">Informations</h5>
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item">
                                <i class="bi bi-geo-alt me-2"></i> <?php echo htmlspecialchars($service['address']); ?>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-clock me-2"></i> Horaires: <?php echo substr($service['opening_time'], 0, 5); ?> - <?php echo substr($service['closing_time'], 0, 5); ?>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-telephone me-2"></i> <?php echo htmlspecialchars($service['provider_phone']); ?>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-envelope me-2"></i> <?php echo htmlspecialchars($service['provider_email']); ?>
                            </li>
                        </ul>
                        
                        <?php if (isLoggedIn()): ?>
                            <a href="booking.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary btn-lg">Réserver maintenant</a>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <a href="login.php">Connectez-vous</a> ou <a href="register.php">inscrivez-vous</a> pour réserver ce service.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Avis (<?php echo count($reviews); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reviews)): ?>
                            <p>Aucun avis pour le moment.</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="mb-4 pb-4 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h5 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h5>
                                            <small class="text-muted"><?php echo formatDate($review['created_at'], 'd/m/Y'); ?></small>
                                        </div>
                                        <div>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-star text-warning"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if (isLoggedIn()): ?>
                            <h4 class="mt-4">Laisser un avis</h4>
                            <form action="add-review.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                
                                <div class="mb-3">
                                    <label for="rating" class="form-label">Note</label>
                                    <select class="form-select" id="rating" name="rating" required>
                                        <option value="">Sélectionnez une note</option>
                                        <option value="5">5 - Excellent</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="3">3 - Bien</option>
                                        <option value="2">2 - Moyen</option>
                                        <option value="1">1 - Mauvais</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="comment" class="form-label">Commentaire</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Soumettre l'avis</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Disponibilités</h3>
                    </div>
                    <div class="card-body">
                        <div id="calendar" class="mb-3"></div>
                        <div id="time-slots">
                            <p>Sélectionnez une date pour voir les créneaux disponibles.</p>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <a href="booking.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary w-100">Réserver</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Localisation</h3>
                    </div>
                    <div class="card-body">
                        <div id="map" style="height: 300px; background-color: #eee;" class="mb-3">
                            <!-- Intégration de carte ici -->
                            <img src="assets/img/map-placeholder.jpg" alt="Carte" class="img-fluid">
                        </div>
                        <address>
                            <i class="bi bi-geo-alt me-2"></i> <?php echo htmlspecialchars($service['address']); ?>
                        </address>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.css" rel="stylesheet">
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                dateClick: function(info) {
                    // Charger les créneaux disponibles pour cette date
                    loadTimeSlots(info.dateStr);
                }
            });
            calendar.render();
            
            function loadTimeSlots(date) {
                // Simuler le chargement des créneaux (à remplacer par un appel AJAX)
                var timeSlotsContainer = document.getElementById('time-slots');
                timeSlotsContainer.innerHTML = '<p>Chargement des créneaux...</p>';
                
                setTimeout(function() {
                    var html = '<h5>Créneaux disponibles pour le ' + formatDate(date) + '</h5>';
                    html += '<div class="list-group">';
                    html += '<a href="booking.php?service_id=<?php echo $service['id']; ?>&date=' + date + '&time=09:00" class="list-group-item list-group-item-action">09:00 - 09:30</a>';
                    html += '<a href="booking.php?service_id=<?php echo $service['id']; ?>&date=' + date + '&time=09:30" class="list-group-item list-group-item-action">09:30 - 10:00</a>';
                    html += '<a href="booking.php?service_id=<?php echo $service['id']; ?>&date=' + date + '&time=10:00" class="list-group-item list-group-item-action">10:00 - 10:30</a>';
                    html += '<a href="booking.php?service_id=<?php echo $service['id']; ?>&date=' + date + '&time=10:30" class="list-group-item list-group-item-action">10:30 - 11:00</a>';
                    html += '</div>';
                    timeSlotsContainer.innerHTML = html;
                }, 500);
            }
            
            function formatDate(dateStr) {
                var date = new Date(dateStr);
                return date.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        });
    </script>
</body>
</html>

