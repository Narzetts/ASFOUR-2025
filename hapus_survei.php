<?php
// hapus_survei.php — ASFOUR v1.0.1
// File ini tidak digunakan oleh alur aktif (dorimutaimu.php menangani hapus langsung).
// Diperkuat dengan sesi, CSRF, prepared statement sebagai cadangan aman.

session_start();

// --- [SEC] Wajib login sebagai admin ---
if (!isset($_SESSION['admin'])) {
    header("Location: futaridakeno.php");
    exit;
}

// --- [SEC] Hanya terima POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dorimutaimu.php");
    exit;
}

// --- [SEC] Validasi CSRF token ---
$token_in = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_in)) {
    die("❌ Permintaan tidak valid (CSRF).");
}

// --- [SEC] Validasi id: harus integer positif ---
$id_int = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id_int === false) {
    die("❌ ID tidak valid.");
}

$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- [SEC] Prepared statement — tidak lagi interpolasi langsung ---
$stmt = $conn->prepare("DELETE FROM hasil_survei WHERE id = ?");
$stmt->bind_param("i", $id_int);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: dorimutaimu.php");
exit;
?>
