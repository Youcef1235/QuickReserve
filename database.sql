-- Base de données pour QuickReserve

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS quickreserve;
USE quickreserve;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'provider', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL
);

-- Table des services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('coiffeur', 'restaurant', 'salle', 'autre') NOT NULL,
    description TEXT,
    address VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'France',
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(255),
    image VARCHAR(255),
    opening_time TIME NOT NULL,
    closing_time TIME NOT NULL,
    slot_duration INT NOT NULL DEFAULT 30, -- Durée d'un créneau en minutes
    max_capacity INT DEFAULT 1, -- Nombre maximum de réservations simultanées
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des jours de fermeture
CREATE TABLE IF NOT EXISTS closing_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    closing_date DATE NOT NULL,
    reason VARCHAR(255),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    notes TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Table des avis
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    reservation_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    status ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
);

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('reservation', 'system', 'review', 'other') NOT NULL DEFAULT 'system',
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des préférences utilisateur
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    email_notifications BOOLEAN NOT NULL DEFAULT TRUE,
    sms_notifications BOOLEAN NOT NULL DEFAULT FALSE,
    language VARCHAR(10) NOT NULL DEFAULT 'fr',
    theme VARCHAR(20) NOT NULL DEFAULT 'light',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des catégories de services
CREATE TABLE IF NOT EXISTS service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL
);

-- Table de liaison entre services et catégories
CREATE TABLE IF NOT EXISTS service_category_mapping (
    service_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (service_id, category_id),
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE CASCADE
);

