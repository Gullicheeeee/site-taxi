-- =====================================================
-- IMPORT DES DONNÉES EXISTANTES DANS SUPABASE
-- Exécuter ce script APRÈS le schéma initial
-- =====================================================

-- Nettoyer les tables existantes
TRUNCATE TABLE page_sections CASCADE;
TRUNCATE TABLE pages CASCADE;
TRUNCATE TABLE blog_posts CASCADE;

-- =====================================================
-- PAGES PRINCIPALES
-- =====================================================

INSERT INTO pages (id, slug, title, hero_title, hero_subtitle, hero_image, meta_title, meta_description, meta_keywords) VALUES

-- Page Accueil
('11111111-1111-1111-1111-111111111111', 'index', 'Accueil',
 'Votre Taxi à Martigues',
 'Transport conventionné CPAM et tous vos déplacements',
 NULL,
 'Taxi Julien Martigues - Taxi Conventionné CPAM - Service 24/7',
 'Taxi conventionné CPAM à Martigues. Agréé Sécurité Sociale pour vos trajets médicaux. Transferts aéroports, gares, longues distances. Service 24/7.',
 'taxi martigues, taxi conventionné, cpam, transport médical, taxi aéroport'),

-- Page Services
('22222222-2222-2222-2222-222222222222', 'services', 'Services',
 'Tous Nos Services',
 'Un service adapté à chacun de vos besoins de transport',
 NULL,
 'Nos Services - Taxi Julien Martigues',
 'Tous les services de Taxi Julien à Martigues : transport conventionné CPAM, aéroports, longues distances, courses classiques, mise à disposition. Service 24/7.',
 'services taxi, transport conventionné, aéroport, longues distances'),

-- Page Transport Conventionné
('33333333-3333-3333-3333-333333333333', 'conventionne', 'Transport Conventionné',
 'Transport Conventionné CPAM',
 'Vos trajets médicaux pris en charge et remboursés par la Sécurité Sociale',
 NULL,
 'Taxi Conventionné CPAM - Taxi Julien Martigues',
 'Transport conventionné CPAM à Martigues. Taxi agréé Sécurité Sociale pour vos trajets médicaux. Remboursement garanti.',
 'taxi conventionné, cpam, sécurité sociale, transport médical, remboursement'),

-- Page Aéroports & Gares
('44444444-4444-4444-4444-444444444444', 'aeroports-gares', 'Aéroports & Gares',
 'Transferts Aéroports & Gares',
 'Voyagez sereinement vers toutes les destinations de la région',
 NULL,
 'Transferts Aéroports & Gares - Taxi Julien Martigues',
 'Transferts aéroports et gares depuis Martigues. Marseille Provence, Aix TGV, Saint-Charles. Tarifs forfaitaires avantageux.',
 'transfert aéroport, gare, marseille provence, aix tgv, saint-charles'),

-- Page Longues Distances
('55555555-5555-5555-5555-555555555555', 'longues-distances', 'Longues Distances',
 'Trajets Longue Distance',
 'Voyagez confortablement partout en France',
 NULL,
 'Longues Distances - Taxi Julien Martigues',
 'Trajets longue distance en taxi depuis Martigues. Nice, Lyon, Toulouse, Paris. Confort optimal, tarifs négociés.',
 'longue distance, nice, lyon, paris, toulouse, trajet france'),

-- Page Courses Classiques
('66666666-6666-6666-6666-666666666666', 'courses-classiques', 'Courses Classiques',
 'Courses Classiques',
 'Vos trajets quotidiens en toute simplicité',
 NULL,
 'Courses Classiques - Taxi Julien Martigues',
 'Courses de taxi classiques à Martigues et environs. Trajets locaux, sorties nocturnes, déplacements professionnels. Service 24/7.',
 'courses taxi, trajets locaux, sorties nocturnes, déplacements'),

-- Page Mise à Disposition
('77777777-7777-7777-7777-777777777777', 'mise-a-disposition', 'Mise à Disposition',
 'Mise à Disposition',
 'Un chauffeur dédié pour toute votre journée',
 NULL,
 'Mise à Disposition - Taxi Julien Martigues',
 'Mise à disposition de taxi à l''heure à Martigues. Chauffeur dédié pour vos journées professionnelles, tourisme, événements.',
 'mise à disposition, chauffeur dédié, journée, tourisme, événements'),

-- Page À Propos
('88888888-8888-8888-8888-888888888888', 'a-propos', 'À Propos',
 'À Propos de Taxi Julien',
 'Votre partenaire transport à Martigues depuis plus de 10 ans',
 NULL,
 'À Propos - Taxi Julien Martigues',
 'Découvrez Taxi Julien, votre taxi conventionné à Martigues. Professionnel, fiable et au service de la région depuis plus de 10 ans.',
 'taxi julien, martigues, expérience, professionnel'),

-- Page Contact
('99999999-9999-9999-9999-999999999999', 'contact', 'Contact',
 'Contactez-nous',
 'Nous sommes à votre écoute 24h/24',
 NULL,
 'Contact - Taxi Julien Martigues',
 'Contactez Taxi Julien à Martigues. Téléphone, email, WhatsApp. Service disponible 24h/24 et 7j/7.',
 'contact, téléphone, email, whatsapp, réservation'),

-- Page Simulateur
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'simulateur', 'Simulateur',
 'Simulateur de Prix',
 'Estimez le coût de votre trajet en quelques clics',
 NULL,
 'Simulateur de Prix - Taxi Julien Martigues',
 'Calculez le tarif de votre course taxi à Martigues. Simulateur de prix en ligne. Tarifs réglementaires.',
 'simulateur, prix, tarif, estimation, calcul'),

-- Page Réservation
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 'reservation', 'Réservation',
 'Réserver un Taxi',
 'Réservez votre trajet en quelques minutes',
 NULL,
 'Réservation - Taxi Julien Martigues',
 'Réservez votre taxi à Martigues en ligne. Service simple et rapide. Confirmation immédiate.',
 'réservation, réserver, taxi, en ligne'),

