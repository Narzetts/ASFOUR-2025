<?php
// aksi_pilih.php — ASFOUR v1.1.0 Bulk Action Handler
session_start();

// --- [SEC] 1. Cek Sesi Admin ---
if (!isset($_SESSION['admin'])) {
    header("Location: futaridakeno.php");
    exit;
}

// Hanya terima metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dorimutaimu.php");
    exit;
}

// --- [SEC] 2. Validasi Token CSRF ---
$token_in = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_in)) {
    die("❌ Permintaan tidak valid (CSRF).");
}

// --- [SEC] 3. Validasi & Sanitasi ID Terpilih ---
$pilih = $_POST['pilih'] ?? [];
if (!is_array($pilih) || empty($pilih)) {
    header("Location: dorimutaimu.php?msg=empty_selection");
    exit;
}

$valid_ids = [];
foreach ($pilih as $id) {
    $val = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($val !== false) {
        $valid_ids[] = (int)$val;
    }
}

if (empty($valid_ids)) {
    header("Location: dorimutaimu.php?msg=empty_selection");
    exit;
}

$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$action = trim($_POST['bulk_action'] ?? '');
$admin_user = $_SESSION['admin'];
$placeholders = implode(',', array_fill(0, count($valid_ids), '?'));

// Helper pencatat log
function catat_log_massal($conn, $user, $aktivitas) {
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (username, aktivitas, waktu) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ss", $user, $aktivitas);
        $stmt->execute();
        $stmt->close();
    }
}

if ($action === 'hapus') {
    // --- AKSI: Hapus Massal via Prepared Statement ---
    $sql = "DELETE FROM hasil_survei WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($valid_ids)), ...$valid_ids);
    $stmt->execute();
    $stmt->close();

    $log_msg = "Menghapus massal " . count($valid_ids) . " aspirasi (ID: " . implode(', ', $valid_ids) . ")";
    catat_log_massal($conn, $admin_user, $log_msg);

    $conn->close();
    header("Location: dorimutaimu.php?msg=bulk_deleted");
    exit;

} elseif (in_array($action, ['status_Baru', 'status_Dibaca', 'status_Ditindaklanjuti'], true)) {
    // --- AKSI: Ubah Status Massal via Prepared Statement ---
    $new_status = str_replace('status_', '', $action);
    $sql = "UPDATE hasil_survei SET status = ? WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $types = 's' . str_repeat('i', count($valid_ids));
    $stmt->bind_param($types, $new_status, ...$valid_ids);
    $stmt->execute();
    $stmt->close();

    $log_msg = "Mengubah status massal " . count($valid_ids) . " aspirasi menjadi '$new_status' (ID: " . implode(', ', $valid_ids) . ")";
    catat_log_massal($conn, $admin_user, $log_msg);

    $conn->close();
    header("Location: dorimutaimu.php?msg=bulk_status_updated");
    exit;

} else {
    $conn->close();
    header("Location: dorimutaimu.php?msg=invalid_action");
    exit;
}
?>