-- Table des options de service
CREATE TABLE IF NOT EXISTS service_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) DEFAULT 0,
    duration INT DEFAULT 0, -- Durée supplémentaire en minutes
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Table de liaison entre réservations et options
CREATE TABLE IF NOT EXISTS reservation_options (
    reservation_id INT NOT NULL,
    option_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    PRIMARY KEY (reservation_id, option_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES service_options(id) ON DELETE CASCADE
);

-- Insertion de données de test
-- Utilisateurs
INSERT INTO users (name, email, password, phone, role, status, created_at) VALUES
('Admin', 'admin@quickreserve.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'admin', 'active', NOW()),
('Restaurant Paris', 'restaurant@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'provider', 'active', NOW()),
('Salon de Coiffure', 'coiffeur@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'provider', 'active', NOW()),
('Centre de Conférences', 'salle@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'provider', 'active', NOW()),
('Jean Dupont', 'jean@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'user', 'active', NOW()),
('Marie Martin', 'marie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'user', 'active', NOW());

-- Services
INSERT INTO services (provider_id, name, type, description, address, city, postal_code, country, phone, email, website, image, opening_time, closing_time, slot_duration, max_capacity, status, created_at) VALUES
(2, 'Le Gourmet', 'restaurant', 'Restaurant gastronomique au cœur de Paris', '123 Avenue des Champs-Élysées', 'Paris', '75008', 'France', '0123456789', 'contact@legourmet.com', 'www.legourmet.com', 'assets/img/restaurant1.jpg', '11:00:00', '23:00:00', 60, 50, 'active', NOW()),
(3, 'Coiffure Élégance', 'coiffeur', 'Salon de coiffure pour hommes et femmes', '45 Rue du Commerce', 'Paris', '75015', 'France', '0123456789', 'contact@coiffure-elegance.com', 'www.coiffure-elegance.com', 'assets/img/coiffeur1.jpg', '09:00:00', '19:00:00', 30, 5, 'active', NOW()),
(4, 'Espace Conférence', 'salle', 'Salles de réunion et de conférence équipées', '78 Boulevard Haussmann', 'Paris', '75008', 'France', '0123456789', 'contact@espace-conference.com', 'www.espace-conference.com', 'assets/img/salle1.jpg', '08:00:00', '20:00:00', 60, 100, 'active', NOW()),
(2, 'Bistrot du Coin', 'restaurant', 'Cuisine traditionnelle française dans une ambiance conviviale', '56 Rue de la Roquette', 'Paris', '75011', 'France', '0123456789', 'contact@bistrotducoin.com', 'www.bistrotducoin.com', 'assets/img/restaurant2.jpg', '12:00:00', '22:00:00', 60, 30, 'active', NOW()),
(3, 'Studio Coupe', 'coiffeur', 'Coiffeur spécialisé dans les coupes modernes', '12 Rue des Martyrs', 'Paris', '75009', 'France', '0123456789', 'contact@studiocoupe.com', 'www.studiocoupe.com', 'assets/img/coiffeur2.jpg', '10:00:00', '20:00:00', 45, 3, 'active', NOW());

-- Réservations
INSERT INTO reservations (user_id, service_id, reservation_date, start_time, end_time, notes, status, created_at) VALUES
(5, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:00:00', '21:00:00', 'Table pour 2 personnes près de la fenêtre', 'confirmed', NOW()),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', '15:00:00', 'Coupe et brushing', 'confirmed', NOW()),
(5, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:00:00', '12:00:00', 'Réunion d\'entreprise pour 20 personnes', 'pending', NOW()),
(6, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '20:00:00', '22:00:00', 'Anniversaire pour 4 personnes', 'confirmed', NOW());

-- Avis
INSERT INTO reviews (user_id, service_id, reservation_id, rating, comment, status, created_at) VALUES
(5, 1, 1, 5, 'Excellent restaurant, service impeccable et nourriture délicieuse !', 'approved', NOW()),
(6, 2, 2, 4, 'Très bon salon de coiffure, je suis satisfaite de ma coupe.', 'approved', NOW()),
(5, 1, NULL, 3, 'Bonne cuisine mais service un peu lent.', 'approved', NOW());

-- Catégories de services
INSERT INTO service_categories (name, description, icon, created_at) VALUES
('Restaurants', 'Tous types de restaurants', 'bi-cup-hot', NOW()),
('Coiffeurs', 'Salons de coiffure pour hommes et femmes', 'bi-scissors', NOW()),
('Salles', 'Espaces pour événements et réunions', 'bi-building', NOW()),
('Gastronomique', 'Restaurants haut de gamme', 'bi-star', NOW()),
('Bistrot', 'Restaurants traditionnels', 'bi-cup', NOW()),
('Coiffure Homme', 'Salons spécialisés pour hommes', 'bi-person', NOW()),
('Coiffure Femme', 'Salons spécialisés pour femmes', 'bi-person-heart', NOW()),
('Salles de réunion', 'Espaces pour réunions professionnelles', 'bi-people', NOW()),
('Salles de fête', 'Espaces pour événements festifs', 'bi-music-note-beamed', NOW());

-- Liaison services-catégories
INSERT INTO service_category_mapping (service_id, category_id) VALUES
(1, 1), (1, 4), -- Le Gourmet: Restaurants, Gastronomique
(2, 2), (2, 6), (2, 7), -- Coiffure Élégance: Coiffeurs, Coiffure Homme, Coiffure Femme
(3, 3), (3, 8), (3, 9), -- Espace Conférence: Salles, Salles de réunion, Salles de fête
(4, 1), (4, 5), -- Bistrot du Coin: Restaurants, Bistrot
(5, 2), (5, 6); -- Studio Coupe: Coiffeurs, Coiffure Homme

-- Options de service
INSERT INTO service_options (service_id, name, description, price, duration, created_at) VALUES
(1, 'Menu Dégustation', 'Menu complet avec accord mets et vins', 120.00, 0, NOW()),
(1, 'Table VIP', 'Emplacement privilégié dans le restaurant', 50.00, 0, NOW()),
(2, 'Coloration', 'Coloration professionnelle', 45.00, 30, NOW()),
(2, 'Soin capillaire', 'Traitement nourrissant pour cheveux', 25.00, 15, NOW()),
(3, 'Équipement audiovisuel', 'Projecteur, écran et système son', 100.00, 0, NOW()),
(3, 'Service de restauration', 'Pause café et collations', 15.00, 0, NOW());