-- Page Blog
('cccccccc-cccc-cccc-cccc-cccccccccccc', 'blog', 'Blog',
 'Notre Blog',
 'Actualités et conseils sur le transport',
 NULL,
 'Blog - Taxi Julien Martigues',
 'Blog Taxi Julien : actualités, conseils transport, informations pratiques sur les services de taxi à Martigues.',
 'blog, actualités, conseils, transport');

-- =====================================================
-- SECTIONS PAGE SERVICES
-- =====================================================

INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order, is_visible) VALUES

-- Services - Cartes principales
('22222222-2222-2222-2222-222222222222', 'services_cards', 'cards',
 'Découvrez Nos Services',
 '{"title": "Découvrez Nos Services", "subtitle": "Cliquez sur un service pour en savoir plus", "items": [
   {"icon": "🏥", "title": "Transport Conventionné", "text": "Service agréé CPAM pour vos trajets médicaux. Remboursement selon vos droits.", "link_url": "conventionne.html", "link_text": "En savoir plus →"},
   {"icon": "✈️", "title": "Aéroports & Gares", "text": "Transferts vers Marseille Provence, Aix TGV, Saint-Charles. Tarifs forfaitaires.", "link_url": "aeroports-gares.html", "link_text": "Voir les tarifs →"},
   {"icon": "🗺️", "title": "Longues Distances", "text": "Trajets partout en France. Nice, Lyon, Toulouse, Paris. Confort optimal.", "link_url": "longues-distances.html", "link_text": "Demander un devis →"},
   {"icon": "🚖", "title": "Courses Classiques", "text": "Vos trajets quotidiens locaux. Disponible 24h/24, 7j/7.", "link_url": "courses-classiques.html", "link_text": "Tous les détails →"},
   {"icon": "🕐", "title": "Mise à Disposition", "text": "Chauffeur dédié pour plusieurs heures ou une journée complète.", "link_url": "mise-a-disposition.html", "link_text": "Découvrir le service →"}
 ]}',
 1, true),

-- Services - Avantages
('22222222-2222-2222-2222-222222222222', 'services_avantages', 'cards',
 'Ce Que Vous Trouverez Dans Tous Nos Services',
 '{"title": "Ce Que Vous Trouverez Dans Tous Nos Services", "items": [
   {"icon": "⭐", "title": "Professionnalisme", "text": "Chauffeur expérimenté, courtois et discret. Service irréprochable à chaque course."},
   {"icon": "🚗", "title": "Véhicule Premium", "text": "Taxi récent, propre, climatisé. Entretien régulier et contrôle technique à jour."},
   {"icon": "🔒", "title": "Sécurité", "text": "Assurance professionnelle complète, respect du code de la route, conduite sécurisée."},
   {"icon": "⏱️", "title": "Ponctualité", "text": "Respect strict des horaires, itinéraires optimisés, vous arrivez à l''heure."},
   {"icon": "💰", "title": "Tarifs Clairs", "text": "Prix conformes à la réglementation, pas de surprise, reçu fourni systématiquement."},
   {"icon": "📱", "title": "Disponibilité", "text": "Service 24h/24, 7j/7. Réservation simple par téléphone, WhatsApp ou en ligne."}
 ]}',
 2, true),

-- Services - Zone intervention
('22222222-2222-2222-2222-222222222222', 'services_zone', 'list',
 'Zone d''Intervention',
 '{"title": "Zone d''Intervention", "subtitle": "Basé à Martigues, nous intervenons dans toute la région", "items": [
   {"title": "Martigues, Port-de-Bouc, Fos-sur-Mer, Istres, Saint-Mitre"},
   {"title": "Marseille, Aix-en-Provence, Salon-de-Provence, Arles, Aubagne"},
   {"title": "Tout le département 13, Var (83), Vaucluse (84), Gard (30)"},
   {"title": "Toute la France sur demande"}
 ]}',
 3, true),

-- Services - CTA
('22222222-2222-2222-2222-222222222222', 'services_cta', 'cta',
 'Prêt à Réserver ?',
 '{"title": "Prêt à Réserver ?", "subtitle": "Choisissez le service adapté à votre besoin", "background": "primary", "cta_primary_text": "Réserver en Ligne", "cta_primary_url": "reservation.html", "cta_secondary_text": "📞 01 23 45 67 89", "cta_secondary_url": "tel:+33123456789"}',
 4, true);

-- =====================================================
-- SECTIONS PAGE CONVENTIONNÉ
-- =====================================================

INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order, is_visible) VALUES

-- Conventionné - Explication
('33333333-3333-3333-3333-333333333333', 'conv_explication', 'text',
 'Qu''est-ce qu''un Taxi Conventionné ?',
 '{"title": "Qu''est-ce qu''un Taxi Conventionné ?", "text": "Un taxi conventionné est un taxi agréé par la Sécurité Sociale (CPAM) pour effectuer des transports médicaux remboursables. Taxi Julien dispose de cet agrément et peut vous transporter pour vos rendez-vous médicaux avec prise en charge par l''Assurance Maladie.\n\nAvantage : Vous n''avancez que la part non remboursée. Le reste est directement facturé à la CPAM selon vos droits."}',
 1, true),

-- Conventionné - Cas d'usage
('33333333-3333-3333-3333-333333333333', 'conv_cas', 'cards',
 'Dans Quels Cas Utiliser un Taxi Conventionné ?',
 '{"title": "Dans Quels Cas Utiliser un Taxi Conventionné ?", "items": [
   {"icon": "🏥", "title": "Consultations Médicales", "text": "Rendez-vous chez le médecin généraliste, spécialiste, kiné, dentiste, etc."},
   {"icon": "🏨", "title": "Hospitalisation", "text": "Transport vers un hôpital, clinique, centre de soins ou établissement médical."},
   {"icon": "💉", "title": "Examens Médicaux", "text": "Scanner, IRM, prise de sang, radiologie, analyses médicales, etc."},
   {"icon": "🩺", "title": "Traitements Réguliers", "text": "Dialyse, chimiothérapie, radiothérapie, rééducation fonctionnelle."},
   {"icon": "🚑", "title": "Retour d''Hospitalisation", "text": "Retour à domicile après une hospitalisation ou une intervention."},
   {"icon": "🏠", "title": "Soins à Domicile", "text": "Transport pour soins infirmiers à domicile, HAD (hospitalisation à domicile)."}
 ]}',
 2, true),

