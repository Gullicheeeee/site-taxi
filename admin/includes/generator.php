<?php
/**
 * GÉNÉRATEUR DE PAGES STATIQUES - CMS Taxi Julien
 *
 * Ce fichier gère la génération des fichiers HTML à partir des données
 * stockées dans Supabase. Quand une page est publiée dans l'admin,
 * le HTML correspondant est généré dans le dossier racine du site.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

class PageGenerator {
    private string $templatesDir;
    private string $outputDir;
    private string $siteUrl;
    private array $siteConfig;

    public function __construct() {
        $this->templatesDir = __DIR__ . '/../templates/';
        $this->outputDir = __DIR__ . '/../../';
        $this->siteUrl = 'https://www.taxijulien.fr';
        $this->siteConfig = $this->loadSiteConfig();
    }

    /**
     * Charger la configuration du site
     */
    private function loadSiteConfig(): array {
        $configFile = __DIR__ . '/../../data/site-config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config) {
                return $config;
            }
        }

        // Configuration par défaut
        return [
            'site_name' => 'Taxi Julien',
            'phone' => '01 23 45 67 89',
            'phone_link' => '+33123456789',
            'email' => 'contact@taxijulien.fr',
            'address' => 'Martigues, Bouches-du-Rhône (13)',
            'hours' => '24h/24, 7j/7',
            'whatsapp' => '33123456789',
            'social' => [
                'facebook' => 'https://facebook.com/taxijulien',
                'instagram' => ''
            ],
            'seo' => [
                'title_suffix' => ' | Taxi Julien Martigues',
                'default_og_image' => '/images/og-image.jpg'
            ]
        ];
    }

    /**
     * Générer une page HTML à partir des données
     */
    public function generatePage(array $page, array $sections = []): array {
        $result = ['success' => false, 'message' => '', 'file' => ''];

        try {
            // Charger le template de base
            $template = $this->loadTemplate('page.html');
            if (!$template) {
                $result['message'] = 'Template page.html introuvable';
                return $result;
            }

            // Construire le contenu des sections
            $sectionsHtml = $this->buildSectionsHtml($sections);

            // Déterminer le slug pour le fichier
            $slug = $page['slug'];
            $filename = $slug === 'index' ? 'index.html' : $slug . '.html';

            // Canonical URL
            $canonical = $slug === 'index'
                ? $this->siteUrl . '/'
                : $this->siteUrl . '/' . $filename;

            // Préparer les remplacements
            $replacements = [
                // SEO Meta
                '{{meta_title}}' => $this->e($page['meta_title'] ?: $page['title']),
                '{{meta_description}}' => $this->e($page['meta_description'] ?? ''),
                '{{meta_keywords}}' => $this->e($page['meta_keywords'] ?? ''),
                '{{canonical}}' => $canonical,
                '{{og_title}}' => $this->e($page['meta_title'] ?: $page['title']),
                '{{og_description}}' => $this->e($page['meta_description'] ?? ''),
                '{{og_image}}' => $this->siteUrl . ($this->siteConfig['seo']['default_og_image'] ?? '/images/og-image.jpg'),
                '{{og_url}}' => $canonical,

                // Hero
                '{{hero_title}}' => $this->e($page['hero_title'] ?? $page['title']),
                '{{hero_subtitle}}' => $this->e($page['hero_subtitle'] ?? ''),
                '{{hero_badges}}' => $this->buildHeroBadges($page),
                '{{hero_cta}}' => $this->buildHeroCta($page),

                // Contenu
                '{{content}}' => $sectionsHtml,

                // Navigation active
                '{{nav_active_index}}' => $slug === 'index' ? ' active' : '',
                '{{nav_active_services}}' => in_array($slug, ['services', 'conventionne', 'aeroports-gares', 'longues-distances', 'courses-classiques', 'mise-a-disposition']) ? ' active' : '',
                '{{nav_active_simulateur}}' => $slug === 'simulateur' ? ' active' : '',
                '{{nav_active_blog}}' => $slug === 'blog' ? ' active' : '',
                '{{nav_active_apropos}}' => $slug === 'a-propos' ? ' active' : '',
                '{{nav_active_contact}}' => $slug === 'contact' ? ' active' : '',

                // Config du site
                '{{site_name}}' => $this->e($this->siteConfig['site_name'] ?? 'Taxi Julien'),
                '{{phone}}' => $this->e($this->siteConfig['phone'] ?? '01 23 45 67 89'),
                '{{phone_link}}' => $this->e($this->siteConfig['phone_link'] ?? '+33123456789'),
                '{{email}}' => $this->e($this->siteConfig['email'] ?? 'contact@taxijulien.fr'),
                '{{address}}' => $this->e($this->siteConfig['address'] ?? 'Martigues, Bouches-du-Rhône (13)'),
                '{{hours}}' => $this->e($this->siteConfig['hours'] ?? '24h/24, 7j/7'),
                '{{whatsapp}}' => $this->e($this->siteConfig['whatsapp'] ?? '33123456789'),
                '{{current_year}}' => date('Y'),

                // Schema JSON-LD
                '{{schema_json}}' => $this->generateSchema($page),

                // Scripts supplémentaires (pour certaines pages comme simulateur)
                '{{extra_scripts}}' => $this->getExtraScripts($slug),
            ];

            // Appliquer les remplacements
            $html = str_replace(array_keys($replacements), array_values($replacements), $template);

            // Écrire le fichier
            $filepath = $this->outputDir . $filename;
            if (file_put_contents($filepath, $html) !== false) {
                $result['success'] = true;
                $result['message'] = 'Page générée avec succès';
                $result['file'] = $filename;
            } else {
                $result['message'] = 'Erreur lors de l\'écriture du fichier ' . $filename;
            }
        } catch (Exception $e) {
            $result['message'] = 'Erreur: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Construire les badges du hero
     */
    private function buildHeroBadges(array $page): string {
        // Badges par défaut pour la page d'accueil
        if ($page['slug'] === 'index') {
            return '
            <div class="badges-container">
                <span class="badge">✓ Agréé CPAM</span>
                <span class="badge">✓ Disponible 24/7</span>
                <span class="badge">✓ Véhicule Premium</span>
                <span class="badge">✓ Ponctualité Garantie</span>
            </div>';
        }
        return '';
    }

    /**
     * Construire les CTA du hero
     */
    private function buildHeroCta(array $page): string {
        // CTA par défaut
        return '
            <div class="cta-buttons">
                <a href="reservation.html" class="btn btn-primary btn-large">Réserver un Taxi</a>
                <a href="simulateur.html" class="btn btn-secondary btn-large">Estimer un Trajet</a>
            </div>';
    }

    /**
     * Construire le HTML des sections
     */
    private function buildSectionsHtml(array $sections): string {
        $html = '';

        foreach ($sections as $section) {
            if (!($section['is_visible'] ?? true)) {
                continue;
            }

            $content = is_string($section['content'])
                ? json_decode($section['content'], true)
                : ($section['content'] ?? []);

            if (!is_array($content)) {
                $content = ['text' => $section['content']];
            }

            $type = $section['section_type'] ?? 'text';

            switch ($type) {
                case 'cards':
                    $html .= $this->renderCardsSection($section, $content);
                    break;
                case 'features':
                    $html .= $this->renderFeaturesSection($section, $content);
                    break;
                case 'text':
                    $html .= $this->renderTextSection($section, $content);
                    break;
                case 'cta':
                    $html .= $this->renderCtaSection($section, $content);
                    break;
                case 'list':
                    $html .= $this->renderListSection($section, $content);
                    break;
                case 'simulator':
                    $html .= $this->renderSimulatorSection($section, $content);
                    break;
                default:
                    $html .= $this->renderTextSection($section, $content);
            }
        }

        return $html;
    }

    /**
     * Rendre une section de cartes (services)
     */
    private function renderCardsSection(array $section, array $content): string {
        $html = '<section class="section">' . "\n";
        $html .= '        <div class="container">' . "\n";

        if (!empty($content['title'])) {
            $html .= '            <div class="section-title">' . "\n";
            $html .= '                <h2>' . $this->e($content['title']) . '</h2>' . "\n";
            if (!empty($content['subtitle'])) {
                $html .= '                <p>' . $this->e($content['subtitle']) . '</p>' . "\n";
            }
            $html .= '            </div>' . "\n";
        }

        if (!empty($content['items']) && is_array($content['items'])) {
            $html .= "\n" . '            <div class="cards-grid">' . "\n";
            foreach ($content['items'] as $item) {
                $html .= '                <div class="card">' . "\n";
                if (!empty($item['icon'])) {
                    $html .= '                    <span class="card-icon">' . $item['icon'] . '</span>' . "\n";
                }
                if (!empty($item['title'])) {
                    $html .= '                    <h3 class="card-title">' . $this->e($item['title']) . '</h3>' . "\n";
                }
                if (!empty($item['text'])) {
                    $html .= '                    <p class="card-text">' . $this->e($item['text']) . '</p>' . "\n";
                }
                if (!empty($item['link_url']) && !empty($item['link_text'])) {
                    $html .= '                    <a href="' . $this->e($item['link_url']) . '" style="color: var(--accent-dark); font-weight: 600;">' . $this->e($item['link_text']) . '</a>' . "\n";
                }
                $html .= '                </div>' . "\n";
            }
            $html .= '            </div>' . "\n";
        }

        if (!empty($content['cta_text']) && !empty($content['cta_url'])) {
            $html .= "\n" . '            <div class="text-center mt-4">' . "\n";
            $html .= '                <a href="' . $this->e($content['cta_url']) . '" class="btn btn-primary">' . $this->e($content['cta_text']) . '</a>' . "\n";
            $html .= '            </div>' . "\n";
        }

        $html .= '        </div>' . "\n";
        $html .= '    </section>' . "\n";

        return $html;
    }

    /**
     * Rendre une section Features (pourquoi nous choisir)
     */
    private function renderFeaturesSection(array $section, array $content): string {
        $html = '<section class="section features">' . "\n";
        $html .= '        <div class="container">' . "\n";

        if (!empty($content['title'])) {
            $html .= '            <div class="section-title">' . "\n";
            $html .= '                <h2>' . $this->e($content['title']) . '</h2>' . "\n";
            $html .= '            </div>' . "\n";
        }

        if (!empty($content['items']) && is_array($content['items'])) {
            $html .= "\n" . '            <div class="cards-grid">' . "\n";
            foreach ($content['items'] as $item) {
                $html .= '                <div class="feature-item">' . "\n";
                if (!empty($item['icon'])) {
                    $html .= '                    <span class="feature-icon">' . $item['icon'] . '</span>' . "\n";
                }
                $html .= '                    <div class="feature-content">' . "\n";
                if (!empty($item['title'])) {
                    $html .= '                        <h3>' . $this->e($item['title']) . '</h3>' . "\n";
                }
                if (!empty($item['text'])) {
                    $html .= '                        <p>' . $this->e($item['text']) . '</p>' . "\n";
                }
                $html .= '                    </div>' . "\n";
                $html .= '                </div>' . "\n";
            }
            $html .= '            </div>' . "\n";
        }

        $html .= '        </div>' . "\n";
        $html .= '    </section>' . "\n";

        return $html;
    }

    /**
     * Rendre une section de texte simple
     */
    private function renderTextSection(array $section, array $content): string {
        $html = '<section class="section">' . "\n";
        $html .= '        <div class="container">' . "\n";

        if (!empty($content['title'])) {
            $html .= '            <div class="section-title">' . "\n";
            $html .= '                <h2>' . $this->e($content['title']) . '</h2>' . "\n";
            if (!empty($content['subtitle'])) {
                $html .= '                <p>' . $this->e($content['subtitle']) . '</p>' . "\n";
            }
            $html .= '            </div>' . "\n";
        }

        if (!empty($content['text'])) {
            $html .= '            <div class="content-block">' . "\n";
            $html .= '                ' . $content['text'] . "\n";
            $html .= '            </div>' . "\n";
        }

        $html .= '        </div>' . "\n";
        $html .= '    </section>' . "\n";

        return $html;
    }

    /**
     * Rendre une section CTA
     */
    private function renderCtaSection(array $section, array $content): string {
        $bg = ($content['background'] ?? 'primary') === 'primary'
            ? 'background: var(--primary); color: white;'
            : 'background: var(--light-gray);';

        $html = '<section class="section" style="' . $bg . ' text-align: center;">' . "\n";
        $html .= '        <div class="container">' . "\n";

        if (!empty($content['title'])) {
            $textColor = ($content['background'] ?? 'primary') === 'primary' ? ' style="color: white;"' : '';
            $html .= '            <h2' . $textColor . '>' . $this->e($content['title']) . '</h2>' . "\n";
        }

        if (!empty($content['subtitle'])) {
            $html .= '            <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.95;">' . $this->e($content['subtitle']) . '</p>' . "\n";
        }

        $html .= '            <div class="cta-buttons">' . "\n";
        if (!empty($content['cta_primary_text'])) {
            $html .= '                <a href="' . $this->e($content['cta_primary_url'] ?? 'reservation.html') . '" class="btn btn-primary btn-large">' . $this->e($content['cta_primary_text']) . '</a>' . "\n";
        }
        if (!empty($content['cta_secondary_text'])) {
            $html .= '                <a href="' . $this->e($content['cta_secondary_url'] ?? '#') . '" class="btn btn-secondary btn-large">' . $this->e($content['cta_secondary_text']) . '</a>' . "\n";
        }
        $html .= '            </div>' . "\n";

        $html .= '        </div>' . "\n";
        $html .= '    </section>' . "\n";

        return $html;
    }

    /**
     * Rendre une section liste (zone d'intervention)
     */
    private function renderListSection(array $section, array $content): string {
        $html = '<section class="section">' . "\n";
        $html .= '        <div class="container">' . "\n";

        if (!empty($content['title'])) {
            $html .= '            <div class="section-title">' . "\n";
            $html .= '                <h2>' . $this->e($content['title']) . '</h2>' . "\n";
            if (!empty($content['subtitle'])) {
                $html .= '                <p>' . $this->e($content['subtitle']) . '</p>' . "\n";
            }
            $html .= '            </div>' . "\n";
        }

        if (!empty($content['items']) && is_array($content['items'])) {
            $html .= "\n" . '            <div style="background: white; padding: 2rem; border-radius: 15px; box-shadow: var(--shadow-md); max-width: 800px; margin: 0 auto;">' . "\n";
            $html .= '                <ul style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; list-style: none;">' . "\n";
            foreach ($content['items'] as $item) {
                $text = is_string($item) ? $item : ($item['title'] ?? $item['text'] ?? '');
                $html .= '                    <li style="display: flex; align-items: center; gap: 0.5rem;">' . "\n";
                $html .= '                        <span style="color: var(--secondary-color);">✓</span> ' . $this->e($text) . "\n";
                $html .= '                    </li>' . "\n";
            }
            $html .= '                </ul>' . "\n";
            $html .= '            </div>' . "\n";
        }

        $html .= '        </div>' . "\n";
        $html .= '    </section>' . "\n";

        return $html;
    }

    /**
     * Rendre la section simulateur
     */
    private function renderSimulatorSection(array $section, array $content): string {
        $html = '<section class="section" style="background: var(--light-gray);">' . "\n";
        $html .= '        <div class="container">' . "\n";

        $html .= '            <div class="section-title">' . "\n";
        $html .= '                <h2>' . $this->e($content['title'] ?? 'Estimez le Prix de votre Course') . '</h2>' . "\n";
        $html .= '                <p>' . $this->e($content['subtitle'] ?? 'Calculez instantanément le tarif de votre trajet') . '</p>' . "\n";
        $html .= '            </div>' . "\n";

        $html .= '
            <div class="simulator-container">
                <form id="quick-simulator">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quick-depart" class="form-label">Adresse de départ</label>
                            <input type="text" id="quick-depart" class="form-control" placeholder="Ex: Martigues" required>
                        </div>
                        <div class="form-group">
                            <label for="quick-arrivee" class="form-label">Adresse d\'arrivée</label>
                            <input type="text" id="quick-arrivee" class="form-control" placeholder="Ex: Aéroport Marseille" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Calculer le Prix</button>
                </form>
                <div class="price-result" id="quick-result">
                    <h3>Estimation de votre course</h3>
                    <div class="price-amount">-- €</div>
                    <a href="reservation.html" class="btn btn-secondary mt-3">Réserver ce Trajet</a>
                </div>
            </div>
        ';

        $html .= '        </div>' . "\n";
        $html .= '    </section>' . "\n";

        return $html;
    }

    /**
     * Charger un template
     */
    private function loadTemplate(string $name): ?string {
        $file = $this->templatesDir . $name;
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return null;
    }

    /**
     * Générer le schema JSON-LD
     */
    private function generateSchema(array $page): string {
        $slug = $page['slug'];

        // Schema LocalBusiness pour la page d'accueil
        if ($slug === 'index') {
            return $this->generateHomeSchema();
        }

        // Schema simple pour les autres pages
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $page['meta_title'] ?: $page['title'],
            'description' => $page['meta_description'] ?? '',
            'url' => $this->siteUrl . '/' . ($slug === 'index' ? '' : $slug . '.html'),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'Taxi Julien',
                'url' => $this->siteUrl
            ]
        ];

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Accueil',
                    'item' => $this->siteUrl . '/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $page['title'],
                    'item' => $this->siteUrl . '/' . $slug . '.html'
                ]
            ]
        ];

        return '<script type="application/ld+json">' . "\n    " . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>\n\n    "
             . '<script type="application/ld+json">' . "\n    " . json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>";
    }

    /**
     * Schema complet pour la page d'accueil
     */
    private function generateHomeSchema(): string {
        $phone = $this->siteConfig['phone_link'] ?? '+33123456789';

        $taxiService = [
            '@context' => 'https://schema.org',
            '@type' => 'TaxiService',
            '@id' => $this->siteUrl . '/#taxiservice',
            'name' => 'Taxi Julien',
            'description' => 'Service de taxi conventionné CPAM à Martigues. Transport médical, aéroports, gares et longues distances. Disponible 24h/24, 7j/7.',
            'url' => $this->siteUrl,
            'telephone' => $phone,
            'email' => $this->siteConfig['email'] ?? 'contact@taxijulien.fr',
            'priceRange' => '€€',
            'image' => $this->siteUrl . '/images/og-image.jpg',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Martigues',
                'postalCode' => '13500',
                'addressRegion' => 'Bouches-du-Rhône',
                'addressCountry' => 'FR'
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => 43.4051,
                'longitude' => 5.0476
            ],
            'openingHoursSpecification' => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'opens' => '00:00',
                'closes' => '23:59'
            ]
        ];

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Accueil',
                    'item' => $this->siteUrl . '/'
                ]
            ]
        ];

        return '<script type="application/ld+json">' . "\n    " . json_encode($taxiService, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>\n\n    "
             . '<script type="application/ld+json">' . "\n    " . json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>";
    }

    /**
     * Scripts supplémentaires pour certaines pages
     */
    private function getExtraScripts(string $slug): string {
        if ($slug === 'simulateur') {
            return '<script src="js/simulateur.js"></script>';
        }
        if ($slug === 'reservation') {
            return '<script src="js/reservation.js"></script>';
        }
        if ($slug === 'contact') {
            return '<script src="js/contact.js"></script>';
        }
        return '';
    }

    /**
     * Générer un article de blog
     */
    public function generateBlogPost(array $post): array {
        $result = ['success' => false, 'message' => '', 'file' => ''];

        try {
            $template = $this->loadTemplate('blog-post.html');
            if (!$template) {
                $result['message'] = 'Template blog-post.html introuvable';
                return $result;
            }

            $publishedDate = date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now'));
            $publishedIso = date('c', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now'));

            $replacements = [
                '{{meta_title}}' => $this->e($post['meta_title'] ?: $post['title'] . ' | Taxi Julien'),
                '{{meta_description}}' => $this->e($post['meta_description'] ?? $post['excerpt'] ?? ''),
                '{{canonical}}' => $this->siteUrl . '/blog/' . $post['slug'] . '.html',
                '{{og_title}}' => $this->e($post['meta_title'] ?: $post['title']),
                '{{og_description}}' => $this->e($post['meta_description'] ?? $post['excerpt'] ?? ''),
                '{{og_image}}' => $post['featured_image'] ?? ($this->siteUrl . '/images/og-image.jpg'),
                '{{og_url}}' => $this->siteUrl . '/blog/' . $post['slug'] . '.html',
                '{{title}}' => $this->e($post['title']),
                '{{content}}' => $post['content'] ?? '',
                '{{excerpt}}' => $this->e($post['excerpt'] ?? ''),
                '{{featured_image}}' => $post['featured_image'] ?? '',
                '{{author}}' => $this->e($post['author_name'] ?? 'Taxi Julien'),
                '{{published_date}}' => $publishedDate,
                '{{published_iso}}' => $publishedIso,
                '{{site_name}}' => $this->e($this->siteConfig['site_name'] ?? 'Taxi Julien'),
                '{{phone}}' => $this->e($this->siteConfig['phone'] ?? ''),
                '{{phone_link}}' => $this->e($this->siteConfig['phone_link'] ?? ''),
                '{{email}}' => $this->e($this->siteConfig['email'] ?? ''),
                '{{address}}' => $this->e($this->siteConfig['address'] ?? ''),
                '{{hours}}' => $this->e($this->siteConfig['hours'] ?? ''),
                '{{whatsapp}}' => $this->e($this->siteConfig['whatsapp'] ?? ''),
                '{{current_year}}' => date('Y'),
                '{{schema_json}}' => $this->generateArticleSchema($post),
            ];

            $html = str_replace(array_keys($replacements), array_values($replacements), $template);

            // Créer le dossier blog si nécessaire
            $blogDir = $this->outputDir . 'blog/';
            if (!is_dir($blogDir)) {
                mkdir($blogDir, 0755, true);
            }

            $filename = $blogDir . $post['slug'] . '.html';
            if (file_put_contents($filename, $html) !== false) {
                $result['success'] = true;
                $result['message'] = 'Article généré avec succès';
                $result['file'] = 'blog/' . $post['slug'] . '.html';
            } else {
                $result['message'] = 'Erreur lors de l\'écriture du fichier';
            }
        } catch (Exception $e) {
            $result['message'] = 'Erreur: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Schema JSON-LD pour un article
     */
    private function generateArticleSchema(array $post): string {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post['title'],
            'description' => $post['meta_description'] ?? $post['excerpt'] ?? '',
            'image' => $post['featured_image'] ?? '',
            'datePublished' => date('c', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now')),
            'dateModified' => date('c', strtotime($post['updated_at'] ?? 'now')),
            'author' => [
                '@type' => 'Person',
                'name' => $post['author_name'] ?? 'Taxi Julien'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Taxi Julien',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $this->siteUrl . '/images/logo.png'
                ]
            ]
        ];

        return '<script type="application/ld+json">' . "\n    " . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>";
    }

    /**
     * Mettre à jour la page de liste du blog
     */
    public function updateBlogList(): array {
        $result = ['success' => false, 'message' => ''];

        try {
            $postsResult = supabase()->select('blog_posts', 'status=eq.published&order=published_at.desc');
            $posts = ($postsResult['success'] ?? false) ? ($postsResult['data'] ?? []) : [];

            $template = $this->loadTemplate('blog-list.html');
            if (!$template) {
                $result['message'] = 'Template blog-list.html introuvable';
                return $result;
            }

            $articlesHtml = '';
            foreach ($posts as $post) {
                $articlesHtml .= $this->renderBlogCard($post);
            }

            if (empty($articlesHtml)) {
                $articlesHtml = '<p style="text-align: center; color: var(--gray-500);">Aucun article publié pour le moment.</p>';
            }

            $replacements = [
                '{{meta_title}}' => 'Blog - Actualités Taxi | Taxi Julien Martigues',
                '{{meta_description}}' => 'Retrouvez nos conseils et actualités sur le transport en taxi à Martigues.',
                '{{canonical}}' => $this->siteUrl . '/blog.html',
                '{{articles}}' => $articlesHtml,
                '{{site_name}}' => $this->e($this->siteConfig['site_name'] ?? 'Taxi Julien'),
                '{{phone}}' => $this->e($this->siteConfig['phone'] ?? ''),
                '{{phone_link}}' => $this->e($this->siteConfig['phone_link'] ?? ''),
                '{{email}}' => $this->e($this->siteConfig['email'] ?? ''),
                '{{address}}' => $this->e($this->siteConfig['address'] ?? ''),
                '{{hours}}' => $this->e($this->siteConfig['hours'] ?? ''),
                '{{whatsapp}}' => $this->e($this->siteConfig['whatsapp'] ?? ''),
                '{{current_year}}' => date('Y'),
            ];

            $html = str_replace(array_keys($replacements), array_values($replacements), $template);

            if (file_put_contents($this->outputDir . 'blog.html', $html) !== false) {
                $result['success'] = true;
                $result['message'] = 'Page blog mise à jour';
            } else {
                $result['message'] = 'Erreur lors de l\'écriture';
            }
        } catch (Exception $e) {
            $result['message'] = 'Erreur: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Rendre une carte d'article de blog
     */
    private function renderBlogCard(array $post): string {
        $date = date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now'));

        $html = '<article class="blog-card">' . "\n";

        if (!empty($post['featured_image'])) {
            $html .= '    <a href="blog/' . $this->e($post['slug']) . '.html" class="blog-card-image">' . "\n";
            $html .= '        <img src="' . $this->e($post['featured_image']) . '" alt="' . $this->e($post['title']) . '">' . "\n";
            $html .= '    </a>' . "\n";
        }

        $html .= '    <div class="blog-card-content">' . "\n";
        $html .= '        <span class="blog-card-date">' . $date . '</span>' . "\n";
        $html .= '        <h3 class="blog-card-title"><a href="blog/' . $this->e($post['slug']) . '.html">' . $this->e($post['title']) . '</a></h3>' . "\n";

        if (!empty($post['excerpt'])) {
            $html .= '        <p class="blog-card-excerpt">' . $this->e($post['excerpt']) . '</p>' . "\n";
        }

        $html .= '        <a href="blog/' . $this->e($post['slug']) . '.html" class="blog-card-link">Lire la suite →</a>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '</article>' . "\n";

        return $html;
    }

    /**
     * Supprimer une page
     */
    public function deletePage(string $slug): bool {
        $filename = $this->outputDir . $slug . '.html';
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }

    /**
     * Supprimer un article de blog
     */
    public function deleteBlogPost(string $slug): bool {
        $filename = $this->outputDir . 'blog/' . $slug . '.html';
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }

    /**
     * Régénérer toutes les pages publiées
     */
    public function regenerateAllPages(): array {
        $results = [];

        $pagesResult = supabase()->select('pages', 'status=eq.published');
        if (($pagesResult['success'] ?? false) && !empty($pagesResult['data'])) {
            foreach ($pagesResult['data'] as $page) {
                $sectionsResult = supabase()->select('page_sections', 'page_id=eq.' . $page['id'] . '&order=display_order.asc');
                $sections = ($sectionsResult['success'] ?? false) ? ($sectionsResult['data'] ?? []) : [];

                $results[$page['slug']] = $this->generatePage($page, $sections);
            }
        }

        return $results;
    }

    /**
     * Régénérer tous les articles de blog publiés
     */
    public function regenerateAllBlogPosts(): array {
        $results = [];

        $postsResult = supabase()->select('blog_posts', 'status=eq.published');
        if (($postsResult['success'] ?? false) && !empty($postsResult['data'])) {
            foreach ($postsResult['data'] as $post) {
                $results[$post['slug']] = $this->generateBlogPost($post);
            }
        }

        $results['blog_list'] = $this->updateBlogList();

        return $results;
    }

    /**
     * Sauvegarder la configuration du site
     */
    public function saveSiteConfig(array $config): bool {
        $this->siteConfig = array_merge($this->siteConfig, $config);
        $configFile = __DIR__ . '/../../data/site-config.json';
        return file_put_contents($configFile, json_encode($this->siteConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    /**
     * Échapper HTML
     */
    private function e(?string $str): string {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Fonction helper pour obtenir une instance du générateur
 */
function pageGenerator(): PageGenerator {
    static $instance = null;
    if ($instance === null) {
        $instance = new PageGenerator();
    }
    return $instance;
}
