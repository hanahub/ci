-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2016 at 10:36 PM
-- Server version: 5.6.11
-- PHP Version: 5.5.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `supermug`
--
CREATE DATABASE IF NOT EXISTS `supermug` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `supermug`;

-- --------------------------------------------------------

--
-- Table structure for table `collection`
--

CREATE TABLE IF NOT EXISTS `collection` (
  `collectionId` int(12) NOT NULL AUTO_INCREMENT,
  `title` varchar(25) NOT NULL,
  `shopifyId` bigint(15) NOT NULL,
  `type` enum('smart','custom') NOT NULL DEFAULT 'custom',
  `audience` varchar(255) NOT NULL,
  `rating` enum('1','2','3','4','5') NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `projectId` int(5) NOT NULL,
  `last_published` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`collectionId`),
  UNIQUE KEY `shopifyId` (`shopifyId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3059 ;

-- --------------------------------------------------------

--
-- Table structure for table `facebook_page`
--

CREATE TABLE IF NOT EXISTS `facebook_page` (
  `pageId` int(12) NOT NULL AUTO_INCREMENT,
  `projectId` int(5) NOT NULL,
  `userId` int(11) NOT NULL,
  `facebookId` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `facebookUid` varchar(45) NOT NULL,
  `picture` varchar(255) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pageId`),
  UNIQUE KEY `projectId` (`projectId`,`userId`,`facebookId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4376 ;

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE IF NOT EXISTS `options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `page_collection_link`
--

CREATE TABLE IF NOT EXISTS `page_collection_link` (
  `id` bigint(15) NOT NULL AUTO_INCREMENT,
  `pageId` bigint(15) NOT NULL,
  `collectionId` bigint(15) NOT NULL,
  `projectId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pageId` (`pageId`,`collectionId`),
  UNIQUE KEY `pageId_2` (`pageId`,`collectionId`,`projectId`),
  UNIQUE KEY `pageId_3` (`pageId`,`collectionId`,`projectId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3057 ;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE IF NOT EXISTS `product` (
  `productId` bigint(15) NOT NULL AUTO_INCREMENT,
  `title` varchar(205) NOT NULL,
  `handle` varchar(205) NOT NULL,
  `image` varchar(305) DEFAULT NULL,
  `published_at` datetime NOT NULL,
  `shopifyId` bigint(15) NOT NULL,
  `projectId` int(5) NOT NULL DEFAULT '1',
  `targeting_page` int(12) DEFAULT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`productId`),
  UNIQUE KEY `id` (`productId`),
  UNIQUE KEY `shopifyId` (`shopifyId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=47679 ;

-- --------------------------------------------------------

--
-- Table structure for table `product_collection_link`
--

CREATE TABLE IF NOT EXISTS `product_collection_link` (
  `id` bigint(15) NOT NULL AUTO_INCREMENT,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `productId` bigint(15) NOT NULL,
  `collectionId` bigint(15) NOT NULL,
  `projectId` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId` (`productId`,`collectionId`,`projectId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=132487 ;

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE IF NOT EXISTS `project` (
  `projectId` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `shop_domain` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `currency` enum('$','£') NOT NULL DEFAULT '$',
  `token` varchar(100) NOT NULL,
  `api_key` varchar(100) NOT NULL,
  `secret` varchar(100) NOT NULL,
  `ad_account` varchar(30) NOT NULL,
  `default_page` int(12) NOT NULL,
  `default_target` int(12) NOT NULL,
  `ad_text` text NOT NULL,
  `color` enum('White','Black','Blue','Green','Navy','Pink','Purple','Red') NOT NULL DEFAULT 'White',
  `slideshow_text` text NOT NULL,
  `slideshow_title` varchar(255) NOT NULL,
  `slideshow_description` text NOT NULL,
  `carousel_text` text NOT NULL,
  PRIMARY KEY (`projectId`),
  UNIQUE KEY `id` (`projectId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `publish_product`
--

CREATE TABLE IF NOT EXISTS `publish_product` (
  `id` bigint(15) NOT NULL AUTO_INCREMENT,
  `productId` bigint(15) NOT NULL,
  `productIds` varchar(255) NOT NULL,
  `collectionId` bigint(15) NOT NULL,
  `pageId` varchar(30) NOT NULL,
  `postId` varchar(30) NOT NULL,
  `ad_id` varchar(30) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `ad_type` varchar(10) NOT NULL DEFAULT 'page_post',
  `description` text NOT NULL,
  `projectId` int(11) NOT NULL,
  `publishedAt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6170 ;

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) NOT NULL,
  `published` tinyint(4) NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1834 ;

-- --------------------------------------------------------

--
-- Table structure for table `targeting`
--

CREATE TABLE IF NOT EXISTS `targeting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projectId` int(3) NOT NULL,
  `collectionId` int(11) NOT NULL,
  `country` varchar(2) NOT NULL,
  `age_from` int(3) NOT NULL,
  `age_to` int(3) NOT NULL,
  `gender` enum('Men','Women','Both') NOT NULL DEFAULT 'Women',
  `interests` varchar(512) NOT NULL,
  `job_titles` varchar(512) NOT NULL,
  `employers` varchar(512) NOT NULL,
  `fields_of_study` varchar(512) NOT NULL,
  `schools` varchar(512) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1403 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facebook_uid` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `picture` varchar(255) NOT NULL,
  `facebook_access_token` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