-- Conventionné - Documents
('33333333-3333-3333-3333-333333333333', 'conv_documents', 'list',
 'Documents à Fournir',
 '{"title": "Documents à Fournir", "items": [
   {"icon": "📋", "title": "Prescription médicale de transport - Délivrée par votre médecin"},
   {"icon": "💳", "title": "Carte Vitale à jour - Pour la télétransmission à la CPAM"},
   {"icon": "🪪", "title": "Pièce d''identité - Carte d''identité, passeport ou titre de séjour"},
   {"icon": "🏥", "title": "Attestation de droits (si applicable) - ALD, CMU-C, ou autre document"}
 ]}',
 3, true),

-- Conventionné - Remboursement
('33333333-3333-3333-3333-333333333333', 'conv_remboursement', 'features',
 'Taux de Remboursement',
 '{"title": "Taux de Remboursement", "items": [
   {"icon": "📊", "title": "Remboursement Standard : 65%", "text": "Reste à charge : 35% (pris en charge par votre mutuelle selon votre contrat)"},
   {"icon": "💯", "title": "Remboursement à 100%", "text": "Si vous êtes en ALD, bénéficiaire CMU-C/CSS, femme enceinte (6e mois), invalide ou accidenté du travail"}
 ]}',
 4, true),

-- Conventionné - CTA
('33333333-3333-3333-3333-333333333333', 'conv_cta', 'cta',
 'Besoin d''un Transport Conventionné ?',
 '{"title": "Besoin d''un Transport Conventionné ?", "subtitle": "Réservez dès maintenant ou contactez-nous pour toute question", "background": "primary", "cta_primary_text": "Réserver en Ligne", "cta_primary_url": "reservation.html", "cta_secondary_text": "📞 01 23 45 67 89", "cta_secondary_url": "tel:+33123456789"}',
 5, true);

-- =====================================================
-- SECTIONS PAGE AÉROPORTS & GARES
-- =====================================================

INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order, is_visible) VALUES

-- Aéroports - Tarifs aéroports
('44444444-4444-4444-4444-444444444444', 'aero_tarifs', 'cards',
 'Transferts Aéroports',
 '{"title": "Transferts Aéroports", "subtitle": "Départs et arrivées sans stress", "items": [
   {"icon": "✈️", "title": "Aéroport Marseille Provence", "text": "Jour : 80€ | Nuit : 100€ - Depuis/vers Martigues, suivi vol, accueil terminal, ~35 min"},
   {"icon": "🛫", "title": "Aéroport de Nîmes", "text": "Sur devis personnalisé - Environ 100 km, tarif négocié, idéal vols low-cost, ~1h15"},
   {"icon": "🌍", "title": "Autres Aéroports", "text": "Nice, Toulon-Hyères, Avignon - Devis gratuit sous 24h, tarif longue distance"}
 ]}',
 1, true),

-- Aéroports - Tarifs gares
('44444444-4444-4444-4444-444444444444', 'aero_gares', 'cards',
 'Transferts Gares',
 '{"title": "Transferts Gares", "subtitle": "Correspondances TGV et trains", "items": [
   {"icon": "🚄", "title": "Gare TGV Aix-en-Provence", "text": "Jour : 80€ | Nuit : 100€ - Surveillance horaire train, correspondances assurées, ~30 min"},
   {"icon": "🚂", "title": "Gare Saint-Charles Marseille", "text": "Jour : 95€ | Nuit : 120€ - Accès direct centre-ville, tous types de trains, ~40 min"},
   {"icon": "🚉", "title": "Autres Gares", "text": "Avignon TGV, Toulon, Arles - Tarifs sur mesure, correspondances optimisées"}
 ]}',
 2, true),

-- Aéroports - Avantages
('44444444-4444-4444-4444-444444444444', 'aero_avantages', 'cards',
 'Pourquoi Nous Choisir ?',
 '{"title": "Pourquoi Nous Choisir ?", "items": [
   {"icon": "⏰", "title": "Ponctualité Absolue", "text": "Nous suivons votre vol ou train en temps réel. En cas de retard, nous ajustons automatiquement."},
   {"icon": "🎯", "title": "Accueil Personnalisé", "text": "Possibilité d''accueil avec panneau nominatif au terminal (sur demande)."},
   {"icon": "🧳", "title": "Aide aux Bagages", "text": "Assistance complète pour vos bagages, du chargement au déchargement."},
   {"icon": "💺", "title": "Confort Maximum", "text": "Véhicule climatisé, sièges confortables, voyage reposant."},
   {"icon": "💰", "title": "Tarif Forfaitaire", "text": "Prix fixe annoncé à l''avance, pas de surprise. Péages et attentes inclus."},
   {"icon": "📱", "title": "Communication Facile", "text": "SMS de confirmation, contact direct avec le chauffeur."}
 ]}',
 3, true),

-- Aéroports - CTA
('44444444-4444-4444-4444-444444444444', 'aero_cta', 'cta',
 'Prêt à Partir ?',
 '{"title": "Prêt à Partir ?", "subtitle": "Réservez votre transfert dès maintenant", "background": "primary", "cta_primary_text": "Réserver Maintenant", "cta_primary_url": "reservation.html", "cta_secondary_text": "📞 01 23 45 67 89", "cta_secondary_url": "tel:+33123456789"}',
 4, true);

-- =====================================================
-- SECTIONS PAGE LONGUES DISTANCES
-- =====================================================

INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order, is_visible) VALUES

-- Longues distances - Destinations
('55555555-5555-5555-5555-555555555555', 'ld_destinations', 'cards',
 'Destinations Populaires',
 '{"title": "Destinations Populaires", "subtitle": "Exemples de trajets fréquemment réalisés", "items": [
   {"icon": "🏖️", "title": "Côte d''Azur", "text": "Nice (~200 km), Cannes (~180 km), Monaco (~220 km), Saint-Tropez (~150 km) - 2h à 2h30"},
   {"icon": "🏛️", "title": "Grandes Villes Sud", "text": "Avignon (~100 km), Nîmes (~110 km), Montpellier (~150 km), Toulouse (~400 km)"},
   {"icon": "⛰️", "title": "Alpes & Lyon", "text": "Lyon (~300 km), Grenoble (~280 km), Chambéry (~350 km), Stations de ski"},
   {"icon": "🗼", "title": "Paris & Au-delà", "text": "Paris (~750 km), Bordeaux (~600 km), autres destinations sur demande"}
 ]}',
 1, true),

