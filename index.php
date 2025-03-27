<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickReserve - Système de Réservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 text-center mb-5">
                <h1>Bienvenue sur QuickReserve</h1>
                <p class="lead">Réservez facilement vos services préférés en quelques clics</p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="card h-100 service-card">
                    <div class="card-body text-center">
                        <i class="bi bi-scissors fs-1 mb-3 text-primary"></i>
                        <h3 class="card-title">Coiffeurs</h3>
                        <p class="card-text">Réservez votre prochain rendez-vous chez le coiffeur sans attendre.</p>
                        <a href="services.php?type=coiffeur" class="btn btn-primary">Réserver</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 service-card">
                    <div class="card-body text-center">
                        <i class="bi bi-cup-hot fs-1 mb-3 text-primary"></i>
                        <h3 class="card-title">Restaurants</h3>
                        <p class="card-text">Trouvez une table dans vos restaurants préférés en quelques secondes.</p>
                        <a href="services.php?type=restaurant" class="btn btn-primary">Réserver</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 service-card">
                    <div class="card-body text-center">
                        <i class="bi bi-building fs-1 mb-3 text-primary"></i>
                        <h3 class="card-title">Salles</h3>
                        <p class="card-text">Réservez des salles pour vos événements professionnels ou personnels.</p>
                        <a href="services.php?type=salle" class="btn btn-primary">Réserver</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Comment ça marche</h3>
                    </div>
                    <div class="card-body">
                        <ol class="list-group list-group-numbered mb-0">
                            <li class="list-group-item">Créez un compte ou connectez-vous</li>
                            <li class="list-group-item">Choisissez le type de service que vous souhaitez réserver</li>
                            <li class="list-group-item">Sélectionnez une date et une heure disponible</li>
                            <li class="list-group-item">Confirmez votre réservation</li>
                            <li class="list-group-item">Recevez une confirmation par email ou SMS</li>
                        </ol>
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

