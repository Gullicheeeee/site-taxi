-- ====================================
-- BASE DE DONNÉES TAXI JULIEN BACK-OFFICE
-- Système de gestion de contenu et SEO
-- ====================================

-- Table des utilisateurs admin
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor') DEFAULT 'editor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des pages (contenu éditable)
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    meta_title VARCHAR(200),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    content LONGTEXT,
    is_published BOOLEAN DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des sections de contenu (pour éditer par blocs)
CREATE TABLE IF NOT EXISTS page_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    section_key VARCHAR(100) NOT NULL,
    section_title VARCHAR(200),
    section_content TEXT,
    section_type ENUM('text', 'html', 'image', 'list') DEFAULT 'text',
    display_order INT DEFAULT 0,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_page_section (page_id, section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des articles de blog
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(200) UNIQUE NOT NULL,
    title VARCHAR(300) NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(500),
    category VARCHAR(100),
    meta_title VARCHAR(200),
    meta_description TEXT,
    author_id INT,
    is_published BOOLEAN DEFAULT 0,
    published_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des médias (images)
CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    alt_text VARCHAR(200),
    title VARCHAR(200),
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des paramètres généraux
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description VARCHAR(500),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des tarifs (pour le simulateur)
CREATE TABLE IF NOT EXISTS tarifs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarif_name VARCHAR(100) NOT NULL,
    tarif_type ENUM('A', 'B', 'C', 'D', 'fixed') NOT NULL,
    base_price DECIMAL(10,2),
    per_km_price DECIMAL(10,2),
    description TEXT,
    is_active BOOLEAN DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des réservations (optionnel - pour stocker les demandes)
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(200) NOT NULL,
    customer_email VARCHAR(200),
    customer_phone VARCHAR(50) NOT NULL,
    pickup_address VARCHAR(500) NOT NULL,
    dropoff_address VARCHAR(500) NOT NULL,
    service_type VARCHAR(100),
    pickup_date DATETIME,
    passengers INT DEFAULT 1,
    notes TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================
-- DONNÉES INITIALES
-- ====================================

-- Utilisateur admin par défaut (mot de passe: admin123 - À CHANGER!)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@taxijulien.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Pages principales
INSERT INTO pages (slug, title, meta_title, meta_description) VALUES
('index', 'Accueil', 'Taxi Julien - Taxi Conventionné Martigues | Réservation 24/7', 'Taxi conventionné CPAM à Martigues. Service 24/7, aéroports, gares, longues distances. Réservation en ligne rapide.'),
('services', 'Nos Services', 'Tous nos Services - Taxi Julien Martigues', 'Découvrez tous nos services de taxi : conventionné CPAM, aéroports, longues distances, mise à disposition.'),
('conventionne', 'Transport Conventionné', 'Taxi Conventionné CPAM - Taxi Julien Martigues', 'Transport conventionné CPAM à Martigues. Remboursement Sécurité Sociale pour vos trajets médicaux.'),
('a-propos', 'À Propos', 'À Propos - Taxi Julien Martigues', 'Découvrez Taxi Julien, votre taxi conventionné à Martigues depuis plus de 10 ans.'),
('contact', 'Contact', 'Contact - Taxi Julien Martigues', 'Contactez Taxi Julien : téléphone, email, formulaire. Service disponible 24h/24.');

-- Paramètres du site
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Taxi Julien', 'text', 'Nom du site'),
('phone_number', '01 23 45 67 89', 'text', 'Numéro de téléphone principal'),
('email', 'contact@taxijulien.fr', 'text', 'Email de contact'),
('address', 'Martigues, Bouches-du-Rhône (13)', 'text', 'Adresse'),
('facebook_url', '', 'text', 'URL Facebook'),
('google_analytics', '', 'text', 'Code Google Analytics'),
('enable_reservations', '1', 'boolean', 'Activer les réservations en ligne');

-- Tarifs initiaux
INSERT INTO tarifs (tarif_name, tarif_type, base_price, per_km_price, description) VALUES
('Tarif A - Jour semaine', 'A', 2.35, 1.11, 'Tarif jour en semaine (7h-19h)'),
('Tarif B - Nuit semaine', 'B', 2.35, 1.44, 'Tarif nuit en semaine (19h-7h)'),
('Tarif C - Jour weekend', 'C', 2.35, 2.22, 'Tarif jour week-end et jours fériés'),
('Tarif D - Nuit weekend', 'D', 2.35, 2.88, 'Tarif nuit week-end et jours fériés'),
('Aéroport Marseille Jour', 'fixed', 80.00, 0, 'Trajet forfaitaire vers Marseille Provence (jour)'),
('Aéroport Marseille Nuit', 'fixed', 100.00, 0, 'Trajet forfaitaire vers Marseille Provence (nuit)'),
('Gare Aix TGV Jour', 'fixed', 80.00, 0, 'Trajet forfaitaire vers Aix TGV (jour)'),
('Gare Aix TGV Nuit', 'fixed', 100.00, 0, 'Trajet forfaitaire vers Aix TGV (nuit)');