-- Longues distances - Avantages
('55555555-5555-5555-5555-555555555555', 'ld_avantages', 'cards',
 'Avantages du Taxi Longue Distance',
 '{"title": "Avantages du Taxi Longue Distance", "items": [
   {"icon": "💺", "title": "Confort Optimal", "text": "Véhicule spacieux et confortable, climatisation, sièges réglables."},
   {"icon": "☕", "title": "Pauses Régulières", "text": "Arrêts toutes les 2h pour vous dégourdir les jambes ou vous restaurer."},
   {"icon": "🚗", "title": "Porte-à-Porte", "text": "Pas de changement de transport, départ de chez vous, arrivée à destination."},
   {"icon": "🧳", "title": "Tous Vos Bagages", "text": "Transportez autant de bagages que nécessaire."},
   {"icon": "🕐", "title": "Horaires Flexibles", "text": "Partez quand vous voulez, possibilité départ tôt ou tard."},
   {"icon": "💰", "title": "Tarif Négocié", "text": "Tarifs au forfait avantageux pour les longues distances."}
 ]}',
 2, true),

-- Longues distances - CTA
('55555555-5555-5555-5555-555555555555', 'ld_cta', 'cta',
 'Besoin d''Aller Loin ?',
 '{"title": "Besoin d''Aller Loin ?", "subtitle": "Obtenez votre devis gratuit en moins de 24h", "background": "primary", "cta_primary_text": "Demander un Devis", "cta_primary_url": "contact.html", "cta_secondary_text": "📞 01 23 45 67 89", "cta_secondary_url": "tel:+33123456789"}',
 3, true);

-- =====================================================
-- SECTIONS PAGE COURSES CLASSIQUES
-- =====================================================

INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order, is_visible) VALUES

-- Courses - Types
('66666666-6666-6666-6666-666666666666', 'courses_types', 'cards',
 'Tous Vos Déplacements Quotidiens',
 '{"title": "Tous Vos Déplacements Quotidiens", "items": [
   {"icon": "🏙️", "title": "Trajets Locaux", "text": "Courses en ville, Port-de-Bouc, Fos-sur-Mer, Istres, Saint-Mitre, tout le secteur"},
   {"icon": "🛒", "title": "Courses & Shopping", "text": "Supermarchés, marchés, centres commerciaux, aide au chargement, retour à domicile"},
   {"icon": "🌙", "title": "Sorties Nocturnes", "text": "Restaurants, bars, événements, concerts, retours de soirée, service jusqu''au matin"},
   {"icon": "👔", "title": "Trajets Professionnels", "text": "Rendez-vous clients, réunions d''affaires, zones industrielles, facturation possible"},
   {"icon": "🏥", "title": "Rendez-vous Médicaux", "text": "Médecin, dentiste, laboratoires, pharmacies, kinésithérapeute, hôpitaux locaux"},
   {"icon": "🏛️", "title": "Démarches Administratives", "text": "Préfecture, mairie, CAF, Pôle Emploi, banques, assurances, notaires, avocats"}
 ]}',
 1, true),

-- Courses - Tarifs
('66666666-6666-6666-6666-666666666666', 'courses_tarifs', 'text',
 'Tarification Réglementaire',
 '{"title": "Tarification Réglementaire", "text": "Tarifs de base :\n• Tarif minimal : 8,00 €\n• Prise en charge : 2,35 €\n• Heure d''attente : 34,60 €/h\n\nTarifs au kilomètre :\n• Tarif A (jour semaine) : 1,11 €/km\n• Tarif B (nuit semaine) : 1,44 €/km\n• Tarif C (jour weekend) : 2,22 €/km\n• Tarif D (nuit weekend) : 2,88 €/km\n\nNuit : 19h00 - 7h00 | Weekend : samedi, dimanche et jours fériés"}',
 2, true),

-- Courses - Engagements
('66666666-6666-6666-6666-666666666666', 'courses_engagements', 'cards',
 'Nos Engagements',
 '{"title": "Nos Engagements", "items": [
   {"icon": "⚡", "title": "Rapidité", "text": "Prise en charge rapide, itinéraire optimisé, vous arrivez à l''heure."},
   {"icon": "💎", "title": "Propreté", "text": "Véhicule impeccablement propre, intérieur et extérieur, nettoyé quotidiennement."},
   {"icon": "🤝", "title": "Courtoisie", "text": "Service professionnel et chaleureux, respect et discrétion assurés."},
   {"icon": "💰", "title": "Prix Juste", "text": "Tarifs réglementaires officiels, compteur visible, reçu fourni."}
 ]}',
 3, true),

-- Courses - CTA
('66666666-6666-6666-6666-666666666666', 'courses_cta', 'cta',
 'Besoin d''un Taxi Maintenant ?',
 '{"title": "Besoin d''un Taxi Maintenant ?", "subtitle": "Appelez-nous, nous sommes disponibles 24h/24", "background": "primary", "cta_primary_text": "📞 01 23 45 67 89", "cta_primary_url": "tel:+33123456789", "cta_secondary_text": "Réserver à l''Avance", "cta_secondary_url": "reservation.html"}',
 4, true);

-- =====================================================
-- SECTIONS PAGE MISE À DISPOSITION
-- =====================================================

INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order, is_visible) VALUES

-- MAD - Concept
('77777777-7777-7777-7777-777777777777', 'mad_concept', 'text',
 'Le Concept de Mise à Disposition',
 '{"title": "Le Concept de Mise à Disposition", "text": "La mise à disposition consiste à louer les services d''un taxi avec chauffeur pour une durée déterminée (quelques heures ou une journée complète). Le véhicule et le chauffeur restent à votre entière disposition pendant cette période.\n\nTarification : 34,60 € / heure + frais kilométriques selon le tarif en vigueur\n\nIdéal pour : Journées avec multiples arrêts, tournées professionnelles, visites touristiques, événements spéciaux."}',
 1, true),

