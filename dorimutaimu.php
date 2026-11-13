<?php
define('APP_VERSION', '1.0.1');

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: futaridakeno.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- [SEC] Generate CSRF token sekali per sesi ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- [SEC] Proses hapus via POST + validasi CSRF + prepared statement ---
// Menggantikan GET ?delete={id} yang rentan CSRF dan SQL injection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    $token_in  = $_POST['csrf_token'] ?? '';
    $id_in     = $_POST['id']         ?? '';

    // Validasi token
    if (!hash_equals($csrf_token, $token_in)) {
        die("❌ Permintaan tidak valid (CSRF).");
    }
    // Validasi id harus integer positif
    $id_int = filter_var($id_in, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_int === false) {
        die("❌ ID tidak valid.");
    }

    $stmt = $conn->prepare("DELETE FROM hasil_survei WHERE id = ?");
    $stmt->bind_param("i", $id_int);
    $stmt->execute();
    $stmt->close();

    header("Location: dorimutaimu.php");
    exit;
}

// Ambil semua data hasil survei
$result = $conn->query("SELECT * FROM hasil_survei");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
      <link rel="icon" type="image/jpeg" href="images/Tsumugi.jpeg">
    <title>Admin ASFOUR 2025</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .dropdown-content {
            margin-top: 5px;
        }
        .img-thumbnail {
            max-width: 100px;
            border-radius: 6px;
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
    </script>
</head>
<body>
<header>
    <div class="header-container">
        <h1>Admin ASFOUR 2025</h1>
        <a href="mirakuru.php" class="logout-btn">Logout</a>
    </div>
</header>

<div class="container">
    <h2>Data Hasil ASFOUR <?php
date_default_timezone_set('Asia/Jakarta');
echo date('Y-m-d H:i:s');
?></h2>
    <a href="export_excel.php" class="export-btn" style="display:inline-block;margin-bottom:10px;padding:8px 16px;background-color:#4CAF50;color:white;text-decoration:none;border-radius:4px;">Export ke Excel</a>


    <div class="table-responsive">
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Aspirasi</th>
                    <th>Opsi</th>
                    <th>Hapus</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= nl2br(htmlspecialchars($row['pengalaman'])) ?></td>
                    <td>
                        <select onchange="toggleContent(<?= $row['id'] ?>, this.value)">
                            <option value="">Pilih</option>
                            <option value="image">Lihat Gambar</option>
                            <option value="time">Lihat Waktu</option>
                            <option value="makanan">Lihat Yang Isi</option>
                            <option value="makan_perhari">Lihat Angkatan</option>
                        </select>

                        <div id="image-<?= $row['id'] ?>" class="dropdown-content" style="display:none;">
                            <?php if($row['foto']): ?>
                                <img src="uploads/<?= $row['foto'] ?>" alt="Foto" class="img-thumbnail" />
                            <?php else: ?>
                                Tidak ada gambar
                            <?php endif; ?>
                        </div>

                        <div id="time-<?= $row['id'] ?>" class="dropdown-content" style="display:none;">
                            <?= htmlspecialchars($row['waktu']) ?>
                        </div>

                        <div id="makanan-<?= $row['id'] ?>" class="dropdown-content" style="display:none;">
                            <?= htmlspecialchars($row['makanan']) ?>
                        </div>

                        <div id="makan_perhari-<?= $row['id'] ?>" class="dropdown-content" style="display:none;">
                            <?= htmlspecialchars($row['makan_perhari']) ?>
                        </div>
                    </td>
                    <td>
                        <?php
                        // --- [SEC] Tombol Hapus: form POST + CSRF, bukan link GET ---
                        ?>
                        <form method="post" style="display:inline;"
                              onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                            <input type="hidden" name="action"     value="hapus">
                            <input type="hidden" name="id"         value="<?= (int)$row['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="delete-btn"
                                    style="background:none;border:none;cursor:pointer;font-weight:bold;color:#e74c3c;padding:0;">
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<footer style="text-align:center; padding:12px; font-size:11px; color:#aaa; margin-top:20px;">
    ASFOUR &copy; 2025 &mdash; v<?php echo APP_VERSION; ?>
</footer>

</body>
</html>

<?php
$conn->close();
?>
