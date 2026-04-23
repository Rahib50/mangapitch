-- MangaPitch Database Schema (phpMyAdmin / XAMPP friendly)
-- How to use (phpMyAdmin):
-- 1) Open phpMyAdmin -> Import -> choose this file -> Go

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `mangapitch`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `mangapitch`;

-- Core user table
CREATE TABLE IF NOT EXISTS `Users` (
  `UserID` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(100) NOT NULL,
  `Email` VARCHAR(150) NOT NULL,
  `Password` VARCHAR(255) NOT NULL,
  `Role` ENUM('Mangaka','Studio','Admin') NOT NULL,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `uq_users_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role-specific tables (1:1 with Users)
CREATE TABLE IF NOT EXISTS `Mangaka` (
  `UserID` INT NOT NULL,
  `PortfolioLink` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  CONSTRAINT `fk_mangaka_user`
    FOREIGN KEY (`UserID`) REFERENCES `Users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Studio` (
  `UserID` INT NOT NULL,
  `RegistrationNumber` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  CONSTRAINT `fk_studio_user`
    FOREIGN KEY (`UserID`) REFERENCES `Users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Admin` (
  `UserID` INT NOT NULL,
  `AdminLevel` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`UserID`),
  CONSTRAINT `fk_admin_user`
    FOREIGN KEY (`UserID`) REFERENCES `Users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Connections between users
CREATE TABLE IF NOT EXISTS `Connections` (
  `UserID_A` INT NOT NULL,
  `UserID_B` INT NOT NULL,
  PRIMARY KEY (`UserID_A`, `UserID_B`),
  KEY `idx_connections_b` (`UserID_B`),
  CONSTRAINT `fk_connections_a`
    FOREIGN KEY (`UserID_A`) REFERENCES `Users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_connections_b`
    FOREIGN KEY (`UserID_B`) REFERENCES `Users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manga tables
CREATE TABLE IF NOT EXISTS `Manga` (
  `MangaID` INT NOT NULL AUTO_INCREMENT,
  `MangakaID` INT NOT NULL,
  `Title` VARCHAR(200) NOT NULL,
  `Synopsis` TEXT DEFAULT NULL,
  `PublishDate` DATE DEFAULT NULL,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`MangaID`),
  KEY `idx_manga_mangaka` (`MangakaID`),
  CONSTRAINT `fk_manga_mangaka`
    FOREIGN KEY (`MangakaID`) REFERENCES `Mangaka` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Genre` (
  `GenreID` INT NOT NULL AUTO_INCREMENT,
  `GenreName` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`GenreID`),
  UNIQUE KEY `uq_genre_name` (`GenreName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Manga_Genre_Map` (
  `MangaID` INT NOT NULL,
  `GenreID` INT NOT NULL,
  PRIMARY KEY (`MangaID`, `GenreID`),
  KEY `idx_mgm_genre` (`GenreID`),
  CONSTRAINT `fk_mgm_manga`
    FOREIGN KEY (`MangaID`) REFERENCES `Manga` (`MangaID`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mgm_genre`
    FOREIGN KEY (`GenreID`) REFERENCES `Genre` (`GenreID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Analytics` (
  `AnalyticsID` INT NOT NULL AUTO_INCREMENT,
  `MangaID` INT NOT NULL,
  `TotalViews` INT NOT NULL DEFAULT 0,
  `VolumesSold` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`AnalyticsID`),
  UNIQUE KEY `uq_analytics_manga` (`MangaID`),
  CONSTRAINT `fk_analytics_manga`
    FOREIGN KEY (`MangaID`) REFERENCES `Manga` (`MangaID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bids / Contracts
CREATE TABLE IF NOT EXISTS `Bids` (
  `BidID` INT NOT NULL AUTO_INCREMENT,
  `MangaID` INT NOT NULL,
  `StudioID` INT NOT NULL,
  `BidAmount` DECIMAL(12,2) NOT NULL,
  `Status` ENUM('Pending','Accepted','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`BidID`),
  KEY `idx_bids_manga` (`MangaID`),
  KEY `idx_bids_studio` (`StudioID`),
  CONSTRAINT `fk_bids_manga`
    FOREIGN KEY (`MangaID`) REFERENCES `Manga` (`MangaID`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bids_studio`
    FOREIGN KEY (`StudioID`) REFERENCES `Studio` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Contracts` (
  `ContractID` INT NOT NULL AUTO_INCREMENT,
  `BidID` INT NOT NULL,
  `SignedDate` DATE DEFAULT NULL,
  `ProductStatus` ENUM('Pre-Production','In-Production','Post-Production','Released') NOT NULL DEFAULT 'Pre-Production',
  PRIMARY KEY (`ContractID`),
  UNIQUE KEY `uq_contracts_bid` (`BidID`),
  CONSTRAINT `fk_contracts_bid`
    FOREIGN KEY (`BidID`) REFERENCES `Bids` (`BidID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages
CREATE TABLE IF NOT EXISTS `Messages` (
  `MessageID` INT NOT NULL AUTO_INCREMENT,
  `SenderID` INT NOT NULL,
  `ReceiverID` INT NOT NULL,
  `MessageText` TEXT NOT NULL,
  `Timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`MessageID`),
  KEY `idx_messages_sender` (`SenderID`),
  KEY `idx_messages_receiver` (`ReceiverID`),
  CONSTRAINT `fk_messages_sender`
    FOREIGN KEY (`SenderID`) REFERENCES `Users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_receiver`
    FOREIGN KEY (`ReceiverID`) REFERENCES `Users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data
INSERT INTO `Genre` (`GenreName`) VALUES
  ('Action'), ('Adventure'), ('Comedy'), ('Drama'),
  ('Fantasy'), ('Horror'), ('Romance'), ('Sci-Fi'),
  ('Slice of Life'), ('Thriller')
ON DUPLICATE KEY UPDATE `GenreName` = VALUES(`GenreName`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