-- MAD - Cas d'usage
('77777777-7777-7777-7777-777777777777', 'mad_cas', 'cards',
 'Cas d''Usage Fréquents',
 '{"title": "Cas d''Usage Fréquents", "items": [
   {"icon": "👔", "title": "Tournées Professionnelles", "text": "Visite de plusieurs clients, réunions multiples, déplacements inter-sites, salons professionnels"},
   {"icon": "🏛️", "title": "Tourisme & Découverte", "text": "Visite de la région, Calanques de Cassis, villages provençaux, circuits personnalisés"},
   {"icon": "🛍️", "title": "Shopping & Loisirs", "text": "Journée shopping, plusieurs magasins, pas de souci de parking, transport des achats"},
   {"icon": "💍", "title": "Événements Spéciaux", "text": "Mariages, anniversaires, cérémonies, sorties de groupe"},
   {"icon": "🏥", "title": "Accompagnement Médical", "text": "Plusieurs consultations, examens médicaux, hôpital avec attente, personne âgée ou PMR"},
   {"icon": "📦", "title": "Déménagement Léger", "text": "Petits objets, documents importants, plusieurs voyages, aide au transport"}
 ]}',
 2, true),

-- MAD - Avantages
('77777777-7777-7777-7777-777777777777', 'mad_avantages', 'cards',
 'Les Avantages de la Mise à Disposition',
 '{"title": "Les Avantages de la Mise à Disposition", "items": [
   {"icon": "🎯", "title": "Flexibilité Totale", "text": "Changez d''itinéraire à tout moment, ajoutez des arrêts, modifiez le planning."},
   {"icon": "⏱️", "title": "Gain de Temps", "text": "Pas d''attente entre deux courses, le taxi reste sur place."},
   {"icon": "💼", "title": "Productivité", "text": "Travaillez, passez vos appels pendant les trajets."},
   {"icon": "🔒", "title": "Sérénité", "text": "Plus de stress de parking, de circulation ou d''horaires."},
   {"icon": "🤝", "title": "Service Personnalisé", "text": "Le chauffeur apprend vos préférences et s''adapte."},
   {"icon": "💰", "title": "Économique", "text": "Souvent plus avantageux que plusieurs courses séparées."}
 ]}',
 3, true),

-- MAD - CTA
('77777777-7777-7777-7777-777777777777', 'mad_cta', 'cta',
 'Intéressé par une Mise à Disposition ?',
 '{"title": "Intéressé par une Mise à Disposition ?", "subtitle": "Contactez-nous pour un devis personnalisé gratuit", "background": "primary", "cta_primary_text": "Demander un Devis", "cta_primary_url": "contact.html", "cta_secondary_text": "📞 01 23 45 67 89", "cta_secondary_url": "tel:+33123456789"}',
 4, true);

-- =====================================================
-- SECTIONS PAGE À PROPOS
-- =====================================================

INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order, is_visible) VALUES

-- À propos - Histoire
('88888888-8888-8888-8888-888888888888', 'apropos_histoire', 'text',
 'Notre Histoire',
 '{"title": "Notre Histoire", "text": "Depuis plus de 10 ans, Taxi Julien est au service des habitants de Martigues et de ses environs. Professionnel du transport de personnes, j''ai à cœur de vous offrir un service de qualité, ponctuel et sécurisé.\n\nAgréé par la CPAM, je me suis spécialisé dans le transport conventionné pour répondre aux besoins des patients nécessitant des trajets médicaux réguliers. Mais je propose également tous types de courses : aéroports, gares, longues distances, déplacements professionnels et personnels.\n\nMa priorité : votre satisfaction. Ponctualité, confort, sécurité et tarifs transparents sont les valeurs qui guident mon travail au quotidien."}',
 1, true),

-- À propos - Valeurs
('88888888-8888-8888-8888-888888888888', 'apropos_valeurs', 'cards',
 'Nos Valeurs',
 '{"title": "Nos Valeurs", "items": [
   {"icon": "⏱️", "title": "Ponctualité", "text": "Respect strict des horaires. Votre temps est précieux, je m''engage à être toujours à l''heure."},
   {"icon": "🤝", "title": "Professionnalisme", "text": "Courtoisie, discrétion et service irréprochable. Votre confort est ma priorité."},
   {"icon": "🔒", "title": "Sécurité", "text": "Conduite prudente, véhicule entretenu régulièrement et assurance professionnelle complète."},
   {"icon": "💎", "title": "Qualité", "text": "Véhicule premium, propre et climatisé pour votre confort, quelle que soit la distance."},
   {"icon": "💰", "title": "Transparence", "text": "Tarifs réglementaires sans surprise. Simulateur en ligne pour estimer vos courses."},
   {"icon": "🌟", "title": "Disponibilité", "text": "Service 24h/24, 7j/7, y compris week-end et jours fériés. Je suis toujours là pour vous."}
 ]}',
 2, true),

-- À propos - Véhicule
('88888888-8888-8888-8888-888888888888', 'apropos_vehicule', 'list',
 'Le Véhicule',
 '{"title": "Le Véhicule", "subtitle": "Un Taxi Confortable et Sécurisé", "items": [
   {"title": "Véhicule récent régulièrement entretenu"},
   {"title": "Climatisation pour votre confort"},
   {"title": "Intérieur spacieux et propre"},
   {"title": "Coffre grande capacité pour vos bagages"},
   {"title": "GPS dernière génération pour optimiser les trajets"},
   {"title": "Assurance tous risques et contrôle technique à jour"}
 ]}',
 3, true),

-- À propos - Certifications
('88888888-8888-8888-8888-888888888888', 'apropos_certifications', 'cards',
 'Certifications & Agréments',
 '{"title": "Certifications & Agréments", "items": [
   {"icon": "🏥", "title": "Agrément CPAM", "text": "Taxi conventionné Sécurité Sociale"},
   {"icon": "📋", "title": "Licence Professionnelle", "text": "Carte professionnelle taxi en cours de validité"},
   {"icon": "🛡️", "title": "Assurance Pro", "text": "Assurance responsabilité civile professionnelle"}
 ]}',
 4, true),

