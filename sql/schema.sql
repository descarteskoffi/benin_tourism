-- Création de la base de données (si nécessaire)
-- CREATE DATABASE IF NOT EXISTS benin_tourisme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE benin_tourisme;

-- Suppression des tables si elles existent (ordre respectant les clés étrangères)
DROP TABLE IF EXISTS demandes_visite;
DROP TABLE IF EXISTS messages_contact;
DROP TABLE IF EXISTS demandes_guide;
DROP TABLE IF EXISTS reservations_hebergement;
DROP TABLE IF EXISTS administrateurs;
DROP TABLE IF EXISTS guides;
DROP TABLE IF EXISTS hebergements;
DROP TABLE IF EXISTS lieux_photos;
DROP TABLE IF EXISTS lieux;

-- 1. Table des lieux touristiques
CREATE TABLE lieux (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom_fr VARCHAR(150) NOT NULL,
  nom_en VARCHAR(150) NOT NULL,
  region_fr VARCHAR(100) NOT NULL,
  region_en VARCHAR(100) NOT NULL,
  categorie VARCHAR(50) NOT NULL, -- Nature, Culture, Plage, Spiritualité
  description_courte_fr VARCHAR(255) NOT NULL,
  description_courte_en VARCHAR(255) NOT NULL,
  histoire_fr TEXT,
  histoire_en TEXT,
  photo_principale VARCHAR(255),
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  horaires_fr VARCHAR(100),
  horaires_en VARCHAR(100),
  tarif_fr VARCHAR(50),
  tarif_en VARCHAR(50),
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lieux_categorie (categorie),
  INDEX idx_lieux_region_fr (region_fr),
  INDEX idx_lieux_region_en (region_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table des photos supplémentaires des lieux
CREATE TABLE lieux_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lieu_id INT NOT NULL,
  chemin_photo VARCHAR(255) NOT NULL,
  FOREIGN KEY (lieu_id) REFERENCES lieux(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des hébergements
CREATE TABLE hebergements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  type_fr VARCHAR(50) NOT NULL, -- Hôtel, Auberge, Maison d'hôtes
  type_en VARCHAR(50) NOT NULL, -- Hotel, Inn, Guest house
  localite VARCHAR(100) NOT NULL,
  quartier VARCHAR(100),
  prix_nuit INT NOT NULL,
  devise VARCHAR(10) DEFAULT 'FCFA',
  description_fr TEXT,
  description_en TEXT,
  photo VARCHAR(255),
  email_contact VARCHAR(150),
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hebergements_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table des guides
CREATE TABLE guides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  photo VARCHAR(255),
  langues_fr VARCHAR(255) NOT NULL,
  langues_en VARCHAR(255) NOT NULL,
  zones_fr VARCHAR(255) NOT NULL,
  zones_en VARCHAR(255) NOT NULL,
  tarif_jour INT NOT NULL,
  devise VARCHAR(10) DEFAULT 'FCFA',
  telephone VARCHAR(30),
  email VARCHAR(150),
  disponible BOOLEAN DEFAULT TRUE,
  gamme ENUM('Economique', 'Confort', 'Premium') DEFAULT 'Economique',
  vehicule_modele VARCHAR(100) DEFAULT NULL,
  capacite_passagers INT DEFAULT 4,
  capacite_bagages INT DEFAULT 3,
  INDEX idx_guides_disponible (disponible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table des réservations d'hébergements
CREATE TABLE reservations_hebergement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  hebergement_id INT NOT NULL,
  nom_client VARCHAR(150) NOT NULL,
  email_client VARCHAR(150) NOT NULL,
  telephone_client VARCHAR(30),
  date_arrivee DATE NOT NULL,
  date_depart DATE NOT NULL,
  nb_personnes INT DEFAULT 1,
  type_chambre VARCHAR(50) DEFAULT 'standard',
  message TEXT,
  statut ENUM('nouvelle', 'proposition_envoyee', 'payee', 'annulee') DEFAULT 'nouvelle',
  token_paiement VARCHAR(64) UNIQUE NULL,
  transaction_id VARCHAR(100) NULL,
  date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (hebergement_id) REFERENCES hebergements(id) ON DELETE CASCADE,
  INDEX idx_res_status (statut),
  INDEX idx_res_heb_id (hebergement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table des demandes de chauffeurs-guides
CREATE TABLE demandes_guide (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guide_id INT NOT NULL,
  nom_client VARCHAR(150) NOT NULL,
  email_client VARCHAR(150) NOT NULL,
  telephone_client VARCHAR(30),
  date_debut DATE NOT NULL,
  date_fin DATE NOT NULL,
  destination VARCHAR(150),
  statut ENUM('nouvelle', 'proposition_envoyee', 'payee', 'annulee') DEFAULT 'nouvelle',
  token_paiement VARCHAR(64) UNIQUE NULL,
  transaction_id VARCHAR(100) NULL,
  date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (guide_id) REFERENCES guides(id) ON DELETE CASCADE,
  INDEX idx_dem_status (statut),
  INDEX idx_dem_guide_id (guide_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table des messages de contact
CREATE TABLE messages_contact (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  sujet VARCHAR(200),
  message TEXT NOT NULL,
  date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table des administrateurs
CREATE TABLE administrateurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom_utilisateur VARCHAR(100) UNIQUE NOT NULL,
  mot_de_passe_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table des demandes de visite unifiées
CREATE TABLE demandes_visite (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lieu_id INT NOT NULL,
  nom_client VARCHAR(150) NOT NULL,
  email_client VARCHAR(150) NOT NULL,
  telephone_client VARCHAR(30) NOT NULL,
  date_arrivee DATE NOT NULL,
  date_depart DATE NOT NULL,
  nb_personnes INT DEFAULT 1,
  besoin_hebergement BOOLEAN DEFAULT FALSE,
  statut ENUM('nouvelle', 'proposition_envoyee', 'payee', 'annulee') DEFAULT 'nouvelle',
  guide_id INT NULL,
  hebergement_id INT NULL,
  token_paiement VARCHAR(64) UNIQUE NOT NULL,
  transaction_id VARCHAR(100) NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lieu_id) REFERENCES lieux(id) ON DELETE CASCADE,
  FOREIGN KEY (guide_id) REFERENCES guides(id) ON DELETE SET NULL,
  FOREIGN KEY (hebergement_id) REFERENCES hebergements(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- INSERTIONS DES DONNÉES DE DÉMONSTRATION
-- ============================================================================

-- 1. Insertion des administrateurs (admin / admin123)
INSERT INTO administrateurs (nom_utilisateur, mot_de_passe_hash) VALUES
('admin', '$2y$10$h16blt3kSVHiF6tU83OQkuRqy5S4/s/ZXQa2ctIdViUViLvqn726a');

-- 2. Insertion des 10 lieux touristiques
INSERT INTO lieux (id, nom_fr, nom_en, region_fr, region_en, categorie, description_courte_fr, description_courte_en, histoire_fr, histoire_en, photo_principale, latitude, longitude, horaires_fr, horaires_en, tarif_fr, tarif_en) VALUES
(1, 'Cité lacustre de Ganvié', 'Ganvié Stilt Village', 'Atlantique (Lac Nokoué)', 'Atlantique (Lake Nokoué)', 'Culture', 
 'Surnommée la Venise de l''Afrique, cette cité sur pilotis abrite plus de 30 000 habitants.', 
 'Known as the Venice of Africa, this stilt village is home to over 30,000 residents.',
 'Ganvié a été fondée au XVIIIe siècle par les populations Toffinu fuyant les razzias esclavagistes du royaume du Dahomey. Le lac Nokoué était alors considéré comme sacré, empêchant les guerriers d''y accéder. Aujourd''hui, c''est un exemple unique d''adaptation humaine en milieu lacustre.',
 'Ganvie was founded in the 18th century by the Toffinu people fleeing slave raids by the Dahomey Kingdom. Lake Nokoue was considered sacred, preventing warriors from entering it. Today, it stands as a unique example of human adaptation to a lacustrine environment.',
 'ganvie.jpg', 6.4678220, 2.4172580, '07h30 - 18h00', '7:30 AM - 6:00 PM', '10 000 FCFA (avec barque)', '10,000 FCFA (with boat ride)'),

(2, 'Palais Royaux d''Abomey', 'Royal Palaces of Abomey', 'Zou', 'Zou', 'Culture',
 'Inscrits au patrimoine mondial de l''UNESCO, ils racontent l''épopée du puissant royaume du Dahomey.',
 'A UNESCO World Heritage site, recounting the epic story of the powerful Dahomey Kingdom.',
 'Le site abrite les palais des rois qui se sont succédé à la tête du royaume de Dahomey entre 1625 et 1900. Ces palais constituent un témoignage exceptionnel d''un royaume disparu. Les bas-reliefs en terre cuite décrivent les symboles de chaque souverain et leurs exploits militaires.',
 'The site houses the palaces of successive kings who ruled the Dahomey Kingdom between 1625 and 1900. These palaces are an exceptional testimony to a vanished kingdom. Terracotta bas-reliefs depict the symbols of each sovereign and their military exploits.',
 'abomey.jpg', 7.1856930, 1.9918230, '08h30 - 17h30', '8:30 AM - 5:30 PM', '5 000 FCFA', '5,000 FCFA'),

(3, 'Route des Esclaves à Ouidah', 'Slave Route in Ouidah', 'Atlantique', 'Atlantique', 'Culture',
 'Un parcours mémoriel historique retraçant le dernier chemin des captifs vers les navires négriers.',
 'A historical memorial path tracing the final journey of captives to the slave ships.',
 'La Route des Esclaves est un chemin de 4 km bordé de monuments commémoratifs, reliant l''ancienne place des enchères (marché aux esclaves) jusqu''à la plage de Ouidah, où se dresse la Porte du Non-Retour, monument érigé en mémoire des millions de déportés.',
 'The Slave Route is a 4 km path lined with memorial monuments, linking the ancient auction site (slave market) to the beach of Ouidah, where the Point of No Return stands—a monument built in memory of the millions deported.',
 'route_esclaves.jpg', 6.3621450, 2.0831200, 'Accès libre (Guide recommandé : 3000 FCFA)', 'Open access (Guide recommended: 3,000 FCFA)', 'Gratuit', 'Free'),

(4, 'Parc National de la Pendjari', 'Pendjari National Park', 'Atacora', 'Atacora', 'Nature',
 'L''une des plus belles réserves de faune d''Afrique de l''Ouest, abritant lions, éléphants et guépards.',
 'One of the finest wildlife reserves in West Africa, home to lions, elephants, and cheetahs.',
 'Couvrant plus de 275 000 hectares, le parc de la Pendjari fait partie du complexe W-Arly-Pendjari, inscrit au patrimoine de l''UNESCO. C''est le dernier refuge majeur pour la grande faune d''Afrique de l''Ouest, notamment les lions de la Pendjari et les éléphants d''Afrique.',
 'Covering over 275,000 hectares, the Pendjari Park is part of the W-Arly-Pendjari complex, a UNESCO World Heritage site. It represents the last major refuge for West African wildlife, particularly Pendjari lions and African elephants.',
 'pendjari.jpg', 11.2384720, 1.5478920, '06h00 - 18h00', '6:00 AM - 6:00 PM', '10 000 FCFA / jour (véhicule en sus)', '10,000 FCFA / day (vehicle extra)'),

(5, 'Plage de Grand-Popo et Bouche du Roy', 'Grand-Popo Beach & Mono River Mouth', 'Mono', 'Mono', 'Plage',
 'Une bande littorale paisible où le fleuve Mono se jette dans l''Océan Atlantique au milieu des mangroves.',
 'A peaceful coastal strip where the Mono River meets the Atlantic Ocean amidst beautiful mangroves.',
 'Grand-Popo offre une atmosphère reposante propice au repos et aux balades écologiques. La Bouche du Roy est le site exceptionnel où les eaux douces du fleuve Mono rencontrent l''océan. C''est également un refuge pour les tortues marines et de nombreuses espèces d''oiseaux.',
 'Grand-Popo offers a relaxing atmosphere suitable for rest and ecological walks. La Bouche du Roy is the exceptional site where the fresh waters of the Mono river meet the ocean. It is also a sanctuary for sea turtles and numerous bird species.',
 'grand_popo.jpg', 6.2798340, 1.8347890, 'Accès libre', 'Open access', 'Gratuit', 'Free'),

(6, 'Temple des Pythons à Ouidah', 'Temple of Pythons in Ouidah', 'Atlantique', 'Atlantique', 'Spiritualité',
 'Un site sacré du culte Vaudou abritant des dizaines de pythons royaux vénérés et inoffensifs.',
 'A sacred Voodoo site housing dozens of revered and harmless royal pythons.',
 'Dans la cosmogonie vaudou locale, le python royal est une divinité protectrice appelée Dagbé. Le temple fait face à la cathédrale de Ouidah, illustrant un esprit de tolérance religieuse et de syncrétisme unique au Bénin. Les visiteurs peuvent y manipuler les pythons sous la supervision des prêtres.',
 'In local Voodoo cosmology, the royal python is a protective deity called Dagbe. The temple faces Ouidah Cathedral, demonstrating a unique spirit of religious tolerance and syncretism in Benin. Visitors can handle pythons under the supervision of priests.',
 'temple_pythons.jpg', 6.3614560, 2.0854330, '08h00 - 18h30', '8:00 AM - 6:30 PM', '2 000 FCFA (Photos autorisées)', '2,000 FCFA (Photos allowed)'),

(7, 'Les Chutes de Kota', 'Kota Waterfalls', 'Atacora', 'Atacora', 'Nature',
 'De magnifiques cascades d''eau nichées au cœur d''une forêt galerie luxuriante près de Natitingou.',
 'Beautiful waterfalls nestled in the heart of a lush gallery forest near Natitingou.',
 'Les chutes de Kota sont formées par une série de cascades sur la rivière Kota dans le massif de l''Atacora. Le site dispose d''un bassin de baignade naturel entouré d''arbres centenaires, constituant un havre de fraîcheur après la traversée des zones chaudes du nord du Bénin.',
 'The Kota waterfalls are formed by a series of cascades on the Kota River in the Atacora mountain range. The site features a natural swimming pool surrounded by ancient trees, offering a refreshing haven after traveling through the hot northern region of Benin.',
 'kota.jpg', 10.2098230, 1.4421940, '08h00 - 18h00', '8:00 AM - 6:00 PM', '1 500 FCFA', '1,500 FCFA'),

(8, 'Collines de Dassa-Zoumè', 'Hills of Dassa-Zoume', 'Collines', 'Collines', 'Spiritualité',
 'La ville aux 41 collines, haut lieu de pèlerinage catholique et de mystères légendaires.',
 'The city of 41 hills, a major site for Catholic pilgrimage and legendary mysteries.',
 'Dassa-Zoumè est célèbre pour ses formations rocheuses granitiques spectaculaires. Elle abrite la Grotte Mariale d''Arigbo, lieu de rassemblement annuel de dizaines de milliers de fidèles. Les collines abritent également d''anciens sanctuaires royaux et racontent l''histoire de la résistance locale aux invasions.',
 'Dassa-Zoume is famous for its spectacular granitic rock formations. It houses the Marian Grotto of Arigbo, the annual gathering place for tens of thousands of pilgrims. The hills also contain ancient royal sanctuaries and tell stories of local resistance to historical invasions.',
 'dassa.jpg', 7.7456720, 2.1834920, 'Accès libre', 'Open access', 'Gratuit', 'Free'),

(9, 'Tata Somba de Koussoucoingou', 'Tata Somba of Koussoucoingou', 'Atacora', 'Atacora', 'Culture',
 'Habitations traditionnelles fortifiées en argile, véritables châteaux forts miniatures uniques au monde.',
 'Traditional fortified clay dwellings, unique miniature castles found nowhere else in the world.',
 'Les Tata Somba sont construits par le peuple Otammari. Ce sont des maisons-forteresses à deux étages conçues à l''origine pour résister aux attaques des bêtes sauvages et des envahisseurs. Le rez-de-chaussée abrite les animaux et la cuisine, tandis que le premier étage sert de terrasse et de chambres.',
 'Tata Somba are built by the Otammari people. These are two-story fortress-houses originally designed to resist attacks from wild animals and invaders. The ground floor houses livestock and the kitchen, while the first floor serves as a terrace and sleeping quarters.',
 'tata_somba.jpg', 10.1834920, 1.2891230, '08h00 - 17h00 (Visite guidée locale)', '8:00 AM - 5:00 PM (Local guided tour)', '3 000 FCFA', '3,000 FCFA'),

(10, 'Centre Songhaï à Porto-Novo', 'Songhai Center in Porto-Novo', 'Ouémé', 'Oueme', 'Nature',
 'Un centre d''innovation agro-écologique de référence mondiale basé sur le principe du "zéro déchet".',
 'A world-renowned agro-ecological innovation center based on the "zero waste" principle.',
 'Fondé par le prêtre dominicain Godfrey Nzamujo en 1985, le centre Songhaï est un modèle mondial d''agriculture intégrée et durable. Il combine production végétale, élevage, pisciculture et énergies renouvelables dans un système circulaire fermé. Le site attire des étudiants et visiteurs du monde entier.',
 'Founded by Dominican priest Godfrey Nzamujo in 1985, the Songhai Center is a global model of integrated and sustainable agriculture. It combines crop production, livestock, fish farming, and renewable energy in a closed circular system. The site attracts students and visitors worldwide.',
 'songhai.jpg', 6.5053000, 2.6184000, '08h00 - 17h30', '8:00 AM - 5:30 PM', '2 000 FCFA', '2,000 FCFA');

-- 3. Insertion des photos supplémentaires pour la galerie
INSERT INTO lieux_photos (lieu_id, chemin_photo) VALUES
(1, 'ganvie_galerie1.jpg'), (1, 'ganvie_galerie2.jpg'),
(2, 'abomey_galerie1.jpg'), (2, 'abomey_galerie2.jpg'),
(3, 'route_esclaves_galerie1.jpg'), (3, 'route_esclaves_galerie2.jpg'),
(4, 'pendjari_galerie1.jpg'), (4, 'pendjari_galerie2.jpg'),
(5, 'grand_popo_galerie1.jpg'), (5, 'grand_popo_galerie2.jpg'),
(6, 'temple_pythons_galerie1.jpg'),
(7, 'kota_galerie1.jpg'),
(9, 'tata_somba_galerie1.jpg');

-- 4. Insertion des 5 hébergements
INSERT INTO hebergements (id, nom, type_fr, type_en, localite, quartier, prix_nuit, devise, description_fr, description_en, photo, email_contact) VALUES
(1, 'Hôtel de la Plage', 'Hôtel', 'Hotel', 'Grand-Popo', 'Front de mer', 45000, 'FCFA', 
 'Un havre de paix face à l''Océan Atlantique proposant des bungalows confortables, une piscine et un restaurant de spécialités locales.', 
 'A peaceful haven facing the Atlantic Ocean offering comfortable bungalows, a swimming pool, and a restaurant serving local specialties.', 
 'hotel_plage_popo.jpg', 'contact@hotelplagepopo.com'),

(2, 'Golden Tulip Le Diplomate', 'Hôtel', 'Hotel', 'Cotonou', 'Haie Vive', 95000, 'FCFA',
 'Hôtel haut de gamme situé dans le quartier résidentiel le plus dynamique de Cotonou, idéal pour les voyages d''affaires et de loisirs.',
 'Premium hotel located in the most dynamic residential area of Cotonou, ideal for business and leisure travel.',
 'golden_tulip.jpg', 'info@goldentulipcotonou.com'),

(3, 'La Casa de Ouidah', 'Maison d''hôtes', 'Guest house', 'Ouidah', 'Ahouandjigo', 25000, 'FCFA',
 'Une maison d''hôtes de charme au cœur du quartier historique, mariant architecture afro-brésilienne et confort moderne.',
 'A charming guest house in the heart of the historic district, blending Afro-Brazilian architecture with modern comfort.',
 'la_casa_ouidah.jpg', 'lacasaouidah@gmail.com'),

(4, 'Auberge de la Pendjari', 'Auberge', 'Inn', 'Tanguiéta', 'Entrée du Parc', 30000, 'FCFA',
 'L''adresse idéale pour les aventuriers en partance pour le safari. Chambres climatisées et conseils précieux de pisteurs.',
 'The perfect place for adventurers setting off on safari. Air-conditioned rooms and valuable tips from local trackers.',
 'auberge_pendjari.jpg', 'reservation@pendjari-auberge.bj'),

(5, 'Chez Valérie - Écolodge', 'Maison d''hôtes', 'Guest house', 'Ganvié', 'Lac Nokoué', 20000, 'FCFA',
 'Vivez l''expérience authentique d''une nuit sur pilotis. Bungalows traditionnels tout confort avec terrasse privée face au coucher du soleil.',
 'Experience the authentic feel of a night on stilts. Comfortable traditional bungalows with private terrace facing the sunset.',
 'ecolodge_ganvie.jpg', 'valerie.ganvie@yahoo.fr');

-- 5. Insertion des 4 guides
INSERT INTO guides (id, nom, photo, langues_fr, langues_en, zones_fr, zones_en, tarif_jour, devise, telephone, email, disponible, gamme, vehicule_modele, capacite_passagers, capacite_bagages) VALUES
(1, 'Jean-Albert Dossou', 'guide_jean.jpg', 
 'Français, Fon, Goun', 'French, Fon, Goun',
 'Sud Bénin (Cotonou, Ouidah, Ganvié, Porto-Novo)', 'Southern Benin (Cotonou, Ouidah, Ganvie, Porto-Novo)',
 15000, 'FCFA', '+229 97 12 34 56', 'jean.dossou@guide.bj', 1, 'Confort', 'Toyota RAV4', 4, 3),

(2, 'Aminata Diallo', 'guide_aminata.jpg',
 'Français, Dendi, Peulh, Anglais (base)', 'French, Dendi, Fulani, English (basic)',
 'Nord Bénin (Parc Pendjari, Natitingou, Koussoucoingou)', 'Northern Benin (Pendjari Park, Natitingou, Koussoucoingou)',
 20000, 'FCFA', '+229 95 98 76 54', 'aminata.diallo@guide.bj', 1, 'Economique', 'Dacia Duster', 4, 2),

(3, 'Samuel Agbado', 'guide_samuel.jpg',
 'Français, Anglais (courant), Fon', 'French, English (fluent), Fon',
 'Tout le Bénin (Spécialiste circuits historiques)', 'All of Benin (Specialist in historical tours)',
 25000, 'FCFA', '+229 90 22 33 44', 'samuel.agbado@guide.bj', 1, 'Premium', 'Toyota Land Cruiser', 7, 6),

(4, 'Christelle Lawson', 'guide_christelle.jpg',
 'Français, Mina, Adja, Anglais', 'French, Mina, Adja, English',
 'Sud et Centre Bénin (Grand-Popo, Dassa, Abomey)', 'South and Central Benin (Grand-Popo, Dassa, Abomey)',
 18000, 'FCFA', '+229 96 44 55 66', 'christelle.lawson@guide.bj', 1, 'Economique', 'Suzuki Ertiga', 7, 4);
