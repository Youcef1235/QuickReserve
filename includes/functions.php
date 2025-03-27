<?php
// Fonctions utilitaires pour l'application

// Fonction pour nettoyer les entrées utilisateur
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est un administrateur
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Fonction pour vérifier si l'utilisateur est un prestataire de service
function isProvider() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'provider';
}

// Fonction pour rediriger vers une autre page
function redirect($url) {
    header("Location: $url");
    exit;
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier un token CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Fonction pour afficher des messages d'alerte
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Fonction pour afficher des messages d'alerte
function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $type = $_SESSION['alert']['type'];
        $message = $_SESSION['alert']['message'];
        
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        
        unset($_SESSION['alert']);
    }
}

// Fonction pour envoyer un email
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: QuickReserve <noreply@quickreserve.com>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Fonction pour formater une date
function formatDate($date, $format = 'd/m/Y H:i') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

// Fonction pour vérifier la disponibilité d'un créneau
function isSlotAvailable($serviceId, $date, $startTime, $endTime) {
    $sql = "SELECT COUNT(*) as count FROM reservations 
            WHERE service_id = :service_id 
            AND reservation_date = :date 
            AND NOT (
                (start_time >= :end_time) OR 
                (end_time <= :start_time)
            )";
    
    $result = fetchOne($sql, [
        'service_id' => $serviceId,
        'date' => $date,
        'start_time' => $startTime,
        'end_time' => $endTime
    ]);
    
    return $result['count'] == 0;
}

// Fonction pour générer des créneaux horaires
function generateTimeSlots($startTime, $endTime, $duration = 30) {
    $slots = [];
    $start = strtotime($startTime);
    $end = strtotime($endTime);
    
    for ($time = $start; $time < $end; $time += $duration * 60) {
        $slotEnd = $time + $duration * 60;
        if ($slotEnd <= $end) {
            $slots[] = [
                'start' => date('H:i', $time),
                'end' => date('H:i', $slotEnd)
            ];
        }
    }
    
    return $slots;
}

// Fonction pour obtenir les créneaux disponibles pour un service à une date donnée
function getAvailableSlots($serviceId, $date) {
    // Récupérer les heures d'ouverture du service
    $sql = "SELECT opening_time, closing_time, slot_duration FROM services WHERE id = :id";
    $service = fetchOne($sql, ['id' => $serviceId]);
    
    if (!$service) {
        return [];
    }
    
    // Générer tous les créneaux possibles
    $allSlots = generateTimeSlots($service['opening_time'], $service['closing_time'], $service['slot_duration']);
    
    // Récupérer les réservations existantes pour cette date
    $sql = "SELECT start_time, end_time FROM reservations 
            WHERE service_id = :service_id AND reservation_date = :date";
    $reservations = fetchAll($sql, [
        'service_id' => $serviceId,
        'date' => $date
    ]);
    
    // Filtrer les créneaux disponibles
    $availableSlots = [];
    foreach ($allSlots as $slot) {
        $isAvailable = true;
        foreach ($reservations as $reservation) {
            if (!(($slot['start'] >= $reservation['end_time']) || 
                  ($slot['end'] <= $reservation['start_time']))) {
                $isAvailable = false;
                break;
            }
        }
        
        if ($isAvailable) {
            $availableSlots[] = $slot;
        }
    }
    
    return $availableSlots;
}