-- À propos - CTA
('88888888-8888-8888-8888-888888888888', 'apropos_cta', 'cta',
 'Faites Confiance à un Professionnel',
 '{"title": "Faites Confiance à un Professionnel", "subtitle": "Plus de 10 ans d''expérience au service de votre mobilité", "background": "primary", "cta_primary_text": "Réserver Maintenant", "cta_primary_url": "reservation.html", "cta_secondary_text": "Nous Contacter", "cta_secondary_url": "contact.html"}',
 5, true);

-- =====================================================
-- SECTIONS PAGE CONTACT
-- =====================================================

INSERT INTO page_sections (page_id, section_key, section_type, title, content, display_order, is_visible) VALUES

-- Contact - Moyens
('99999999-9999-9999-9999-999999999999', 'contact_moyens', 'cards',
 'Moyens de Contact',
 '{"title": "Moyens de Contact", "items": [
   {"icon": "📞", "title": "Téléphone", "text": "01 23 45 67 89 - Service disponible 24h/24, 7j/7"},
   {"icon": "📧", "title": "Email", "text": "contact@taxijulien.fr - Réponse sous 24h maximum"},
   {"icon": "💬", "title": "WhatsApp", "text": "06 12 34 56 78 - Message ou appel via WhatsApp"}
 ]}',
 1, true),

-- Contact - Infos pratiques
('99999999-9999-9999-9999-999999999999', 'contact_infos', 'cards',
 'Informations Pratiques',
 '{"title": "Informations Pratiques", "items": [
   {"icon": "📍", "title": "Secteur d''Activité", "text": "Basé à Martigues. Intervention dans tout le département des Bouches-du-Rhône (13) et au-delà."},
   {"icon": "🕐", "title": "Horaires", "text": "Service 24h/24, 7j/7. Disponible tous les jours, y compris week-end et jours fériés."},
   {"icon": "💳", "title": "Moyens de Paiement", "text": "Espèces, carte bancaire, chèque, facturation entreprise"}
 ]}',
 2, true),

-- Contact - CTA
('99999999-9999-9999-9999-999999999999', 'contact_cta', 'cta',
 'Besoin d''un Taxi Maintenant ?',
 '{"title": "Besoin d''un Taxi Maintenant ?", "subtitle": "Appelez-nous ou réservez en ligne", "background": "primary", "cta_primary_text": "📞 Appeler Maintenant", "cta_primary_url": "tel:+33123456789", "cta_secondary_text": "Réserver en Ligne", "cta_secondary_url": "reservation.html"}',
 3, true);

-- =====================================================
-- PARAMÈTRES DU SITE
-- =====================================================

INSERT INTO settings (key, value) VALUES
('site_name', 'Taxi Julien'),
('site_tagline', 'Votre taxi conventionné de confiance à Martigues'),
('contact_phone', '01 23 45 67 89'),
('contact_email', 'contact@taxijulien.fr'),
('contact_address', 'Martigues, Bouches-du-Rhône (13)'),
('whatsapp', '33612345678'),
('facebook_url', ''),
('instagram_url', ''),
('twitter_url', ''),
('linkedin_url', ''),
('google_analytics_id', ''),
('google_tag_manager_id', ''),
('facebook_pixel_id', '')
ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;

-- =====================================================
-- ARTICLES DE BLOG
-- =====================================================

INSERT INTO blog_posts (id, slug, title, excerpt, content, featured_image, category, meta_title, meta_description, is_published, published_at, created_at) VALUES

-- Article 1 : Transfert Aéroport
('b1111111-1111-1111-1111-111111111111',
 'conseils-transfert-aeroport',
 '10 Conseils pour Préparer votre Transfert Aéroport',
 'Découvrez nos astuces pour voyager sereinement vers l''aéroport de Marseille-Provence. Timing, bagages, formalités : on vous dit tout pour partir l''esprit tranquille.',
 '<h2>Préparez votre transfert aéroport comme un pro</h2>
<p>Un transfert aéroport réussi commence par une bonne préparation. Voici nos 10 conseils essentiels :</p>

<h3>1. Réservez à l''avance</h3>
<p>Ne laissez rien au hasard. Réservez votre taxi la veille au minimum pour garantir votre place.</p>

<h3>2. Prévoyez une marge de temps</h3>
<p>Comptez au minimum 2h avant l''heure de décollage pour un vol national, 3h pour l''international.</p>

<h3>3. Préparez vos documents</h3>
<p>Passeport, carte d''identité, billets : vérifiez tout la veille du départ.</p>

<h3>4. Communiquez votre numéro de vol</h3>
<p>Cela nous permet de suivre les éventuels retards et d''adapter notre horaire.</p>

<h3>5. Pesez vos bagages</h3>
<p>Évitez les mauvaises surprises à l''enregistrement en vérifiant le poids de vos valises.</p>

<h3>6. Gardez les essentiels en cabine</h3>
<p>Médicaments, chargeurs, documents importants : gardez-les toujours à portée de main.</p>

<h3>7. Confirmez votre réservation</h3>
<p>Un petit appel la veille permet de confirmer l''heure et l''adresse de prise en charge.</p>

<h3>8. Soyez prêt à l''heure</h3>
<p>Votre chauffeur sera ponctuel, soyez-le aussi pour éviter tout stress.</p>

<h3>9. Indiquez le terminal</h3>
<p>Marseille-Provence a deux terminaux : précisez le vôtre lors de la réservation.</p>

<h3>10. Profitez du trajet</h3>
<p>Détendez-vous, votre chauffeur s''occupe de tout !</p>',
 NULL,
 'Voyages',
 '10 Conseils pour votre Transfert Aéroport - Taxi Julien',
 'Nos conseils pour un transfert aéroport réussi depuis Martigues vers Marseille-Provence. Timing, préparation, bagages.',
 true,
 '2024-11-15 10:00:00',
 '2024-11-15 10:00:00'),

