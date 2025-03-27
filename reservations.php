<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les réservations de l'utilisateur
$sql = "SELECT r.*, s.name as service_name, s.type as service_type, u.name as provider_name 
        FROM reservations r 
        JOIN services s ON r.service_id = s.id 
        JOIN users u ON s.provider_id = u.id 
        WHERE r.user_id = :user_id 
        ORDER BY r.reservation_date DESC, r.start_time DESC";
$reservations = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);

// Filtrer les réservations par statut
$status = cleanInput($_GET['status'] ?? '');
$filteredReservations = $reservations;

if (!empty($status)) {
    $filteredReservations = array_filter($reservations, function($reservation) use ($status) {
        return $reservation['status'] === $status;
    });
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations - QuickReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Mes réservations</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="btn-group" role="group" aria-label="Filtrer par statut">
                    <a href="reservations.php" class="btn btn-outline-primary <?php echo empty($status) ? 'active' : ''; ?>">Toutes</a>
                    <a href="reservations.php?status=pending" class="btn btn-outline-primary <?php echo $status === 'pending' ? 'active' : ''; ?>">En attente</a>
                    <a href="reservations.php?status=confirmed" class="btn btn-outline-primary <?php echo $status === 'confirmed' ? 'active' : ''; ?>">Confirmées</a>
                    <a href="reservations.php?status=cancelled" class="btn btn-outline-primary <?php echo $status === 'cancelled' ? 'active' : ''; ?>">Annulées</a>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="services.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i> Nouvelle réservation
                </a>
            </div>
        </div>
        
        <?php if (empty($filteredReservations)): ?>
            <div class="alert alert-info">
                Aucune réservation trouvée.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($filteredReservations as $reservation): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($reservation['service_name']); ?></h5>
                                <?php if ($reservation['status'] === 'pending'): ?>
                                    <span class="badge bg-warning">En attente</span>
                                <?php elseif ($reservation['status'] === 'confirmed'): ?>
                                    <span class="badge bg-success">Confirmée</span>
                                <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                    <span class="badge bg-danger">Annulée</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush mb-3">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Réservation #:</span>
                                        <strong><?php echo $reservation['id']; ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Type:</span>
                                        <strong><?php echo ucfirst(htmlspecialchars($reservation['service_type'])); ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Prestataire:</span>
                                        <strong><?php echo htmlspecialchars($reservation['provider_name']); ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Date:</span>
                                        <strong><?php echo formatDate($reservation['reservation_date'], 'd/m/Y'); ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Heure:</span>
                                        <strong><?php echo substr($reservation['start_time'], 0, 5); ?> - <?php echo substr($reservation['end_time'], 0, 5); ?></strong>
                                    </li>
                                </ul>
                                
                                <?php if (!empty($reservation['notes'])): ?>
                                    <div class="mb-3">
                                        <h6>Notes:</h6>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($reservation['notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="reservation-details.php?id=<?php echo $reservation['id']; ?>" class="btn btn-outline-primary">Détails</a>
                                    
                                    <?php if ($reservation['status'] === 'pending' || $reservation['status'] === 'confirmed'): ?>
                                        <?php
                                        // Vérifier si la réservation peut être annulée (24h à l'avance)
                                        $reservationDateTime = strtotime($reservation['reservation_date'] . ' ' . $reservation['start_time']);
                                        $canCancel = $reservationDateTime > (time() + 24 * 60 * 60);
                                        ?>
                                        
                                        <?php if ($canCancel): ?>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $reservation['id']; ?>">
                                                Annuler
                                            </button>
                                            
                                            <!-- Modal de confirmation d'annulation -->
                                            <div class="modal fade" id="cancelModal<?php echo $reservation['id']; ?>" tabindex="-1" aria-labelledby="cancelModalLabel<?php echo $reservation['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="cancelModalLabel<?php echo $reservation['id']; ?>">Confirmer l'annulation</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Êtes-vous sûr de vouloir annuler cette réservation ?</p>
                                                            <p><strong>Service:</strong> <?php echo htmlspecialchars($reservation['service_name']); ?></p>
                                                            <p><strong>Date:</strong> <?php echo formatDate($reservation['reservation_date'], 'd/m/Y'); ?></p>
                                                            <p><strong>Heure:</strong> <?php echo substr($reservation['start_time'], 0, 5); ?> - <?php echo substr($reservation['end_time'], 0, 5); ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                            <a href="cancel-reservation.php?id=<?php echo $reservation['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" class="btn btn-danger">Confirmer l'annulation</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-danger" disabled title="L'annulation doit être effectuée au moins 24h à l'avance">
                                                Annuler
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>

