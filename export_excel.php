<?php
// export_excel.php — ASFOUR v1.1.0 Export Handler (XLS & CSV with UTF-8 BOM)
session_start();

// --- [SEC] Cek Sesi Admin ---
if (!isset($_SESSION['admin'])) {
    header("Location: futaridakeno.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Tangkap filter dari URL (sinkron dengan filter dashboard)
$q         = trim($_GET['q'] ?? '');
$kategori  = trim($_GET['kategori'] ?? '');
$angkatan  = trim($_GET['angkatan'] ?? '');
$status    = trim($_GET['status'] ?? '');
$tgl_mulai = trim($_GET['tgl_mulai'] ?? '');
$tgl_akhir = trim($_GET['tgl_akhir'] ?? '');
$format    = strtolower(trim($_GET['format'] ?? 'xls'));

$where_clauses = [];
$params        = [];
$types         = '';

if ($q !== '') {
    $where_clauses[] = "(pengalaman LIKE ? OR kode_tiket LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($kategori !== '' && $kategori !== 'Semua') {
    $where_clauses[] = "makanan = ?";
    $params[] = $kategori;
    $types .= 's';
}

if ($angkatan !== '' && $angkatan !== 'Semua') {
    $where_clauses[] = "makan_perhari = ?";
    $params[] = $angkatan;
    $types .= 's';
}

if ($status !== '' && $status !== 'Semua') {
    $where_clauses[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($tgl_mulai !== '') {
    $where_clauses[] = "DATE(waktu) >= ?";
    $params[] = $tgl_mulai;
    $types .= 's';
}

if ($tgl_akhir !== '') {
    $where_clauses[] = "DATE(waktu) <= ?";
    $params[] = $tgl_akhir;
    $types .= 's';
}

$sql = "SELECT * FROM hasil_survei";
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- [LOG] Catat Aktivitas Ekspor ---
$log_format = strtoupper($format);
$log_desc = "Export data (Format: $log_format";
if (!empty($where_clauses)) {
    $log_desc .= ", difilter)";
} else {
    $log_desc .= ", semua data)";
}
$log_stmt = $conn->prepare("INSERT INTO log_aktivitas (username, aktivitas, waktu) VALUES (?, ?, NOW())");
if ($log_stmt) {
    $log_stmt->bind_param("ss", $_SESSION['admin'], $log_desc);
    $log_stmt->execute();
    $log_stmt->close();
}

$filename_base = "rekap_asfour_" . date('Ymd_His');

if ($format === 'csv') {
    // --- FORMAT: CSV dengan UTF-8 BOM ---
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename={$filename_base}.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $output = fopen('php://output', 'w');
    // Tulis UTF-8 BOM agar Excel membukanya tanpa karakter rusak
    fputs($output, "\xEF\xBB\xBF");

    // Header Kolom
    fputcsv($output, ['No (ID)', 'Kode Tiket', 'Status', 'Anonim', 'Kategori Pengisi', 'Angkatan', 'Aspirasi', 'Link Foto', 'Waktu']);

    while ($row = $result->fetch_assoc()) {
        $foto_url = $row['foto'] ? 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $row['foto'] : '-';
        $is_anon = ($row['anonim'] ?? 0) ? 'Ya' : 'Tidak';
        fputcsv($output, [
            $row['id'],
            $row['kode_tiket'] ?? '-',
            $row['status'] ?? 'Baru',
            $is_anon,
            $row['makanan'],
            $row['makan_perhari'],
            $row['pengalaman'],
            $foto_url,
            $row['waktu'] ?? '-'
        ]);
    }
    fclose($output);

} else {
    // --- FORMAT: HTML-Excel (.xls) ---
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename={$filename_base}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />";
    echo "<table border='1'>";
    echo "<tr style='background-color:#2c3e50;color:#ffffff;font-weight:bold;'>
        <th>No (ID)</th>
        <th>Kode Tiket</th>
        <th>Status</th>
        <th>Anonim</th>
        <th>Kategori Pengisi</th>
        <th>Angkatan</th>
        <th>Aspirasi</th>
        <th>Link Foto</th>
        <th>Waktu</th>
    </tr>";

    while ($row = $result->fetch_assoc()) {
        $foto_url = $row['foto'] ? 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $row['foto'] : '';
        $is_anon = ($row['anonim'] ?? 0) ? 'Ya' : 'Tidak';

        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['kode_tiket'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['status'] ?? 'Baru') . "</td>";
        echo "<td>{$is_anon}</td>";
        echo "<td>" . htmlspecialchars($row['makanan']) . "</td>";
        echo "<td>" . htmlspecialchars($row['makan_perhari']) . "</td>";
        echo "<td>" . nl2br(htmlspecialchars($row['pengalaman'])) . "</td>";
        echo "<td>";
        if ($foto_url) {
            echo "<a href='$foto_url'>$foto_url</a>";
        } else {
            echo "-";
        }
        echo "</td>";
        echo "<td>" . ($row['waktu'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$stmt->close();
$conn->close();
exit;
?>