-- Article 2 : Transport Conventionné CPAM
('b2222222-2222-2222-2222-222222222222',
 'transport-conventionne-cpam-guide',
 'Transport Conventionné CPAM : Comment ça Marche ?',
 'Tout savoir sur le remboursement de vos trajets médicaux : qui peut en bénéficier, quelles sont les démarches, quels documents fournir. Guide complet 2024.',
 '<h2>Le transport sanitaire conventionné expliqué</h2>
<p>Le transport conventionné permet aux assurés de bénéficier d''une prise en charge de leurs frais de transport pour se rendre à des soins médicaux.</p>

<h3>Qui peut en bénéficier ?</h3>
<p>Le transport conventionné s''adresse aux personnes dont l''état de santé nécessite un déplacement pour des soins :</p>
<ul>
<li>Hospitalisation (entrée et sortie)</li>
<li>Traitements réguliers (dialyse, chimiothérapie, radiothérapie)</li>
<li>Examens et contrôles médicaux</li>
<li>Consultations chez un spécialiste</li>
</ul>

<h3>Les conditions de prise en charge</h3>
<p>Pour être remboursé, vous devez disposer d''une prescription médicale de transport établie par votre médecin. Cette prescription précise le mode de transport adapté à votre état.</p>

<h3>Les démarches à suivre</h3>
<ol>
<li>Obtenez une prescription médicale de transport de votre médecin</li>
<li>Contactez un taxi conventionné comme Taxi Julien</li>
<li>Présentez votre carte vitale et la prescription au chauffeur</li>
<li>Signez le bon de transport à l''arrivée</li>
</ol>

<h3>Le remboursement</h3>
<p>En tant que taxi conventionné, nous pratiquons le tiers-payant : vous n''avez rien à avancer. La Sécurité Sociale nous règle directement.</p>

<h3>Pourquoi choisir Taxi Julien ?</h3>
<p>Nous sommes agréés CPAM depuis plus de 10 ans. Notre expérience garantit un service adapté à votre état de santé et une gestion administrative simplifiée.</p>',
 NULL,
 'Santé',
 'Transport Conventionné CPAM : Guide Complet - Taxi Julien',
 'Guide complet du transport conventionné CPAM. Conditions, démarches, remboursement. Taxi Julien, agréé Sécurité Sociale à Martigues.',
 true,
 '2024-11-08 10:00:00',
 '2024-11-08 10:00:00'),

-- Article 3 : Martigues et environs
('b3333333-3333-3333-3333-333333333333',
 'incontournables-martigues-environs',
 'Les Incontournables de Martigues et ses Environs',
 'Partez à la découverte de la "Venise Provençale" et de ses trésors cachés. Nos recommandations de lieux à visiter, restaurants et activités.',
 '<h2>Découvrez Martigues, la Venise Provençale</h2>
<p>Martigues, surnommée la "Venise Provençale" pour ses canaux pittoresques, regorge de trésors à découvrir. En tant que taxi local, nous connaissons les meilleurs spots !</p>

<h3>Le Quartier de l''Île</h3>
<p>Le cœur historique de Martigues avec ses maisons de pêcheurs colorées qui se reflètent dans les eaux du canal. Le "Miroir aux Oiseaux" est l''un des sites les plus photographiés de Provence.</p>

<h3>L''Église de la Madeleine</h3>
<p>Magnifique église baroque du XVIIe siècle, classée monument historique. Son intérieur richement décoré vaut le détour.</p>

<h3>Le Port de Carro</h3>
<p>Authentique port de pêche avec ses pointus colorés. Idéal pour déguster du poisson frais dans les restaurants du port.</p>

<h3>La Côte Bleue</h3>
<p>Les criques sauvages de la Côte Bleue offrent des eaux turquoise propices à la baignade et au snorkeling.</p>

<h3>Le Marché du Cours</h3>
<p>Tous les jeudis et dimanches, le grand marché provençal anime le centre-ville avec ses produits locaux.</p>

<h3>Nos recommandations restaurants</h3>
<ul>
<li>Le Garage - Cuisine bistronomique</li>
<li>Le Bouchon à la Mer - Fruits de mer</li>
<li>La Table de la Rascasse - Vue sur le port</li>
</ul>

<p>Besoin d''un taxi pour découvrir la région ? Nous proposons des circuits touristiques personnalisés !</p>',
 NULL,
 'Découverte',
 'Que Voir à Martigues ? Les Incontournables - Taxi Julien',
 'Découvrez les incontournables de Martigues : Miroir aux Oiseaux, Côte Bleue, restaurants. Guide local par Taxi Julien.',
 true,
 '2024-11-01 10:00:00',
 '2024-11-01 10:00:00'),

-- Article 4 : Tarifs Taxi
('b4444444-4444-4444-4444-444444444444',
 'comprendre-tarifs-taxi-abcd',
 'Comprendre les Tarifs des Taxis : Tarif A, B, C, D',
 'Vous vous demandez comment sont calculés les tarifs de taxi ? Nous vous expliquons la différence entre les tarifs A, B, C et D.',
 '<h2>Les tarifs de taxi expliqués simplement</h2>
<p>Les tarifs des taxis sont réglementés par arrêté préfectoral. Comprendre leur fonctionnement vous permet de mieux anticiper le coût de vos trajets.</p>

<h3>Les 4 tarifs officiels</h3>

<h4>Tarif A - Course de jour</h4>
<p>Applicable du lundi au samedi, de 7h à 19h. C''est le tarif le plus économique pour vos déplacements en journée.</p>

<h4>Tarif B - Course de nuit</h4>
<p>Applicable tous les jours de 19h à 7h. Majoration d''environ 20% par rapport au tarif A.</p>

<h4>Tarif C - Dimanche et jours fériés de jour</h4>
<p>Applicable le dimanche et les jours fériés, de 7h à 19h. Légère majoration par rapport au tarif A.</p>

<h4>Tarif D - Dimanche et jours fériés de nuit</h4>
<p>Applicable le dimanche et les jours fériés, de 19h à 7h. C''est le tarif le plus élevé.</p>

<h3>Comment est calculé le prix ?</h3>
<p>Le compteur taximètre calcule automatiquement le prix en fonction de :</p>
<ul>
<li>La prise en charge (montant fixe au départ)</li>
<li>La distance parcourue (prix au kilomètre)</li>
<li>Le temps d''attente (en cas d''embouteillage)</li>
</ul>

<h3>Les forfaits aéroports</h3>
<p>Pour les transferts aéroports, nous proposons des tarifs forfaitaires fixes. Pas de surprise : le prix annoncé est le prix final, quel que soit le trafic.</p>

<h3>Conseil</h3>
<p>Utilisez notre simulateur en ligne pour estimer le coût de votre trajet avant de réserver !</p>',
 NULL,
 'Conseils',
 'Tarifs Taxi Expliqués : A, B, C, D - Taxi Julien',
 'Comprendre les tarifs taxi : Tarif A, B, C, D. Comment sont calculés les prix ? Explications simples par Taxi Julien Martigues.',
 true,
 '2024-10-25 10:00:00',
 '2024-10-25 10:00:00'),

