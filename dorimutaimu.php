<?php
// dorimutaimu.php — ASFOUR v1.1.0 Admin Dashboard
define('APP_VERSION', '1.1.0');

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: futaridakeno.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- [SEC] Generate CSRF token per sesi ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
$admin_user = $_SESSION['admin'];

// Helper pencatat log aktivitas
function catat_log($conn, $user, $aktivitas) {
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (username, aktivitas, waktu) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ss", $user, $aktivitas);
        $stmt->execute();
        $stmt->close();
    }
}

$alert_msg = "";
$alert_type = "";

// Tangkap notifikasi dari parameter redirect
$msg_param = $_GET['msg'] ?? '';
if ($msg_param === 'deleted') {
    $alert_msg = "✅ Data aspirasi berhasil dihapus.";
    $alert_type = "success";
} elseif ($msg_param === 'bulk_deleted') {
    $alert_msg = "✅ Data aspirasi terpilih berhasil dihapus secara massal.";
    $alert_type = "success";
} elseif ($msg_param === 'bulk_status_updated') {
    $alert_msg = "✅ Status data aspirasi terpilih berhasil diperbarui.";
    $alert_type = "success";
} elseif ($msg_param === 'empty_selection') {
    $alert_msg = "⚠️ Tidak ada data yang dipilih untuk diproses.";
    $alert_type = "error";
}

// =========================================================================
// HANDLER AKSI POST (Ganti Password, Hapus Tunggal, Ubah Status Tunggal)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_in = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $token_in)) {
        die("❌ Permintaan tidak valid (CSRF).");
    }

    $action = $_POST['action'] ?? '';

    // --- 1. Aksi Ganti Password Admin ---
    if ($action === 'ganti_password') {
        $pwd_lama = $_POST['pwd_lama'] ?? '';
        $pwd_baru = $_POST['pwd_baru'] ?? '';
        $pwd_konf = $_POST['pwd_konfirmasi'] ?? '';

        if (mb_strlen($pwd_baru) < 8) {
            $alert_msg = "❌ Password baru minimal harus 8 karakter.";
            $alert_type = "error";
        } elseif ($pwd_baru !== $pwd_konf) {
            $alert_msg = "❌ Konfirmasi password baru tidak cocok.";
            $alert_type = "error";
        } else {
            // Verifikasi password lama
            $stmt_pwd = $conn->prepare("SELECT password FROM admin WHERE username = ?");
            $stmt_pwd->bind_param("s", $admin_user);
            $stmt_pwd->execute();
            $stmt_pwd->bind_result($db_hash);
            $stmt_pwd->fetch();
            $stmt_pwd->close();

            if (!$db_hash || !password_verify($pwd_lama, $db_hash)) {
                $alert_msg = "❌ Password lama yang Anda masukkan salah.";
                $alert_type = "error";
            } else {
                $new_hash = password_hash($pwd_baru, PASSWORD_DEFAULT);
                $stmt_upd = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
                $stmt_upd->bind_param("ss", $new_hash, $admin_user);
                $stmt_upd->execute();
                $stmt_upd->close();

                catat_log($conn, $admin_user, "Mengubah password admin");
                $alert_msg = "✅ Password admin berhasil diperbarui!";
                $alert_type = "success";
            }
        }
    }

    // --- 2. Aksi Hapus Tunggal ---
    if ($action === 'hapus') {
        $id_int = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id_int !== false) {
            $stmt = $conn->prepare("DELETE FROM hasil_survei WHERE id = ?");
            $stmt->bind_param("i", $id_int);
            $stmt->execute();
            $stmt->close();

            catat_log($conn, $admin_user, "Menghapus aspirasi ID: " . $id_int);
            $alert_msg = "✅ Aspirasi ID #{$id_int} berhasil dihapus.";
            $alert_type = "success";
        }
    }

    // --- 3. Aksi Ubah Status Tunggal ---
    if ($action === 'ubah_status') {
        $id_int = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status_baru = trim($_POST['status_baru'] ?? '');
        $allowed_status = ['Baru', 'Dibaca', 'Ditindaklanjuti'];

        if ($id_int !== false && in_array($status_baru, $allowed_status, true)) {
            $stmt = $conn->prepare("UPDATE hasil_survei SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status_baru, $id_int);
            $stmt->execute();
            $stmt->close();

            catat_log($conn, $admin_user, "Mengubah status aspirasi ID #{$id_int} menjadi '{$status_baru}'");
            $alert_msg = "✅ Status aspirasi ID #{$id_int} diubah menjadi {$status_baru}.";
            $alert_type = "success";
        }
    }
}

