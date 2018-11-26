-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 26, 2018 at 07:15 PM
-- Server version: 5.5.47-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `google`
--

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `prime_key` varchar(24) NOT NULL,
  `owner` text NOT NULL,
  `hash` text NOT NULL,
  `name` text NOT NULL,
  `size` bigint(20) NOT NULL,
  `listed` int(11) NOT NULL,
  `active` int(11) NOT NULL,
  PRIMARY KEY (`prime_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mirrors`
--

CREATE TABLE IF NOT EXISTS `mirrors` (
  `prime_key` int(11) NOT NULL AUTO_INCREMENT,
  `owner` text NOT NULL,
  `parent` text NOT NULL,
  `id` text NOT NULL,
  `failures` int(11) NOT NULL,
  PRIMARY KEY (`prime_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=133 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `prime_key` varchar(21) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `permission` int(11) NOT NULL,
  `joined` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `join_ip` text NOT NULL,
  `last_ip` text NOT NULL,
  `alias` text NOT NULL,
  `show_private` int(11) NOT NULL,
  PRIMARY KEY (`prime_key`),
  UNIQUE KEY `prime_key` (`prime_key`),
  UNIQUE KEY `prime_key_2` (`prime_key`),
  UNIQUE KEY `prime_key_3` (`prime_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
