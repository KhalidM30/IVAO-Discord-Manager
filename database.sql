-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 09:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `staff_discord`
--

-- --------------------------------------------------------

--
-- Table structure for table `discord_guilds`
--

CREATE TABLE `discord_guilds` (
  `id` int(11) NOT NULL,
  `guild_name` varchar(235) NOT NULL,
  `guild_id` varchar(235) NOT NULL,
  `dep` int(11) DEFAULT NULL,
  `staff_type` enum('HQ','DIV','ALL') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discord_guilds`
--

INSERT INTO `discord_guilds` (`id`, `guild_name`, `guild_id`, `dep`, `staff_type`) VALUES
(2, 'IVAO Staff', '111111', NULL, 'ALL'),
(3, 'IVAO Events Department', '111154762', 4, 'ALL'),
(4, 'IVAO ATC Department', '11111190', 1, 'ALL'),
(5, 'IVAO Flight Operations Department', '14144493831', 6, 'ALL'),
(6, 'IVAO World Tour Department', '441050414', 12, 'HQ');

-- --------------------------------------------------------

--
-- Table structure for table `discord_roles`
--

CREATE TABLE `discord_roles` (
  `role_id` int(11) NOT NULL,
  `guild_id` int(11) DEFAULT NULL,
  `discord_id` varchar(25) DEFAULT NULL,
  `discord_name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discord_roles`
--

INSERT INTO `discord_roles` (`role_id`, `guild_id`, `discord_id`, `discord_name`) VALUES
(1, 2, '1457', 'CEO'),
(2, 2, '140188502', 'Executive Directors'),
(3, 2, '14461', 'Executive Assistants'),
(4, 2, '1401086724834201692', 'Executive'),
(5, 2, '1401086726', 'PRE/VPRE'),
(6, 2, '18026062850', 'Board of Governors'),
(7, 2, '14010850560', 'BoG Advisor'),
(72, 6, '17948496', 'Executive Directors'),
(73, 6, '14178866', 'Executive Assistants'),
(74, 6, '14119134', 'Executive'),
(75, 6, '141772155197', 'World Tours (HQ)');

-- --------------------------------------------------------

--
-- Table structure for table `discord_users`
--

CREATE TABLE `discord_users` (
  `user_id` int(20) NOT NULL,
  `discord_id` varchar(21) NOT NULL,
  `vid` int(11) NOT NULL,
  `name` text NOT NULL,
  `division` text NOT NULL,
  `nickname` text NOT NULL,
  `positions` text NOT NULL,
  `supervisor` tinyint(1) NOT NULL,
  `discord_access_token` varchar(200) NOT NULL,
  `discord_refresh_token` varchar(200) NOT NULL,
  `joined_at` datetime NOT NULL,
  `last_checked_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discord_token_expires` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ivao_departments`
--

CREATE TABLE `ivao_departments` (
  `dep_id` int(11) NOT NULL,
  `dep_code` varchar(11) NOT NULL,
  `dep_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ivao_departments`
--

INSERT INTO `ivao_departments` (`dep_id`, `dep_code`, `dep_name`) VALUES
(1, 'ATC', 'ATC Operations'),
(2, 'BOG', 'Board of Governors'),
(3, 'DEV', 'Development Operations'),
(4, 'EVENT', 'Events'),
(5, 'EXEC', 'Executive'),
(6, 'FO', 'Flight Operations'),
(7, 'MA', 'Membership'),
(8, 'NPO', 'NPO Officers'),
(9, 'PR', 'Public Relations'),
(10, 'SO', 'Special Operations'),
(11, 'TRA', 'Training'),
(12, 'WT', 'World Tour');

-- --------------------------------------------------------

--
-- Table structure for table `ivao_department_teams`
--

CREATE TABLE `ivao_department_teams` (
  `team_id` int(11) NOT NULL,
  `dep_id` int(11) DEFAULT NULL,
  `team_code` varchar(30) NOT NULL,
  `team_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ivao_department_teams`
--

INSERT INTO `ivao_department_teams` (`team_id`, `dep_id`, `team_code`, `team_name`) VALUES
(1, 1, 'ATC-DIV-ADV', 'ATC Operations Division Advisors'),
(2, 1, 'ATC-DIV-COORD', 'ATC Operations Coordinators'),
(3, 1, 'ATC-DIV-FIR-ADV', 'Division FIR Advisors'),
(4, 1, 'ATC-DIV-FIR-CH', 'Division FIR Chiefs'),
(5, 1, 'ATC-HQ-ADV', 'ATC Operations HQ Advisors'),
(6, 1, 'ATC-HQ-DIR', 'ATC Operations Directors'),
(7, 2, 'BOG', 'Board of Governors'),
(8, 2, 'BOG-LEGAL', 'Board Advisory Council (Legal)'),
(9, 3, 'DEV-DIV-WM', 'Division Webmasters'),
(10, 3, 'DEV-HQ-DIR', 'Development Operations Directors'),
(11, 3, 'DEV-HQ-DM', 'Documentation Management'),
(12, 3, 'DEV-HQ-FA', 'Forum Administration'),
(13, 3, 'DEV-HQ-MTL', 'Management Traffic Liveries'),
(14, 3, 'DEV-HQ-SA', 'Server Administration Team'),
(15, 3, 'DEV-HQ-SOFT', 'Software Developement Team'),
(16, 3, 'DEV-HQ-US', 'User Support'),
(17, 3, 'DEV-HQ-WEB', 'Web Developement Team'),
(18, 3, 'LEGACY', 'Legacy unused roles'),
(19, 4, 'EVENT-DIV-ADV', 'Events Division Advisors'),
(20, 4, 'EVENT-DIV-COORD', 'Events Division Coordinators'),
(21, 4, 'EVENT-HQ-ADV', 'Events HQ Advisors'),
(22, 4, 'EVENT-HQ-DIR', 'Events HQ Directors'),
(23, 5, 'DIV-DIR', 'Division Directors'),
(24, 5, 'EXEC-AS', 'Executive Directors for Activity Services'),
(25, 5, 'EXEC-CS', 'Executive Directors for Central Services'),
(26, 5, 'EXEC-HQ', 'Chief Executive Officer'),
(27, 5, 'EXEC-IS', 'Executive Directors for Infrastructure Services'),
(28, 5, 'EXEC-LS', 'Executive Directors for Local Services'),
(29, 5, 'EXEC-OS', 'Executive Directors for Operational Services'),
(30, 6, 'FO-DIV-ADV', 'Flight Operations Division Advisors'),
(31, 6, 'FO-DIV-COORD', 'Flight Operations Division Coordinators'),
(32, 6, 'FO-HQ-ADV', 'Flight Operations HQ Advisors'),
(33, 6, 'FO-HQ-DIR', 'Flight Operations HQ Directors'),
(34, 7, 'MA-DIV-ADV', 'Membership Division Advisors'),
(35, 7, 'MA-DIV-COORD', 'Membership Division Coordinators'),
(36, 7, 'MA-HQ-ADV', 'Membership HQ Advisors'),
(37, 7, 'MA-HQ-DIR', 'Membership Directors'),
(38, 7, 'MA-HQ-HLP', 'Membership Helpdesk'),
(39, 8, 'NPO', 'NPO Officers'),
(40, 9, 'PR-DIV-ADV', 'Public Relations Directors'),
(41, 9, 'PR-DIV-COORD', 'Public Relations Directors'),
(42, 9, 'PR-HQ-CE', 'Community Engagement Team'),
(43, 9, 'PR-HQ-CP', 'Creator Partnership'),
(44, 9, 'PR-HQ-CT', 'Content Team'),
(45, 9, 'PR-HQ-DC', 'Digital Content Team'),
(46, 9, 'PR-HQ-DIR', 'Public Relations Directors'),
(47, 9, 'PR-HQ-EC', 'Editorial Content Team'),
(48, 9, 'PR-HQ-SM', 'Social Media Team'),
(49, 10, 'SO-DIV-ADV', 'Special Operations Division Advisors'),
(50, 10, 'SO-DIV-COORD', 'Special Operations Division Coordinators'),
(51, 10, 'SO-HQ-ADV', 'Special Operations HQ Advisors'),
(52, 10, 'SO-HQ-DIR', 'Special Operations Directors'),
(53, 10, 'SO-HQ-TRA', 'Special Operations HQ Training Advisors'),
(54, 11, 'TRA-DIV-COORD', 'Training Division Coordinators'),
(55, 11, 'TRA-DIV-TADV', 'Training Division Training Advisors'),
(56, 11, 'TRA-DIV-TRA', 'Training Division Trainer'),
(57, 11, 'TRA-HQ-DIR', 'Training Directors'),
(58, 11, 'TRA-HQ-HPA', 'Training HQ Pilots Advisors'),
(59, 11, 'TRA-HQ-HPM', 'Training HQ Pilots Managers'),
(60, 11, 'TRA-HQ-SRTA', 'Training Senior Training Advisors'),
(61, 11, 'TRA-HQ-TDA', 'Training HQ Documentation Advisors'),
(62, 11, 'TRA-HQ-TDM', 'Training HQ Documentation Managers'),
(63, 12, 'WT-HQ-ADV', 'World Tour HQ Advisors'),
(64, 12, 'WT-HQ-DIR', 'World Tour HQ Directors');

-- --------------------------------------------------------

--
-- Table structure for table `position_roles`
--

CREATE TABLE `position_roles` (
  `position_role_id` int(11) NOT NULL,
  `position` varchar(10) NOT NULL,
  `discord_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Type the position with the wanted discord roles. if the position has many users, just but " * " after the prefix';

--
-- Dumping data for table `position_roles`
--

INSERT INTO `position_roles` (`position_role_id`, `position`, `discord_id`) VALUES
(1, 'CEO', 1),
(2, 'CEO', 4),
(3, 'EXEC*', 2),
(4, 'EXEC*', 4),
(5, 'EXA*', 3),
(6, 'EXA*', 4);
-- --------------------------------------------------------

--
-- Table structure for table `team_roles`
--

CREATE TABLE `team_roles` (
  `team_role_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `discord_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `temp_users`
--

CREATE TABLE `temp_users` (
  `token` varchar(200) NOT NULL,
  `vid` int(11) NOT NULL,
  `name` text NOT NULL,
  `division` text NOT NULL,
  `positions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`positions`)),
  `supervisor` tinyint(1) NOT NULL,
  `request_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `discord_guilds`
--
ALTER TABLE `discord_guilds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_guild_linking` (`dep`);

--
-- Indexes for table `discord_roles`
--
ALTER TABLE `discord_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD KEY `guild_roles_linking` (`guild_id`);

--
-- Indexes for table `discord_users`
--
ALTER TABLE `discord_users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `ivao_departments`
--
ALTER TABLE `ivao_departments`
  ADD PRIMARY KEY (`dep_id`);

--
-- Indexes for table `ivao_department_teams`
--
ALTER TABLE `ivao_department_teams`
  ADD PRIMARY KEY (`team_id`),
  ADD KEY `department_linking` (`dep_id`);

--
-- Indexes for table `position_roles`
--
ALTER TABLE `position_roles`
  ADD PRIMARY KEY (`position_role_id`),
  ADD KEY `position_linking` (`discord_id`);

--
-- Indexes for table `team_roles`
--
ALTER TABLE `team_roles`
  ADD PRIMARY KEY (`team_role_id`),
  ADD KEY `team_linking` (`team_id`),
  ADD KEY `discord_linking` (`discord_id`);

--
-- Indexes for table `temp_users`
--
ALTER TABLE `temp_users`
  ADD PRIMARY KEY (`token`);

--
-- AUTO_INCREMENT for dumped tables
--


--
-- AUTO_INCREMENT for table `discord_guilds`
--
ALTER TABLE `discord_guilds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `discord_roles`
--
ALTER TABLE `discord_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `discord_users`
--
ALTER TABLE `discord_users`
  MODIFY `user_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `ivao_departments`
--
ALTER TABLE `ivao_departments`
  MODIFY `dep_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ivao_department_teams`
--
ALTER TABLE `ivao_department_teams`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `position_roles`
--
ALTER TABLE `position_roles`
  MODIFY `position_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `team_roles`
--
ALTER TABLE `team_roles`
  MODIFY `team_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `discord_guilds`
--
ALTER TABLE `discord_guilds`
  ADD CONSTRAINT `department_guild_linking` FOREIGN KEY (`dep`) REFERENCES `ivao_departments` (`dep_id`);

--
-- Constraints for table `discord_roles`
--
ALTER TABLE `discord_roles`
  ADD CONSTRAINT `guild_roles_linking` FOREIGN KEY (`guild_id`) REFERENCES `discord_guilds` (`id`);

--
-- Constraints for table `ivao_department_teams`
--
ALTER TABLE `ivao_department_teams`
  ADD CONSTRAINT `department_linking` FOREIGN KEY (`dep_id`) REFERENCES `ivao_departments` (`dep_id`);

--
-- Constraints for table `position_roles`
--
ALTER TABLE `position_roles`
  ADD CONSTRAINT `position_linking` FOREIGN KEY (`discord_id`) REFERENCES `discord_roles` (`role_id`);

--
-- Constraints for table `team_roles`
--
ALTER TABLE `team_roles`
  ADD CONSTRAINT `discord_linking` FOREIGN KEY (`discord_id`) REFERENCES `discord_roles` (`role_id`),
  ADD CONSTRAINT `team_linking` FOREIGN KEY (`team_id`) REFERENCES `ivao_department_teams` (`team_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
