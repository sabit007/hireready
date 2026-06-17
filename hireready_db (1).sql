-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2026 at 06:39 AM
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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_address` varchar(500) DEFAULT '',
  `rep_name` varchar(255) NOT NULL,
  `rep_role` varchar(255) DEFAULT '',
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT '',
  `password_hash` varchar(255) NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `company_name`, `company_address`, `rep_name`, `rep_role`, `email`, `phone`, `password_hash`, `is_approved`, `last_login`, `created_at`) VALUES
(1, 'HireReady Inc.', '123 Tech Lane, Dhaka', 'Admin User', 'HR Manager', 'admin@hireready.com', '+8801700000000', '$2y$12$/m5IQLE00nf2Pk3fu3KwHeO3AxBggqoCpGdZJvWdEczX6cE6r3DC2', 1, NULL, '2026-06-17 02:15:15'),
(2, 'UIU', 'MadaniAvenue', 'Bottler', 'HR', 'bottler@gmail.com', '1212121212120', '$2y$12$egkwNZBm9ZNNDc.Tj3LFSevM3hVSEQTIAuJA4iNuEgJ5adCBdCxsi', 1, '2026-06-17 09:50:54', '2026-06-17 02:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `quiz_passed` tinyint(1) DEFAULT 0,
  `quiz_score` int(11) DEFAULT 0,
  `total_marks` int(11) DEFAULT 0,
  `answers_json` text DEFAULT NULL,
  `cv_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `job_id`, `quiz_id`, `user_id`, `name`, `email`, `quiz_passed`, `quiz_score`, `total_marks`, `answers_json`, `cv_path`, `status`, `created_at`) VALUES
(1, 1, 1, 1, 'web p', 'you@example.com', 1, 100, 5, '{\"0\":1}', NULL, 'approved', '2026-06-17 02:48:04'),
(2, 1, 1, 3, 'new', 'b@gmail.com', 1, 100, 5, '{\"0\":0}', NULL, 'pending', '2026-06-17 10:35:41'),
(8, 2, 2, 3, 'new', 'b@gmail.com', 1, 100, 10, '{\"0\":0}', NULL, 'pending', '2026-06-17 09:54:43');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `topics` text DEFAULT NULL,
  `instructor` varchar(255) NOT NULL,
  `duration` varchar(100) DEFAULT '',
  `modules` text DEFAULT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `admin_id`, `title`, `description`, `topics`, `instructor`, `duration`, `modules`, `status`, `created_at`) VALUES
(1, 2, 'How to win', 'Be a winner', 'Winning', 'YourDad', '2', '1. Win\r\n2. Win again\r\n3. Win forever', 'active', '2026-06-17 03:08:32'),
(2, 2, 'How to Game', 'Game on', 'Gaming', 'YourFriend', '5', 'Game Genre\r\nGame Mechanics\r\nGame Strategy', 'active', '2026-06-17 03:50:21'),
(3, 2, 'JS', 'JS', NULL, 'JS', '2', '', 'active', '2026-06-17 08:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `course_enrollments`
--

CREATE TABLE `course_enrollments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT '',
  `user_email` varchar(255) DEFAULT '',
  `enrolled_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_enrollments`
--

INSERT INTO `course_enrollments` (`id`, `course_id`, `user_id`, `user_name`, `user_email`, `enrolled_at`) VALUES
(1, 1, 2, 'wwe', 'a@gmail.com', '2026-06-17 04:34:49'),
(2, 2, 2, 'wwe', 'a@gmail.com', '2026-06-17 04:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `course_topics`
--

CREATE TABLE `course_topics` (
  `course_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_topics`
--

INSERT INTO `course_topics` (`course_id`, `topic_id`) VALUES
(3, 8);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `job_type` varchar(50) NOT NULL,
  `location` varchar(50) NOT NULL,
  `salary` varchar(100) DEFAULT '',
  `skills` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `admin_id`, `title`, `job_type`, `location`, `salary`, `skills`, `description`, `status`, `created_at`) VALUES
(1, 2, 'sdad', 'Part-time', 'Onsite', '2134', 'adsasda', '', 'active', '2026-06-17 02:47:32'),
(2, 2, 'Joke', 'Full-time', 'Onsite', '80k', 'Humor', 'laugh', 'active', '2026-06-17 08:19:11');

-- --------------------------------------------------------

--
-- Table structure for table `question_topics`
--

CREATE TABLE `question_topics` (
  `question_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `topics` text DEFAULT NULL,
  `pass_mark` int(11) DEFAULT 70,
  `time_limit` int(11) DEFAULT 30,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `admin_id`, `job_id`, `title`, `topics`, `pass_mark`, `time_limit`, `status`, `created_at`) VALUES
