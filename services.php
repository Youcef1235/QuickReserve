<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Récupérer le type de service demandé
$type = cleanInput($_GET['type'] ?? '');

// Récupérer les services
$params = [];
$sql = "SELECT s.*, u.name as provider_name, u.email as provider_email 
        FROM services s 
        JOIN users u ON s.provider_id = u.id 
        WHERE s.status = 'active'";

if (!empty($type)) {
    $sql .= " AND s.type = :type";
    $params['type'] = $type;
}

$sql .= " ORDER BY s.name ASC";
$services = fetchAll($sql, $params);

// Titre de la page en fonction du type
$pageTitle = "Tous les services";
if (!empty($type)) {
    switch ($type) {
        case 'coiffeur':
            $pageTitle = "Coiffeurs";
            break;
        case 'restaurant':
            $pageTitle = "Restaurants";
            break;
        case 'salle':
            $pageTitle = "Salles";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - QuickReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4"><?php echo $pageTitle; ?></h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <form action="" method="GET" class="d-flex">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    <input type="text" name="search" class="form-control me-2" placeholder="Rechercher...">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-md-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Filtrer par
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                            <li><a class="dropdown-item" href="services.php">Tous les services</a></li>
                            <li><a class="dropdown-item" href="services.php?type=coiffeur">Coiffeurs</a></li>
                            <li><a class="dropdown-item" href="services.php?type=restaurant">Restaurants</a></li>
                            <li><a class="dropdown-item" href="services.php?type=salle">Salles</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($services)): ?>
            <div class="alert alert-info">
                Aucun service disponible pour le moment.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($services as $service): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo !empty($service['image']) ? $service['image'] : 'assets/img/default-service.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($service['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($service['description'], 0, 100)) . '...'; ?></p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($service['address']); ?>
                                    </small>
                                </p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i> <?php echo substr($service['opening_time'], 0, 5); ?> - <?php echo substr($service['closing_time'], 0, 5); ?>
                                    </small>
                                </p>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="service-details.php?id=<?php echo $service['id']; ?>" class="btn btn-primary">Voir détails</a>
                                <?php if (isLoggedIn()): ?>
                                    <a href="booking.php?service_id=<?php echo $service['id']; ?>" class="btn btn-outline-primary">Réserver</a>
                                <?php endif; ?>
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

