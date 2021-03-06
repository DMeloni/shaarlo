-- phpMyAdmin SQL Dump
-- version 4.2.12deb2+deb8u1build0.15.04.1
-- http://www.phpmyadmin.net
--
-- Client :  localhost
-- Généré le :  Jeu 01 Décembre 2016 à 12:16
-- Version du serveur :  5.6.28-0ubuntu0.15.04.1
-- Version de PHP :  5.6.4-4ubuntu6.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `shaarlimy`
--
CREATE DATABASE IF NOT EXISTS `shaarlimy` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `shaarlimy`;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
`id` int(11) NOT NULL,
  `categorie` varchar(50) NOT NULL,
  `tag` varchar(50) NOT NULL,
  `recurence` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `liens`
--

DROP TABLE IF EXISTS `liens`;
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
  `tags` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `liens_clic`
--

DROP TABLE IF EXISTS `liens_clic`;
CREATE TABLE IF NOT EXISTS `liens_clic` (
  `id_commun` varchar(32) NOT NULL,
  `nb_clic` int(10) NOT NULL DEFAULT '1',
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
`id` int(255) NOT NULL,
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `message` text NOT NULL,
  `id_shaarlieur` varchar(255) NOT NULL,
  `EST_TRAITE` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mes_rss`
--

DROP TABLE IF EXISTS `mes_rss`;
CREATE TABLE IF NOT EXISTS `mes_rss` (
  `username` varchar(255) NOT NULL,
  `id_rss` varchar(32) NOT NULL COMMENT 'id de la table rss',
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `alias` varchar(255) NOT NULL COMMENT 'Nom du flux à l''écran',
  `pseudo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `poussins_transactions`
--

DROP TABLE IF EXISTS `poussins_transactions`;
CREATE TABLE IF NOT EXISTS `poussins_transactions` (
`id` int(11) NOT NULL,
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `pseudo_source` varchar(50) NOT NULL,
  `pseudo_cible` varchar(50) NOT NULL,
  `date_jour` varchar(8) NOT NULL,
  `id_lien` varchar(32) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `rss`
--

DROP TABLE IF EXISTS `rss`;
CREATE TABLE IF NOT EXISTS `rss` (
  `id` varchar(32) NOT NULL COMMENT 'md5 de l''url',
  `url` varchar(255) NOT NULL COMMENT 'url du flux rss',
  `rss_titre` varchar(255) NOT NULL COMMENT 'titre du flux rss',
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `url_simplifiee` varchar(255) NOT NULL,
  `url_favicon` varchar(255) NOT NULL,
  `404` tinyint(1) NOT NULL DEFAULT '0',
  `erreur` tinyint(1) NOT NULL,
  `erreur_message` varchar(255) NOT NULL,
  `censure` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `shaarlieur`
--

DROP TABLE IF EXISTS `shaarlieur`;
CREATE TABLE IF NOT EXISTS `shaarlieur` (
  `id` varchar(50) NOT NULL,
  `pwd` varchar(80) NOT NULL COMMENT 'steak haché',
  `email` varchar(255) NOT NULL,
  `pseudo` varchar(255) NOT NULL,
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `url` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `inscription_auto` tinyint(1) NOT NULL DEFAULT '0',
  `date_derniere_connexion` varchar(14) NOT NULL,
  `nb_connexion` int(11) NOT NULL DEFAULT '0',
  `shaarli_url` varchar(255) NOT NULL,
  `shaarli_private` tinyint(1) NOT NULL DEFAULT '1',
  `shaarli_ok` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Indique si le shaarli est modéré',
  `shaarli_url_id_ok` varchar(255) NOT NULL,
  `shaarli_url_ok` varchar(255) NOT NULL,
  `soumission_date` varchar(14) DEFAULT NULL,
  `shaarli_on_abonnements` int(1) DEFAULT '1' COMMENT 'Indique si le shaarli apparait dans la page des abonnements',
  `shaarli_on_river` int(1) NOT NULL DEFAULT '1' COMMENT 'Indique si le shaarli doit apparaitre dans la page des flux',
  `shaarli_delai` int(11) NOT NULL DEFAULT '1' COMMENT 'Nombre de minute entre chaque appel',
  `poussins_limite` int(11) NOT NULL DEFAULT '5',
  `poussins_solde` int(11) NOT NULL,
  `shaarli_river_tags` varchar(200) NOT NULL,
  `shaarli_techniques_tags` varchar(50) NOT NULL,
  `moderateur` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `shaarlieur_liens_clic`
--

DROP TABLE IF EXISTS `shaarlieur_liens_clic`;
CREATE TABLE IF NOT EXISTS `shaarlieur_liens_clic` (
  `id_commun` varchar(32) NOT NULL,
  `id_shaarlieur` varchar(250) NOT NULL,
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `shaarlieur_liens_ignore`
--

DROP TABLE IF EXISTS `shaarlieur_liens_ignore`;
CREATE TABLE IF NOT EXISTS `shaarlieur_liens_ignore` (
  `id_commun` varchar(32) NOT NULL,
  `id_shaarlieur` varchar(250) NOT NULL,
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `shaarliste`
--

DROP TABLE IF EXISTS `shaarliste`;
CREATE TABLE IF NOT EXISTS `shaarliste` (
  `username` varchar(255) NOT NULL,
  `pseudo` varchar(255) NOT NULL,
  `date_insert` varchar(14) NOT NULL,
  `date_update` varchar(14) NOT NULL,
  `url` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `signalement`
--

DROP TABLE IF EXISTS `signalement`;
CREATE TABLE IF NOT EXISTS `signalement` (
  `username` varchar(255) NOT NULL,
  `date_signalement` varchar(14) NOT NULL,
  `id_lien` varchar(32) NOT NULL COMMENT 'id de la table liens'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
`id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL COMMENT 'valeur du tag',
  `id_lien` varchar(32) NOT NULL COMMENT 'id de la table liens',
  `date_update` varchar(14) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=141813 DEFAULT CHARSET=utf8;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `liens`
--
ALTER TABLE `liens`
 ADD PRIMARY KEY (`id`), ADD KEY `id_commun` (`id_commun`), ADD KEY `id_rss` (`id_rss`), ADD KEY `article_uuid` (`article_uuid`), ADD FULLTEXT KEY `article_description` (`article_description`), ADD FULLTEXT KEY `article_url` (`article_url`), ADD FULLTEXT KEY `article_titre` (`article_titre`), ADD FULLTEXT KEY `full` (`article_titre`,`article_description`);

--
-- Index pour la table `liens_clic`
--
ALTER TABLE `liens_clic`
 ADD PRIMARY KEY (`id_commun`);

--
-- Index pour la table `message`
--
ALTER TABLE `message`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mes_rss`
--
ALTER TABLE `mes_rss`
 ADD PRIMARY KEY (`username`,`id_rss`), ADD KEY `username` (`username`), ADD KEY `id_rss` (`id_rss`);

--
-- Index pour la table `poussins_transactions`
--
ALTER TABLE `poussins_transactions`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `pseudo_source` (`pseudo_source`,`pseudo_cible`,`date_jour`,`id_lien`);

--
-- Index pour la table `rss`
--
ALTER TABLE `rss`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idactive` (`id`,`active`), ADD KEY `active` (`active`), ADD KEY `date_insert` (`date_insert`), ADD KEY `date_update` (`date_update`);

--
-- Index pour la table `shaarlieur`
--
ALTER TABLE `shaarlieur`
 ADD UNIQUE KEY `username` (`id`), ADD KEY `shaarli_url_id_ok` (`shaarli_url_id_ok`), ADD KEY `shaarli_on_abonnements` (`shaarli_on_abonnements`);

--
-- Index pour la table `shaarlieur_liens_clic`
--
ALTER TABLE `shaarlieur_liens_clic`
 ADD PRIMARY KEY (`id_commun`,`id_shaarlieur`);

--
-- Index pour la table `shaarlieur_liens_ignore`
--
ALTER TABLE `shaarlieur_liens_ignore`
 ADD PRIMARY KEY (`id_commun`,`id_shaarlieur`);

--
-- Index pour la table `shaarliste`
--
ALTER TABLE `shaarliste`
 ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `tags`
--
ALTER TABLE `tags`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `nom` (`nom`,`id_lien`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `message`
--
ALTER TABLE `message`
MODIFY `id` int(255) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=19;
--
-- AUTO_INCREMENT pour la table `poussins_transactions`
--
ALTER TABLE `poussins_transactions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=33;
--
-- AUTO_INCREMENT pour la table `tags`
--
ALTER TABLE `tags`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=141813;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
