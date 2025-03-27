<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isLoggedIn() || !isAdmin()) {
    setAlert('danger', 'Accès non autorisé.');
    redirect('../index.php');
}

// Statistiques générales
$stats = [
    'users' => fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'services' => fetchOne("SELECT COUNT(*) as count FROM services")['count'],
    'reservations' => fetchOne("SELECT COUNT(*) as count FROM reservations")['count'],
    'pending_reservations' => fetchOne("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'")['count'],
    'reviews' => fetchOne("SELECT COUNT(*) as count FROM reviews")['count'],
    'pending_reviews' => fetchOne("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'")['count']
];

// Récupérer les dernières réservations
$latestReservations = fetchAll("
    SELECT r.*, u.name as user_name, s.name as service_name 
    FROM reservations r 
    JOIN users u ON r.user_id = u.id 
    JOIN services s ON r.service_id = s.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
");

// Récupérer les derniers utilisateurs inscrits
$latestUsers = fetchAll("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Récupérer les derniers avis
$latestReviews = fetchAll("
    SELECT r.*, u.name as user_name, s.name as service_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN services s ON r.service_id = s.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Administration - QuickReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tableau de bord</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Exporter</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Imprimer</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="bi bi-calendar"></i> Cette semaine
                        </button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Utilisateurs</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['users']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Services</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['services']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-shop fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Réservations</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['reservations']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Avis</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['reviews']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-star fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Réservations récentes</h6>
                                <a href="reservations.php" class="btn btn-sm btn-primary">Voir tout</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Utilisateur</th>
                                                <th>Service</th>
                                                <th>Date</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latestReservations as $reservation): ?>
                                                <tr>
                                                    <td><?php echo $reservation['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($reservation['user_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($reservation['service_name']); ?></td>
                                                    <td><?php echo formatDate($reservation['reservation_date']); ?></td>
                                                    <td>
                                                        <?php if ($reservation['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning">En attente</span>
                                                        <?php elseif ($reservation['status'] === 'confirmed'): ?>
                                                            <span class="badge bg-success">Confirmée</span>
                                                        <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                                            <span class="badge bg-danger">Annulée</span>
                                                        <?php elseif ($reservation['status'] === 'completed'): ?>
                                                            <span class="badge bg-info">Terminée</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="reservation-details.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($latestReservations)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">Aucune réservation trouvée.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Nouveaux utilisateurs</h6>
                                <a href="users.php" class="btn btn-sm btn-primary">Voir tout</a>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php foreach ($latestUsers as $user): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill"><?php echo ucfirst($user['role']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($latestUsers)): ?>
                                        <li class="list-group-item text-center">Aucun utilisateur trouvé.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Derniers avis</h6>
                                <a href="reviews.php" class="btn btn-sm btn-primary">Voir tout</a>
                            </div>
                            <div class="card-body">
                                <?php foreach ($latestReviews as $review): ?>
                                    <div class="mb-3 pb-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
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
                                        <div class="mb-1">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($review['service_name']); ?> - 
                                                <?php echo formatDate($review['created_at'], 'd/m/Y'); ?>
                                            </small>
                                        </div>
                                        <p class="mb-0"><?php echo htmlspecialchars(substr($review['comment'], 0, 100)) . (strlen($review['comment']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($latestReviews)): ?>
                                    <p class="text-center">Aucun avis trouvé.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>