// =========================================================================
// QUERY STATISTIK AGREGAT UNTUK KARTU STATISTIK
// =========================================================================
$total_aspirasi = 0;
$res_total = $conn->query("SELECT COUNT(*) AS total FROM hasil_survei");
if ($res_total) {
    $row_t = $res_total->fetch_assoc();
    $total_aspirasi = (int)($row_t['total'] ?? 0);
}

// Statistik Status
$stat_status = ['Baru' => 0, 'Dibaca' => 0, 'Ditindaklanjuti' => 0];
$res_st = $conn->query("SELECT status, COUNT(*) AS jml FROM hasil_survei GROUP BY status");
if ($res_st) {
    while ($r = $res_st->fetch_assoc()) {
        $s = $r['status'] ?? 'Baru';
        if (isset($stat_status[$s])) {
            $stat_status[$s] = (int)$r['jml'];
        }
    }
}

// Statistik Kategori
$stat_kategori = ['Guru' => 0, 'Siswa' => 0, 'Warga Sekolah' => 0, 'Umum' => 0];
$res_kat = $conn->query("SELECT makanan, COUNT(*) AS jml FROM hasil_survei GROUP BY makanan");
if ($res_kat) {
    while ($r = $res_kat->fetch_assoc()) {
        $k = $r['makanan'] ?? '';
        if (isset($stat_kategori[$k])) {
            $stat_kategori[$k] = (int)$r['jml'];
        }
    }
}

// Statistik Angkatan Siswa
$stat_angkatan = ["A'23" => 0, "A'24" => 0, "A'25" => 0, "A'26" => 0];
$res_ang = $conn->query("SELECT makan_perhari, COUNT(*) AS jml FROM hasil_survei WHERE makanan = 'Siswa' GROUP BY makan_perhari");
if ($res_ang) {
    while ($r = $res_ang->fetch_assoc()) {
        $a = $r['makan_perhari'] ?? '';
        if (isset($stat_angkatan[$a])) {
            $stat_angkatan[$a] = (int)$r['jml'];
        }
    }
}

// =========================================================================
// FILTER, PENCARIAN, DAN PAGINATION
// =========================================================================
$q         = trim($_GET['q'] ?? '');
$f_kat     = trim($_GET['kategori'] ?? '');
$f_ang     = trim($_GET['angkatan'] ?? '');
$f_status  = trim($_GET['status'] ?? '');
$tgl_mulai = trim($_GET['tgl_mulai'] ?? '');
$tgl_akhir = trim($_GET['tgl_akhir'] ?? '');

$limit = filter_var($_GET['limit'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['min_range' => 10, 'max_range' => 50]]) ?: 10;
$page  = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;

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

if ($f_kat !== '' && $f_kat !== 'Semua') {
    $where_clauses[] = "makanan = ?";
    $params[] = $f_kat;
    $types .= 's';
}

if ($f_ang !== '' && $f_ang !== 'Semua') {
    $where_clauses[] = "makan_perhari = ?";
    $params[] = $f_ang;
    $types .= 's';
}

if ($f_status !== '' && $f_status !== 'Semua') {
    $where_clauses[] = "status = ?";
    $params[] = $f_status;
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

$where_sql = !empty($where_clauses) ? " WHERE " . implode(' AND ', $where_clauses) : "";

// Hitung total data terfilter
$count_sql = "SELECT COUNT(*) AS total_filtered FROM hasil_survei" . $where_sql;
$stmt_cnt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt_cnt->bind_param($types, ...$params);
}
$stmt_cnt->execute();
$res_cnt = $stmt_cnt->get_result();
$total_filtered = (int)($res_cnt->fetch_assoc()['total_filtered'] ?? 0);
$stmt_cnt->close();

$total_pages = ceil($total_filtered / $limit) ?: 1;
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

// Ambil data halaman aktif
$data_sql = "SELECT * FROM hasil_survei" . $where_sql . " ORDER BY id DESC LIMIT ?, ?";
$stmt_data = $conn->prepare($data_sql);
$data_params = $params;
$data_types  = $types . 'ii';
$data_params[] = $offset;
$data_params[] = $limit;
$stmt_data->bind_param($data_types, ...$data_params);
$stmt_data->execute();
$result = $stmt_data->get_result();

