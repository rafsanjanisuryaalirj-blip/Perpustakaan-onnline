-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 23 Okt 2026 pada 20.51
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
(1, 4, 'Mengunyah Rindu', 'Budi Maryono', '982384', 'Fiksi', 'all', 1, NULL, '2026-01-26 02:48:25', '1A', 1, '11', 'book_1769399260_6976e3dc930ca.jpg'),
(2, 4, 'Bu, aku ingin pelukmu', 'Reza Mustopa', '4522343', 'Fiksi', 'all', 1, NULL, '2026-01-26 02:49:48', '1A', 1, '10', 'book_1769399253_6976e3d564a9a.png'),
(3, 4, 'Madilog', 'Tan Malaka', '533454', 'Referensi', 'all', 1, NULL, '2026-01-26 02:52:47', '1B', 1, '9', 'book_1769399247_6976e3cfbddeb.jpeg'),
(4, 4, 'Sebuah Seni Untuk Bersikap Bodoamat', 'Mark Manson', '345645', 'Non-Fiksi', 'all', 0, NULL, '2026-01-26 02:55:04', '1B', 2, '8', 'book_1769399240_6976e3c8d253c.png'),
(6, 4, 'Sang Alkemis', 'Paulo Coelho', '9786020656069', 'Lainnya', 'all', 1, NULL, '2026-01-26 04:03:53', '1B', 4, '6', 'book_1769400245_6976e7b53bd5e.jpg'),
(7, 4, 'B.J. Habibie : Sebuah Biografi', 'Fatimah Fayrus', '9786231643094', 'Biografi', 'all', 1, NULL, '2026-01-26 04:07:07', '1C', 2, '4', 'book_1769400427_6976e86b698e3.jpg'),
(14, 4, 'The Art of Stoicisms', 'Adora Kinara', '34567890', 'Non-Fiksi', 'teacher_only', 1, NULL, '2026-02-10 07:15:57', '1', 2, '5', 'book_1770707752_698adb282818f.jpg'),
(18, 14, 'Spy X Family', 'Tatsuya Endo', '987-982', 'Fiksi', 'all', 0, 2, '2026-02-19 03:53:24', '1D', 3, '4', 'book_1771473204_6996893442426.jpg'),
(19, 14, 'B.J Habibie', 'Fatimah Fayrus', '987492', 'Biografi', 'teacher_only', 0, 3, '2026-02-19 03:55:35', '1B', 2, '2', 'book_1771473335_699689b72f067.jpg'),
(30, 4, 'Laskar Pelangii', 'Andrea Hirata', '4523543', 'Fiksi', 'all', 1, NULL, '2026-10-23 17:37:10', '1', 4, '2', 'book_1792777030_6adb9b466e76e.jpg'),
(31, 4, 'Laskar Pelangii', 'Andrea Hirata', '4523543', 'Fiksi', 'all', 1, NULL, '2026-10-23 17:37:10', '1', 4, '2', 'book_1792777030_6adb9b466e76e.jpg'),
(32, 4, 'Laskar Pelangii', 'Andrea Hirata', '4523543', 'Fiksi', 'all', 1, NULL, '2026-10-23 17:37:10', '1', 4, '2', 'book_1792777030_6adb9b466e76e.jpg');

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
(6, 4, 83, 14, 7, 'stain', 'Terkena Coklat', 30000.00, 'paid', '2026-10-23 16:22:59', '2026-10-23 16:23:21'),
(9, 4, 89, 4, 7, 'minor_tear', NULL, 10000.00, 'paid', '2026-10-23 17:30:23', '2026-10-23 17:40:30'),
(10, 4, 82, 4, 7, 'stain', NULL, 30000.00, 'pending', '2026-10-23 17:40:40', '2026-10-23 17:40:40');

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
(35, 4, 4, 4, '2026-02-12 10:45:20', '2026-02-19 10:47:25', '2026-02-12 10:48:57', 'returned', 0.00, 'unpaid'),
(37, 4, 7, 4, '2026-02-16 08:58:19', '2026-02-23 09:19:17', '2026-02-16 09:19:28', 'returned', 0.00, 'unpaid'),
(38, 4, 4, 4, '2026-02-16 09:01:58', '2026-02-23 09:19:17', '2026-02-16 09:19:27', 'returned', 0.00, 'unpaid'),
(40, 4, 7, 4, '2026-02-16 09:24:52', '2026-02-23 09:25:54', '2026-02-16 09:43:44', 'returned', 0.00, 'unpaid'),
(41, 4, 2, 4, '2026-02-16 09:25:15', '2026-02-23 09:25:54', '2026-02-16 09:26:10', 'returned', 0.00, 'unpaid'),
(43, 4, 7, 4, '2026-02-16 09:44:14', '2026-03-03 09:44:21', '2026-02-16 09:52:27', 'returned', 0.00, 'unpaid'),
(44, 4, 2, 4, '2026-02-16 09:53:13', '2026-02-23 09:53:21', '2026-02-20 13:48:58', 'returned', 0.00, 'unpaid'),
(53, 4, 2, 4, '2026-02-20 13:49:26', '2026-02-23 13:49:32', '2026-02-20 13:56:17', 'returned', 0.00, 'unpaid'),
(54, 4, 2, 4, '2026-02-20 13:59:23', '2026-02-23 14:04:27', '2026-02-20 14:25:14', 'returned', 0.00, 'unpaid'),
(62, 4, 4, 4, '2026-02-23 08:35:49', '2026-02-26 08:36:18', '2026-02-23 08:36:23', 'returned', 0.00, 'unpaid'),
(63, 4, 1, 4, '2026-02-23 08:35:49', '2026-02-26 08:36:18', '2026-02-23 08:36:21', 'returned', 0.00, 'unpaid'),
(64, 4, 4, 4, '2026-02-23 08:42:45', '2026-02-26 23:59:59', '2026-03-12 09:15:06', 'returned', 14000.00, 'unpaid'),
(65, 4, 1, 4, '2026-02-23 08:42:45', '2026-02-26 23:59:59', '2026-02-26 10:14:33', 'returned', 0.00, 'unpaid'),
(66, 4, 4, 4, '2026-03-12 09:37:50', '2026-03-15 23:59:59', '2026-03-12 09:38:54', 'returned', 0.00, 'unpaid'),
(68, 4, 7, 4, '2026-03-16 08:35:33', '2026-03-19 23:59:59', '2026-03-16 08:39:42', 'returned', 0.00, 'unpaid'),
(72, 4, 2, 14, '2026-03-16 08:51:35', '2026-03-19 23:59:59', '2026-03-16 08:55:47', 'returned', 0.00, 'unpaid'),
(73, 4, 7, 14, '2026-03-16 08:51:35', '2026-03-19 23:59:59', '2026-03-16 08:55:51', 'returned', 0.00, 'unpaid'),
(74, 4, 2, 14, '2026-03-16 10:03:33', '2026-03-19 23:59:59', '2026-03-16 10:06:40', 'returned', 0.00, 'unpaid'),
(75, 4, 7, 14, '2026-03-16 10:03:33', '2026-03-19 23:59:59', '2026-03-16 10:06:43', 'returned', 0.00, 'unpaid'),
(76, 4, 1, 4, '2026-03-16 10:05:19', '2026-03-19 23:59:59', '2026-03-23 11:31:12', 'returned', 3000.00, 'unpaid'),
(78, 4, 2, 4, '2026-03-23 11:32:11', '2026-03-26 23:59:59', '2026-03-23 13:32:49', 'returned', 0.00, 'unpaid'),
(80, 4, 1, 4, '2026-03-23 13:33:15', '2026-03-26 23:59:59', '2026-10-23 23:18:24', 'returned', 210000.00, 'paid'),
(82, 4, 7, 4, '2026-10-23 20:26:12', '2026-10-26 23:59:59', '2026-10-23 20:27:18', 'returned', 0.00, 'unpaid'),
(83, 4, 7, 14, '2026-10-23 20:36:56', '2026-10-26 23:59:59', '2026-10-23 23:18:22', 'returned', 0.00, 'unpaid'),
(89, 4, 7, 4, '2026-10-24 00:29:01', '2026-11-03 23:59:59', '2026-10-24 00:29:56', 'returned', 0.00, 'unpaid');

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
(17, 13, 7, NULL, '2026-09-23 07:22:46');

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
(4, 4, 'Anjali Saputra', 'anjalisaputra@gmail.com', '0094234', 'student', 'active', '2026-02-10 01:14:22', 2),
(14, 4, 'surya ali rafsanjani', 'raf@gmail.com', '2929', 'student', 'active', '2026-03-16 01:48:31', 2),
(16, 4, 'Adi Triyanto', 'aditrianto@gmail.com', '0098765', 'student', 'active', '2026-10-23 17:41:22', 1),
(17, 14, 'Fabian', 'fabian@gmail.com', '666666', 'student', 'active', '2026-10-23 17:53:08', 3);

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
(55, 4, 13, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"The Art of Stoicism\" ke koleksi favorit Anda.', 'info', 0, '2026-02-20 04:08:09', '2026-02-20 04:08:09'),
(57, 4, 13, 'Buku Baru Tersedia', 'Buku \"Spy X Family\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-20 07:33:33', '2026-02-20 07:33:33'),
(59, 4, 13, 'Buku Baru Tersedia', 'Buku \"B.J. Habibie : Sebuah Biografi\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-03-09 01:26:12', '2026-03-09 01:26:12'),
(61, 4, 13, 'Buku Tersedia!', 'Buku \"Sebuah Seni Untuk Bersikap Bodoamat\" yang Anda tunggu sudah tersedia. Segera lakukan peminjaman!', 'info', 0, '2026-03-12 02:15:06', '2026-03-12 02:15:06'),
(63, 4, 13, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"B.J. Habibie : Sebuah Biografi\" ke koleksi favorit Anda.', 'info', 0, '2026-09-23 07:22:46', '2026-09-23 07:22:46'),
(64, 4, 13, 'Buku Baru Tersedia', 'Buku \"Laskar Pelangi\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-10-23 16:27:08', '2026-10-23 16:27:08'),
(65, 4, 20, 'Buku Baru Tersedia', 'Buku \"Laskar Pelangi\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-10-23 16:27:15', '2026-10-23 16:27:15'),
(66, 4, 13, 'Buku Baru Tersedia', 'Buku \"Laskar Pelangi\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-10-23 17:37:10', '2026-10-23 17:37:10'),
(67, 4, 20, 'Buku Baru Tersedia', 'Buku \"Laskar Pelangi\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-10-23 17:37:15', '2026-10-23 17:37:15'),
(68, 4, 13, 'Denda Lunas', 'Denda keterlambatan buku \"Mengunyah Rindu\" sebesar Rp 210.000 telah dinyatakan lunas oleh petugas perpustakaan.', '', 0, '2026-10-23 17:42:29', '2026-10-23 17:42:29');

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
(5, 13, 14, 5, 'bagus', '2026-02-20 03:30:01'),
(6, 13, 6, 4, 'wow', '2026-02-20 03:37:50'),
(7, 13, 3, 1, 'keren banget', '2026-02-20 03:42:52'),
(8, 13, 5, 5, 'bagus banget', '2026-02-20 03:45:37'),
(9, 13, 7, 5, 'sangat bagus', '2026-10-23 18:44:30');

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
  `damage_fines_config` text DEFAULT NULL,
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

INSERT INTO `schools` (`id`, `name`, `slug`, `status`, `activation_code`, `created_at`, `email`, `phone`, `address`, `description`, `logo`, `profile_picture`, `npsn`, `website`, `photo_path`, `founded_year`, `borrow_duration`, `late_fine`, `damage_fines_config`, `max_books`, `max_books_student`, `max_books_teacher`, `max_books_employee`, `scan_access_key`, `custom_base_url`) VALUES
(4, 'AUSTRALIA INDEPENDENTS SCHOOL', 'australia-independents-school', 'active', NULL, '2026-01-26 02:42:13', 'australiaindependentschool@sch.id', NULL, NULL, NULL, NULL, NULL, '39023341', NULL, 'public/uploads/school-photos/school_1773277970_69b213126c1ea.jpg', 0, 5, 1000.00, '{\"minor_tear\":10000,\"major_tear\":50000,\"water_damage\":100000,\"stain\":30000,\"cover_damage\":40000,\"spine_damage\":35000,\"missing_pages\":150000,\"other\":0}', 4, 2, 5, 5, '202cbc376a9ccfea1279ef3c20f0b0b2', 'https://viewpoint-ditzy-untangled.ngrok-free.dev'),
(14, 'SMK BINA MANDIRI MULTIMEDIA', 'smk-bina-mandiri-multimedia', 'active', NULL, '2026-02-19 03:51:47', 'smkbinamandirimultimedia@sch.id', NULL, NULL, NULL, NULL, NULL, '23545245', NULL, 'public/uploads/school-photos/school_1792777944_6adb9ed87eb58.png', NULL, 7, 500.00, NULL, 3, 3, 10, 5, '28a30e5d4fbb54bf7d31f2bd805b030a', 'https://viewpoint-ditzy-untangled.ngrok-free.dev');

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
(2, 4, 'light', '[]', '[]', '2026-01-26 02:43:32', '2026-10-23 18:43:49');

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
(13, 'Anjali Saputra', '0094234', 'XII', 'Rekayasa Perangkat Lunak', NULL, 'L', NULL, 'anjalisaputra@gmail.com', NULL, 'uploads/anggota/anggota_13_1792776694_6adb99f66012b.png', '2026-02-16 01:30:07', '2026-10-23 17:31:34'),
(14, 'surya', '2346558', NULL, NULL, NULL, NULL, NULL, 'uya4767@gmail.com', NULL, NULL, '2026-02-11 06:23:05', '2026-02-11 06:23:05'),
(18, 'Anjali Saputra', '111111', NULL, NULL, NULL, NULL, NULL, 'anjalisaputra@gmail.com', NULL, 'uploads/anggota/anggota_18_1771476240_69969510dbb14.jpeg', '2026-02-19 04:20:45', '2026-02-19 04:44:00'),
(20, 'surya ali rafsanjani', '2929', 'XII', 'Rekayasa Perangkat Lunak', '2008-12-29', 'L', 'jalan,pattimura blok c4 no 39', 'raf@gmail.com', '085891129163', 'uploads/anggota/anggota_20_1773626867_69b765f343aa2.webp', '2026-03-16 02:07:41', '2026-03-16 02:08:46'),
(24, 'Adi Triyantos', '098734234', NULL, NULL, NULL, NULL, NULL, 'aditrianto@gmail.com', NULL, NULL, '2026-10-23 16:26:25', '2026-10-23 16:26:25');

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
(25, 4, 'Peringatan Hari Kemerdekaan RI', '2026-10-23', 0, 'kemerdekaan', 'Dirgahayu Republik Indonesia (17 Agustus)', '2026-10-23 10:00:02', '2026-10-23 16:35:10'),
(26, 4, 'Hari Raya Idul Fitri', '2026-10-24', 0, 'idulfitri', 'Perayaan Hari Raya Idul Fitri 1 Syawal', '2026-10-23 10:00:02', '2026-10-23 17:10:33'),
(27, 4, 'Hari Guru Nasional', '2026-10-23', 0, 'hariguru', 'Penghormatan Guru & Tenaga Kependidikan (25 November)', '2026-10-23 10:00:02', '2026-10-23 16:35:14'),
(28, 4, 'Perayaan Tahun Baru Masehi', '2026-10-23', 0, 'tahunbaru', 'Menyambut Tahun Baru & Harapan Baru', '2026-10-23 10:00:02', '2026-10-23 11:22:49'),
(29, 4, 'Hari Raya Natal & Tahun Baru', '2026-10-23', 0, 'natal', 'Perayaan Hari Natal & Libur Akhir Tahun (25 Desember)', '2026-10-23 10:00:02', '2026-10-23 11:22:51'),
(30, 15, 'Peringatan Hari Kemerdekaan RI', '2026-10-23', 0, 'kemerdekaan', 'Dirgahayu Republik Indonesia (17 Agustus)', '2026-10-23 10:43:15', '2026-10-23 10:43:15'),
(31, 15, 'Hari Raya Idul Fitri', '2026-03-21', 0, 'idulfitri', 'Perayaan Hari Raya Idul Fitri 1 Syawal', '2026-10-23 10:43:15', '2026-10-23 10:43:15'),
(32, 15, 'Hari Guru Nasional', '2026-11-25', 0, 'hariguru', 'Penghormatan Guru & Tenaga Kependidikan (25 November)', '2026-10-23 10:43:15', '2026-10-23 10:43:15'),
(33, 15, 'Perayaan Tahun Baru & Imlek', '2026-01-01', 0, 'tahunbaru', 'Menyambut Tahun Baru & Harapan Baru', '2026-10-23 10:43:15', '2026-10-23 10:43:15'),
(34, 15, 'Hari Raya Natal & Tahun Baru', '2026-12-25', 0, 'natal', 'Perayaan Hari Natal & Libur Akhir Tahun (25 Desember)', '2026-10-23 10:43:15', '2026-10-23 10:43:15'),
(35, 4, 'Perayaan Hari Raya Imlek', '2026-10-23', 0, 'imlek', 'Gong Xi Fa Cai — Tahun Baru Imlek Penuh Berkah', '2026-10-23 10:53:53', '2026-10-23 11:22:54'),
(36, 17, 'Peringatan Hari Kemerdekaan RI', '2026-08-17', 0, 'kemerdekaan', 'Dirgahayu Republik Indonesia (17 Agustus)', '2026-10-23 16:12:17', '2026-10-23 16:12:17'),
(37, 17, 'Hari Raya Idul Fitri', '2026-03-21', 0, 'idulfitri', 'Perayaan Hari Raya Idul Fitri 1 Syawal', '2026-10-23 16:12:17', '2026-10-23 16:12:17'),
(38, 17, 'Hari Guru Nasional', '2026-11-25', 0, 'hariguru', 'Penghormatan Guru & Tenaga Kependidikan (25 November)', '2026-10-23 16:12:17', '2026-10-23 16:12:17'),
(39, 17, 'Perayaan Tahun Baru Masehi', '2026-10-23', 1, 'tahunbaru', 'Menyambut Tahun Baru & Harapan Baru', '2026-10-23 16:12:17', '2026-10-23 16:12:22'),
(40, 17, 'Perayaan Hari Raya Imlek', '2026-10-23', 0, 'imlek', 'Gong Xi Fa Cai — Tahun Baru Imlek Penuh Berkah', '2026-10-23 16:12:17', '2026-10-23 16:12:22'),
(41, 17, 'Hari Raya Natal & Tahun Baru', '2026-12-25', 0, 'natal', 'Perayaan Hari Natal & Libur Akhir Tahun (25 Desember)', '2026-10-23 16:12:17', '2026-10-23 16:12:17'),
(42, 14, 'Peringatan Hari Kemerdekaan RI', '2026-08-17', 0, 'kemerdekaan', 'Dirgahayu Republik Indonesia (17 Agustus)', '2026-10-23 17:51:41', '2026-10-23 17:51:41'),
(43, 14, 'Hari Raya Idul Fitri', '2026-03-21', 0, 'idulfitri', 'Perayaan Hari Raya Idul Fitri 1 Syawal', '2026-10-23 17:51:41', '2026-10-23 17:51:41'),
(44, 14, 'Hari Guru Nasional', '2026-11-25', 0, 'hariguru', 'Penghormatan Guru & Tenaga Kependidikan (25 November)', '2026-10-23 17:51:41', '2026-10-23 17:51:41'),
(45, 14, 'Perayaan Tahun Baru Masehi', '2026-01-01', 0, 'tahunbaru', 'Menyambut Tahun Baru & Harapan Baru', '2026-10-23 17:51:41', '2026-10-23 17:51:41'),
(46, 14, 'Perayaan Hari Raya Imlek', '2026-02-10', 0, 'imlek', 'Gong Xi Fa Cai — Tahun Baru Imlek Penuh Berkah', '2026-10-23 17:51:41', '2026-10-23 17:51:41'),
(47, 14, 'Hari Raya Natal & Tahun Baru', '2026-12-25', 0, 'natal', 'Perayaan Hari Natal & Libur Akhir Tahun (25 Desember)', '2026-10-23 17:51:41', '2026-10-23 17:51:41');

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
(3, 4, 'Budi', 'australiaindependentsschool@sch.id', NULL, '$2y$10$p.D7vB6u14epbpDLMJsqgeyyXT0VtOGtpOvDZLUmpdA1brIOwShle', NULL, '2026-01-25 20:57:13', 1, '2026-01-26 02:42:24', 'admin', '2026-01-26 02:42:14'),
(13, 4, 'Anjali Saputra', 'anjalisaputra@gmail.com', '0094234', '$2y$10$GMvyhJwwsiBDCCL6fN2Bw.nvWZrvpZmEej1Qy2OHbpBM.VbkXQJji', NULL, NULL, 0, NULL, 'student', '2026-02-10 01:14:22'),
(16, 14, 'Gani', 'smkbinamandirimultimedia@sch.id', NULL, '$2y$10$xJgd.CLQLLrZQ.osocHNR.484jKUUJYOlsvhUSokbXbN.qX6glc2a', NULL, '2026-02-18 22:06:47', 1, '2026-02-19 03:51:55', 'admin', '2026-02-19 03:51:47'),
(20, 4, 'surya ali rafsanjani', 'raf@gmail.com', '2929', '$2y$10$p.D7vB6u14epbpDLMJsqgeyyXT0VtOGtpOvDZLUmpdA1brIOwShle', NULL, NULL, 0, NULL, 'student', '2026-03-16 01:48:31'),
(25, 4, 'Adi Triyanto', 'aditrianto@gmail.com', '0098765', '$2y$10$Im330PSAhLwngCmTt3ILIuf/MUGnxSxXBvHt5fcItnmQuzcuXaeje', NULL, NULL, 0, NULL, 'student', '2026-10-23 17:41:22'),
(26, 14, 'Fabian', 'fabian@gmail.com', '666666', '$2y$10$MKLImds0k7NS71tmifadCOhHsRTZIqm4iVVrrj5woOeJcobHO4ulK', NULL, NULL, 0, NULL, 'student', '2026-10-23 17:53:08');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `book_damage_fines`
--
ALTER TABLE `book_damage_fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `book_maintenance`
--
ALTER TABLE `book_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `borrows`
--
ALTER TABLE `borrows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT untuk tabel `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rating_buku`
--
ALTER TABLE `rating_buku`
  MODIFY `id_rating` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `school_themes`
--
ALTER TABLE `school_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `school_theme_presets`
--
ALTER TABLE `school_theme_presets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `special_themes`
--
ALTER TABLE `special_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT untuk tabel `theme_templates`
--
ALTER TABLE `theme_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
