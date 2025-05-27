-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Waktu pembuatan: 27 Bulan Mei 2025 pada 16.38
-- Versi server: 5.7.44
-- Versi PHP: 8.3.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_chatai`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_activity_submissions`
--

CREATE TABLE `tb_activity_submissions` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `status` enum('submitted','pending') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_activity_submissions`
--

INSERT INTO `tb_activity_submissions` (`id`, `activity_id`, `user_id`, `file_name`, `submitted_at`, `status`) VALUES
(1, 17, 6, '67f488b14519e-logo-polmed-png (1).png', '2025-04-08 09:23:45', 'submitted'),
(2, 14, 4, '67f48ff80892e-logo-polmed-png (1).png', '2025-04-08 09:54:48', 'submitted');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_assignments`
--

CREATE TABLE `tb_assignments` (
  `id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_name` varchar(255) DEFAULT NULL,
  `is_link` tinyint(1) DEFAULT '0',
  `due_date` varchar(200) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_assignments`
--

INSERT INTO `tb_assignments` (`id`, `classroom_id`, `activity_id`, `title`, `description`, `file_name`, `is_link`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 4, 15, 'Buat tampilan', 'Tugas buyat', '67f4a3bb2efe5-logo-polmed-png (1).png', 0, '2025-04-10T11:18', '2025-04-08 11:19:07', NULL),
(2, 3, 18, 'Tugas Bab 1 disini', 'Silahkan upload tugas Bab 1 disini', '', 0, '2025-04-11T14:33', '2025-04-08 12:33:32', NULL),
(3, 4, 19, 'Tugas desain web pemula', 'silahkan upload tugas uts anda disini, silahkan lihat contoh di file yang saya krim ', '67f4bb49055d8-images.png', 0, '2025-04-26T12:59', '2025-04-08 12:59:37', '2025-04-09 08:08:20'),
(4, 4, 19, 'asdasdasd', '-', '-', 0, '2025-05-03T17:26', '2025-05-02 17:26:56', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_assignment_submissions`
--

CREATE TABLE `tb_assignment_submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submission_type` enum('text','file','link') NOT NULL,
  `file_name` text,
  `submitted_at` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_assignment_submissions`
--

INSERT INTO `tb_assignment_submissions` (`id`, `assignment_id`, `user_id`, `submission_type`, `file_name`, `submitted_at`, `status`, `grade`, `feedback`) VALUES
(8, 3, 17, 'text', 'ini jawaban saya terima kasih', '2025-04-11 09:56:21', 'graded', 90.00, 'sangbat');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_attendance`
--

CREATE TABLE `tb_attendance` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` datetime NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_attendance`
--

INSERT INTO `tb_attendance` (`id`, `activity_id`, `classroom_id`, `creator_id`, `title`, `status`, `created_at`, `start_time`, `end_time`) VALUES
(1, 16, 4, 4, 'Absensi Mahasiswa dan Dosen', 'open', '2025-04-08 08:21:35', '2025-04-08 14:16:00', '2025-04-08 11:19:00'),
(4, 15, 4, 4, 'absen ty', 'open', '2025-04-08 09:58:38', '2025-04-08 13:58:00', '2025-04-24 09:58:00'),
(5, 18, 3, 17, 'Absensi Pertemuan ke - II', 'open', '2025-04-08 12:37:34', '2025-04-08 12:37:00', '2025-04-08 12:41:00'),
(6, 19, 4, 4, 'Presensi Minggu UTS', 'open', '2025-04-08 13:44:33', '2025-04-08 13:44:00', '2025-04-19 13:44:00'),
(7, 19, 4, 4, 'Presensi Minggu i', 'open', '2025-05-02 12:03:52', '2025-05-17 12:03:00', '2025-05-24 12:03:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_attendance_records`
--

CREATE TABLE `tb_attendance_records` (
  `id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('present','late','sick','absent') NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_attendance_records`
--

INSERT INTO `tb_attendance_records` (`id`, `attendance_id`, `user_id`, `status`, `photo_path`, `submitted_at`, `latitude`, `longitude`) VALUES
(6, 1, 4, 'late', '67f49218533ae-attendance.jpg', '2025-04-08 10:03:52', NULL, NULL),
(8, 5, 17, 'present', '67f4b6402cfdf-attendance.jpg', '2025-04-08 12:38:08', NULL, NULL),
(9, 5, 4, 'late', '67f4b6e374a0e-attendance.jpg', '2025-04-08 12:40:51', NULL, NULL),
(17, 6, 17, 'present', '67f884ad954d1-attendance.jpg', '2025-04-11 09:55:41', 3.524875359165397, 98.61235409375264);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_classrooms`
--

CREATE TABLE `tb_classrooms` (
  `id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `class_code` varchar(8) NOT NULL,
  `class_link` varchar(255) NOT NULL,
  `type` enum('public','private') DEFAULT 'public',
  `created_at` datetime DEFAULT NULL,
  `classroom_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_classrooms`
--

INSERT INTO `tb_classrooms` (`id`, `creator_id`, `title`, `description`, `class_code`, `class_link`, `type`, `created_at`, `classroom_image`) VALUES
(1, 17, 'CE-4C (Kemanan Jaringan)', '-', 'FFB8FA6E', 'index.php?page=classroom&code=FFB8FA6E', 'public', '2025-03-25 22:34:33', '67e2ce1ccf3d9-67de273babe5f-profile-default-icon-2048x2045-u3j7s5nj.png'),
(2, 17, 'CE-4C (Kemanan Jaringan) 2', 'CE-4C (Kemanan Jaringan) 2', '43A9914E', 'index.php?page=classroom&code=43A9914E', 'public', '2025-03-25 22:39:08', '67e2ce1ccf3d9-67de273babe5f-profile-default-icon-2048x2045-u3j7s5nj.png'),
(3, 17, 'Bimbingan Desertasi Mahasiswa', 'Bimbingan Desertasi S3, Prof. Poltak', 'FB12CD25', 'index.php?page=classroom&code=FB12CD25', 'public', '2025-03-26 01:21:48', '67e2f43c47490-images.png'),
(4, 4, 'Test Class Komputer Medan', 'Deskripsi Test Komputer Medan', 'EF2C17DB', 'index.php?page=classroom&code=EF2C17DB', 'public', '2025-03-27 09:51:29', '67e4bd316bc0d-logo-polmed-png.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_classroom_activities`
--

CREATE TABLE `tb_classroom_activities` (
  `id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT NULL,
  `is_link` tinyint(1) DEFAULT '0',
  `file_name` varchar(250) NOT NULL,
  `type` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_classroom_activities`
--

INSERT INTO `tb_classroom_activities` (`id`, `classroom_id`, `title`, `description`, `created_at`, `is_link`, `file_name`, `type`) VALUES
(14, 3, 'Pengumpulan Tugas UTS Semster Genap 2024/2025', 'Silhahkan untuk mengumpul Tugas anda berikut', '2025-03-31 23:55:46', 0, '', ''),
(15, 4, 'ty', 'sdf', '2025-04-08 07:46:50', 0, '67f471fa502ca-67e73d032866c-UJIAN TENGAH SEMESTER DESAIN WEB.docx', ''),
(16, 4, 'Minggu Pertama', 'Silhkan lakukan pembuatan tugas dengan baik s', '2025-04-08 08:21:22', 0, '', ''),
(17, 4, 'Minggu Ke-2', 'Silahkan Download Materi dibawah ini', '2025-04-08 08:23:44', 0, '', ''),
(18, 3, 'Pertemuan ke - II', 'Pertemuan ke - II', '2025-04-08 12:32:44', 0, '', 'material'),
(19, 4, 'Minggu UTS ', 'silahkan akses menu materi ini', '2025-04-08 12:54:41', 0, '', 'material'),
(20, 4, 'tesdf', 'sdfsdfsd', '2025-04-08 12:58:07', 0, '', 'material'),
(21, 4, 'sadfsd', 'sdfsdf', '2025-04-11 10:37:56', 0, '', 'material');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_classroom_members`
--

CREATE TABLE `tb_classroom_members` (
  `id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('lecturer','student') DEFAULT 'student',
  `joined_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_classroom_members`
--

INSERT INTO `tb_classroom_members` (`id`, `classroom_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 17, 'lecturer', '2025-03-25 22:34:33'),
(2, 2, 17, 'lecturer', '2025-03-25 22:39:08'),
(7, 2, 4, 'student', '2025-03-26 00:00:32'),
(8, 3, 17, 'lecturer', '2025-03-26 01:21:48'),
(9, 3, 4, 'student', '2025-03-26 01:24:31'),
(10, 4, 4, 'lecturer', '2025-03-27 09:51:29'),
(11, 3, 6, 'student', '2025-03-31 17:59:48'),
(12, 4, 6, 'student', '2025-04-08 09:20:18'),
(13, 4, 17, 'student', '2025-04-09 09:28:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_friends`
--

CREATE TABLE `tb_friends` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `friend_id` int(11) DEFAULT NULL,
  `status` enum('pending','accepted') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_friends`
--

INSERT INTO `tb_friends` (`id`, `user_id`, `friend_id`, `status`, `created_at`) VALUES
(40, 4, 7, 'accepted', '2025-03-20 09:54:12'),
(41, 4, 6, 'accepted', '2025-03-20 15:56:00'),
(42, 4, 8, 'pending', '2025-03-22 01:42:01'),
(43, 6, 7, 'pending', '2025-03-22 14:58:45'),
(44, 4, 1, 'pending', '2025-03-22 16:12:17'),
(45, 17, 4, 'accepted', '2025-03-22 17:46:13'),
(46, 17, 6, 'accepted', '2025-03-22 17:54:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_meetings`
--

CREATE TABLE `tb_meetings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `title` varchar(255) NOT NULL,
  `platform` varchar(255) DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_meetings`
--

INSERT INTO `tb_meetings` (`id`, `user_id`, `date`, `time`, `title`, `platform`, `meeting_link`, `created_at`) VALUES
(1, 6, '2025-03-17', '09:00:00', 'Tugas Kelompok Desain Web', 'zoom', NULL, '2025-03-18 00:48:02'),
(2, 6, '2025-03-21', '10:00:00', 'Tugas Kelompok Desain Frontend untuk Mobile Rent Car', 'google', NULL, '2025-03-18 00:49:29'),
(3, 6, '2025-03-07', '10:00:00', 'Tugas Kelompok Desain Web 2', 'zoom', NULL, '2025-03-18 00:54:45'),
(4, 4, '2025-03-14', '09:00:00', 'Meetings for Database Design ', 'zoom', NULL, '2025-03-18 08:49:44'),
(5, 6, '2025-03-06', '12:00:00', 'Backend Design for Mobile', 'zoom', NULL, '2025-03-18 08:53:39'),
(6, 6, '2025-03-13', '10:20:00', 'Tugas Kelompok Desain Web Pemula', 'zoom', NULL, '2025-03-18 09:13:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_meetings_video_calls`
--

CREATE TABLE `tb_meetings_video_calls` (
  `id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `meeting_code` varchar(8) NOT NULL,
  `meeting_link` varchar(255) NOT NULL,
  `type` enum('instant','scheduled') NOT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_meetings_video_calls`
--

INSERT INTO `tb_meetings_video_calls` (`id`, `classroom_id`, `creator_id`, `meeting_code`, `meeting_link`, `type`, `scheduled_at`, `created_at`) VALUES
(1, 3, 17, '642E3EAB', 'index.php?page=video_call_meeting&code=642E3EAB', 'instant', NULL, '2025-04-01 00:21:11'),
(2, 3, 17, '774E1D9C', 'index.php?page=video_call_meeting&code=774E1D9C', 'instant', NULL, '2025-04-01 00:21:42'),
(3, 3, 17, 'FAFA50CE', 'index.php?page=video_call_meeting&code=FAFA50CE', 'instant', NULL, '2025-04-01 00:21:48'),
(4, 3, 17, '49FE2A19', 'index.php?page=video_call_meeting&code=49FE2A19', 'scheduled', '2025-04-02 02:23:00', '2025-04-01 00:23:31'),
(5, 3, 17, '33F68C9C', 'index.php?page=video_call_meeting&code=33F68C9C', 'instant', NULL, '2025-04-01 00:25:19'),
(6, 3, 17, '2D1D0B90', 'index.php?page=video_call_meeting&code=2D1D0B90', 'instant', NULL, '2025-04-01 00:30:22'),
(7, 3, 17, 'CCA96C87', 'index.php?page=video_call_meeting&code=CCA96C87', 'instant', NULL, '2025-04-01 00:33:25'),
(8, 3, 17, '92C8BC16', 'index.php?page=video_call_meeting&code=92C8BC16', 'instant', NULL, '2025-04-01 00:38:55'),
(9, 3, 17, '97A1497C', 'index.php?page=video_call_meeting&code=97A1497C', 'instant', NULL, '2025-04-01 00:39:15'),
(10, 3, 17, '24132499', 'index.php?page=video_call_meeting&code=24132499', 'instant', NULL, '2025-04-01 00:40:37'),
(11, 3, 17, '651086A9', 'index.php?page=video_call_meeting&code=651086A9', 'instant', NULL, '2025-04-01 00:40:48'),
(12, 3, 17, '02071A63', 'index.php?page=video_call_meeting&code=02071A63', 'scheduled', '2025-04-02 02:41:00', '2025-04-01 00:41:02'),
(13, 3, 17, '1AD45ADE', 'index.php?page=video_call_meeting&code=1AD45ADE', 'scheduled', '2025-04-02 02:41:00', '2025-04-01 00:41:04'),
(14, 3, 17, '6CEF2594', 'index.php?page=video_call_meeting&code=6CEF2594', 'scheduled', '2025-04-02 02:41:00', '2025-04-01 00:41:04'),
(15, 3, 17, 'BB02964A', 'index.php?page=video_call_meeting&code=BB02964A', 'scheduled', '2025-04-02 02:41:00', '2025-04-01 00:41:04'),
(16, 3, 17, '31BCA11B', 'index.php?page=video_call_meeting&code=31BCA11B', 'scheduled', '2025-04-02 02:41:00', '2025-04-01 00:41:05'),
(17, 3, 17, '4A8E4EA9', 'index.php?page=video_call_meeting&code=4A8E4EA9', 'scheduled', '2025-04-02 02:41:00', '2025-04-01 00:41:05'),
(18, 3, 17, 'ABC3E516', 'index.php?page=video_call_meeting&code=ABC3E516', 'scheduled', '2025-04-02 02:41:00', '2025-04-01 00:41:05'),
(19, 3, 17, '60FBF498', 'index.php?page=video_call_meeting&code=60FBF498', 'instant', NULL, '2025-04-01 00:47:49'),
(20, 3, 17, '1E933471', 'index.php?page=video_call_meeting&code=1E933471', 'instant', NULL, '2025-04-01 00:47:53'),
(21, 3, 17, 'AA99A904', 'index.php?page=video_call_meeting&code=AA99A904', 'instant', NULL, '2025-04-01 00:50:59'),
(22, 3, 17, '1FDD103F', 'index.php?page=video_call_meeting&code=1FDD103F', 'instant', NULL, '2025-04-01 00:51:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_meeting_invites`
--

CREATE TABLE `tb_meeting_invites` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_meeting_invites`
--

INSERT INTO `tb_meeting_invites` (`id`, `meeting_id`, `user_id`) VALUES
(1, 1, 4),
(2, 2, 1),
(3, 2, 4),
(4, 4, 6),
(5, 5, 4),
(6, 6, 4),
(7, 6, 7);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_messages`
--

CREATE TABLE `tb_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_messages`
--

INSERT INTO `tb_messages` (`id`, `sender_id`, `receiver_id`, `message`, `file_name`, `file_type`, `file_size`, `timestamp`, `is_read`) VALUES
(1, 4, 6, 'sdfsdf', NULL, NULL, NULL, '2025-03-20 16:34:08', 1),
(2, 6, 4, 'hallow', NULL, NULL, NULL, '2025-03-20 16:34:30', 1),
(3, 4, 6, 'kamu apa kabar', NULL, NULL, NULL, '2025-03-20 16:34:35', 1),
(4, 6, 4, 'baik aja nih', NULL, NULL, NULL, '2025-03-20 16:34:40', 1),
(5, 4, 6, 'asdasd', NULL, NULL, NULL, '2025-03-20 16:35:06', 1),
(6, 4, 6, 'hallo', NULL, NULL, NULL, '2025-03-20 16:36:28', 1),
(7, 4, 6, 'asdasd', NULL, NULL, NULL, '2025-03-20 16:36:42', 1),
(8, 4, 6, 'test pesan', NULL, NULL, NULL, '2025-03-20 16:36:58', 1),
(9, 4, 6, 'jsdfsdf', NULL, NULL, NULL, '2025-03-21 01:59:20', 1),
(10, 6, 4, 'ASKDFJA;LSDJFNLAKSJDFNLAKSJdFNLASKJDNFLAKSJDNFLKAJSDNFLAKJsNDFLKASJDNFLAKSJDFNLASKJdFNLASKJdFNALSKJdFNLASKJdFNLAKSJdFNLAKSJdFNLAKSJdFNLAKSJdNFLAKSJdNFLAKSJDNfLAKSJDNFLAKSJDFNLASKJDFNLAKSJDNFLAKSJDFn', NULL, NULL, NULL, '2025-03-22 01:40:06', 1),
(11, 4, 6, 'fdfgd', NULL, NULL, NULL, '2025-03-22 01:46:22', 1),
(12, 4, 6, 'sfgdfg', NULL, NULL, NULL, '2025-03-22 01:46:24', 1),
(13, 4, 6, 'dfg\\', NULL, NULL, NULL, '2025-03-22 01:46:25', 1),
(14, 4, 6, 'dfgdfg', NULL, NULL, NULL, '2025-03-22 01:46:27', 1),
(15, 4, 6, 'sdfsdf', NULL, NULL, NULL, '2025-03-22 01:51:59', 1),
(16, 4, 6, 'sdfsdf', NULL, NULL, NULL, '2025-03-22 01:52:01', 1),
(17, 4, 6, 'ahjasdf', NULL, NULL, NULL, '2025-03-22 01:52:20', 1),
(18, 4, 6, 'hallow', NULL, NULL, NULL, '2025-03-22 01:52:23', 1),
(19, 4, 6, '', '67de17f2bcbe3-67dbe2e7e175c-67dba008da996-Roadmap Penelitian dan PkM MI (1).pdf', NULL, NULL, '2025-03-22 01:52:50', 1),
(20, 4, 6, '', '67de17f2bcbe3-67dbe2e7e175c-67dba008da996-Roadmap Penelitian dan PkM MI (1).pdf', NULL, NULL, '2025-03-22 01:52:50', 1),
(21, 4, 6, '', '67de18692020a-i.png', NULL, NULL, '2025-03-22 01:54:49', 1),
(22, 4, 6, '', '67de18692020a-i.png', NULL, NULL, '2025-03-22 01:54:49', 1),
(23, 6, 4, 'hellow', NULL, NULL, NULL, '2025-03-22 01:58:38', 1),
(24, 6, 4, 'sdfsdf', NULL, NULL, NULL, '2025-03-22 01:58:40', 1),
(25, 4, 6, 'askjasdf', NULL, NULL, NULL, '2025-03-22 02:07:36', 1),
(26, 4, 6, 'sad', NULL, NULL, NULL, '2025-03-22 02:07:42', 1),
(27, 4, 6, 'helow', NULL, NULL, NULL, '2025-03-22 02:07:45', 1),
(28, 4, 6, '', '67de1b778dac8-67dbd337df5be-67dbb7bf2405a-67dba008da996-Roadmap Penelitian dan PkM MI.pdf', NULL, NULL, '2025-03-22 02:07:51', 1),
(29, 4, 6, '', '67de1b778dac8-67dbd337df5be-67dbb7bf2405a-67dba008da996-Roadmap Penelitian dan PkM MI.pdf', NULL, NULL, '2025-03-22 02:07:51', 1),
(30, 4, 6, '', '67de273babe5f-profile-default-icon-2048x2045-u3j7s5nj.png', NULL, NULL, '2025-03-22 02:58:03', 1),
(31, 4, 6, '', '67de273babe5f-profile-default-icon-2048x2045-u3j7s5nj.png', NULL, NULL, '2025-03-22 02:58:03', 1),
(32, 4, 6, '', '67de6a9e690f5-67dbe2e7e175c-67dba008da996-Roadmap Penelitian dan PkM MI (1).pdf', NULL, NULL, '2025-03-22 07:45:34', 1),
(33, 4, 6, '', '67de6a9e690f5-67dbe2e7e175c-67dba008da996-Roadmap Penelitian dan PkM MI (1).pdf', NULL, NULL, '2025-03-22 07:45:34', 1),
(34, 6, 4, 'sdf', NULL, NULL, NULL, '2025-03-22 07:59:51', 1),
(35, 6, 4, '', '67de6e33e1436-Transkrip Nilai S2.pdf', NULL, NULL, '2025-03-22 08:00:51', 1),
(36, 6, 4, '', '67de6e33e1436-Transkrip Nilai S2.pdf', NULL, NULL, '2025-03-22 08:00:51', 1),
(37, 4, 6, 'hellow', NULL, NULL, NULL, '2025-03-22 09:00:58', 1),
(38, 4, 6, 'sdf', NULL, NULL, NULL, '2025-03-22 09:19:28', 1),
(39, 4, 6, '', '67de80a8e6ed3-67de273babe5f-profile-default-icon-2048x2045-u3j7s5nj.png', 'image/png', 98423, '2025-03-22 09:19:36', 1),
(40, 4, 6, '', '67de80a8e6ed3-67de273babe5f-profile-default-icon-2048x2045-u3j7s5nj.png', NULL, NULL, '2025-03-22 09:19:36', 1),
(41, 4, 6, '', '67de80de2f25d-67de1b778dac8-67dbd337df5be-67dbb7bf2405a-67dba008da996-Roadmap Penelitian dan PkM MI.pdf', 'application/pdf', 890612, '2025-03-22 09:20:30', 1),
(42, 4, 6, '', '67de80de2f25d-67de1b778dac8-67dbd337df5be-67dbb7bf2405a-67dba008da996-Roadmap Penelitian dan PkM MI.pdf', NULL, NULL, '2025-03-22 09:20:30', 1),
(43, 4, 6, '', '67de80eccd421-67de273babe5f-profile-default-icon-2048x2045-u3j7s5nj.png', 'image/png', 98423, '2025-03-22 09:20:44', 1),
(44, 4, 6, '', '67de80eccd421-67de273babe5f-profile-default-icon-2048x2045-u3j7s5nj.png', NULL, NULL, '2025-03-22 09:20:44', 1),
(45, 4, 6, 'hai', NULL, NULL, NULL, '2025-03-22 14:39:08', 1),
(46, 4, 6, 'sdf', NULL, NULL, NULL, '2025-03-22 14:39:14', 1),
(47, 4, 6, 'sdf', NULL, NULL, NULL, '2025-03-22 14:39:46', 1),
(48, 4, 6, 'sdf', NULL, NULL, NULL, '2025-03-22 14:39:51', 1),
(49, 6, 4, 'hellow', NULL, NULL, NULL, '2025-03-22 14:40:29', 1),
(50, 4, 6, 'hai', NULL, NULL, NULL, '2025-03-22 14:40:44', 1),
(51, 4, 6, 'helow', NULL, NULL, NULL, '2025-03-22 14:46:57', 1),
(52, 6, 4, 'wakwak', NULL, NULL, NULL, '2025-03-22 14:47:32', 1),
(53, 6, 4, 'sdf', NULL, NULL, NULL, '2025-03-22 14:48:09', 1),
(54, 6, 4, 'okay', NULL, NULL, NULL, '2025-03-22 14:48:30', 1),
(55, 4, 6, 'okay', NULL, NULL, NULL, '2025-03-22 14:48:57', 1),
(56, 4, 6, 'sdf', NULL, NULL, NULL, '2025-03-22 14:54:10', 1),
(57, 6, 4, 'sdfsdf', NULL, NULL, NULL, '2025-03-22 14:54:20', 1),
(58, 7, 4, 'ar', NULL, NULL, NULL, '2025-03-22 14:55:48', 1),
(59, 4, 7, 'whatsapp bro', NULL, NULL, NULL, '2025-03-22 14:56:01', 1),
(60, 7, 4, 'okay', NULL, NULL, NULL, '2025-03-22 14:56:56', 1),
(61, 4, 7, 'whats', NULL, NULL, NULL, '2025-03-22 14:57:07', 1),
(62, 7, 4, 'sdf', NULL, NULL, NULL, '2025-03-22 14:57:21', 1),
(63, 4, 7, '', '67decfda1146b-67dbe2e7e175c-67dba008da996-Roadmap Penelitian dan PkM MI (1).pdf', 'application/pdf', 890612, '2025-03-22 14:57:30', 0),
(64, 4, 7, '', '67decfda1146b-67dbe2e7e175c-67dba008da996-Roadmap Penelitian dan PkM MI (1).pdf', NULL, NULL, '2025-03-22 14:57:30', 0),
(65, 6, 4, 'cool', NULL, NULL, NULL, '2025-03-22 14:57:53', 1),
(66, 4, 7, 'helow', NULL, NULL, NULL, '2025-03-22 16:09:14', 0),
(67, 6, 17, 'hellow my friend', NULL, NULL, NULL, '2025-03-22 17:55:07', 1),
(68, 17, 6, 'hai how are you', NULL, NULL, NULL, '2025-03-22 17:55:24', 1),
(69, 6, 17, 'whatsapp now', NULL, NULL, NULL, '2025-03-22 17:55:54', 1),
(70, 17, 6, 'yeah good', NULL, NULL, NULL, '2025-03-22 17:56:06', 1),
(71, 17, 6, 'how about you', NULL, NULL, NULL, '2025-03-22 17:56:15', 1),
(72, 17, 6, '', '67def9eab2728-tb_pengabdian.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 55888, '2025-03-22 17:56:58', 1),
(73, 17, 6, '', '67def9eab2728-tb_pengabdian.xlsx', NULL, NULL, '2025-03-22 17:56:58', 1),
(74, 17, 6, '', '67defa0b7a640-db_pdj_tb_jemaat.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 14508, '2025-03-22 17:57:31', 1),
(75, 17, 6, '', '67defa0b7a640-db_pdj_tb_jemaat.xlsx', NULL, NULL, '2025-03-22 17:57:31', 1),
(76, 6, 17, 'Thanks for the data, i suggest it right, then you can follow my github account', NULL, NULL, NULL, '2025-03-22 17:58:22', 1),
(77, 6, 17, 'then how', NULL, NULL, NULL, '2025-03-22 17:59:00', 1),
(78, 17, 6, 'its feel good', NULL, NULL, NULL, '2025-03-22 17:59:09', 1),
(79, 17, 6, 'good?', NULL, NULL, NULL, '2025-03-22 17:59:17', 1),
(80, 17, 6, 'cool after backwards', NULL, NULL, NULL, '2025-03-22 17:59:34', 1),
(81, 17, 6, 'it seems allright', NULL, NULL, NULL, '2025-03-22 17:59:50', 1),
(82, 6, 17, 'its okay', NULL, NULL, NULL, '2025-03-22 18:00:31', 1),
(83, 6, 17, 'sdfsdf', NULL, NULL, NULL, '2025-03-22 18:00:40', 1),
(84, 6, 17, 'its oaj', NULL, NULL, NULL, '2025-03-22 18:00:51', 1),
(85, 6, 17, 'asd', NULL, NULL, NULL, '2025-03-22 18:00:57', 1),
(86, 6, 17, 'allright buddy ...', NULL, NULL, NULL, '2025-03-22 18:01:13', 1),
(87, 17, 6, 'oaky', NULL, NULL, NULL, '2025-03-22 18:03:22', 1),
(88, 4, 17, 'hallow', NULL, NULL, NULL, '2025-03-23 08:40:46', 1),
(89, 17, 4, '', '67dfc9201ed4a-67def9eab2728-tb_pengabdian.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 55888, '2025-03-23 08:41:04', 1),
(90, 17, 4, '', '67dfc9201ed4a-67def9eab2728-tb_pengabdian.xlsx', NULL, NULL, '2025-03-23 08:41:04', 1),
(91, 17, 4, 'ini filenya', NULL, NULL, NULL, '2025-03-23 08:41:14', 1),
(92, 17, 6, 'helow', NULL, NULL, NULL, '2025-03-23 08:56:31', 1),
(93, 4, 17, 'hai', NULL, NULL, NULL, '2025-03-23 08:59:42', 1),
(94, 4, 17, 'whatsapp ?', NULL, NULL, NULL, '2025-03-23 08:59:45', 1),
(95, 4, 17, '', '67dfcdb9a69e4-Surya-Dharma-2.png', 'image/png', 65675, '2025-03-23 09:00:41', 1),
(96, 4, 17, '', '67dfcdb9a69e4-Surya-Dharma-2.png', NULL, NULL, '2025-03-23 09:00:41', 1),
(97, 4, 17, 'wkwkwkw', NULL, NULL, NULL, '2025-03-23 09:01:04', 1),
(98, 4, 17, 'sdfsdfsdf', NULL, NULL, NULL, '2025-03-23 09:01:06', 1),
(99, 4, 17, 'sdfsdf', NULL, NULL, NULL, '2025-03-23 09:01:08', 1),
(100, 4, 17, 'sdf', NULL, NULL, NULL, '2025-03-23 09:01:09', 1),
(101, 4, 17, 'sdf', NULL, NULL, NULL, '2025-03-23 09:01:09', 1),
(102, 4, 17, 'sdf', NULL, NULL, NULL, '2025-03-23 09:01:10', 1),
(103, 4, 17, 'sdf', NULL, NULL, NULL, '2025-03-23 09:01:10', 1),
(104, 4, 17, 'sdf', NULL, NULL, NULL, '2025-03-23 09:01:10', 1),
(105, 4, 17, 'sdf', NULL, NULL, NULL, '2025-03-23 09:01:10', 1),
(106, 4, 17, 'sd', NULL, NULL, NULL, '2025-03-23 09:01:11', 1),
(107, 4, 17, 'fs', NULL, NULL, NULL, '2025-03-23 09:01:11', 1),
(108, 4, 17, 'df', NULL, NULL, NULL, '2025-03-23 09:01:12', 1),
(109, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:12', 1),
(110, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:12', 1),
(111, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:12', 1),
(112, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:13', 1),
(113, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:13', 1),
(114, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:13', 1),
(115, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:13', 1),
(116, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:13', 1),
(117, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:13', 1),
(118, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:14', 1),
(119, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:14', 1),
(120, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:14', 1),
(121, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:14', 1),
(122, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:14', 1),
(123, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:15', 1),
(124, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:15', 1),
(125, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:15', 1),
(126, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:15', 1),
(127, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:15', 1),
(128, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:16', 1),
(129, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:16', 1),
(130, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:16', 1),
(131, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:16', 1),
(132, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:16', 1),
(133, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:17', 1),
(134, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:17', 1),
(135, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:17', 1),
(136, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:17', 1),
(137, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:17', 1),
(138, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:18', 1),
(139, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:18', 1),
(140, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:18', 1),
(141, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:18', 1),
(142, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:18', 1),
(143, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:19', 1),
(144, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:19', 1),
(145, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:19', 1),
(146, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:19', 1),
(147, 4, 17, 'pf', NULL, NULL, NULL, '2025-03-23 09:01:21', 1),
(148, 4, 17, 'gh', NULL, NULL, NULL, '2025-03-23 09:01:22', 1),
(149, 4, 17, 'fgh', NULL, NULL, NULL, '2025-03-23 09:01:22', 1),
(150, 4, 17, 'fgh', NULL, NULL, NULL, '2025-03-23 09:01:22', 1),
(151, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:22', 1),
(152, 4, 17, 'gh', NULL, NULL, NULL, '2025-03-23 09:01:22', 1),
(153, 4, 17, 'fgh', NULL, NULL, NULL, '2025-03-23 09:01:23', 1),
(154, 4, 17, 'fg', NULL, NULL, NULL, '2025-03-23 09:01:23', 1),
(155, 4, 17, 'hf', NULL, NULL, NULL, '2025-03-23 09:01:23', 1),
(156, 4, 17, 'gh', NULL, NULL, NULL, '2025-03-23 09:01:23', 1),
(157, 4, 17, 'fgh', NULL, NULL, NULL, '2025-03-23 09:01:24', 1),
(158, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:24', 1),
(159, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:24', 1),
(160, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:24', 1),
(161, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:24', 1),
(162, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:25', 1),
(163, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:25', 1),
(164, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:25', 1),
(165, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:25', 1),
(166, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:25', 1),
(167, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:26', 1),
(168, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:26', 1),
(169, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:26', 1),
(170, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:26', 1),
(171, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:26', 1),
(172, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:26', 1),
(173, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:27', 1),
(174, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:27', 1),
(175, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:27', 1),
(176, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:27', 1),
(177, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:27', 1),
(178, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:28', 1),
(179, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:28', 1),
(180, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:28', 1),
(181, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:28', 1),
(182, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:28', 1),
(183, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:29', 1),
(184, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:29', 1),
(185, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:29', 1),
(186, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:29', 1),
(187, 4, 17, 'f', NULL, NULL, NULL, '2025-03-23 09:01:29', 1),
(188, 4, 17, 'fgh', NULL, NULL, NULL, '2025-03-23 09:01:30', 1),
(189, 4, 17, 'fgh', NULL, NULL, NULL, '2025-03-23 09:01:30', 1),
(190, 4, 17, 'fg', NULL, NULL, NULL, '2025-03-23 09:01:30', 1),
(191, 4, 17, 'hfg', NULL, NULL, NULL, '2025-03-23 09:01:30', 1),
(192, 4, 17, 'hf', NULL, NULL, NULL, '2025-03-23 09:01:31', 1),
(193, 4, 17, 'ghf', NULL, NULL, NULL, '2025-03-23 09:01:31', 1),
(194, 4, 17, 'gh', NULL, NULL, NULL, '2025-03-23 09:01:31', 1),
(195, 4, 17, 'q', NULL, NULL, NULL, '2025-03-23 09:01:32', 1),
(196, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:32', 1),
(197, 4, 17, 'e', NULL, NULL, NULL, '2025-03-23 09:01:33', 1),
(198, 4, 17, 'r', NULL, NULL, NULL, '2025-03-23 09:01:33', 1),
(199, 4, 17, 't', NULL, NULL, NULL, '2025-03-23 09:01:33', 1),
(200, 4, 17, 'y', NULL, NULL, NULL, '2025-03-23 09:01:33', 1),
(201, 4, 17, 'u', NULL, NULL, NULL, '2025-03-23 09:01:34', 1),
(202, 4, 17, 'i', NULL, NULL, NULL, '2025-03-23 09:01:34', 1),
(203, 4, 17, 'o', NULL, NULL, NULL, '2025-03-23 09:01:34', 1),
(204, 4, 17, 'p', NULL, NULL, NULL, '2025-03-23 09:01:34', 1),
(205, 4, 17, 'o', NULL, NULL, NULL, '2025-03-23 09:01:34', 1),
(206, 4, 17, 'i', NULL, NULL, NULL, '2025-03-23 09:01:35', 1),
(207, 4, 17, 'u', NULL, NULL, NULL, '2025-03-23 09:01:35', 1),
(208, 4, 17, 'y', NULL, NULL, NULL, '2025-03-23 09:01:35', 1),
(209, 4, 17, 't', NULL, NULL, NULL, '2025-03-23 09:01:36', 1),
(210, 4, 17, 'r', NULL, NULL, NULL, '2025-03-23 09:01:36', 1),
(211, 4, 17, 'e', NULL, NULL, NULL, '2025-03-23 09:01:36', 1),
(212, 4, 17, 'qw', NULL, NULL, NULL, '2025-03-23 09:01:37', 1),
(213, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:37', 1),
(214, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:37', 1),
(215, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:37', 1),
(216, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:37', 1),
(217, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:37', 1),
(218, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:38', 1),
(219, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:38', 1),
(220, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:38', 1),
(221, 4, 17, 'w', NULL, NULL, NULL, '2025-03-23 09:01:38', 1),
(222, 17, 4, 'h', NULL, NULL, NULL, '2025-03-25 15:17:05', 1),
(223, 17, 4, 'hello', NULL, NULL, NULL, '2025-03-25 23:47:12', 1),
(224, 4, 17, 'tes', NULL, NULL, NULL, '2025-03-27 01:51:37', 1),
(225, 4, 17, 'test', NULL, NULL, NULL, '2025-03-27 01:56:14', 1),
(226, 17, 4, 'tesd', NULL, NULL, NULL, '2025-03-28 02:03:01', 1),
(227, 17, 4, 'tes', NULL, NULL, NULL, '2025-03-28 02:06:32', 1),
(228, 17, 4, 'sdf', NULL, NULL, NULL, '2025-03-28 02:44:44', 1),
(229, 6, 17, 'okey', NULL, NULL, NULL, '2025-03-31 11:12:15', 1),
(230, 4, 17, 'sdf', NULL, NULL, NULL, '2025-03-31 18:29:21', 1),
(231, 17, 4, 'sdf', NULL, NULL, NULL, '2025-03-31 18:39:41', 1),
(232, 6, 17, 'tyes', NULL, NULL, NULL, '2025-04-08 02:28:45', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_users`
--

CREATE TABLE `tb_users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `profile_image` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_seen` datetime DEFAULT NULL,
  `verification_token` varchar(32) DEFAULT NULL,
  `token` varchar(250) NOT NULL,
  `is_verified` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `tb_users`
--

INSERT INTO `tb_users` (`id`, `name`, `email`, `password`, `role_id`, `profile_image`, `created_at`, `updated_at`, `last_seen`, `verification_token`, `token`, `is_verified`) VALUES
(1, 'brams', 'user@gmail.com', '$2y$10$jL3Ww/bGjFVZ5fn0pYEZceG3SlGRny/zrncdGycUZzYbpEeoU/PxG', 1, '67da8d2bb9663-Screenshot_2025-01-25_at_00.37.09-removebg-preview.png', '2025-03-11 03:01:35', '2025-03-19 09:23:55', NULL, NULL, '', 0),
(2, 'Baliku', 'bali@gmail.com', '$2y$10$jL3Ww/bGjFVZ5fn0pYEZceG3SlGRny/zrncdGycUZzYbpEeoU/PxG', 1, '67d9c3cf03b2e-unnamed (1).png', '2025-03-17 02:56:30', '2025-03-18 19:04:47', NULL, NULL, '', 0),
(4, 'D. Abraham Tarigan', 'abrahamtambac@gmail.com', '$2y$10$W8ttSU7clvs0zBt7/5rFye4e.Z.FD2k29dQlsWBWQWFY7FTDiq2g2', 1, '67d798baa13cb-images (3).jpeg', '2025-03-17 03:18:00', '2025-05-09 02:44:20', '2025-04-11 05:22:40', NULL, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI0IiwiZW1haWwiOiJhYnJhaGFtdGFtYmFjQGdtYWlsLmNvbSIsInJvbGVfaWQiOiIxIiwiaWF0IjoxNzQ2NzU4NjYwLCJleHAiOjE3NDY3NjIyNjB9.Q6CfXqduxVM7IzUbDTP07W7hvCb32RLfg3C2Q4xM4W4', 1),
(5, 'Rafi Yoga', 'rafiyoga123@gmail.com', '$2y$10$qeDGlT/h7blrB9yLHhD9yu/HwGhjTY/48BNW1qmi6SkNOeO1Suc6y', 1, '67d7a48e72c20-i.png', '2025-03-17 03:42:47', '2025-03-17 04:26:54', NULL, NULL, '', 0),
(6, 'Krishen Tarigan', 'krishen@gmail.com', '$2y$10$2kt3yzoBF1A3hLfacTtjfOnLua2LCpaHEsY5pRK8QoVjFhh7uS/pK', 1, '67d8c44273d5c-i.png', '2025-03-18 00:42:02', '2025-04-09 02:28:06', '2025-04-09 02:28:06', NULL, '', 1),
(7, 'Mhd Arkan Nabawi', 'arkannabawi123@gmail.com', '$2y$10$6AOYefNJP1rxIcf8JWshX./UMze2RYM7Xh2I/Yuq/YJvk.HJILZHO', 1, '67d8e2a5d8a2b-download (4).jpeg', '2025-03-18 03:02:31', '2025-03-22 14:57:44', '2025-03-22 14:57:44', NULL, '', 0),
(8, 'terst', 'tesdf@gmail.com', '$2y$10$XJrUiOMqzGktuFj6cKfLtO6WK/E3GXPwYNQ0MH9l9psnsUWI.wGde', 1, NULL, '2025-03-21 01:06:52', '2025-03-21 01:06:52', NULL, NULL, '', 0),
(17, 'Bschneigger Tarigan Tambak', 'bschneigger@gmail.com', '$2y$10$5V4HrHKsi/fJxMzST9T49uhDYgSgZuT/8IMTMlor8iJ5cugCwG.ti', 1, '67eb475c59b89-67e5f45062a4c-pngtree-money-3d-icon-finance-png-image_12920735.png', '2025-03-22 17:37:48', '2025-04-11 05:22:39', '2025-04-11 05:22:39', NULL, '', 1),
(18, 'cekricke', 'cekricek@gmailwsd.com', '$2y$10$VfTFhqpdJYOuxarmLYYaleXY45Yr5tPmdssY7CWLfKC1BfQTj8Yua', 1, NULL, '2025-03-26 00:27:20', '2025-03-26 00:27:20', NULL, '876557052a818ed231272bee83843115', '', 0),
(19, 'Devanta Abraham Tarigan', 'devanta.at@polmed.ac.id', '$2y$10$LQpYsUW3SpInrrJ8GgQv9OrCbNOPSeNexULW5HbGn4oFf4YI39YfC', 1, '67f4b4009f8ce-download (5).jpeg', '2025-03-26 01:31:32', '2025-04-08 05:28:45', '2025-04-08 05:28:45', '4155a17547e1365553a4c435da652c93', '', 1);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_activity_submissions`
--
ALTER TABLE `tb_activity_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tb_assignments`
--
ALTER TABLE `tb_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classroom_id` (`classroom_id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- Indeks untuk tabel `tb_assignment_submissions`
--
ALTER TABLE `tb_assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tb_attendance`
--
ALTER TABLE `tb_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `classroom_id` (`classroom_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indeks untuk tabel `tb_attendance_records`
--
ALTER TABLE `tb_attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance_user` (`attendance_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tb_classrooms`
--
ALTER TABLE `tb_classrooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_code` (`class_code`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indeks untuk tabel `tb_classroom_activities`
--
ALTER TABLE `tb_classroom_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classroom_id` (`classroom_id`);

--
-- Indeks untuk tabel `tb_classroom_members`
--
ALTER TABLE `tb_classroom_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `classroom_id` (`classroom_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tb_friends`
--
ALTER TABLE `tb_friends`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `friend_id` (`friend_id`);

--
-- Indeks untuk tabel `tb_meetings`
--
ALTER TABLE `tb_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tb_meetings_video_calls`
--
ALTER TABLE `tb_meetings_video_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classroom_id` (`classroom_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indeks untuk tabel `tb_meeting_invites`
--
ALTER TABLE `tb_meeting_invites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meeting_id` (`meeting_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tb_messages`
--
ALTER TABLE `tb_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indeks untuk tabel `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_activity_submissions`
--
ALTER TABLE `tb_activity_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_assignments`
--
ALTER TABLE `tb_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `tb_assignment_submissions`
--
ALTER TABLE `tb_assignment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `tb_attendance`
--
ALTER TABLE `tb_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `tb_attendance_records`
--
ALTER TABLE `tb_attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `tb_classrooms`
--
ALTER TABLE `tb_classrooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `tb_classroom_activities`
--
ALTER TABLE `tb_classroom_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `tb_classroom_members`
--
ALTER TABLE `tb_classroom_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `tb_friends`
--
ALTER TABLE `tb_friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT untuk tabel `tb_meetings`
--
ALTER TABLE `tb_meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `tb_meetings_video_calls`
--
ALTER TABLE `tb_meetings_video_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `tb_meeting_invites`
--
ALTER TABLE `tb_meeting_invites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `tb_messages`
--
ALTER TABLE `tb_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

--
-- AUTO_INCREMENT untuk tabel `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tb_activity_submissions`
--
ALTER TABLE `tb_activity_submissions`
  ADD CONSTRAINT `tb_activity_submissions_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `tb_classroom_activities` (`id`),
  ADD CONSTRAINT `tb_activity_submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_assignments`
--
ALTER TABLE `tb_assignments`
  ADD CONSTRAINT `tb_assignments_ibfk_1` FOREIGN KEY (`classroom_id`) REFERENCES `tb_classrooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_assignments_ibfk_2` FOREIGN KEY (`activity_id`) REFERENCES `tb_classroom_activities` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_assignment_submissions`
--
ALTER TABLE `tb_assignment_submissions`
  ADD CONSTRAINT `tb_assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `tb_assignments` (`id`),
  ADD CONSTRAINT `tb_assignment_submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_attendance`
--
ALTER TABLE `tb_attendance`
  ADD CONSTRAINT `tb_attendance_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `tb_classroom_activities` (`id`),
  ADD CONSTRAINT `tb_attendance_ibfk_2` FOREIGN KEY (`classroom_id`) REFERENCES `tb_classrooms` (`id`),
  ADD CONSTRAINT `tb_attendance_ibfk_3` FOREIGN KEY (`creator_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_attendance_records`
--
ALTER TABLE `tb_attendance_records`
  ADD CONSTRAINT `tb_attendance_records_ibfk_1` FOREIGN KEY (`attendance_id`) REFERENCES `tb_attendance` (`id`),
  ADD CONSTRAINT `tb_attendance_records_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_classrooms`
--
ALTER TABLE `tb_classrooms`
  ADD CONSTRAINT `tb_classrooms_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_classroom_activities`
--
ALTER TABLE `tb_classroom_activities`
  ADD CONSTRAINT `tb_classroom_activities_ibfk_1` FOREIGN KEY (`classroom_id`) REFERENCES `tb_classrooms` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_classroom_members`
--
ALTER TABLE `tb_classroom_members`
  ADD CONSTRAINT `tb_classroom_members_ibfk_1` FOREIGN KEY (`classroom_id`) REFERENCES `tb_classrooms` (`id`),
  ADD CONSTRAINT `tb_classroom_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_friends`
--
ALTER TABLE `tb_friends`
  ADD CONSTRAINT `tb_friends_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`),
  ADD CONSTRAINT `tb_friends_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_meetings`
--
ALTER TABLE `tb_meetings`
  ADD CONSTRAINT `tb_meetings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_meetings_video_calls`
--
ALTER TABLE `tb_meetings_video_calls`
  ADD CONSTRAINT `tb_meetings_video_calls_ibfk_1` FOREIGN KEY (`classroom_id`) REFERENCES `tb_classrooms` (`id`),
  ADD CONSTRAINT `tb_meetings_video_calls_ibfk_2` FOREIGN KEY (`creator_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_meeting_invites`
--
ALTER TABLE `tb_meeting_invites`
  ADD CONSTRAINT `tb_meeting_invites_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `tb_meetings` (`id`),
  ADD CONSTRAINT `tb_meeting_invites_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_messages`
--
ALTER TABLE `tb_messages`
  ADD CONSTRAINT `tb_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `tb_users` (`id`),
  ADD CONSTRAINT `tb_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `tb_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