// Parameter query string untuk link ekspor & pagination
$query_params = $_GET;
unset($query_params['page']);
$filter_qs = http_build_query($query_params);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="images/Tsumugi.jpeg">
    <title>Admin ASFOUR 2025 (v<?= APP_VERSION ?>)</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .dropdown-content {
            margin-top: 5px;
            font-size: 13px;
        }
        .img-thumbnail {
            max-width: 110px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
    <script>
        function toggleContent(id, type) {
            const items = ['image', 'time', 'makanan', 'makan_perhari'];
            items.forEach(item => {
                const el = document.getElementById(item + '-' + id);
                if (el) el.style.display = 'none';
            });

            const target = document.getElementById(type + '-' + id);
            if (target) target.style.display = 'block';
        }

        function togglePasswordCard() {
            const card = document.getElementById('pwdCard');
            if (card) {
                card.style.display = (card.style.display === 'block') ? 'none' : 'block';
            }
        }

        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.row-select');
            checkboxes.forEach(cb => cb.checked = master.checked);
        }
    </script>
</head>
<body>
<header>
    <div class="header-container">
        <h1>Admin ASFOUR 2025</h1>
        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="btn-primary" onclick="togglePasswordCard()" style="background:#34495e;">
                🔑 Ganti Password
            </button>
            <a href="mirakuru.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<div class="container">

    <?php if ($alert_msg): ?>
        <div class="alert-box <?= $alert_type === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($alert_msg) ?>
        </div>
    <?php endif; ?>

    <!-- 1. KOTAK GANTI PASSWORD ADMIN -->
    <div class="pwd-card" id="pwdCard">
        <h3 style="margin-bottom:12px; font-size:16px; color:#2c3e50;">Ubah Password Administrator</h3>
        <form method="post" class="pwd-form">
            <input type="hidden" name="action" value="ganti_password">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="filter-group">
                <label>Password Lama</label>
                <input type="password" name="pwd_lama" required autocomplete="current-password">
            </div>
            <div class="filter-group">
                <label>Password Baru (min 8 char)</label>
                <input type="password" name="pwd_baru" required minlength="8" autocomplete="new-password">
            </div>
            <div class="filter-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="pwd_konfirmasi" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn-primary" style="height:38px;">Simpan Password</button>
            <button type="button" class="page-btn" onclick="togglePasswordCard()" style="height:38px;">Batal</button>
        </form>
    </div>

    <!-- 2. KARTU STATISTIK AGREGAT -->
    <div class="stat-grid">
        <div class="stat-card">
            <h4>Total Aspirasi</h4>
            <div class="stat-number"><?= number_format($total_aspirasi) ?></div>
            <div class="stat-detail">Data keseluruhan di sistem</div>
        </div>
        <div class="stat-card stat-orange">
            <h4>Status Aspirasi</h4>
            <div class="stat-number"><?= $stat_status['Baru'] ?> <span style="font-size:14px;font-weight:normal;color:#7f8c8d;">Baru</span></div>
            <div class="stat-detail">
                <?= $stat_status['Dibaca'] ?> Dibaca &bull; <?= $stat_status['Ditindaklanjuti'] ?> Ditindaklanjuti
            </div>
        </div>
        <div class="stat-card stat-green">
            <h4>Kategori Pengisi</h4>
            <div class="stat-number"><?= $stat_kategori['Siswa'] ?> <span style="font-size:14px;font-weight:normal;color:#7f8c8d;">Siswa</span></div>
            <div class="stat-detail">
                Guru: <?= $stat_kategori['Guru'] ?> &bull; Warga: <?= $stat_kategori['Warga Sekolah'] ?> &bull; Umum: <?= $stat_kategori['Umum'] ?>
            </div>
        </div>
        <div class="stat-card stat-purple">
            <h4>Angkatan Siswa</h4>
            <div class="stat-number"><?= array_sum($stat_angkatan) ?></div>
            <div class="stat-detail">
                A'23: <?= $stat_angkatan["A'23"] ?> &bull; A'24: <?= $stat_angkatan["A'24"] ?> &bull; A'25: <?= $stat_angkatan["A'25"] ?> &bull; A'26: <?= $stat_angkatan["A'26"] ?>
            </div>
        </div>
    </div>

    <!-- 3. FILTER, PENCARIAN & EKSPOR -->
    <div class="filter-card">
        <form method="get" action="dorimutaimu.php">
            <div class="filter-row">
                <div class="filter-group" style="flex: 2 1 200px;">
                    <label>Pencarian Teks / Tiket</label>
                    <input type="text" name="q" placeholder="Cari isi aspirasi atau kode tiket..." value="<?= htmlspecialchars($q) ?>">
                </div>
                <div class="filter-group">
                    <label>Kategori</label>
                    <select name="kategori">
                        <option value="Semua">Semua Kategori</option>
                        <option value="Guru" <?= $f_kat === 'Guru' ? 'selected' : '' ?>>Guru</option>
                        <option value="Siswa" <?= $f_kat === 'Siswa' ? 'selected' : '' ?>>Siswa</option>
                        <option value="Warga Sekolah" <?= $f_kat === 'Warga Sekolah' ? 'selected' : '' ?>>Warga Sekolah</option>
                        <option value="Umum" <?= $f_kat === 'Umum' ? 'selected' : '' ?>>Umum</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Angkatan</label>
                    <select name="angkatan">
                        <option value="Semua">Semua Angkatan</option>
                        <option value="A'23" <?= $f_ang === "A'23" ? 'selected' : '' ?>>A'23</option>
                        <option value="A'24" <?= $f_ang === "A'24" ? 'selected' : '' ?>>A'24</option>
                        <option value="A'25" <?= $f_ang === "A'25" ? 'selected' : '' ?>>A'25</option>
                        <option value="A'26" <?= $f_ang === "A'26" ? 'selected' : '' ?>>A'26</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Semua">Semua Status</option>
                        <option value="Baru" <?= $f_status === 'Baru' ? 'selected' : '' ?>>Baru</option>
                        <option value="Dibaca" <?= $f_status === 'Dibaca' ? 'selected' : '' ?>>Dibaca</option>
                        <option value="Ditindaklanjuti" <?= $f_status === 'Ditindaklanjuti' ? 'selected' : '' ?>>Ditindaklanjuti</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Tgl Mulai</label>
                    <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tgl_mulai) ?>">
                </div>
                <div class="filter-group">
                    <label>Tgl Akhir</label>
                    <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>">
                </div>
                <div class="filter-group" style="flex: 0 1 90px;">
                    <label>Baris</label>
                    <select name="limit">
                        <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                    </select>
                </div>
                <div style="display:flex; gap:6px;">
                    <button type="submit" class="btn-primary" style="height:36px;">Filter</button>
                    <a href="dorimutaimu.php" class="page-btn" style="height:36px; line-height:22px;">Reset</a>
                </div>
            </div>
        </form>

        <div style="margin-top:14px; padding-top:12px; border-top:1px dashed #e1e8ed; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div style="font-size:13px; color:#555;">
                Menampilkan <strong><?= $total_filtered ?></strong> data hasil pencarian.
            </div>
            <div style="display:flex; gap:8px;">
                <a href="export_excel.php?format=xls&<?= $filter_qs ?>" class="export-btn" style="margin:0; background-color:#27ae60; padding:6px 14px; font-size:12px;">
                    📥 Ekspor ke Excel (.xls)
                </a>
                <a href="export_excel.php?format=csv&<?= $filter_qs ?>" class="export-btn" style="margin:0; background-color:#2980b9; padding:6px 14px; font-size:12px;">
                    📄 Ekspor ke CSV (UTF-8 BOM)
                </a>
            </div>
        </div>
    </div>

    <!-- 4. FORM AKSI MASSAL & TABEL DATA -->
    <form method="post" action="aksi_pilih.php" onsubmit="return confirm('Jalankan aksi terpilih pada baris yang dicentang?')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="bulk-toolbar">
            <label style="font-size:13px; font-weight:bold; color:#2c3e50;">Aksi Massal:</label>
            <select name="bulk_action" required>
                <option value="">-- Pilih Aksi --</option>
                <option value="status_Baru">Tandai: Baru</option>
                <option value="status_Dibaca">Tandai: Dibaca</option>
                <option value="status_Ditindaklanjuti">Tandai: Ditindaklanjuti</option>
                <option value="hapus">Hapus Data Terpilih</option>
            </select>
            <button type="submit" class="btn-primary">Terapkan</button>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                        <th>ID / Tiket</th>
                        <th>Aspirasi</th>
                        <th>Status</th>
                        <th>Opsi Detail</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px; color:#888;">
                            Tidak ada data aspirasi yang sesuai dengan kriteria filter.
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        $st = $row['status'] ?? 'Baru';
                        $tiket = $row['kode_tiket'] ?? '-';
                        $is_anon = ($row['anonim'] ?? 0) ? true : false;
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="pilih[]" value="<?= (int)$row['id'] ?>" class="row-select">
                        </td>
                        <td>
                            <strong>#<?= $row['id'] ?></strong><br>
                            <span style="font-size:11px; color:#7f8c8d;"><?= htmlspecialchars($tiket) ?></span>
                            <?php if ($is_anon): ?>
                                <span class="badge-anonim">Anonim</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= nl2br(htmlspecialchars($row['pengalaman'])) ?>
                        </td>
                        <td>
                            <span class="badge-status badge-<?= htmlspecialchars($st) ?>">
                                <?= htmlspecialchars($st) ?>
                            </span>
                            <div style="margin-top:6px;">
                                <!-- Form ubah status cepat per baris -->
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="ubah_status">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <select name="status_baru" onchange="this.form.submit()" style="font-size:11px; padding:2px 4px; border-radius:4px;">
                                        <option value="Baru" <?= $st === 'Baru' ? 'selected' : '' ?>>Baru</option>
                                        <option value="Dibaca" <?= $st === 'Dibaca' ? 'selected' : '' ?>>Dibaca</option>
                                        <option value="Ditindaklanjuti" <?= $st === 'Ditindaklanjuti' ? 'selected' : '' ?>>Ditindaklanjuti</option>
                                    </select>
                                </form>
                            </div>
                        </td>
                        <td>
                            <select onchange="toggleContent(<?= $row['id'] ?>, this.value)" style="padding:4px; font-size:12px;">
                                <option value="">Pilih</option>
                                <option value="image">Lihat Gambar</option>
                                <option value="time">Lihat Waktu</option>
                                <option value="makanan">Lihat Yang Isi</option>
                                <option value="makan_perhari">Lihat Angkatan</option>
                            </select>

                            <div id="image-<?= $row['id'] ?>" class="dropdown-content" style="display:none;">
                                <?php if($row['foto']): ?>
                                    <a href="uploads/<?= htmlspecialchars($row['foto']) ?>" target="_blank">
                                        <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" alt="Foto" class="img-thumbnail" />
                                    </a>
                                <?php else: ?>
                                    <span style="color:#888;">Tidak ada gambar</span>
                                <?php endif; ?>
                            </div>

                            <div id="time-<?= $row['id'] ?>" class="dropdown-content" style="display:none;">
                                🕒 <?= htmlspecialchars($row['waktu']) ?>
                            </div>

                            <div id="makanan-<?= $row['id'] ?>" class="dropdown-content" style="display:none;">
                                👤 <?= htmlspecialchars($row['makanan']) ?>
                            </div>

                            <div id="makan_perhari-<?= $row['id'] ?>" class="dropdown-content" style="display:none;">
                                🎓 <?= htmlspecialchars($row['makan_perhari'] ?: '-') ?>
                            </div>
                        </td>
                        <td>
                            <!-- Tombol Hapus: form POST + CSRF -->
                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Yakin ingin menghapus data aspirasi #<?= $row['id'] ?>?')">
                                <input type="hidden" name="action"     value="hapus">
                                <input type="hidden" name="id"         value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button type="submit" class="delete-btn"
                                        style="background:none;border:none;cursor:pointer;font-weight:bold;color:#e74c3c;padding:0;font-size:13px;">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </form>

    <!-- 5. KONTROL PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <div style="font-size:13px; color:#555;">
            Halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong>
        </div>
        <div class="pagination-links">
            <?php if ($page > 1): ?>
                <a href="dorimutaimu.php?page=1&<?= $filter_qs ?>" class="page-btn">&laquo; Awal</a>
                <a href="dorimutaimu.php?page=<?= $page - 1 ?>&<?= $filter_qs ?>" class="page-btn">&lsaquo; Prev</a>
            <?php endif; ?>

            <?php
            $start_p = max(1, $page - 2);
            $end_p   = min($total_pages, $page + 2);
            for ($p = $start_p; $p <= $end_p; $p++):
            ?>
                <a href="dorimutaimu.php?page=<?= $p ?>&<?= $filter_qs ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="dorimutaimu.php?page=<?= $page + 1 ?>&<?= $filter_qs ?>" class="page-btn">Next &rsaquo;</a>
                <a href="dorimutaimu.php?page=<?= $total_pages ?>&<?= $filter_qs ?>" class="page-btn">Akhir &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<footer style="text-align:center; padding:15px; font-size:12px; color:#7f8c8d; margin-top:30px;">
    ASFOUR &copy; 2025 &mdash; v<?= APP_VERSION ?> | Administrator: <strong><?= htmlspecialchars($admin_user) ?></strong>
</footer>

</body>
</html>

<?php
$stmt_data->close();
$conn->close();
?>
