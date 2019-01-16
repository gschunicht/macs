-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 16, 2019 at 01:11 PM
-- Server version: 10.2.15-MariaDB-10.2.15+maria~stretch
-- PHP Version: 7.0.33-0+deb9u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `macs`
--

-- --------------------------------------------------------

--
-- Table structure for table `access`
--

CREATE TABLE `access` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mach_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `event` text NOT NULL,
  `login_id` int(11) DEFAULT NULL,
  `usage` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mach`
--

CREATE TABLE `mach` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `mach_nr` int(11) NOT NULL,
  `desc` text NOT NULL,
  `last_seen` int(11) NOT NULL,
  `active` int(1) NOT NULL,
  `version` int(11) NOT NULL DEFAULT -1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `update_available`
--

CREATE TABLE `update_available` (
  `id` int(11) NOT NULL,
  `mach_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `login` text NOT NULL,
  `hash` text NOT NULL,
  `badge_id` text NOT NULL,
  `email` text NOT NULL,
  `last_seen` int(11) NOT NULL,
  `active` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_Access`
-- (See below for the actual view)
--
CREATE TABLE `view_Access` (
`id` int(11)
,`user_id` int(11)
,`mach_id` int(11)
,`userName` text
,`machName` text
,`machDesc` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_EventLog`
-- (See below for the actual view)
--
CREATE TABLE `view_EventLog` (
`userName` text
,`machName` text
,`logonName` text
,`event` text
,`logDateTime` datetime
,`usage` int(11)
,`id` int(11)
,`user_id` int(11)
,`machine_id` int(11)
,`timestamp` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_MachLastUse`
-- (See below for the actual view)
--
CREATE TABLE `view_MachLastUse` (
`maxID` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `wifi`
--

CREATE TABLE `wifi` (
  `id` int(11) NOT NULL,
  `ssid` text NOT NULL,
  `pw` text NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure for view `view_Access`
--
DROP TABLE IF EXISTS `view_Access`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_Access`  AS  select `a`.`id` AS `id`,`a`.`user_id` AS `user_id`,`a`.`mach_id` AS `mach_id`,`u`.`name` AS `userName`,`m`.`name` AS `machName`,`m`.`desc` AS `machDesc` from ((`access` `a` left join `user` `u` on(`a`.`user_id` = `u`.`id`)) left join `mach` `m` on(`a`.`mach_id` = `m`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_EventLog`
--
DROP TABLE IF EXISTS `view_EventLog`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_EventLog`  AS  select `user`.`name` AS `userName`,`mach`.`name` AS `machName`,`l`.`name` AS `logonName`,`log`.`event` AS `event`,from_unixtime(`log`.`timestamp`) AS `logDateTime`,`log`.`usage` AS `usage`,`log`.`id` AS `id`,`log`.`user_id` AS `user_id`,`log`.`machine_id` AS `machine_id`,`log`.`timestamp` AS `timestamp` from (((`log` left join `mach` on(`log`.`machine_id` = `mach`.`id`)) left join `user` on(`log`.`user_id` = `user`.`id`)) left join `user` `l` on(`log`.`login_id` = `l`.`id`)) order by `log`.`timestamp` desc ;

-- --------------------------------------------------------

--
-- Structure for view `view_MachLastUse`
--
DROP TABLE IF EXISTS `view_MachLastUse`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_MachLastUse`  AS  select max(`log`.`id`) AS `maxID` from `log` where `log`.`event` in ('LOCKED','UNLOCKED') group by `log`.`machine_id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access`
--
ALTER TABLE `access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `MachUser` (`mach_id`,`user_id`),
  ADD KEY `id_2` (`id`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `machine_id` (`machine_id`);

--
-- Indexes for table `mach`
--
ALTER TABLE `mach`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `update_available`
--
ALTER TABLE `update_available`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `id_2` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access`
--
ALTER TABLE `access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1610;
--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8606;
--
-- AUTO_INCREMENT for table `mach`
--
ALTER TABLE `mach`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;
--
-- AUTO_INCREMENT for table `update_available`
--
ALTER TABLE `update_available`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21932;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=299;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
