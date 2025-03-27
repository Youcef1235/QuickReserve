<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer l'ID de la réservation
$reservationId = intval($_GET['id'] ?? 0);

if ($reservationId <= 0) {
    setAlert('danger', 'Réservation non trouvée.');
    redirect('reservations.php');
}

// Récupérer les détails de la réservation
$sql = "SELECT r.*, s.name as service_name, s.type as service_type, u.name as provider_name 
        FROM reservations r 
        JOIN services s ON r.service_id = s.id 
        JOIN users u ON s.provider_id = u.id 
        WHERE r.id = :id AND r.user_id = :user_id";
$reservation = fetchOne($sql, [
    'id' => $reservationId,
    'user_id' => $_SESSION['user_id']
]);

if (!$reservation) {
    setAlert('danger', 'Réservation non trouvée.');
    redirect('reservations.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de réservation - QuickReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h2 class="mb-0"><i class="bi bi-check-circle me-2"></i> Réservation confirmée</h2>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-calendar-check text-success" style="font-size: 5rem;"></i>
                            <h3 class="mt-3">Merci pour votre réservation !</h3>
                            <p class="lead">Votre réservation a été enregistrée avec succès.</p>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">Détails de la réservation</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Numéro de réservation:</span>
                                        <strong>#<?php echo $reservationId; ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Service:</span>
                                        <strong><?php echo htmlspecialchars($reservation['service_name']); ?></strong>
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
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Statut:</span>
                                        <strong>
                                            <?php if ($reservation['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">En attente</span>
                                            <?php elseif ($reservation['status'] === 'confirmed'): ?>
                                                <span class="badge bg-success">Confirmée</span>
                                            <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                                <span class="badge bg-danger">Annulée</span>
                                            <?php endif; ?>
                                        </strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle me-2"></i> Informations importantes</h5>
                            <p>Votre réservation est actuellement <strong>en attente de confirmation</strong> par le prestataire. Vous recevrez une notification par email dès que votre réservation sera confirmée.</p>
                            <p>Si vous souhaitez annuler ou modifier votre réservation, veuillez le faire au moins 24 heures à l'avance.</p>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="reservations.php" class="btn btn-outline-primary">Mes réservations</a>
                            <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>

