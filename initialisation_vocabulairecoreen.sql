-- Initialisation reconstruite depuis les fichiers PHP de l'application.
-- À utiliser uniquement pour une nouvelle installation SANS ancienne base à restaurer.
-- Ce fichier ne restaure aucun compte ni historique antérieur.
-- Pas de DROP, ni de réinitialisation de mots de passe.
CREATE DATABASE IF NOT EXISTS `vocabulairecoreen`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vocabulairecoreen`;

CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `User_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Login` VARCHAR(100) NOT NULL,
  `Password` VARCHAR(255) NOT NULL COMMENT 'PRIVATE',
  `Nom` VARCHAR(150) NOT NULL DEFAULT '',
  `Prenom` VARCHAR(150) NOT NULL DEFAULT '',
  `Mail1` VARCHAR(255) DEFAULT NULL,
  `Mail2` VARCHAR(255) DEFAULT NULL,
  `Telephone` VARCHAR(40) DEFAULT NULL,
  `loginServiceSMSFree` VARCHAR(255) DEFAULT NULL COMMENT 'PRIVATE',
  `clefServiceSMSFree` VARCHAR(255) DEFAULT NULL COMMENT 'PRIVATE',
  `KeyAPI` TEXT DEFAULT NULL COMMENT 'PRIVATE',
  `SecretAPI` TEXT DEFAULT NULL COMMENT 'PRIVATE',
  `mail1Enable` TINYINT(1) NOT NULL DEFAULT 0,
  `mail2Enable` TINYINT(1) NOT NULL DEFAULT 0,
  `ServiceSMSFreeEnable` TINYINT(1) NOT NULL DEFAULT 0,
  `Date_cre` DATETIME DEFAULT NULL,
  `Date_upd` DATETIME DEFAULT NULL,
  `Administrateur` TINYINT(1) NOT NULL DEFAULT 0,
  `SAdministrateur` TINYINT(1) NOT NULL DEFAULT 0,
  `Valider` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`User_id`),
  UNIQUE KEY `uniq_login` (`Login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `historique` (
  `Id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `User_id` INT UNSIGNED NOT NULL,
  `Date` DATETIME NOT NULL,
  `AdresseIp` VARCHAR(45) DEFAULT NULL,
  `Host` TEXT DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `idx_user_date` (`User_id`, `Date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `parametrage` (
  `Clef` VARCHAR(128) NOT NULL,
  `Valeur` TEXT DEFAULT NULL,
  `Unite` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`Clef`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `table_date_update` (
  `Table_name` VARCHAR(64) NOT NULL,
  `Date` DATETIME(1) NOT NULL,
  `LOCK_User_id` INT DEFAULT NULL,
  `Date_out` DATETIME DEFAULT NULL,
  PRIMARY KEY (`Table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- L'utilisateur SQL coreen_app dispose déjà de SELECT, INSERT, UPDATE, DELETE
-- sur vocabulairecoreen.* via le GRANT exécuté précédemment.
-- Pour créer un compte dans l'interface, puis le valider, consulter les instructions.