(1, 2, 1, 'IDK', '', 100, 2, 'active', '2026-06-17 02:47:32'),
(2, 2, 2, 'LMAO', NULL, 100, 2, 'active', '2026-06-17 08:19:11');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` varchar(20) DEFAULT 'MCQ',
  `mark` int(11) DEFAULT 1,
  `options_json` text DEFAULT NULL,
  `correct_answer` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `question_type`, `mark`, `options_json`, `correct_answer`) VALUES
(3, 1, '', 'MCQ', 5, '[\"java\",\"not java\"]', 'A'),
(5, 2, 'What does java mean?', 'MCQ', 10, '[\"laugh\",\"cry\"]', 'A');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_topics`
--

CREATE TABLE `quiz_topics` (
  `quiz_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_topics`
--

INSERT INTO `quiz_topics` (`quiz_id`, `topic_id`) VALUES
(1, 1),
(1, 5),
(1, 9),
(1, 11),
(1, 15),
(2, 8),
(2, 15),
(2, 16),
(2, 24);

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`id`, `category`, `name`) VALUES
(1, 'Programming', 'Programming Fundamentals'),
(2, 'Programming', 'OOP'),
(3, 'Programming', 'Data Structures'),
(4, 'Programming', 'Algorithms'),
(5, 'Programming', 'Python'),
(6, 'Programming', 'Java'),
(7, 'Programming', 'PHP'),
(8, 'Programming', 'JavaScript'),
(9, 'Programming', 'TypeScript'),
(10, 'Web Development', 'HTML'),
(11, 'Web Development', 'CSS'),
(12, 'Web Development', 'React'),
(13, 'Web Development', 'Vue'),
(14, 'Web Development', 'Angular'),
(15, 'Web Development', 'Node.js'),
(16, 'Web Development', 'Express.js'),
(17, 'Web Development', 'Laravel'),
(18, 'Web Development', 'REST APIs'),
(19, 'Web Development', 'Authentication'),
(20, 'Databases', 'MySQL'),
(21, 'Databases', 'PostgreSQL'),
(22, 'Databases', 'MongoDB'),
(23, 'Databases', 'Database Design'),
(24, 'Databases', 'SQL Queries'),
(25, 'Cloud & DevOps', 'Linux'),
(26, 'Cloud & DevOps', 'Git'),
(27, 'Cloud & DevOps', 'GitHub'),
(28, 'Cloud & DevOps', 'Docker'),
(29, 'Cloud & DevOps', 'Kubernetes'),
(30, 'Cloud & DevOps', 'AWS'),
(31, 'Cloud & DevOps', 'Azure'),
(32, 'Cloud & DevOps', 'CI/CD'),
(33, 'Cybersecurity', 'Network Security'),
(34, 'Cybersecurity', 'Cryptography'),
(35, 'Cybersecurity', 'Ethical Hacking'),
(36, 'Cybersecurity', 'Penetration Testing'),
(37, 'Cybersecurity', 'OWASP'),
(38, 'Data & AI', 'Data Analysis'),
(39, 'Data & AI', 'Machine Learning'),
(40, 'Data & AI', 'Deep Learning'),
(41, 'Data & AI', 'Pandas'),
(42, 'Data & AI', 'NumPy'),
(43, 'Data & AI', 'TensorFlow'),
(44, 'Soft Skills', 'Problem Solving'),
(45, 'Soft Skills', 'Communication'),
(46, 'Soft Skills', 'Teamwork'),
(47, 'Soft Skills', 'Debugging');

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

-- --------------------------------------------------------

--
-- Table structure for table `user_question_results`
--

CREATE TABLE `user_question_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_job_user` (`job_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_course_user` (`course_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `course_topics`
--
ALTER TABLE `course_topics`
  ADD PRIMARY KEY (`course_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `question_topics`
--
ALTER TABLE `question_topics`
  ADD PRIMARY KEY (`question_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_topics`
--
ALTER TABLE `quiz_topics`
  ADD PRIMARY KEY (`quiz_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_question_results`
--
ALTER TABLE `user_question_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `question_id` (`question_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_question_results`
--
ALTER TABLE `user_question_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applicants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD CONSTRAINT `course_enrollments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_enrollments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_topics`
--
ALTER TABLE `course_topics`
  ADD CONSTRAINT `course_topics_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_topics_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `question_topics`
--
ALTER TABLE `question_topics`
  ADD CONSTRAINT `question_topics_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `question_topics_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_topics`
--
ALTER TABLE `quiz_topics`
  ADD CONSTRAINT `quiz_topics_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_topics_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_question_results`
--
ALTER TABLE `user_question_results`
  ADD CONSTRAINT `user_question_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_question_results_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_question_results_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
