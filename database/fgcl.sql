-- =============================================================
-- BASE DE DONNÉES : FGCL — fgcl_db
-- Compatible : MySQL 5.7+ / MariaDB 10.3+
-- IMPORTANT : Importer via phpMyAdmin ou mysql -u root fgcl_db < fgcl.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS fgcl_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE fgcl_db;

-- Désactiver les contraintes FK pendant l'import
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS factures;
DROP TABLE IF EXISTS interventions;
DROP TABLE IF EXISTS prestations;
DROP TABLE IF EXISTS utilisateurs;

SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════
-- TABLE : utilisateurs (admin + techniciens + clients)
-- ═══════════════════════════════════════════
CREATE TABLE utilisateurs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(100)  NOT NULL,
    prenom       VARCHAR(100)  NOT NULL,
    email        VARCHAR(150)  NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255)  NOT NULL,
    role         ENUM('admin','technicien','client') NOT NULL DEFAULT 'client',
    telephone    VARCHAR(20)   DEFAULT NULL,
    adresse      TEXT          DEFAULT NULL,
    actif        TINYINT(1)    NOT NULL DEFAULT 1,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════
-- TABLE : prestations
-- ═══════════════════════════════════════════
CREATE TABLE prestations (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    titre         VARCHAR(200)   NOT NULL,
    description   TEXT           DEFAULT NULL,
    client_id     INT            NOT NULL,
    technicien_id INT            DEFAULT NULL,
    statut        ENUM('en_attente','en_cours','termine','annule') NOT NULL DEFAULT 'en_attente',
    date_debut    DATE           DEFAULT NULL,
    date_fin      DATE           DEFAULT NULL,
    montant       DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prest_client FOREIGN KEY (client_id)
        REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    CONSTRAINT fk_prest_tech FOREIGN KEY (technicien_id)
        REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════
-- TABLE : interventions
-- ═══════════════════════════════════════════
CREATE TABLE interventions (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    prestation_id     INT  NOT NULL,
    technicien_id     INT  NOT NULL,
    rapport           TEXT DEFAULT NULL,
    statut            ENUM('planifie','en_cours','termine') NOT NULL DEFAULT 'planifie',
    date_intervention DATETIME NOT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_intv_prest FOREIGN KEY (prestation_id)
        REFERENCES prestations(id) ON DELETE CASCADE,
    CONSTRAINT fk_intv_tech FOREIGN KEY (technicien_id)
        REFERENCES utilisateurs(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════
-- TABLE : factures
-- ═══════════════════════════════════════════
CREATE TABLE factures (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    prestation_id INT           NOT NULL UNIQUE,
    client_id     INT           NOT NULL,
    numero        VARCHAR(50)   NOT NULL UNIQUE,
    montant_ht    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tva           DECIMAL(5,2)  NOT NULL DEFAULT 19.25,
    montant_ttc   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    statut        ENUM('impayee','payee','annulee') NOT NULL DEFAULT 'impayee',
    date_emission DATE          NOT NULL,
    date_echeance DATE          DEFAULT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fact_prest  FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE RESTRICT,
    CONSTRAINT fk_fact_client FOREIGN KEY (client_id)     REFERENCES utilisateurs(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════
-- DONNÉES DE TEST
-- Mot de passe : Admin123! pour admin
-- Mot de passe : Tech123!  pour techniciens
-- Mot de passe : Client123! pour clients
-- (Hashs générés avec password_hash($pass, PASSWORD_DEFAULT))
-- ═══════════════════════════════════════════

INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone, adresse) VALUES
('FGCL',    'Admin',   'admin@fgcl.com',         '$2y$10$TKh8H1.PfbuNIlQqYTMz3eK.k3hcFn7nTe3enFPnv3YxbT9h1VYLK', 'admin',      '699 00 00 00', 'Siège FGCL, Douala'),
('Chimson', 'Paul',    'chimson@fgcl.com',        '$2y$10$TKh8H1.PfbuNIlQqYTMz3eK.k3hcFn7nTe3enFPnv3YxbT9h1VYLK', 'technicien', '699 77 42 30', 'Bonanjo, Douala'),
('Martin',  'Jean',    'martin@fgcl.com',         '$2y$10$TKh8H1.PfbuNIlQqYTMz3eK.k3hcFn7nTe3enFPnv3YxbT9h1VYLK', 'technicien', '652 87 42 30', 'Akwa, Douala'),
('Njoya',   'Sophie',  'sophie.njoya@gmail.com',  '$2y$10$TKh8H1.PfbuNIlQqYTMz3eK.k3hcFn7nTe3enFPnv3YxbT9h1VYLK', 'client',     '677 45 89 23', 'Bonanjo, Douala'),
('Savio',   'Dominic', 'savio@yahoo.com',         '$2y$10$TKh8H1.PfbuNIlQqYTMz3eK.k3hcFn7nTe3enFPnv3YxbT9h1VYLK', 'client',     '699 00 00 01', 'Ndokoti, Douala'),
('Brasseries','du Cameroun','brasseries@gmail.com','$2y$10$TKh8H1.PfbuNIlQqYTMz3eK.k3hcFn7nTe3enFPnv3YxbT9h1VYLK', 'client',    '687 24 37 23', 'Ndokoti, Douala');

-- IMPORTANT : Le hash ci-dessus = mot de passe "password"
-- Pour créer un vrai hash : php -r "echo password_hash('VotreMotDePasse', PASSWORD_DEFAULT);"

INSERT INTO prestations (titre, description, client_id, technicien_id, statut, date_debut, date_fin, montant) VALUES
('Développement logiciel',  'Création application mobile et web.',        4, 3, 'en_cours',  '2026-03-01', '2026-04-30', 300000.00),
('Installation réseau',     'Installation et sécurisation du réseau.',    6, 2, 'en_attente','2026-03-20', '2026-03-25', 200000.00),
('Maintenance IT',          'Maintenance du parc informatique complet.',   5, 2, 'termine',   '2026-01-10', '2026-01-20', 150000.00);

INSERT INTO interventions (prestation_id, technicien_id, rapport, statut, date_intervention) VALUES
(1, 3, 'Analyse des besoins effectuée. Maquettes validées.', 'en_cours', '2026-03-15 10:00:00'),
(2, 2, NULL,                                                 'planifie', '2026-03-20 09:00:00'),
(3, 2, 'Maintenance terminée. 12 postes mis à jour.',       'termine',  '2026-01-15 14:00:00');

INSERT INTO factures (prestation_id, client_id, numero, montant_ht, tva, montant_ttc, statut, date_emission, date_echeance) VALUES
(3, 5, 'FAC-2026-0001', 150000.00, 19.25, 178875.00, 'payee', '2026-01-21', '2026-02-21');
