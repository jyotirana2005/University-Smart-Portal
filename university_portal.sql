-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 04, 2025 at 04:35 PM
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
-- Database: `university_portal`now i have exported DB data and i want ki koi chlaye to vo data access kr paye mere data se directly bina kuch kre additional
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `posted_role` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `posted_by`, `posted_role`, `created_at`) VALUES
(1, 'Exams', 'Exams may be start from 1st week of next month.', 2, 'faculty', '2025-08-04 13:54:33');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `status` enum('Present','Absent') DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `date`, `subject`, `status`, `department`, `semester`, `section`, `faculty_id`, `created_at`) VALUES
(1, 11, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(2, 12, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(3, 13, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(4, 14, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(5, 15, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(6, 16, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(7, 17, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(8, 18, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(9, 19, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(10, 20, '2025-08-04', 'French', 'Present', 'ML', '4', 'O', 2, '2025-08-04 12:43:10'),
(11, 21, '2025-08-04', 'Sanskrit', 'Present', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(12, 22, '2025-08-04', 'Sanskrit', 'Present', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(13, 23, '2025-08-04', 'Sanskrit', 'Present', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(14, 24, '2025-08-04', 'Sanskrit', 'Absent', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(15, 25, '2025-08-04', 'Sanskrit', 'Present', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(16, 26, '2025-08-04', 'Sanskrit', 'Present', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(17, 27, '2025-08-04', 'Sanskrit', 'Present', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(18, 28, '2025-08-04', 'Sanskrit', 'Present', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(19, 29, '2025-08-04', 'Sanskrit', 'Absent', 'BA', '1', 'C', 2, '2025-08-04 12:44:03'),
(20, 30, '2025-08-04', 'Sanskrit', 'Present', 'BA', '1', 'C', 2, '2025-08-04 12:44:03');

-- --------------------------------------------------------

--
-- Table structure for table `discussion_posts`
--

CREATE TABLE `discussion_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `posted_on` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussion_posts`
--

INSERT INTO `discussion_posts` (`id`, `user_id`, `topic`, `message`, `posted_on`) VALUES
(1, 1, 'Semester', 'When new semester will begin?', '2025-08-04 08:34:34'),
(2, 1, 'Academic', 'Do anyone have new semester syllabus of AI course?', '2025-08-04 08:35:04');

-- --------------------------------------------------------

--
-- Table structure for table `discussion_replies`
--

CREATE TABLE `discussion_replies` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `replied_on` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussion_replies`
--

INSERT INTO `discussion_replies` (`id`, `post_id`, `user_id`, `message`, `replied_on`) VALUES
(1, 2, 2, 'yes', '2025-08-04 13:39:13');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_profiles`
--

CREATE TABLE `faculty_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_profiles`
--

INSERT INTO `faculty_profiles` (`id`, `user_id`, `employee_id`, `designation`, `phone`, `email`, `qualification`, `experience`, `specialization`, `address`, `created_at`, `updated_at`) VALUES
(1, 2, 'DS054', 'Assistant Professor', '9855555548', 'jyoti.rana054@adgitmdelhi.ac.in', 'M.tech', 3, 'mtech in DS', 'Ghaziabad UP', '2025-08-04 13:57:13', '2025-08-04 13:57:13');

-- --------------------------------------------------------

--
-- Table structure for table `grievances`
--

CREATE TABLE `grievances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('academic','hostel','admin') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','resolved','rejected') DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grievances`
--

INSERT INTO `grievances` (`id`, `user_id`, `type`, `description`, `status`, `response`, `created_at`) VALUES
(3, 1, 'hostel', 'No proper electricity and water facilities', 'pending', NULL, '2025-08-04 08:20:02'),
(4, 1, 'academic', 'Subject teacher on leave from 15 past days and next week we have exams.', 'resolved', NULL, '2025-08-04 08:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `hod_profiles`
--

CREATE TABLE `hod_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hod_profiles`
--

INSERT INTO `hod_profiles` (`id`, `user_id`, `employee_id`, `designation`, `phone`, `email`, `qualification`, `experience`, `specialization`, `address`, `created_at`, `updated_at`) VALUES
(1, 3, 'HOD2456', 'HOD', '5466812369', 'jyotirana.renusharmafoundation@gmail.com', 'Mtech ML', 5, 'Machine Learning', 'Address', '2025-08-04 14:00:53', '2025-08-04 14:00:53');

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('Sick Leave','Casual Leave','Emergency Leave') DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approver_id` int(11) DEFAULT NULL,
  `applied_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `user_id`, `leave_type`, `from_date`, `to_date`, `reason`, `status`, `approver_id`, `applied_on`, `rejection_reason`) VALUES
(1, 1, 'Sick Leave', '2025-08-04', '2025-08-07', 'Viral fever', 'Pending', NULL, '2025-08-04 08:29:03', NULL),
(2, 1, 'Emergency Leave', '2025-08-04', '2025-08-04', 'Earthquake?', 'Pending', NULL, '2025-08-04 08:34:04', NULL),
(3, 2, 'Sick Leave', '2025-08-07', '2025-08-10', 'Cough and cold ', 'Pending', NULL, '2025-08-04 13:38:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approver_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `planned_courses`
--

CREATE TABLE `planned_courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `posted_role` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `planned_courses`
--

INSERT INTO `planned_courses` (`id`, `title`, `description`, `semester`, `posted_by`, `posted_role`, `created_at`) VALUES
(1, 'Introduction to Artificial Intelligence', 'Explore the fundamentals of Artificial Intelligence, including search algorithms, knowledge representation, and basic machine learning concepts. Ideal for beginners looking to understand how AI works in the real world.', '3', 2, 'faculty', '2025-08-04 08:42:03'),
(2, 'Data Structures & Algorithms in Python', 'Learn how to solve complex problems efficiently using data structures like arrays, stacks, queues, linked lists, trees, and graphs. Includes algorithm analysis and hands-on coding in Python.', '4', 2, 'faculty', '2025-08-04 08:42:28'),
(3, 'Machine Learning with Scikit-Learn', 'This course introduces you to machine learning techniques using Python’s Scikit-learn library. Learn to build predictive models, perform classification, regression, and model evaluation.', '5', 2, 'faculty', '2025-08-04 08:42:54');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `roll` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `roll`, `name`, `department`, `semester`, `section`) VALUES
(1, '101', 'Amit Kumar', 'AI&DS', '7', 'F11'),
(2, '102', 'Neha Sharma', 'AI&DS', '7', 'F11'),
(3, '103', 'Isha Sharma', 'AI&DS', '7', 'F11'),
(4, '104', 'Ravi Verma', 'AI&DS', '7', 'F11'),
(5, '105', 'Devansh Patel', 'AI&DS', '7', 'F11'),
(6, '106', 'Kiara Singh', 'AI&DS', '7', 'F11'),
(7, '107', 'Rohan Verma', 'AI&DS', '7', 'F11'),
(8, '108', 'Ananya Rao', 'AI&DS', '7', 'F11'),
(9, '109', 'Yuvraj Desai', 'AI&DS', '7', 'F11'),
(10, '5415611922', 'Jyoti Rana', 'AI&DS', '7', 'F11'),
(11, '101', 'Amit Kumar', 'ML', '4', 'O'),
(12, '102', 'Neha Sharma', 'ML', '4', 'O'),
(13, '103', 'Isha Sharma', 'ML', '4', 'O'),
(14, '104', 'Ravi Verma', 'ML', '4', 'O'),
(15, '105', 'Devansh Patel', 'ML', '4', 'O'),
(16, '106', 'Kiara Singh', 'ML', '4', 'O'),
(17, '107', 'Rohan Verma', 'ML', '4', 'O'),
(18, '108', 'Ananya Rao', 'ML', '4', 'O'),
(19, '109', 'Yuvraj Desai', 'ML', '4', 'O'),
(20, '5415611922', 'Jyoti Rana', 'ML', '4', 'O'),
(21, 'CS2024001', 'Aarav Sharma', 'BA', '1', 'C'),
(22, 'CS2024002', 'Ananya Patel', 'BA', '1', 'C'),
(23, 'CS2024003', 'Rohan Gupta', 'BA', '1', 'C'),
(24, 'CS2024004', 'Priya Singh', 'BA', '1', 'C'),
(25, 'CS2024005', 'Karan Verma', 'BA', '1', 'C'),
(26, 'CS2024006', 'Sneha Agarwal', 'BA', '1', 'C'),
(27, 'CS2024007', 'Arjun Kumar', 'BA', '1', 'C'),
(28, 'CS2024008', 'Meera Joshi', 'BA', '1', 'C'),
(29, 'CS2024009', 'Siddharth Rao', 'BA', '1', 'C'),
(30, 'CS2024010', 'Kavya Reddy', 'BA', '1', 'C');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `roll_number` varchar(50) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`id`, `user_id`, `roll_number`, `semester`, `year`, `phone`, `address`, `updated_at`) VALUES
(1, 1, '5415611922', '7', '2022', '999-999-9999', 'Ghaziabad UP', '2025-08-04 08:48:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('student','faculty','hod') DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department`, `created_at`) VALUES
(1, 'JR_stu', 'jyotiserana@gmail.com', '$2y$10$nya.bmwXoNqh/rNG6tyo1.0ISLhrBzjWZOThWTjyQSpJGs//9BCiS', 'student', 'AI', '2025-08-02 17:04:20'),
(2, 'JR', 'jyoti.rana054@adgitmdelhi.ac.in', '$2y$10$g7M8pydAd1AUl8SqjjwGR..b8o42sq46/p3xqiQEHJWenv6FgEbzq', 'faculty', 'DS', '2025-08-02 17:09:11'),
(3, 'JR_HOD', 'jyotirana.renusharmafoundation@gmail.com', '$2y$10$keI0iudsb0Y2nu9.4Z2HoOkubzT/7SKUSaWXAmwmq.Xy8ZqvuW/.W', 'hod', 'ML', '2025-08-02 17:10:57');

-- --------------------------------------------------------

--
-- Table structure for table `user_announcement_views`
--

CREATE TABLE `user_announcement_views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `discussion_posts`
--
ALTER TABLE `discussion_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `grievances`
--
ALTER TABLE `grievances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `hod_profiles`
--
ALTER TABLE `hod_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approver_id` (`approver_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `planned_courses`
--
ALTER TABLE `planned_courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_announcement_views`
--
ALTER TABLE `user_announcement_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_view` (`user_id`,`announcement_id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `discussion_posts`
--
ALTER TABLE `discussion_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grievances`
--
ALTER TABLE `grievances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hod_profiles`
--
ALTER TABLE `hod_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `planned_courses`
--
ALTER TABLE `planned_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_announcement_views`
--
ALTER TABLE `user_announcement_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `discussion_posts`
--
ALTER TABLE `discussion_posts`
  ADD CONSTRAINT `discussion_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  ADD CONSTRAINT `discussion_replies_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `discussion_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discussion_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  ADD CONSTRAINT `faculty_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grievances`
--
ALTER TABLE `grievances`
  ADD CONSTRAINT `grievances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `hod_profiles`
--
ALTER TABLE `hod_profiles`
  ADD CONSTRAINT `hod_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_applications_ibfk_2` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_announcement_views`
--
ALTER TABLE `user_announcement_views`
  ADD CONSTRAINT `user_announcement_views_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_announcement_views_ibfk_2` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