-- Article 5 : Taxi écologique
('b5555555-5555-5555-5555-555555555555',
 'taxi-choix-ecologique-responsable',
 'Le Taxi : Un Choix Écologique et Responsable',
 'Contrairement aux idées reçues, le taxi peut être un mode de transport éco-responsable. Découvrez pourquoi et comment nous agissons.',
 '<h2>Le taxi, partenaire de la mobilité durable</h2>
<p>À l''heure où chacun s''interroge sur son empreinte carbone, le taxi apparaît comme une solution de transport plus vertueuse qu''on ne le pense.</p>

<h3>Moins de voitures, moins de pollution</h3>
<p>Un taxi remplace plusieurs véhicules particuliers. En choisissant le taxi plutôt que votre voiture personnelle, vous contribuez à réduire le nombre de véhicules en circulation.</p>

<h3>Des véhicules récents et entretenus</h3>
<p>Les taxis sont soumis à des contrôles techniques réguliers et sont généralement des véhicules récents aux normes anti-pollution les plus strictes.</p>

<h3>L''éco-conduite</h3>
<p>Les chauffeurs de taxi professionnels sont formés à l''éco-conduite, permettant de réduire la consommation de carburant et les émissions de CO2.</p>

<h3>Vers des flottes plus vertes</h3>
<p>De plus en plus de taxis adoptent des véhicules hybrides ou électriques. C''est une tendance de fond dans la profession.</p>

<h3>Le taxi vs la voiture personnelle</h3>
<ul>
<li>Pas de recherche de stationnement (moins de pollution)</li>
<li>Trajets optimisés par des professionnels</li>
<li>Véhicules aux normes récentes</li>
<li>Mutualisation possible (partage de courses)</li>
</ul>

<h3>Notre engagement</h3>
<p>Chez Taxi Julien, nous nous engageons dans une démarche éco-responsable : entretien régulier du véhicule, éco-conduite, et réflexion sur l''évolution vers un véhicule hybride.</p>',
 NULL,
 'Écologie',
 'Le Taxi, un Choix Écologique - Taxi Julien Martigues',
 'Le taxi, mode de transport écologique ? Découvrez pourquoi choisir le taxi est un geste pour l''environnement.',
 true,
 '2024-10-18 10:00:00',
 '2024-10-18 10:00:00'),

-- Article 6 : Fêtes de fin d'année
('b6666666-6666-6666-6666-666666666666',
 'deplacements-fetes-fin-annee',
 'Vos Déplacements pendant les Fêtes de Fin d''Année',
 'Réveillon, repas de famille, shopping de Noël : organisez vos déplacements des fêtes sans stress. Nos conseils et disponibilités.',
 '<h2>Profitez des fêtes sans vous soucier du transport</h2>
<p>Les fêtes de fin d''année sont synonymes de retrouvailles en famille, de soirées festives et de moments de partage. Ne laissez pas la question du transport gâcher ces instants précieux.</p>

<h3>Réveillon en toute sécurité</h3>
<p>Après un bon réveillon, ne prenez pas le volant. Réservez votre taxi à l''avance pour rentrer en toute sécurité. Nous sommes disponibles toute la nuit du 31 décembre.</p>

<h3>Repas de famille</h3>
<p>Mamie habite à l''autre bout du département ? Nous vous conduisons chez vos proches et venons vous rechercher quand vous le souhaitez.</p>

<h3>Shopping de Noël</h3>
<p>Plus de stress pour trouver une place de parking en centre-ville ou dans les centres commerciaux. Le taxi vous dépose et vous reprend chargé de cadeaux.</p>

<h3>Transferts gare et aéroport</h3>
<p>Beaucoup de voyageurs arrivent en train ou en avion pour les fêtes. Nous assurons les transferts depuis Marseille-Provence et les gares de la région.</p>

<h3>Nos disponibilités</h3>
<p>Nous restons disponibles 24h/24 pendant toute la période des fêtes :</p>
<ul>
<li>24 décembre : disponible jour et nuit</li>
<li>25 décembre : disponible toute la journée</li>
<li>31 décembre : service renforcé pour le réveillon</li>
<li>1er janvier : disponible dès 6h du matin</li>
</ul>

<h3>Réservez tôt !</h3>
<p>Les fêtes sont une période chargée. Pour être sûr d''avoir votre taxi, réservez plusieurs jours à l''avance, surtout pour le réveillon du 31.</p>

<p>Toute l''équipe de Taxi Julien vous souhaite de joyeuses fêtes ! 🎄</p>',
 NULL,
 'Événements',
 'Taxi pour les Fêtes de Fin d''Année - Taxi Julien',
 'Réservez votre taxi pour les fêtes : réveillon, repas de famille, transferts. Disponible 24/7 pendant les fêtes à Martigues.',
 true,
 '2024-10-10 10:00:00',
 '2024-10-10 10:00:00');

-- =====================================================
-- MESSAGE DE FIN
-- =====================================================
SELECT 'Import terminé ! ' ||
       (SELECT COUNT(*) FROM pages) || ' pages, ' ||
       (SELECT COUNT(*) FROM page_sections) || ' sections et ' ||
       (SELECT COUNT(*) FROM blog_posts) || ' articles de blog créés.' as result;
