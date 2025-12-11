-- =====================================================
-- SCHEMA SEO-FIRST CMS - BACK-OFFICE COMPLET
-- =====================================================

-- =====================================================
-- 1. PAGES & CONTENUS
-- =====================================================

-- Table pages améliorée
ALTER TABLE pages ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'draft'; -- draft, published, scheduled
ALTER TABLE pages ADD COLUMN IF NOT EXISTS is_indexed BOOLEAN DEFAULT true;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS canonical_url TEXT;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS noindex BOOLEAN DEFAULT false;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS nofollow BOOLEAN DEFAULT false;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS focus_keyword VARCHAR(255);
ALTER TABLE pages ADD COLUMN IF NOT EXISTS secondary_keywords TEXT[]; -- Array de mots-clés secondaires
ALTER TABLE pages ADD COLUMN IF NOT EXISTS seo_score INTEGER DEFAULT 0; -- Score SEO 0-100
ALTER TABLE pages ADD COLUMN IF NOT EXISTS readability_score INTEGER DEFAULT 0; -- Score lisibilité 0-100
ALTER TABLE pages ADD COLUMN IF NOT EXISTS word_count INTEGER DEFAULT 0;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS last_seo_analysis TIMESTAMP;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS published_at TIMESTAMP;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS scheduled_at TIMESTAMP;

-- Historique des versions de pages
CREATE TABLE IF NOT EXISTS page_versions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    version_number INTEGER NOT NULL,
    title TEXT,
    content JSONB, -- Contenu complet en JSON
    hero_title TEXT,
    hero_subtitle TEXT,
    meta_title TEXT,
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    created_by UUID REFERENCES admins(id),
    change_summary TEXT
);

-- Brouillons automatiques
CREATE TABLE IF NOT EXISTS page_drafts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    content JSONB,
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(page_id)
);

-- =====================================================
-- 2. BLOCS DE CONTENU AVANCÉS
-- =====================================================

-- Types de blocs étendus
ALTER TABLE page_sections ADD COLUMN IF NOT EXISTS block_settings JSONB DEFAULT '{}';
ALTER TABLE page_sections ADD COLUMN IF NOT EXISTS seo_data JSONB DEFAULT '{}'; -- Données SEO du bloc
ALTER TABLE page_sections ADD COLUMN IF NOT EXISTS internal_links JSONB DEFAULT '[]'; -- Liens internes dans le bloc

-- Nouveaux types de blocs: text, image, gallery, faq, table, cta, video, accordion, testimonial, stats

-- =====================================================
-- 3. MAILLAGE INTERNE
-- =====================================================

-- Table des liens internes
CREATE TABLE IF NOT EXISTS internal_links (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    source_page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    target_page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    anchor_text TEXT NOT NULL,
    link_context TEXT, -- Phrase autour du lien
    section_id UUID REFERENCES page_sections(id) ON DELETE SET NULL,
    is_automatic BOOLEAN DEFAULT false, -- Lien suggéré automatiquement
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(source_page_id, target_page_id, anchor_text)
);

-- Suggestions de liens
CREATE TABLE IF NOT EXISTS link_suggestions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    source_page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    target_page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    suggested_anchor TEXT,
    relevance_score DECIMAL(3,2), -- 0.00 à 1.00
    is_accepted BOOLEAN DEFAULT NULL, -- null = pending, true = accepted, false = rejected
    created_at TIMESTAMP DEFAULT NOW()
);

-- =====================================================
-- 4. MÉDIA & IMAGES SEO
-- =====================================================

-- Extension de la table media
ALTER TABLE media ADD COLUMN IF NOT EXISTS title TEXT;
ALTER TABLE media ADD COLUMN IF NOT EXISTS caption TEXT;
ALTER TABLE media ADD COLUMN IF NOT EXISTS seo_filename TEXT; -- Nom de fichier optimisé SEO
ALTER TABLE media ADD COLUMN IF NOT EXISTS dimensions JSONB; -- {width, height}
ALTER TABLE media ADD COLUMN IF NOT EXISTS is_optimized BOOLEAN DEFAULT false;
ALTER TABLE media ADD COLUMN IF NOT EXISTS original_size INTEGER; -- Taille avant compression
ALTER TABLE media ADD COLUMN IF NOT EXISTS compression_ratio DECIMAL(4,2);
ALTER TABLE media ADD COLUMN IF NOT EXISTS used_on_pages UUID[]; -- Pages utilisant cette image
ALTER TABLE media ADD COLUMN IF NOT EXISTS focal_point JSONB; -- {x, y} pour le recadrage

-- =====================================================
-- 5. REDIRECTIONS & SEO TECHNIQUE
-- =====================================================

CREATE TABLE IF NOT EXISTS redirections (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    source_url TEXT NOT NULL UNIQUE,
    target_url TEXT NOT NULL,
    redirect_type INTEGER DEFAULT 301, -- 301, 302, 307
    is_active BOOLEAN DEFAULT true,
    hit_count INTEGER DEFAULT 0,
    last_hit_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    created_by UUID REFERENCES admins(id)
);

-- Configuration robots.txt
CREATE TABLE IF NOT EXISTS robots_config (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_agent VARCHAR(255) DEFAULT '*',
    rules JSONB, -- [{type: 'allow'|'disallow', path: '/xxx'}]
    updated_at TIMESTAMP DEFAULT NOW()
);

