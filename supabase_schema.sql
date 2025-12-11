-- =============================================
-- SCHEMA SUPABASE - TAXI JULIEN BACK-OFFICE
-- =============================================

-- Table des pages
CREATE TABLE IF NOT EXISTS pages (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    slug VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    meta_title VARCHAR(200),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    hero_title VARCHAR(300),
    hero_subtitle TEXT,
    hero_image VARCHAR(500),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table des sections de page (contenu éditable par blocs)
CREATE TABLE IF NOT EXISTS page_sections (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    section_key VARCHAR(100) NOT NULL,
    section_type VARCHAR(50) DEFAULT 'text',
    title VARCHAR(300),
    content TEXT,
    image VARCHAR(500),
    link_url VARCHAR(500),
    link_text VARCHAR(200),
    display_order INT DEFAULT 0,
    is_visible BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(page_id, section_key)
);

-- Table des articles de blog
CREATE TABLE IF NOT EXISTS blog_posts (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    slug VARCHAR(200) UNIQUE NOT NULL,
    title VARCHAR(300) NOT NULL,
    excerpt TEXT,
    content TEXT,
    featured_image VARCHAR(500),
    category VARCHAR(100),
    meta_title VARCHAR(200),
    meta_description TEXT,
    is_published BOOLEAN DEFAULT false,
    published_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table des images/médias
CREATE TABLE IF NOT EXISTS media (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    alt_text VARCHAR(300),
    uploaded_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table des paramètres
CREATE TABLE IF NOT EXISTS settings (
    key VARCHAR(100) PRIMARY KEY,
    value TEXT,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table des admins
CREATE TABLE IF NOT EXISTS admins (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(200),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    last_login TIMESTAMP WITH TIME ZONE
);

-- =============================================
-- DONNÉES INITIALES
-- =============================================

-- Admin par défaut (mot de passe: admin123)
INSERT INTO admins (username, password_hash, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@taxijulien.fr')
ON CONFLICT (username) DO NOTHING;

-- Pages principales
INSERT INTO pages (slug, title, meta_title, meta_description, hero_title, hero_subtitle) VALUES
('index', 'Accueil', 'Taxi Julien - Taxi Conventionné Martigues | Réservation 24/7', 'Taxi conventionné CPAM à Martigues. Service 24/7, aéroports, gares, longues distances.', 'Taxi Conventionné à Martigues', 'Votre transport médical et vos trajets quotidiens en toute sérénité'),
('services', 'Nos Services', 'Tous nos Services - Taxi Julien Martigues', 'Découvrez tous nos services de taxi à Martigues.', 'Nos Services', 'Un service adapté à tous vos besoins'),
('conventionne', 'Transport Conventionné', 'Taxi Conventionné CPAM - Taxi Julien', 'Transport conventionné CPAM à Martigues. Remboursement Sécurité Sociale.', 'Transport Conventionné CPAM', 'Vos trajets médicaux pris en charge'),
('aeroports-gares', 'Aéroports & Gares', 'Transferts Aéroports Gares - Taxi Julien', 'Transferts aéroports et gares depuis Martigues.', 'Aéroports & Gares', 'Transferts vers tous les aéroports et gares de la région'),
('longues-distances', 'Longues Distances', 'Taxi Longues Distances - Taxi Julien', 'Service de taxi longues distances.', 'Longues Distances', 'Voyagez partout en France'),
('courses-classiques', 'Courses Classiques', 'Courses Classiques - Taxi Julien', 'Courses de taxi classiques à Martigues.', 'Courses Classiques', 'Pour tous vos déplacements quotidiens'),
('mise-a-disposition', 'Mise à Disposition', 'Mise à Disposition - Taxi Julien', 'Service de mise à disposition.', 'Mise à Disposition', 'Un chauffeur à votre service'),
('a-propos', 'À Propos', 'À Propos - Taxi Julien Martigues', 'Découvrez Taxi Julien.', 'À Propos de Taxi Julien', 'Votre taxi de confiance depuis plus de 10 ans'),
('contact', 'Contact', 'Contact - Taxi Julien Martigues', 'Contactez Taxi Julien.', 'Contactez-nous', 'Nous sommes à votre écoute'),
('blog', 'Blog', 'Blog - Taxi Julien', 'Actualités et conseils taxi.', 'Notre Blog', 'Actualités et conseils'),
('reservation', 'Réservation', 'Réserver un Taxi - Taxi Julien', 'Réservez votre taxi en ligne.', 'Réserver un Taxi', 'Réservation simple et rapide'),
('simulateur', 'Simulateur', 'Simulateur de Prix - Taxi Julien', 'Estimez le prix de votre course.', 'Simulateur de Prix', 'Estimez le coût de votre trajet')
ON CONFLICT (slug) DO NOTHING;

-- Sections de la page d'accueil
INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order) VALUES
((SELECT id FROM pages WHERE slug = 'index'), 'services_card_1', 'card', 'Transport Conventionné', 'Trajets médicaux pris en charge par la CPAM avec toutes les formalités administratives.', 1),
((SELECT id FROM pages WHERE slug = 'index'), 'services_card_2', 'card', 'Aéroports & Gares', 'Transferts vers Marseille Provence, Aéroport de Nîmes, Gare TGV Aix-en-Provence, Avignon...', 2),
((SELECT id FROM pages WHERE slug = 'index'), 'services_card_3', 'card', 'Longues Distances', 'Déplacements interrégionaux, déménagements, voyages d''affaires partout en France.', 3),
((SELECT id FROM pages WHERE slug = 'index'), 'services_card_4', 'card', 'Service 24/7', 'Disponible jour et nuit, week-end et jours fériés. Nous sommes toujours là pour vous.', 4)
ON CONFLICT (page_id, section_key) DO NOTHING;

-- Paramètres par défaut
INSERT INTO settings (key, value) VALUES
('site_name', 'Taxi Julien'),
('phone', '01 23 45 67 89'),
('email', 'contact@taxijulien.fr'),
('address', 'Martigues, Bouches-du-Rhône (13)'),
('whatsapp', '33123456789'),
('google_analytics', ''),
('facebook_pixel', '')
ON CONFLICT (key) DO NOTHING;

-- Articles de blog existants
INSERT INTO blog_posts (slug, title, excerpt, content, category, meta_title, meta_description, is_published, published_at) VALUES
('transfert-aeroport-marseille', 'Transfert Aéroport Marseille Provence', 'Tout savoir sur les transferts vers l''aéroport Marseille Provence depuis Martigues.', '<p>Contenu de l''article...</p>', 'Aéroports', 'Transfert Aéroport Marseille - Taxi Julien', 'Guide complet pour vos transferts vers l''aéroport Marseille Provence.', true, NOW()),
('taxi-conventionne-cpam', 'Comment fonctionne le taxi conventionné CPAM ?', 'Guide complet sur le transport conventionné et le remboursement par la Sécurité Sociale.', '<p>Contenu de l''article...</p>', 'Conventionné', 'Taxi Conventionné CPAM - Guide Complet', 'Tout savoir sur le taxi conventionné CPAM et le remboursement.', true, NOW())
ON CONFLICT (slug) DO NOTHING;

-- =============================================
-- POLICIES RLS (Row Level Security)
-- =============================================

-- Activer RLS
ALTER TABLE pages ENABLE ROW LEVEL SECURITY;
ALTER TABLE page_sections ENABLE ROW LEVEL SECURITY;
ALTER TABLE blog_posts ENABLE ROW LEVEL SECURITY;
ALTER TABLE media ENABLE ROW LEVEL SECURITY;
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE admins ENABLE ROW LEVEL SECURITY;

-- Policies pour lecture publique
CREATE POLICY "Public read pages" ON pages FOR SELECT USING (true);
CREATE POLICY "Public read sections" ON page_sections FOR SELECT USING (true);
CREATE POLICY "Public read published posts" ON blog_posts FOR SELECT USING (is_published = true);
CREATE POLICY "Public read media" ON media FOR SELECT USING (true);
CREATE POLICY "Public read settings" ON settings FOR SELECT USING (true);

-- Policies pour écriture (service role uniquement)
CREATE POLICY "Service write pages" ON pages FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Service write sections" ON page_sections FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Service write posts" ON blog_posts FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Service write media" ON media FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Service write settings" ON settings FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Service write admins" ON admins FOR ALL USING (true) WITH CHECK (true);

-- =============================================
-- STORAGE BUCKET
-- =============================================

-- Créer le bucket pour les images (à faire via le dashboard ou l'API Storage)
-- INSERT INTO storage.buckets (id, name, public) VALUES ('images', 'images', true);
