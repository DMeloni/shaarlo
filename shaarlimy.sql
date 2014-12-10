-- phpMyAdmin SQL Dump
-- version 4.1.9
-- http://www.phpmyadmin.net
--
-- Client :  shaarlimy.mysql.db
-- Généré le :  Mer 10 Décembre 2014 à 16:22
-- Version du serveur :  5.1.73-2+squeeze+build1+1-log
-- Version de PHP :  5.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `shaarlimy`
--

-- --------------------------------------------------------

--
-- Structure de la table `liens`
--

CREATE TABLE IF NOT EXISTS `liens` (
  `id` varchar(32) NOT NULL COMMENT 'MD5 de l''uuid ',
  `id_commun` varchar(32) NOT NULL COMMENT 'MD5 de l''url simplifiée',
  `article_url` varchar(255) NOT NULL COMMENT 'URL de l''article',
  `url_simplifiee` varchar(255) NOT NULL COMMENT 'URL simplifiée sans https',
  `article_titre` text NOT NULL COMMENT 'title de l''article',
  `article_description` mediumtext NOT NULL COMMENT 'description de l''article',
  `date_insert` varchar(14) NOT NULL COMMENT 'Date d''insertion',
  `date_update` varchar(14) NOT NULL COMMENT 'Date d''update',
  `spam` tinyint(1) NOT NULL,
  `article_date` varchar(14) NOT NULL,
  `article_uuid` varchar(255) NOT NULL COMMENT 'uuid de l''article',
  `id_rss` varchar(32) NOT NULL,
  `id_rss_origin` varchar(32) NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id_commun` (`id_commun`),
  KEY `id_rss` (`id_rss`),
  KEY `article_uuid` (`article_uuid`),
  FULLTEXT KEY `article_description` (`article_description`),
  FULLTEXT KEY `full` (`article_titre`,`article_description`),
  FULLTEXT KEY `article_url` (`article_url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `liens_clic`
--

CREATE TABLE IF NOT EXISTS `liens_clic` (
  `id_commun` varchar(32) NOT NULL,
  `nb_clic` int(10) NOT NULL DEFAULT '1',
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  PRIMARY KEY (`id_commun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mes_rss`
--

CREATE TABLE IF NOT EXISTS `mes_rss` (
  `username` varchar(255) NOT NULL,
  `id_rss` varchar(32) NOT NULL COMMENT 'id de la table rss',
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `alias` varchar(255) NOT NULL COMMENT 'Nom du flux à l''écran',
  `pseudo` varchar(255) NOT NULL,
  PRIMARY KEY (`username`,`id_rss`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `rss`
--

CREATE TABLE IF NOT EXISTS `rss` (
  `id` varchar(32) NOT NULL COMMENT 'md5 de l''url',
  `url` varchar(255) NOT NULL COMMENT 'url du flux rss',
  `rss_titre` varchar(255) NOT NULL COMMENT 'titre du flux rss',
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `url_simplifiee` varchar(255) NOT NULL,
  `url_favicon` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `shaarliste`
--

CREATE TABLE IF NOT EXISTS `shaarliste` (
  `username` varchar(255) NOT NULL,
  `pseudo` varchar(255) NOT NULL,
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `url` varchar(255) NOT NULL,
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `signalement`
--

CREATE TABLE IF NOT EXISTS `signalement` (
  `username` varchar(255) NOT NULL,
  `date_signalement` varchar(14) NOT NULL,
  `id_lien` varchar(32) NOT NULL COMMENT 'id de la table liens'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `nom` int(11) NOT NULL COMMENT 'valeur du tag',
  `id_lien` varchar(32) NOT NULL COMMENT 'id de la table liens',
  KEY `nom` (`nom`,`id_lien`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
