-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Okt 2026 pada 16.24
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan_online`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `barcode_sessions`
--

CREATE TABLE `barcode_sessions` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `session_token` varchar(32) NOT NULL,
  `status` enum('active','completed','expired') DEFAULT 'active',
  `member_barcode` varchar(255) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `books_scanned` longtext DEFAULT NULL COMMENT 'JSON array of scanned book data',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 30 minute)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(100) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `access_level` enum('all','teacher_only') DEFAULT 'all',
  `copies` int(11) DEFAULT 1,
  `max_borrow_days` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shelf` varchar(50) DEFAULT NULL,
  `row_number` int(11) DEFAULT NULL,
  `lokasi_rak` varchar(255) DEFAULT NULL,
  `cover_image` varchar(225) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `books`
--

INSERT INTO `books` (`id`, `school_id`, `title`, `author`, `isbn`, `category`, `access_level`, `copies`, `max_borrow_days`, `created_at`, `shelf`, `row_number`, `lokasi_rak`, `cover_image`) VALUES
(1, 4, 'Mengunyah Rindu', 'Budi Maryono', '982384', 'Fiksi', 'all', 0, NULL, '2026-01-26 02:48:25', '1A', 1, '11', 'book_1769399260_6976e3dc930ca.jpg'),
(2, 4, 'Bu, aku ingin pelukmu', 'Reza Mustopa', '4522343', 'Fiksi', 'all', 1, NULL, '2026-01-26 02:49:48', '1A', 1, '10', 'book_1769399253_6976e3d564a9a.png'),
(3, 4, 'Madilog', 'Tan Malaka', '533454', 'Referensi', 'all', 1, NULL, '2026-01-26 02:52:47', '1B', 1, '9', 'book_1769399247_6976e3cfbddeb.jpeg'),
(4, 4, 'Sebuah Seni Untuk Bersikap Bodoamat', 'Mark Manson', '345645', 'Non-Fiksi', 'all', 0, NULL, '2026-01-26 02:55:04', '1B', 2, '8', 'book_1769399240_6976e3c8d253c.png'),
(5, 4, 'The Psychology of Money', 'Morgan Housel', '9786238371044', 'Lainnya', 'all', 1, NULL, '2026-01-26 04:02:19', '1B', 5, '7', 'book_1769400139_6976e74ba9d73.jpg'),
(6, 4, 'Sang Alkemis', 'Paulo Coelho', '9786020656069', 'Lainnya', 'all', 1, NULL, '2026-01-26 04:03:53', '1B', 4, '6', 'book_1769400245_6976e7b53bd5e.jpg'),
(7, 4, 'B.J. Habibie : Sebuah Biografi', 'Fatimah Fayrus', '9786231643094', 'Biografi', 'all', 1, NULL, '2026-01-26 04:07:07', '1C', 2, '4', 'book_1769400427_6976e86b698e3.jpg'),
(8, 10, 'dfgdfg', 'dfgdfg', '232343', 'Referensi', 'all', 1, NULL, '2026-01-29 04:24:26', '1A', 3, NULL, 'book_1769660674_697ae1029671b.jpg'),
(14, 4, 'The Art of Stoicism', 'Adora Kinara', '34567890', 'Non-Fiksi', 'teacher_only', 1, NULL, '2026-02-10 07:15:57', '1', 2, '5', 'book_1770707752_698adb282818f.jpg'),
(18, 14, 'Spy X Familyy', 'Tatsuya Endo', '987-982', 'Fiksi', 'all', 0, 2, '2026-02-19 03:53:24', '1D', 3, '4', 'book_1771473204_6996893442426.jpg'),
(19, 14, 'B.J Habibie', 'Fatimah Fayrus', '987492', 'Biografi', 'teacher_only', 0, 3, '2026-02-19 03:55:35', '1B', 2, '2', 'book_1771473335_699689b72f067.jpg'),
(22, 4, 'B.J. Habibie : Sebuah Biografi', 'Fatimah Fayrus', '9786231643094', 'Biografi', 'all', 1, NULL, '2026-03-24 03:20:50', '1C', 2, '4', 'book_1769400427_6976e86b698e3.jpg'),
(23, 4, 'B.J. Habibie : Sebuah Biografi', 'Fatimah Fayrus', '9786231643094', 'Biografi', 'all', 1, NULL, '2026-03-24 03:20:50', '1C', 2, '4', 'book_1769400427_6976e86b698e3.jpg'),
(24, 4, 'B.J. Habibie : Sebuah Biografi', 'Fatimah Fayrus', '9786231643094', 'Biografi', 'all', 1, NULL, '2026-03-24 03:20:50', '1C', 2, '4', 'book_1769400427_6976e86b698e3.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `book_damage_fines`
--

CREATE TABLE `book_damage_fines` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `borrow_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `damage_type` varchar(50) NOT NULL,
  `damage_description` text DEFAULT NULL,
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `book_damage_fines`
--

INSERT INTO `book_damage_fines` (`id`, `school_id`, `borrow_id`, `member_id`, `book_id`, `damage_type`, `damage_description`, `fine_amount`, `status`, `created_at`, `updated_at`) VALUES
(5, 4, 58, 6, 4, 'major_tear', 'Robek di halaman 12', 50000.00, 'pending', '2026-02-20 07:35:09', '2026-02-20 07:35:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `book_maintenance`
--

CREATE TABLE `book_maintenance` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `book_maintenance`
--

INSERT INTO `book_maintenance` (`id`, `book_id`, `status`, `notes`, `updated_at`) VALUES
(1, 3, 'Good', 'Bagus', '2026-01-26 02:55:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `borrows`
--

CREATE TABLE `borrows` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `borrowed_at` datetime DEFAULT current_timestamp(),
  `due_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue','pending_return','pending_confirmation') DEFAULT 'borrowed',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_status` enum('unpaid','paid') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `borrows`
--

INSERT INTO `borrows` (`id`, `school_id`, `book_id`, `member_id`, `borrowed_at`, `due_at`, `returned_at`, `status`, `fine_amount`, `fine_status`) VALUES
(31, 10, 8, 3, '2026-01-30 08:32:40', '2026-02-06 02:32:40', NULL, 'pending_confirmation', 0.00, 'unpaid'),
(35, 4, 4, 4, '2026-02-12 10:45:20', '2026-02-19 10:47:25', '2026-02-12 10:48:57', 'returned', 0.00, 'unpaid'),
(37, 4, 7, 4, '2026-02-16 08:58:19', '2026-02-23 09:19:17', '2026-02-16 09:19:28', 'returned', 0.00, 'unpaid'),
(38, 4, 4, 4, '2026-02-16 09:01:58', '2026-02-23 09:19:17', '2026-02-16 09:19:27', 'returned', 0.00, 'unpaid'),
(39, 4, 1, 6, '2026-02-16 09:18:14', '2026-02-23 09:18:33', '2026-02-16 09:19:25', 'returned', 0.00, 'unpaid'),
(40, 4, 7, 4, '2026-02-16 09:24:52', '2026-02-23 09:25:54', '2026-02-16 09:43:44', 'returned', 0.00, 'unpaid'),
(41, 4, 2, 4, '2026-02-16 09:25:15', '2026-02-23 09:25:54', '2026-02-16 09:26:10', 'returned', 0.00, 'unpaid'),
(42, 4, 4, 6, '2026-02-16 09:36:32', '2026-02-23 09:36:48', '2026-02-16 09:43:43', 'returned', 0.00, 'unpaid'),
(43, 4, 7, 4, '2026-02-16 09:44:14', '2026-03-03 09:44:21', '2026-02-16 09:52:27', 'returned', 0.00, 'unpaid'),
(44, 4, 2, 4, '2026-02-16 09:53:13', '2026-02-23 09:53:21', '2026-02-20 13:48:58', 'returned', 0.00, 'unpaid'),
(50, 14, 18, 11, '2026-02-19 15:04:17', '2026-02-26 15:04:39', NULL, 'overdue', 0.00, 'unpaid'),
(51, 14, 19, 10, '2026-02-19 15:14:57', '2026-02-22 15:16:52', NULL, 'borrowed', 0.00, 'unpaid'),
(52, 4, 2, 6, '2026-02-20 13:45:04', '2026-02-23 13:45:23', '2026-02-20 13:48:56', 'returned', 0.00, 'unpaid'),
(53, 4, 2, 4, '2026-02-20 13:49:26', '2026-02-23 13:49:32', '2026-02-20 13:56:17', 'returned', 0.00, 'unpaid'),
(54, 4, 2, 4, '2026-02-20 13:59:23', '2026-02-23 14:04:27', '2026-02-20 14:25:14', 'returned', 0.00, 'unpaid'),
(55, 4, 1, 6, '2026-02-20 14:23:49', '2026-02-23 14:24:13', '2026-02-20 14:25:16', 'returned', 0.00, 'unpaid'),
(56, 4, 7, 6, '2026-02-20 14:26:22', '2026-02-23 14:28:05', '2026-02-23 08:17:49', 'returned', 0.00, 'unpaid'),
(58, 4, 4, 6, '2026-02-20 14:27:34', '2026-02-23 14:28:05', '2026-02-20 14:49:57', 'returned', 0.00, 'unpaid'),
(62, 4, 4, 4, '2026-02-23 08:35:49', '2026-02-26 08:36:18', '2026-02-23 08:36:23', 'returned', 0.00, 'unpaid'),
(63, 4, 1, 4, '2026-02-23 08:35:49', '2026-02-26 08:36:18', '2026-02-23 08:36:21', 'returned', 0.00, 'unpaid'),
(64, 4, 4, 4, '2026-02-23 08:42:45', '2026-02-26 23:59:59', '2026-03-12 09:15:06', 'returned', 14000.00, 'unpaid'),
(65, 4, 1, 4, '2026-02-23 08:42:45', '2026-02-26 23:59:59', '2026-02-26 10:14:33', 'returned', 0.00, 'unpaid'),
(66, 4, 4, 4, '2026-03-12 09:37:50', '2026-03-15 23:59:59', '2026-03-12 09:38:54', 'returned', 0.00, 'unpaid'),
(68, 4, 7, 4, '2026-03-16 08:35:33', '2026-03-19 23:59:59', '2026-03-16 08:39:42', 'returned', 0.00, 'unpaid'),
(69, 4, 14, 7, '2026-03-16 08:47:09', '2026-03-19 23:59:59', '2026-03-16 08:50:35', 'returned', 0.00, 'unpaid'),
(70, 4, 7, 7, '2026-03-16 08:47:09', '2026-03-19 23:59:59', '2026-03-16 08:50:34', 'returned', 0.00, 'unpaid'),
(71, 4, 2, 7, '2026-03-16 08:47:09', '2026-03-19 23:59:59', '2026-03-16 08:50:36', 'returned', 0.00, 'unpaid'),
(72, 4, 2, 14, '2026-03-16 08:51:35', '2026-03-19 23:59:59', '2026-03-16 08:55:47', 'returned', 0.00, 'unpaid'),
(73, 4, 7, 14, '2026-03-16 08:51:35', '2026-03-19 23:59:59', '2026-03-16 08:55:51', 'returned', 0.00, 'unpaid'),
(74, 4, 2, 14, '2026-03-16 10:03:33', '2026-03-19 23:59:59', '2026-03-16 10:06:40', 'returned', 0.00, 'unpaid'),
(75, 4, 7, 14, '2026-03-16 10:03:33', '2026-03-19 23:59:59', '2026-03-16 10:06:43', 'returned', 0.00, 'unpaid'),
(76, 4, 1, 4, '2026-03-16 10:05:19', '2026-03-19 23:59:59', '2026-03-23 11:31:12', 'returned', 3000.00, 'unpaid'),
(78, 4, 2, 4, '2026-03-23 11:32:11', '2026-03-26 23:59:59', '2026-03-23 13:32:49', 'returned', 0.00, 'unpaid'),
(79, 4, 7, 6, '2026-03-23 11:32:45', '2026-03-26 23:59:59', '2026-03-23 13:32:49', 'returned', 0.00, 'unpaid'),
(80, 4, 1, 4, '2026-03-23 13:33:15', '2026-03-26 23:59:59', NULL, 'overdue', 181000.00, 'unpaid'),
(81, 4, 4, 6, '2026-03-23 13:33:48', '2026-03-26 23:59:59', NULL, 'overdue', 181000.00, 'unpaid');

-- --------------------------------------------------------

--
-- Struktur dari tabel `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `favorites`
--

INSERT INTO `favorites` (`id`, `student_id`, `book_id`, `category`, `created_at`) VALUES
(16, 14, 14, NULL, '2026-02-20 07:31:24'),
(17, 13, 24, NULL, '2026-09-23 07:22:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `role` enum('student','teacher','employee') DEFAULT 'student',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `max_pinjam` int(11) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `members`
--

INSERT INTO `members` (`id`, `school_id`, `name`, `email`, `nisn`, `role`, `status`, `created_at`, `max_pinjam`) VALUES
(3, 10, 'fafas', 'asdas@gmail.com', '1211211', 'student', 'active', '2026-01-29 04:25:45', 2),
(4, 4, 'Anjali Saputra', 'anjalisaputra@gmail.com', '0094234', 'student', 'active', '2026-02-10 01:14:22', 2),
(6, 4, 'surya', 'uya4767@gmail.com', '2346558', 'student', 'active', '2026-02-10 06:53:08', 5),
(7, 4, 'Sonia Sugiana', 'sonia@gmail.com', '2345345', 'teacher', 'active', '2026-02-12 03:46:23', 5),
(10, 14, 'Ganiiii', 'gani@gmail.com', '222222', 'teacher', 'active', '2026-02-19 03:57:49', 10),
(11, 14, 'Anjali Saputra', 'anjalisaputra@gmail.com', '111111', 'student', 'active', '2026-02-19 04:02:36', 3),
(13, 4, 'tuti', 'tuti@gmail.com', '888888', 'student', 'active', '2026-03-12 01:17:51', 2),
(14, 4, 'surya ali rafsanjani', 'raf@gmail.com', '2929', 'student', 'active', '2026-03-16 01:48:31', 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('borrow','return_request','return_confirm','late_warning','info','new_book') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `school_id`, `student_id`, `title`, `message`, `type`, `is_read`, `created_at`, `updated_at`) VALUES
(18, 4, 3, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"Bu, aku ingin pelukmu\" ke koleksi favorit Anda.', 'info', 0, '2026-02-03 08:15:54', '2026-02-03 08:15:54'),
(45, 4, 13, 'Buku Baru Tersedia', 'Buku \"stoicsm\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-10 07:15:57', '2026-02-10 07:15:57'),
(46, 4, 14, 'Buku Baru Tersedia', 'Buku \"stoicsm\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-10 07:16:25', '2026-02-10 07:16:25'),
(47, 4, 13, 'Buku Baru Tersedia', 'Buku \"coding\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 01:55:28', '2026-02-11 01:55:28'),
(48, 4, 14, 'Buku Baru Tersedia', 'Buku \"coding\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 01:55:32', '2026-02-11 01:55:32'),
(49, 4, 13, 'Buku Baru Tersedia', 'Buku \"program\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 02:16:20', '2026-02-11 02:16:20'),
(50, 4, 14, 'Buku Baru Tersedia', 'Buku \"program\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 02:16:23', '2026-02-11 02:16:23'),
(51, 4, 13, 'Buku Baru Tersedia', 'Buku \"jh\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 02:21:13', '2026-02-11 02:21:13'),
(52, 4, 14, 'Buku Baru Tersedia', 'Buku \"jh\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 02:21:17', '2026-02-11 02:21:17'),
(53, 4, 13, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"jh\" ke koleksi favorit Anda.', 'info', 0, '2026-02-16 04:02:10', '2026-02-16 04:02:10'),
(54, 4, 13, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"program\" ke koleksi favorit Anda.', 'info', 0, '2026-02-19 03:23:18', '2026-02-19 03:23:18'),
(55, 4, 13, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"The Art of Stoicism\" ke koleksi favorit Anda.', 'info', 0, '2026-02-20 04:08:09', '2026-02-20 04:08:09'),
(56, 4, 14, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"The Art of Stoicism\" ke koleksi favorit Anda.', 'info', 0, '2026-02-20 07:31:24', '2026-02-20 07:31:24'),
(57, 4, 13, 'Buku Baru Tersedia', 'Buku \"Spy X Family\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-20 07:33:33', '2026-02-20 07:33:33'),
(58, 4, 14, 'Buku Baru Tersedia', 'Buku \"Spy X Family\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-20 07:33:36', '2026-02-20 07:33:36'),
(59, 4, 13, 'Buku Baru Tersedia', 'Buku \"B.J. Habibie : Sebuah Biografi\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-03-09 01:26:12', '2026-03-09 01:26:12'),
(60, 4, 14, 'Buku Baru Tersedia', 'Buku \"B.J. Habibie : Sebuah Biografi\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-03-09 01:26:16', '2026-03-09 01:26:16'),
(61, 4, 13, 'Buku Tersedia!', 'Buku \"Sebuah Seni Untuk Bersikap Bodoamat\" yang Anda tunggu sudah tersedia. Segera lakukan peminjaman!', 'info', 0, '2026-03-12 02:15:06', '2026-03-12 02:15:06'),
(62, 4, 19, 'Buku Tersedia!', 'Buku \"Sebuah Seni Untuk Bersikap Bodoamat\" yang Anda tunggu sudah tersedia. Segera lakukan peminjaman!', 'info', 0, '2026-03-12 02:38:54', '2026-03-12 02:38:54'),
(63, 4, 13, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"B.J. Habibie : Sebuah Biografi\" ke koleksi favorit Anda.', 'info', 0, '2026-09-23 07:22:46', '2026-09-23 07:22:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `jenis_notifikasi` enum('telat','peringatan','pengembalian','info','sukses','buku','default') DEFAULT 'default',
  `tanggal` datetime DEFAULT current_timestamp(),
  `status_baca` tinyint(1) DEFAULT 0 COMMENT '0 = belum dibaca, 1 = sudah dibaca',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rating_buku`
--

CREATE TABLE `rating_buku` (
  `id_rating` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rating_buku`
--

INSERT INTO `rating_buku` (`id_rating`, `id_user`, `id_buku`, `rating`, `komentar`, `created_at`) VALUES
(1, 9, 7, 5, 'bgu\r\n', '2026-02-06 03:11:02'),
(2, 4, 7, 4, 'Mantap', '2026-02-09 03:55:16'),
(3, 13, 7, 5, 'tesas', '2026-02-20 03:29:44'),
(5, 13, 14, 5, 'bagus', '2026-02-20 03:30:01'),
(6, 13, 6, 4, 'wow', '2026-02-20 03:37:50'),
(7, 13, 3, 1, 'keren banget', '2026-02-20 03:42:52'),
(8, 13, 5, 5, 'bagus banget', '2026-02-20 03:45:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` enum('pending','active','rejected') DEFAULT 'pending',
  `activation_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `npsn` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `founded_year` int(11) DEFAULT NULL,
  `borrow_duration` int(11) DEFAULT 7,
  `late_fine` decimal(10,2) DEFAULT 500.00,
  `max_books` int(11) DEFAULT 3,
  `max_books_student` int(11) DEFAULT 3,
  `max_books_teacher` int(11) DEFAULT 10,
  `max_books_employee` int(11) DEFAULT 5,
  `scan_access_key` varchar(64) DEFAULT NULL,
  `custom_base_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `schools`
--

INSERT INTO `schools` (`id`, `name`, `slug`, `status`, `activation_code`, `created_at`, `email`, `phone`, `address`, `description`, `logo`, `profile_picture`, `npsn`, `website`, `photo_path`, `founded_year`, `borrow_duration`, `late_fine`, `max_books`, `max_books_student`, `max_books_teacher`, `max_books_employee`, `scan_access_key`, `custom_base_url`) VALUES
(4, 'AUSTRALIA INDEPENDENTS SCHOOL', '', 'pending', NULL, '2026-01-26 02:42:13', 'australiaindependentschool@sch.id', NULL, NULL, NULL, NULL, NULL, '39023341', NULL, 'public/uploads/school-photos/school_1773277970_69b213126c1ea.jpg', 0, 3, 1000.00, 4, 2, 5, 5, '1b317002f71c4f5fc71038e10597930b', 'https://ungaudy-bitless-jeffrey.ngrok-free.dev'),
(5, 'sdfsdf', 'sdfsdf', 'pending', NULL, '2026-01-26 04:37:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5, NULL, NULL),
(7, 'sdfsdfasdasd', 'sdfsdfasdasd', 'pending', NULL, '2026-01-26 04:39:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5, NULL, NULL),
(8, 'sdfbhsd', 'sdfbhsd', 'pending', NULL, '2026-01-26 04:42:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5, NULL, NULL),
(9, 'hdgdfg', 'hdgdfg', 'pending', NULL, '2026-01-26 04:44:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5, NULL, NULL),
(10, 'smamaju', 'smamaju', 'pending', NULL, '2026-01-29 02:37:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5, NULL, NULL),
(11, 'smk bm3', 'smk-bm3', 'pending', NULL, '2026-02-02 02:46:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5, NULL, NULL),
(14, 'SMK BINA MANDIRI MULTIMEDIA', 'smk-bina-mandiri-multimedia', 'pending', NULL, '2026-02-19 03:51:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5, '28a30e5d4fbb54bf7d31f2bd805b030a', 'https://ungaudy-bitless-jeffrey.ngrok-free.dev');

-- --------------------------------------------------------

--
-- Struktur dari tabel `school_themes`
--

CREATE TABLE `school_themes` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `theme_name` varchar(50) DEFAULT 'light',
  `custom_colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_colors`)),
  `typography` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`typography`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `school_themes`
--

INSERT INTO `school_themes` (`id`, `school_id`, `theme_name`, `custom_colors`, `typography`, `created_at`, `updated_at`) VALUES
(2, 4, 'light', '[]', NULL, '2026-01-26 02:43:32', '2026-09-23 07:21:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `school_theme_presets`
--

CREATE TABLE `school_theme_presets` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `preset_name` varchar(100) NOT NULL,
  `colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`colors`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `school_theme_presets`
--

INSERT INTO `school_theme_presets` (`id`, `school_id`, `preset_name`, `colors`, `created_at`) VALUES
(1, 4, 'Green White', '{\"color-bg\":\"#99ffd8\",\"color-surface\":\"#ffffff\",\"color-text\":\"#000000\",\"color-accent\":\"#000000\",\"color-muted\":\"#000000\",\"color-border\":\"#000000\"}', '2026-02-23 02:37:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` char(1) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nama_lengkap`, `nisn`, `kelas`, `jurusan`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `email`, `no_hp`, `foto`, `created_at`, `updated_at`) VALUES
(3, 'Budi', NULL, NULL, NULL, NULL, NULL, NULL, 'australiaindependentsschool@sch.id', NULL, NULL, '2026-02-02 02:53:09', '2026-02-02 02:53:09'),
(4, 'Anjali Saputra', '0094234', 'XI', 'Rekayasa Perangkat Lunak', '2008-01-17', 'L', 'Limus', 'anjalisaputra@gmail.com', '089234234', 'uploads/siswa/siswa_4_1769479766_69781e562270d.webp', '2026-01-26 03:07:53', '2026-01-27 02:09:26'),
(9, 'Suryaaa', '000000', NULL, NULL, NULL, NULL, NULL, 'uya4767@gmail.com', NULL, 'uploads/siswa/siswa_9_1770188218_6982edbac928b.jpg', '2026-02-02 02:50:24', '2026-02-06 06:55:14'),
(11, 'fafas', '1211211', NULL, NULL, NULL, NULL, NULL, 'asdas@gmail.com', NULL, NULL, '2026-02-02 02:45:49', '2026-02-02 02:45:49'),
(13, 'Anjali Saputra', '0094234', 'XII', 'Rekayasa Perangkat Lunak', NULL, 'L', NULL, 'anjalisaputra@gmail.com', NULL, 'uploads/anggota/anggota_13_1771557263_6997d18f5f200.jpeg', '2026-02-16 01:30:07', '2026-02-20 03:18:38'),
(14, 'surya', '2346558', NULL, NULL, NULL, NULL, NULL, 'uya4767@gmail.com', NULL, NULL, '2026-02-11 06:23:05', '2026-02-11 06:23:05'),
(17, 'Ganiiii', '222222', NULL, NULL, NULL, NULL, NULL, 'gani@gmail.com', NULL, NULL, '2026-02-19 04:01:56', '2026-02-19 04:03:01'),
(18, 'Anjali Saputra', '111111', NULL, NULL, NULL, NULL, NULL, 'anjalisaputra@gmail.com', NULL, 'uploads/anggota/anggota_18_1771476240_69969510dbb14.jpeg', '2026-02-19 04:20:45', '2026-02-19 04:44:00'),
(20, 'surya ali rafsanjani', '2929', 'XII', 'Rekayasa Perangkat Lunak', '2008-12-29', 'L', 'jalan,pattimura blok c4 no 39', 'raf@gmail.com', '085891129163', 'uploads/anggota/anggota_20_1773626867_69b765f343aa2.webp', '2026-03-16 02:07:41', '2026-03-16 02:08:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `special_themes`
--

CREATE TABLE `special_themes` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `theme_key` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `special_themes`
--

INSERT INTO `special_themes` (`id`, `school_id`, `name`, `date`, `is_active`, `theme_key`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Test Theme', '2026-02-12', 1, 'kemerdekaan', NULL, '2026-02-12 02:10:07', '2026-02-12 02:10:07'),
(19, 4, 'Lebaran', '2026-02-20', 0, 'idulfitri', '', '2026-02-20 02:21:26', '2026-02-24 01:49:35'),
(20, 4, 'Hari Guru', '2026-02-20', 0, 'hariguru', '', '2026-02-20 02:24:16', '2026-02-24 01:49:31'),
(21, 4, 'Natal', '2026-02-20', 0, 'natal', '', '2026-02-20 02:30:56', '2026-02-20 02:49:05'),
(22, 4, 'imlek', '2027-01-20', 0, 'tahunbaru', '', '2026-02-20 08:04:18', '2026-02-23 02:22:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `theme_templates`
--

CREATE TABLE `theme_templates` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `colors` longtext DEFAULT NULL,
  `typography` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `code_expires_at` timestamp NULL DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `role` enum('admin','librarian','student','teacher','employee') DEFAULT 'librarian',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `nisn`, `password`, `verification_code`, `code_expires_at`, `is_verified`, `verified_at`, `role`, `created_at`) VALUES
(3, 4, 'Budi', 'australiaindependentsschool@sch.id', NULL, '$2y$10$YYkXfCuDmZWDCEymXPClM.viULRMLWKBxk70BmOxepED0GMNeOWiS', NULL, '2026-01-25 20:57:13', 1, '2026-01-26 02:42:24', 'admin', '2026-01-26 02:42:14'),
(5, 5, 'Gani', 'sdfdf@sch.id', NULL, '$2y$10$y5RgsJjO.nbQ3XXqFeQ4X.5zkiCKKAXEZnH.EhVXPdyvOPc/.MRDm', NULL, '2026-01-25 22:52:04', 1, '2026-01-26 04:37:14', 'admin', '2026-01-26 04:37:04'),
(6, 7, 'sdfsdfasdasd', 'sdfsdfsf@sch.id', NULL, '$2y$10$qTK44/muE8jMTShecEdkZuPoFN3Kh9dDnc80qmzQo1bLNL6zgQ3ua', NULL, '2026-01-25 22:54:59', 1, '2026-01-26 04:40:09', 'admin', '2026-01-26 04:39:59'),
(7, 8, 'sghdfgdf', 'sdsdfsfdf@sch.id', NULL, '$2y$10$E3CEHA.8I4ICe1cYR7hdve6bfeEtSuhXHjrf4q.D.Ux9h.QyZpaQG', NULL, '2026-01-25 22:57:48', 1, '2026-01-26 04:42:57', 'admin', '2026-01-26 04:42:48'),
(8, 9, 'ertert', 'hgdfgdfg@sch.id', NULL, '$2y$10$kIJvRguAWIEKu6XrYqSjLOM8SRIDa0Tgz5PxDcTD1Lkdx7D0QpIpW', NULL, NULL, 1, '2026-01-26 04:44:53', 'admin', '2026-01-26 04:44:45'),
(10, 10, 'maju', 'maju@sch.id', NULL, '$2y$10$P0Dvl957VriyhB2Ss5ZQv.VvPhlJS0iRKgbFyeYnT58YQ9bCBsTS6', NULL, NULL, 1, '2026-01-29 02:37:56', 'admin', '2026-01-29 02:37:45'),
(11, 10, 'fafas', 'asdas@gmail.com', '1211211', '$2y$10$kxRqC.NMu.l1Gt/nT6ITQ.8gRFdrhBww3Bc1AeUxU5qCQq5kbzJey', NULL, NULL, 0, NULL, 'student', '2026-01-29 04:25:45'),
(12, 11, 'someone', 'ada@sch.id', NULL, '$2y$10$hK3aWhbPDT0JIxa.hKx1UOnsgOz554F0tAsK3j.KM4CVW5briRGXO', '548097', '2026-02-01 21:01:29', 0, NULL, 'admin', '2026-02-02 02:46:30'),
(13, 4, 'Anjali Saputra', 'anjalisaputra@gmail.com', '0094234', '$2y$10$KCo55BDxMYIXkX0V7nutBuBW89.oi9f/WZ6NKQFegU7VbfDcHgMNq', NULL, NULL, 0, NULL, 'student', '2026-02-10 01:14:22'),
(14, 4, 'surya', 'uya4767@gmail.com', '2346558', '$2y$10$wKS/KvZ9STkaNTy1SNR/wOTcc.OLewapm55fPQF.6oB6xh4cdmryK', NULL, NULL, 0, NULL, 'student', '2026-02-10 06:53:08'),
(15, 4, 'Sonia Sugiana', 'sonia@gmail.com', '2345345', '$2y$10$lF7iBPw94PG6G9cVOGY1iuvQJ0Nph4O.be/LomLYe8qfViqyjzlvm', NULL, NULL, 0, NULL, 'teacher', '2026-02-12 03:46:23'),
(16, 14, 'Gani', 'smkbinamandirimultimedia@sch.id', NULL, '$2y$10$xJgd.CLQLLrZQ.osocHNR.484jKUUJYOlsvhUSokbXbN.qX6glc2a', NULL, '2026-02-18 22:06:47', 1, '2026-02-19 03:51:55', 'admin', '2026-02-19 03:51:47'),
(17, 14, 'Ganiiii', 'gani@gmail.com', '222222', '$2y$10$AC4JYVpuJSn.cVwGAIRuB.MQ6vetNZqr6DTqKTF/Anhaxm98Ij0Rq', NULL, NULL, 0, NULL, 'student', '2026-02-19 03:57:50'),
(18, 14, 'Anjali Saputra', 'anjalisaputra@gmail.com', '111111', '$2y$10$.DDjhK6LF0TIl5P4AobyJOf3LgrIptdQ50B4EgD4riej9.EWb/tvG', NULL, NULL, 0, NULL, 'student', '2026-02-19 04:02:36'),
(19, 4, 'tuti', 'tuti@gmail.com', '888888', '$2y$10$u6DE2vuzF.DtEKy0uS14L.oisV56tQtHz5YNQHwadUlgAzCIUVIZS', NULL, NULL, 0, NULL, 'student', '2026-03-12 01:17:51'),
(20, 4, 'surya ali rafsanjani', 'raf@gmail.com', '2929', '$2y$10$fqu/IWuTDxdjsqpqKX32zOYPn8.0yLdhYtaM98BxTaNUmTMRlAz8e', NULL, NULL, 0, NULL, 'student', '2026-03-16 01:48:31');

-- --------------------------------------------------------

--
-- Struktur dari tabel `waitlist`
--

CREATE TABLE `waitlist` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `book_author` varchar(255) NOT NULL,
  `member_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','notified','fulfilled','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `waitlist`
--

INSERT INTO `waitlist` (`id`, `school_id`, `book_title`, `book_author`, `member_id`, `created_at`, `status`) VALUES
(1, 4, 'Sebuah Seni Untuk Bersikap Bodoamat', 'Mark Manson', 4, '2026-03-09 07:32:03', 'notified'),
(2, 4, 'Sebuah Seni Untuk Bersikap Bodoamat', 'Mark Manson', 13, '2026-03-12 01:18:31', 'notified'),
(3, 1, 'Spy X Familyy', 'Tatsuya Endo', 2, '2026-03-12 01:30:50', 'pending'),
(4, 14, 'B.J Habibie', 'Fatimah Fayrus', 11, '2026-03-16 01:36:34', 'pending');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `barcode_sessions`
--
ALTER TABLE `barcode_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indeks untuk tabel `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indeks untuk tabel `book_damage_fines`
--
ALTER TABLE `book_damage_fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_borrow_id` (`borrow_id`),
  ADD KEY `idx_member_id` (`member_id`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indeks untuk tabel `book_maintenance`
--
ALTER TABLE `book_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indeks untuk tabel `borrows`
--
ALTER TABLE `borrows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indeks untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_book` (`student_id`,`book_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_book` (`book_id`),
  ADD KEY `idx_student_book` (`student_id`,`book_id`);

--
-- Indeks untuk tabel `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_members_school_status` (`school_id`,`status`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_school` (`student_id`,`school_id`),
  ADD KEY `idx_read_status` (`is_read`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `idx_student_unread` (`student_id`,`is_read`,`created_at`),
  ADD KEY `idx_student_type` (`student_id`,`type`,`created_at`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `idx_siswa` (`id_siswa`),
  ADD KEY `idx_status` (`status_baca`),
  ADD KEY `idx_jenis` (`jenis_notifikasi`),
  ADD KEY `idx_tanggal` (`tanggal`);
ALTER TABLE `notifikasi` ADD FULLTEXT KEY `ft_search` (`judul`,`pesan`);

--
-- Indeks untuk tabel `rating_buku`
--
ALTER TABLE `rating_buku`
  ADD PRIMARY KEY (`id_rating`),
  ADD UNIQUE KEY `unique_user_book` (`id_user`,`id_buku`);

--
-- Indeks untuk tabel `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_schools_status` (`status`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `school_themes`
--
ALTER TABLE `school_themes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`),
  ADD KEY `idx_school_id` (`school_id`);

--
-- Indeks untuk tabel `school_theme_presets`
--
ALTER TABLE `school_theme_presets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD KEY `idx_nisn` (`nisn`),
  ADD KEY `idx_email` (`email`);

--
-- Indeks untuk tabel `special_themes`
--
ALTER TABLE `special_themes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `date` (`date`,`is_active`);

--
-- Indeks untuk tabel `theme_templates`
--
ALTER TABLE `theme_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_users_school_email` (`school_id`,`email`),
  ADD KEY `idx_verification_code` (`verification_code`),
  ADD KEY `idx_is_verified` (`is_verified`);

--
-- Indeks untuk tabel `waitlist`
--
ALTER TABLE `waitlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `book_title` (`book_title`,`book_author`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `barcode_sessions`
--
ALTER TABLE `barcode_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `book_damage_fines`
--
ALTER TABLE `book_damage_fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `book_maintenance`
--
ALTER TABLE `book_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `borrows`
--
ALTER TABLE `borrows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT untuk tabel `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rating_buku`
--
ALTER TABLE `rating_buku`
  MODIFY `id_rating` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `school_themes`
--
ALTER TABLE `school_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `school_theme_presets`
--
ALTER TABLE `school_theme_presets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `special_themes`
--
ALTER TABLE `special_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `theme_templates`
--
ALTER TABLE `theme_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `waitlist`
--
ALTER TABLE `waitlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `barcode_sessions`
--
ALTER TABLE `barcode_sessions`
  ADD CONSTRAINT `barcode_sessions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `barcode_sessions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `book_damage_fines`
--
ALTER TABLE `book_damage_fines`
  ADD CONSTRAINT `fk_damage_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_damage_borrow` FOREIGN KEY (`borrow_id`) REFERENCES `borrows` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_damage_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_damage_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `book_maintenance`
--
ALTER TABLE `book_maintenance`
  ADD CONSTRAINT `fk_maintenance_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `borrows`
--
ALTER TABLE `borrows`
  ADD CONSTRAINT `borrows_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrows_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrows_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `school_themes`
--
ALTER TABLE `school_themes`
  ADD CONSTRAINT `school_themes_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