-- =====================================================
-- 6. ANALYTICS & PERFORMANCE SEO
-- =====================================================

CREATE TABLE IF NOT EXISTS seo_metrics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    ctr DECIMAL(5,2) DEFAULT 0,
    avg_position DECIMAL(5,2),
    top_queries JSONB, -- [{query, impressions, clicks, position}]
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(page_id, date)
);

-- Historique des optimisations
CREATE TABLE IF NOT EXISTS seo_optimizations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    page_id UUID REFERENCES pages(id) ON DELETE CASCADE,
    optimization_type VARCHAR(50), -- title, meta, content, links, images
    before_value TEXT,
    after_value TEXT,
    expected_impact VARCHAR(20), -- low, medium, high
    actual_impact JSONB, -- Métriques avant/après
    created_at TIMESTAMP DEFAULT NOW(),
    created_by UUID REFERENCES admins(id)
);

-- Alertes SEO
CREATE TABLE IF NOT EXISTS seo_alerts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    page_id UUID REFERENCES pages(id) ON DELETE SET NULL,
    alert_type VARCHAR(50), -- orphan_page, thin_content, missing_meta, duplicate_title, etc.
    severity VARCHAR(20), -- info, warning, error, critical
    message TEXT,
    details JSONB,
    is_resolved BOOLEAN DEFAULT false,
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);

-- =====================================================
-- 7. UTILISATEURS & PERMISSIONS
-- =====================================================

-- Extension table admins
ALTER TABLE admins ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'editor'; -- admin, seo_manager, editor, reviewer
ALTER TABLE admins ADD COLUMN IF NOT EXISTS permissions JSONB DEFAULT '{}';
ALTER TABLE admins ADD COLUMN IF NOT EXISTS last_login TIMESTAMP;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS avatar_url TEXT;

-- Rôles et permissions
CREATE TABLE IF NOT EXISTS roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100),
    permissions JSONB NOT NULL, -- {pages: {create, edit, delete, publish}, media: {...}, seo: {...}}
    created_at TIMESTAMP DEFAULT NOW()
);

-- Insérer les rôles par défaut
INSERT INTO roles (name, display_name, permissions) VALUES
('admin', 'Administrateur', '{"pages": {"create": true, "edit": true, "delete": true, "publish": true}, "media": {"upload": true, "delete": true}, "seo": {"full": true}, "users": {"manage": true}, "settings": {"manage": true}}'),
('seo_manager', 'Responsable SEO', '{"pages": {"create": true, "edit": true, "delete": false, "publish": true}, "media": {"upload": true, "delete": false}, "seo": {"full": true}, "users": {"manage": false}, "settings": {"manage": false}}'),
('editor', 'Rédacteur', '{"pages": {"create": true, "edit": true, "delete": false, "publish": false}, "media": {"upload": true, "delete": false}, "seo": {"view": true}, "users": {"manage": false}, "settings": {"manage": false}}'),
('reviewer', 'Relecteur', '{"pages": {"create": false, "edit": false, "delete": false, "publish": false, "review": true}, "media": {"upload": false, "delete": false}, "seo": {"view": true}, "users": {"manage": false}, "settings": {"manage": false}}')
ON CONFLICT (name) DO NOTHING;

-- Journal des modifications
CREATE TABLE IF NOT EXISTS activity_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES admins(id) ON DELETE SET NULL,
    action VARCHAR(50), -- create, update, delete, publish, unpublish
    entity_type VARCHAR(50), -- page, media, settings, user
    entity_id UUID,
    entity_title TEXT,
    changes JSONB, -- {field: {old, new}}
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- =====================================================
-- 8. CONFIGURATION & PARAMÈTRES
-- =====================================================

-- Paramètres SEO globaux
INSERT INTO settings (key, value) VALUES
('seo_default_title_suffix', ' | Taxi Julien Martigues'),
('seo_title_separator', ' - '),
('seo_default_og_image', ''),
('sitemap_frequency', 'weekly'),
('sitemap_priority_homepage', '1.0'),
('sitemap_priority_pages', '0.8'),
('sitemap_priority_blog', '0.6'),
('analytics_enabled', 'false'),
('gsc_property', ''),
('performance_lazy_loading', 'true'),
('performance_image_optimization', 'true'),
('performance_cache_duration', '3600')
ON CONFLICT (key) DO NOTHING;

-- =====================================================
-- 9. INDEX POUR PERFORMANCE
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_pages_status ON pages(status);
CREATE INDEX IF NOT EXISTS idx_pages_seo_score ON pages(seo_score);
CREATE INDEX IF NOT EXISTS idx_internal_links_source ON internal_links(source_page_id);
CREATE INDEX IF NOT EXISTS idx_internal_links_target ON internal_links(target_page_id);
CREATE INDEX IF NOT EXISTS idx_seo_metrics_page_date ON seo_metrics(page_id, date);
CREATE INDEX IF NOT EXISTS idx_seo_alerts_unresolved ON seo_alerts(is_resolved) WHERE is_resolved = false;
CREATE INDEX IF NOT EXISTS idx_activity_log_user ON activity_log(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_entity ON activity_log(entity_type, entity_id);

-- =====================================================
-- FIN DU SCHEMA
-- =====================================================
SELECT 'Schema SEO-First CMS créé avec succès!' as result;
