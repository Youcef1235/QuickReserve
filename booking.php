<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    setAlert('warning', 'Vous devez être connecté pour effectuer une réservation.');
    redirect('login.php');
}

// Récupérer l'ID du service
$serviceId = intval($_GET['service_id'] ?? 0);

if ($serviceId <= 0) {
    setAlert('danger', 'Service non trouvé.');
    redirect('services.php');
}

// Récupérer les détails du service
$sql = "SELECT s.*, u.name as provider_name 
        FROM services s 
        JOIN users u ON s.provider_id = u.id 
        WHERE s.id = :id AND s.status = 'active'";
$service = fetchOne($sql, ['id' => $serviceId]);

if (!$service) {
    setAlert('danger', 'Service non trouvé.');
    redirect('services.php');
}

// Récupérer la date et l'heure sélectionnées (si disponibles)
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedTime = $_GET['time'] ?? '';

// Récupérer les créneaux disponibles pour la date sélectionnée
$availableSlots = getAvailableSlots($serviceId, $selectedDate);

$errors = [];
$success = false;

// Traitement du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $date = cleanInput($_POST['date'] ?? '');
        $startTime = cleanInput($_POST['start_time'] ?? '');
        $endTime = cleanInput($_POST['end_time'] ?? '');
        $notes = cleanInput($_POST['notes'] ?? '');
        
        // Validation des champs
        if (empty($date)) {
            $errors[] = "La date est requise.";
        }
        
        if (empty($startTime)) {
            $errors[] = "L'heure de début est requise.";
        }
        
        if (empty($endTime)) {
            $errors[] = "L'heure de fin est requise.";
        }
        
        // Vérifier si le créneau est disponible
        if (empty($errors) && !isSlotAvailable($serviceId, $date, $startTime, $endTime)) {
            $errors[] = "Ce créneau n'est plus disponible. Veuillez en choisir un autre.";
        }
        
        // Si pas d'erreurs, créer la réservation
        if (empty($errors)) {
            $reservationData = [
                'user_id' => $_SESSION['user_id'],
                'service_id' => $serviceId,
                'reservation_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => $notes,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $reservationId = insert('reservations', $reservationData);
            
            if ($reservationId) {
                // Envoyer un email de confirmation
                $subject = "Confirmation de réservation - QuickReserve";
                $message = "
                    <html>
                    <head>
                        <title>Confirmation de réservation</title>
                    </head>
                    <body>
                        <h2>Confirmation de réservation</h2>
                        <p>Bonjour " . $_SESSION['user_name'] . ",</p>
                        <p>Votre réservation a été enregistrée avec succès.</p>
                        <p><strong>Service:</strong> " . htmlspecialchars($service['name']) . "</p>
                        <p><strong>Date:</strong> " . formatDate($date, 'd/m/Y') . "</p>
                        <p><strong>Heure:</strong> " . substr($startTime, 0, 5) . " - " . substr($endTime, 0, 5) . "</p>
                        <p>Votre réservation est en attente de confirmation par le prestataire.</p>
                        <p>Vous recevrez une notification dès que votre réservation sera confirmée.</p>
                        <p>Cordialement,<br>L'équipe QuickReserve</p>
                    </body>
                    </html>
                ";
                
                sendEmail($_SESSION['user_email'], $subject, $message);
                
                // Rediriger vers la page de confirmation
                setAlert('success', 'Votre réservation a été enregistrée avec succès.');
                redirect('reservation-confirmation.php?id=' . $reservationId);
            } else {
                $errors[] = "Une erreur est survenue lors de la création de la réservation.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - <?php echo htmlspecialchars($service['name']); ?> - QuickReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Réservation - <?php echo htmlspecialchars($service['name']); ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $selectedDate; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Créneaux disponibles</label>
                                <div class="row" id="time-slots">
                                    <?php if (empty($availableSlots)): ?>
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                Aucun créneau disponible pour cette date. Veuillez sélectionner une autre date.
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($availableSlots as $slot): ?>
                                            <div class="col-md-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="time_slot" id="slot_<?php echo $slot['start']; ?>" value="<?php echo $slot['start'] . '-' . $slot['end']; ?>" <?php echo ($selectedTime == $slot['start']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="slot_<?php echo $slot['start']; ?>">
                                                        <?php echo $slot['start']; ?> - <?php echo $slot['end']; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes / Demandes spéciales</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <h5>Récapitulatif</h5>
                                <ul class="list-group mb-3">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Service:</span>
                                        <strong><?php echo htmlspecialchars($service['name']); ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Prestataire:</span>
                                        <strong><?php echo htmlspecialchars($service['provider_name']); ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Date:</span>
                                        <strong id="recap-date"><?php echo formatDate($selectedDate, 'd/m/Y'); ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Heure:</span>
                                        <strong id="recap-time">À sélectionner</strong>
                                    </li>
                                </ul>
                            </div>
                            
                            <input type="hidden" name="start_time" id="start_time">
                            <input type="hidden" name="end_time" id="end_time">
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Confirmer la réservation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser le sélecteur de date
            flatpickr("#date", {
                locale: "fr",
                minDate: "today",
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr) {
                    // Recharger la page avec la nouvelle date
                    window.location.href = 'booking.php?service_id=<?php echo $serviceId; ?>&date=' + dateStr;
                }
            });
            
            // Gérer la sélection des créneaux
            const timeSlotInputs = document.querySelectorAll('input[name="time_slot"]');
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            const recapTime = document.getElementById('recap-time');
            
            timeSlotInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    const timeSlot = this.value.split('-');
                    startTimeInput.value = timeSlot[0];
                    endTimeInput.value = timeSlot[1];
                    recapTime.textContent = timeSlot[0] + ' - ' + timeSlot[1];
                });
                
                // Initialiser avec le créneau sélectionné
                if (input.checked) {
                    const timeSlot = input.value.split('-');
                    startTimeInput.value = timeSlot[0];
                    endTimeInput.value = timeSlot[1];
                    recapTime.textContent = timeSlot[0] + ' - ' + timeSlot[1];
                }
            });
            
            // Mettre à jour le récapitulatif lorsque la date change
            document.getElementById('date').addEventListener('change', function() {
                document.getElementById('recap-date').textContent = new Date(this.value).toLocaleDateString('fr-FR');
            });
        });
    </script>
</body>
</html>

