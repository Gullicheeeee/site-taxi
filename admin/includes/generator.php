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

            // Construire le hero
            $heroHtml = $this->buildHeroHtml($page);

            // Préparer les données de remplacement
            $replacements = [
                '{{meta_title}}' => $this->e($page['meta_title'] ?: $page['title'] . ($this->siteConfig['seo']['title_suffix'] ?? '')),
                '{{meta_description}}' => $this->e($page['meta_description'] ?? ''),
                '{{meta_keywords}}' => $this->e($page['meta_keywords'] ?? ''),
                '{{canonical}}' => $this->siteUrl . '/' . $page['slug'] . '.html',
                '{{og_title}}' => $this->e($page['meta_title'] ?: $page['title']),
                '{{og_description}}' => $this->e($page['meta_description'] ?? ''),
                '{{og_image}}' => $page['hero_image'] ?: ($this->siteUrl . ($this->siteConfig['seo']['default_og_image'] ?? '/images/og-image.jpg')),
                '{{og_url}}' => $this->siteUrl . '/' . $page['slug'] . '.html',
                '{{page_title}}' => $this->e($page['title']),
                '{{hero_title}}' => $this->e($page['hero_title'] ?? $page['title']),
                '{{hero_subtitle}}' => $this->e($page['hero_subtitle'] ?? ''),
                '{{hero_image}}' => $page['hero_image'] ?? '',
                '{{hero_section}}' => $heroHtml,
                '{{content}}' => $sectionsHtml,
                '{{slug}}' => $page['slug'],
                '{{current_year}}' => date('Y'),
                '{{updated_at}}' => date('Y-m-d'),
                // Config du site
                '{{site_name}}' => $this->e($this->siteConfig['site_name'] ?? 'Taxi Julien'),
                '{{phone}}' => $this->e($this->siteConfig['phone'] ?? ''),
                '{{phone_link}}' => $this->e($this->siteConfig['phone_link'] ?? ''),
                '{{email}}' => $this->e($this->siteConfig['email'] ?? ''),
                '{{address}}' => $this->e($this->siteConfig['address'] ?? ''),
                '{{hours}}' => $this->e($this->siteConfig['hours'] ?? ''),
                '{{whatsapp}}' => $this->e($this->siteConfig['whatsapp'] ?? ''),
            ];

            // Appliquer les remplacements
            $html = str_replace(array_keys($replacements), array_values($replacements), $template);

            // Inclure header et footer
            $html = $this->includePartials($html, $page['slug']);

            // Générer le schema JSON-LD
            $schema = $this->generateSchema($page);
            $html = str_replace('{{schema_json}}', $schema, $html);

            // Écrire le fichier
            $filename = $this->outputDir . $page['slug'] . '.html';
            if (file_put_contents($filename, $html) !== false) {
                $result['success'] = true;
                $result['message'] = 'Page générée avec succès';
                $result['file'] = $page['slug'] . '.html';
            } else {
                $result['message'] = 'Erreur lors de l\'écriture du fichier';
            }
        } catch (Exception $e) {
            $result['message'] = 'Erreur: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Construire le HTML du hero
     */
    private function buildHeroHtml(array $page): string {
        if (empty($page['hero_title'])) {
            return '';
        }

        $heroTitle = $this->e($page['hero_title']);
        $heroSubtitle = $this->e($page['hero_subtitle'] ?? '');

        $html = '<section class="hero">';
        $html .= '<div class="container hero-content">';
        $html .= '<h1>' . $heroTitle . '</h1>';

        if ($heroSubtitle) {
            $html .= '<p class="hero-subtitle">' . $heroSubtitle . '</p>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
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
                case 'hero':
                    $html .= $this->renderHeroSection($section, $content);
                    break;
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
                case 'image_text':
                    $html .= $this->renderImageTextSection($section, $content);
                    break;
                case 'contact_info':
                    $html .= $this->renderContactInfoSection($section, $content);
                    break;
                default:
                    $html .= $this->renderTextSection($section, $content);
            }
        }

        return $html;
    }

    /**
     * Rendre une section Hero
     */
    private function renderHeroSection(array $section, array $content): string {
        $html = '<section class="hero">';
        $html .= '<div class="container hero-content">';

        if (!empty($content['title'])) {
            $html .= '<h1>' . $this->e($content['title']) . '</h1>';
        }

        if (!empty($content['subtitle'])) {
            $html .= '<p class="hero-subtitle">' . $this->e($content['subtitle']) . '</p>';
        }

        // Badges
        if (!empty($content['badges']) && is_array($content['badges'])) {
            $html .= '<div class="badges-container">';
            foreach ($content['badges'] as $badge) {
                $html .= '<span class="badge">' . $this->e($badge) . '</span>';
            }
            $html .= '</div>';
        }

        // CTAs
        if (!empty($content['cta_primary_text']) || !empty($content['cta_secondary_text'])) {
            $html .= '<div class="cta-buttons">';
            if (!empty($content['cta_primary_text'])) {
                $html .= '<a href="' . $this->e($content['cta_primary_url'] ?? '#') . '" class="btn btn-primary btn-large">' . $this->e($content['cta_primary_text']) . '</a>';
            }
            if (!empty($content['cta_secondary_text'])) {
                $html .= '<a href="' . $this->e($content['cta_secondary_url'] ?? '#') . '" class="btn btn-secondary btn-large">' . $this->e($content['cta_secondary_text']) . '</a>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Rendre une section de cartes
     */
    private function renderCardsSection(array $section, array $content): string {
        $html = '<section class="section">';
        $html .= '<div class="container">';

        if (!empty($content['title']) || !empty($content['subtitle'])) {
            $html .= '<div class="section-title">';
            if (!empty($content['title'])) {
                $html .= '<h2>' . $this->e($content['title']) . '</h2>';
            }
            if (!empty($content['subtitle'])) {
                $html .= '<p>' . $this->e($content['subtitle']) . '</p>';
            }
            $html .= '</div>';
        }

        if (!empty($content['items']) && is_array($content['items'])) {
            $html .= '<div class="cards-grid">';
            foreach ($content['items'] as $item) {
                $html .= '<div class="card">';
                if (!empty($item['icon'])) {
                    $html .= '<span class="card-icon">' . $this->e($item['icon']) . '</span>';
                }
                if (!empty($item['title'])) {
                    $html .= '<h3 class="card-title">' . $this->e($item['title']) . '</h3>';
                }
                if (!empty($item['text'])) {
                    $html .= '<p class="card-text">' . $this->e($item['text']) . '</p>';
                }
                if (!empty($item['link_url']) && !empty($item['link_text'])) {
                    $html .= '<a href="' . $this->e($item['link_url']) . '" style="color: var(--accent-dark); font-weight: 600;">' . $this->e($item['link_text']) . '</a>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Rendre une section Features
     */
    private function renderFeaturesSection(array $section, array $content): string {
        $html = '<section class="section features">';
        $html .= '<div class="container">';

        if (!empty($content['title'])) {
            $html .= '<div class="section-title">';
            $html .= '<h2>' . $this->e($content['title']) . '</h2>';
            $html .= '</div>';
        }

        if (!empty($content['items']) && is_array($content['items'])) {
            $html .= '<div class="cards-grid">';
            foreach ($content['items'] as $item) {
                $html .= '<div class="feature-item">';
                if (!empty($item['icon'])) {
                    $html .= '<span class="feature-icon">' . $this->e($item['icon']) . '</span>';
                }
                $html .= '<div class="feature-content">';
                if (!empty($item['title'])) {
                    $html .= '<h3>' . $this->e($item['title']) . '</h3>';
                }
                if (!empty($item['text'])) {
                    $html .= '<p>' . $this->e($item['text']) . '</p>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Rendre une section de texte
     */
    private function renderTextSection(array $section, array $content): string {
        $html = '<section class="section">';
        $html .= '<div class="container">';

        if (!empty($content['title'])) {
            $html .= '<div class="section-title">';
            $html .= '<h2>' . $this->e($content['title']) . '</h2>';
            $html .= '</div>';
        }

        if (!empty($content['text'])) {
            $html .= '<div class="content-block">';
            // Le texte peut contenir du HTML formaté par l'éditeur
            $html .= $content['text'];
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Rendre une section CTA
     */
    private function renderCtaSection(array $section, array $content): string {
        $bgClass = ($content['background'] ?? 'primary') === 'primary'
            ? 'style="background: var(--primary); color: white; text-align: center;"'
            : 'style="background: var(--light-gray); text-align: center;"';

        $html = '<section class="section" ' . $bgClass . '>';
        $html .= '<div class="container">';

        if (!empty($content['title'])) {
            $textColor = ($content['background'] ?? 'primary') === 'primary' ? ' style="color: white;"' : '';
            $html .= '<h2' . $textColor . '>' . $this->e($content['title']) . '</h2>';
        }

        if (!empty($content['subtitle'])) {
            $html .= '<p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.95;">' . $this->e($content['subtitle']) . '</p>';
        }

        if (!empty($content['cta_primary_text']) || !empty($content['cta_secondary_text'])) {
            $html .= '<div class="cta-buttons">';
            if (!empty($content['cta_primary_text'])) {
                $html .= '<a href="' . $this->e($content['cta_primary_url'] ?? '#') . '" class="btn btn-primary btn-large">' . $this->e($content['cta_primary_text']) . '</a>';
            }
            if (!empty($content['cta_secondary_text'])) {
                $html .= '<a href="' . $this->e($content['cta_secondary_url'] ?? '#') . '" class="btn btn-secondary btn-large">' . $this->e($content['cta_secondary_text']) . '</a>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Rendre une section liste
     */
    private function renderListSection(array $section, array $content): string {
        $html = '<section class="section">';
        $html .= '<div class="container">';

        if (!empty($content['title']) || !empty($content['subtitle'])) {
            $html .= '<div class="section-title">';
            if (!empty($content['title'])) {
                $html .= '<h2>' . $this->e($content['title']) . '</h2>';
            }
            if (!empty($content['subtitle'])) {
                $html .= '<p>' . $this->e($content['subtitle']) . '</p>';
            }
            $html .= '</div>';
        }

        if (!empty($content['items']) && is_array($content['items'])) {
            $html .= '<div style="background: white; padding: 2rem; border-radius: 15px; box-shadow: var(--shadow-md); max-width: 800px; margin: 0 auto;">';
            $html .= '<ul style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; list-style: none;">';
            foreach ($content['items'] as $item) {
                $text = $item['title'] ?? $item['text'] ?? '';
                $icon = $item['icon'] ?? '✓';
                $html .= '<li style="display: flex; align-items: center; gap: 0.5rem;">';
                $html .= '<span style="color: var(--secondary-color);">' . $this->e($icon) . '</span> ' . $this->e($text);
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Rendre une section Image + Texte
     */
    private function renderImageTextSection(array $section, array $content): string {
        $position = $content['image_position'] ?? 'left';
        $flexDirection = $position === 'right' ? 'row-reverse' : 'row';

        $html = '<section class="section">';
        $html .= '<div class="container">';
        $html .= '<div style="display: flex; flex-direction: ' . $flexDirection . '; gap: 3rem; align-items: center; flex-wrap: wrap;">';

        // Image
        if (!empty($section['image'])) {
            $html .= '<div style="flex: 1; min-width: 300px;">';
            $html .= '<img src="' . $this->e($section['image']) . '" alt="' . $this->e($content['title'] ?? '') . '" style="width: 100%; border-radius: 15px; box-shadow: var(--shadow-lg);">';
            $html .= '</div>';
        }

        // Texte
        $html .= '<div style="flex: 1; min-width: 300px;">';
        if (!empty($content['title'])) {
            $html .= '<h2>' . $this->e($content['title']) . '</h2>';
        }
        if (!empty($content['text'])) {
            $html .= '<div class="content-block">' . $content['text'] . '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Rendre une section infos de contact
     */
    private function renderContactInfoSection(array $section, array $content): string {
        $html = '<section class="section">';
        $html .= '<div class="container">';
        $html .= '<div style="background: white; padding: 2rem; border-radius: 15px; box-shadow: var(--shadow-md); max-width: 600px; margin: 0 auto;">';

        if (!empty($content['phone'])) {
            $html .= '<p style="margin-bottom: 1rem;"><strong>Téléphone :</strong> <a href="tel:' . preg_replace('/\s+/', '', $content['phone']) . '">' . $this->e($content['phone']) . '</a></p>';
        }
        if (!empty($content['email'])) {
            $html .= '<p style="margin-bottom: 1rem;"><strong>Email :</strong> <a href="mailto:' . $this->e($content['email']) . '">' . $this->e($content['email']) . '</a></p>';
        }
        if (!empty($content['address'])) {
            $html .= '<p style="margin-bottom: 1rem;"><strong>Adresse :</strong> ' . $this->e($content['address']) . '</p>';
        }
        if (!empty($content['hours'])) {
            $html .= '<p><strong>Horaires :</strong> ' . $this->e($content['hours']) . '</p>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';

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
     * Inclure les partials (header, footer)
     */
    private function includePartials(string $html, string $currentSlug): string {
        // Header
        $header = $this->loadTemplate('partials/header.html');
        if ($header) {
            // Marquer le lien actif
            $header = $this->setActiveNavLink($header, $currentSlug);
            $html = str_replace('{{> header}}', $header, $html);
        }

        // Footer
        $footer = $this->loadTemplate('partials/footer.html');
        if ($footer) {
            $footer = str_replace([
                '{{phone}}',
                '{{phone_link}}',
                '{{email}}',
                '{{address}}',
                '{{hours}}',
                '{{whatsapp}}',
                '{{current_year}}'
            ], [
                $this->e($this->siteConfig['phone'] ?? ''),
                $this->e($this->siteConfig['phone_link'] ?? ''),
                $this->e($this->siteConfig['email'] ?? ''),
                $this->e($this->siteConfig['address'] ?? ''),
                $this->e($this->siteConfig['hours'] ?? ''),
                $this->e($this->siteConfig['whatsapp'] ?? ''),
                date('Y')
            ], $footer);
            $html = str_replace('{{> footer}}', $footer, $html);
        }

        return $html;
    }

    /**
     * Définir le lien de navigation actif
     */
    private function setActiveNavLink(string $header, string $slug): string {
        // Retirer tous les "active" existants
        $header = str_replace(' active', '', $header);

        // Mapper les slugs aux patterns de navigation
        $navMap = [
            'index' => 'href="index.html"',
            'services' => 'href="services.html"',
            'conventionné' => 'href="services.html"',
            'aeroports-gares' => 'href="services.html"',
            'longues-distances' => 'href="services.html"',
            'courses-classiques' => 'href="services.html"',
            'mise-a-disposition' => 'href="services.html"',
            'simulateur' => 'href="simulateur.html"',
            'blog' => 'href="blog.html"',
            'a-propos' => 'href="a-propos.html"',
            'contact' => 'href="contact.html"',
        ];

        if (isset($navMap[$slug])) {
            $pattern = $navMap[$slug];
            $header = str_replace(
                $pattern . ' class="nav-link"',
                $pattern . ' class="nav-link active"',
                $header
            );
        }

        return $header;
    }

    /**
     * Générer le schema JSON-LD
     */
    private function generateSchema(array $page): string {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $page['meta_title'] ?: $page['title'],
            'description' => $page['meta_description'] ?? '',
            'url' => $this->siteUrl . '/' . $page['slug'] . '.html',
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'Taxi Julien',
                'url' => $this->siteUrl
            ]
        ];

        // Breadcrumb
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
                    'item' => $this->siteUrl . '/' . $page['slug'] . '.html'
                ]
            ]
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n"
             . '    <script type="application/ld+json">' . json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
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

            $replacements = [
                '{{meta_title}}' => $this->e($post['meta_title'] ?: $post['title'] . ($this->siteConfig['seo']['title_suffix'] ?? '')),
                '{{meta_description}}' => $this->e($post['meta_description'] ?? $post['excerpt'] ?? ''),
                '{{canonical}}' => $this->siteUrl . '/blog/' . $post['slug'] . '.html',
                '{{og_title}}' => $this->e($post['meta_title'] ?: $post['title']),
                '{{og_description}}' => $this->e($post['meta_description'] ?? $post['excerpt'] ?? ''),
                '{{og_image}}' => $post['featured_image'] ?? ($this->siteUrl . ($this->siteConfig['seo']['default_og_image'] ?? '/images/og-image.jpg')),
                '{{og_url}}' => $this->siteUrl . '/blog/' . $post['slug'] . '.html',
                '{{title}}' => $this->e($post['title']),
                '{{content}}' => $post['content'] ?? '',
                '{{excerpt}}' => $this->e($post['excerpt'] ?? ''),
                '{{featured_image}}' => $post['featured_image'] ?? '',
                '{{author}}' => $this->e($post['author_name'] ?? 'Taxi Julien'),
                '{{published_date}}' => date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now')),
                '{{published_iso}}' => date('c', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now')),
                '{{slug}}' => $post['slug'],
                '{{current_year}}' => date('Y'),
                '{{site_name}}' => $this->e($this->siteConfig['site_name'] ?? 'Taxi Julien'),
                '{{phone}}' => $this->e($this->siteConfig['phone'] ?? ''),
                '{{phone_link}}' => $this->e($this->siteConfig['phone_link'] ?? ''),
                '{{email}}' => $this->e($this->siteConfig['email'] ?? ''),
                '{{address}}' => $this->e($this->siteConfig['address'] ?? ''),
                '{{hours}}' => $this->e($this->siteConfig['hours'] ?? ''),
                '{{whatsapp}}' => $this->e($this->siteConfig['whatsapp'] ?? ''),
            ];

            $html = str_replace(array_keys($replacements), array_values($replacements), $template);
            $html = $this->includePartials($html, 'blog');

            // Schema pour l'article
            $articleSchema = $this->generateArticleSchema($post);
            $html = str_replace('{{schema_json}}', $articleSchema, $html);

            // S'assurer que le dossier blog existe
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
     * Générer le schema JSON-LD pour un article
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
            ],
            'mainEntityOfPage' => $this->siteUrl . '/blog/' . $post['slug'] . '.html'
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Mettre à jour la page de liste du blog
     */
    public function updateBlogList(): array {
        $result = ['success' => false, 'message' => ''];

        try {
            // Récupérer tous les articles publiés
            $postsResult = supabase()->select('blog_posts', 'status=eq.published&order=published_at.desc');
            if (!$postsResult['success']) {
                $result['message'] = 'Erreur lors de la récupération des articles';
                return $result;
            }

            $posts = $postsResult['data'] ?? [];

            $template = $this->loadTemplate('blog-list.html');
            if (!$template) {
                $result['message'] = 'Template blog-list.html introuvable';
                return $result;
            }

            // Construire la liste des articles
            $articlesHtml = '';
            foreach ($posts as $post) {
                $articlesHtml .= $this->renderBlogCard($post);
            }

            $replacements = [
                '{{meta_title}}' => 'Blog - Actualités Taxi Martigues' . ($this->siteConfig['seo']['title_suffix'] ?? ''),
                '{{meta_description}}' => 'Retrouvez tous nos conseils et actualités sur le transport en taxi à Martigues : transport médical, aéroports, astuces voyage.',
                '{{canonical}}' => $this->siteUrl . '/blog.html',
                '{{articles}}' => $articlesHtml,
                '{{articles_count}}' => count($posts),
                '{{current_year}}' => date('Y'),
                '{{site_name}}' => $this->e($this->siteConfig['site_name'] ?? 'Taxi Julien'),
                '{{phone}}' => $this->e($this->siteConfig['phone'] ?? ''),
                '{{phone_link}}' => $this->e($this->siteConfig['phone_link'] ?? ''),
                '{{email}}' => $this->e($this->siteConfig['email'] ?? ''),
                '{{address}}' => $this->e($this->siteConfig['address'] ?? ''),
                '{{hours}}' => $this->e($this->siteConfig['hours'] ?? ''),
                '{{whatsapp}}' => $this->e($this->siteConfig['whatsapp'] ?? ''),
            ];

            $html = str_replace(array_keys($replacements), array_values($replacements), $template);
            $html = $this->includePartials($html, 'blog');

            $filename = $this->outputDir . 'blog.html';
            if (file_put_contents($filename, $html) !== false) {
                $result['success'] = true;
                $result['message'] = 'Page blog mise à jour';
            } else {
                $result['message'] = 'Erreur lors de l\'écriture du fichier';
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
        $html = '<article class="blog-card">';

        if (!empty($post['featured_image'])) {
            $html .= '<a href="blog/' . $this->e($post['slug']) . '.html" class="blog-card-image">';
            $html .= '<img src="' . $this->e($post['featured_image']) . '" alt="' . $this->e($post['title']) . '">';
            $html .= '</a>';
        }

        $html .= '<div class="blog-card-content">';
        $html .= '<span class="blog-card-date">' . $date . '</span>';
        $html .= '<h3 class="blog-card-title"><a href="blog/' . $this->e($post['slug']) . '.html">' . $this->e($post['title']) . '</a></h3>';

        if (!empty($post['excerpt'])) {
            $html .= '<p class="blog-card-excerpt">' . $this->e($post['excerpt']) . '</p>';
        }

        $html .= '<a href="blog/' . $this->e($post['slug']) . '.html" class="blog-card-link">Lire la suite &rarr;</a>';
        $html .= '</div>';
        $html .= '</article>';

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

        // Récupérer toutes les pages publiées
        $pagesResult = supabase()->select('pages', 'status=eq.published');
        if ($pagesResult['success'] && !empty($pagesResult['data'])) {
            foreach ($pagesResult['data'] as $page) {
                // Récupérer les sections
                $sectionsResult = supabase()->select('page_sections', 'page_id=eq.' . $page['id'] . '&order=display_order.asc');
                $sections = $sectionsResult['success'] ? $sectionsResult['data'] : [];

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
        if ($postsResult['success'] && !empty($postsResult['data'])) {
            foreach ($postsResult['data'] as $post) {
                $results[$post['slug']] = $this->generateBlogPost($post);
            }
        }

        // Mettre à jour la liste du blog
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
