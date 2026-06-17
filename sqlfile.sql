-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2026 at 06:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hireready_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT '',
  `password_hash` varchar(255) NOT NULL,
  `field` varchar(255) DEFAULT '',
  `survey_done` tinyint(1) DEFAULT 0,
  `cv_path` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `cv_data` longtext DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `field`, `survey_done`, `cv_path`, `created_at`, `cv_data`, `role`) VALUES
(1, 'web p', 'you@example.com', '231232131', '$2y$12$SxdlpAje5RtpCWP1rjVjbuK8zh8xp49Q97RFoxGnCUJaI.foRVFJe', 'Web Development', 1, NULL, '2026-06-17 02:15:37', NULL, NULL),
(2, 'wwe', 'a@gmail.com', '23123123213213', '$2y$12$GFcue5f3YwWFBxgunLf2/OQn1R/b3XNV3AhwOHJmTyXdBgvO0noVW', 'Web Development', 1, NULL, '2026-06-17 04:30:25', '{\"field\":\"Web Development\",\"role\":\"Frontend Developer\",\"experience_level\":\"Intermediate\",\"current_status\":\"Working Professional\",\"technologies\":[\"React\",\"CSS\",\"Django\",\"HTML\"],\"skill_prog\":4,\"skill_db\":4,\"skill_ps\":1,\"skill_comm\":3,\"has_projects\":true,\"projects\":[{\"name\":\"${name}\",\"desc\":\"${desc}\",\"techs\":\"${techs}\",\"github\":\"${github}\"}],\"link_github\":\"sdasd,asdasda/asd\",\"link_linkedin\":\"linkedasdsada\",\"link_portfolio\":\"linked.com\",\"education_level\":\"Master\'s\",\"edu_institution\":\"UNI\",\"edu_degree\":\"uni\",\"edu_year\":2001,\"summary\":\"I AANSDndasnda asdasdas ada\",\"primary_goal\":\"Improve Skills First\",\"availability\":\"5-10 hours\",\"work_arrangement\":\"Remote\",\"employment_type\":\"Internship\"}', 'Frontend Developer'),
(3, 'new', 'b@gmail.com', '134124214', '$2y$12$VyNVNrg22HxiDbkLHzRbW.F4H4GtBfu0kb9z3IbIY4hXFlF5rE2rO', 'Mobile Development', 1, NULL, '2026-06-17 06:13:37', '{\"field\":\"Mobile Development\",\"role\":\"Frontend Developer\",\"experience_level\":\"Intermediate\",\"current_status\":\"Working Professional\",\"technologies\":[\"MongoDB\",\"MySQL\",\"Node.js\",\"Vue\"],\"skill_prog\":4,\"skill_db\":3,\"skill_ps\":5,\"skill_comm\":3,\"has_projects\":false,\"projects\":[],\"link_github\":\"git.com\",\"link_linkedin\":\"link.com\",\"link_portfolio\":\"\",\"education_level\":\"Bachelor\'s\",\"edu_institution\":\"ninja village\",\"edu_degree\":\"shonen\",\"edu_year\":2009,\"summary\":\"idk man, life hard, cant think too much\",\"primary_goal\":\"Improve Skills First\",\"availability\":\"Less than 5 hours\",\"work_arrangement\":\"Remote\",\"employment_type\":\"Internship\"}', 'Frontend Developer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
