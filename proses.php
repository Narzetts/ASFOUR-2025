<?php
// proses.php — ASFOUR v1.1.0
session_start();

// --- [SEC] Endpoint penyedia CSRF token untuk halaman publik (index.html) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['csrf'])) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);
    exit();
}

// Hanya proses jika request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.html");
    exit();
}

// --- [SEC] 1. Anti-Spam: Cek Honeypot (harus kosong) ---
if (!empty($_POST['website_hp'])) {
    // Jika bot mengisi kolom jebakan, batalkan diam-diam
    die("❌ Permintaan tidak valid.");
}

// --- [SEC] 2. Validasi CSRF Token ---
$token_in = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_in)) {
    die("❌ Token keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.");
}

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- [SEC] 3. Anti-Spam: Rate Limiting per IP (Maks 3 kiriman / jam) ---
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$stmt_rate = $conn->prepare("SELECT COUNT(*) FROM hasil_survei WHERE ip_address = ? AND waktu >= NOW() - INTERVAL 1 HOUR");
if ($stmt_rate) {
    $stmt_rate->bind_param("s", $ip_address);
    $stmt_rate->execute();
    $stmt_rate->bind_result($recent_count);
    $stmt_rate->fetch();
    $stmt_rate->close();

    if ($recent_count >= 3) {
        $conn->close();
        die("❌ Anda telah mencapai batas pengiriman aspirasi (maksimal 3 aspirasi per jam). Silakan coba lagi nanti.");
    }
}

// --- [SEC] 4. Whitelist & Sanitasi Kategori (makanan) ---
$allowed_kategori = ['Guru', 'Warga Sekolah', 'Siswa', 'Umum'];
$kategori_raw = $_POST['makanan'] ?? '';
if (is_array($kategori_raw)) {
    $kategori_raw = reset($kategori_raw);
}
$kategori = trim($kategori_raw);

if (!in_array($kategori, $allowed_kategori, true)) {
    $conn->close();
    die("❌ Kategori pengisi tidak valid.");
}

// --- [SEC] 5. Whitelist & Validasi Angkatan Dinamis (termasuk A'26) ---
$allowed_angkatan = ["A'23", "A'24", "A'25", "A'26"];
$angkatan = '';
if ($kategori === 'Siswa') {
    $angkatan_in = trim($_POST['makan_perhari'] ?? '');
    if (!in_array($angkatan_in, $allowed_angkatan, true)) {
        $conn->close();
        die("❌ Pilihan angkatan tidak valid untuk kategori Siswa.");
    }
    $angkatan = $angkatan_in;
}

// --- [SEC] 6. Validasi Panjang Aspirasi (Min 10, Maks 1000 karakter) ---
$pengalaman = trim($_POST['pengalaman'] ?? '');
$len = mb_strlen($pengalaman);
if ($len < 10) {
    $conn->close();
    die("❌ Aspirasi terlalu singkat! Minimal 10 karakter.");
}
if ($len > 1000) {
    $conn->close();
    die("❌ Teks aspirasi melebihi batas maksimum 1000 karakter.");
}

// --- [SEC] 7. Opsi Kirim Anonim ---
$anonim = (isset($_POST['anonim']) && $_POST['anonim'] === '1') ? 1 : 0;

// --- [SEC] 8. Buat Kode Tiket Unik (Format: ASF-XXXXXX) ---
$kode_tiket = 'ASF-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

// --- [SEC] 9. Validasi Upload Foto (Ekstensi + finfo MIME Asli + Ukuran Maks 5MB) ---
$foto = '';
if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $conn->close();
        die("❌ Terjadi kesalahan saat mengunggah file (Kode: " . (int)$_FILES['foto']['error'] . ").");
    }

    // Batas ukuran 5 MB (5 * 1024 * 1024 bytes)
    $max_size = 5 * 1024 * 1024;
    if ($_FILES['foto']['size'] > $max_size) {
        $conn->close();
        die("❌ Ukuran file terlalu besar! Maksimal 5 MB.");
    }

    // Whitelist ekstensi dan pemetaan MIME yang diizinkan
    $allowed_mimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif'
    ];

    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($ext, $allowed_mimes)) {
        $conn->close();
        die("❌ Ekstensi file tidak didukung! Hanya JPG, JPEG, PNG, dan GIF.");
    }

    // Pemeriksaan MIME type asli menggunakan finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
    finfo_close($finfo);

    if ($real_mime !== $allowed_mimes[$ext]) {
        $conn->close();
        die("❌ Isi berkas tidak valid atau tidak sesuai dengan format gambar yang diizinkan.");
    }

    // Validasi tambahan: verifikasi struktur gambar
    if (@getimagesize($_FILES['foto']['tmp_name']) === false) {
        $conn->close();
        die("❌ Berkas bukan gambar valid.");
    }

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Buat nama acak unik dan aman
    $foto = uniqid("img_", true) . '.' . $ext;
    $target_file = $target_dir . $foto;

    if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
        $conn->close();
        die("❌ Gagal menyimpan file lampiran.");
    }
}

// --- [SEC] 10. Simpan data ke database via Prepared Statement ---
$status_awal = 'Baru';
$sql = "INSERT INTO hasil_survei (makanan, makan_perhari, pengalaman, foto, status, kode_tiket, anonim, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssis", $kategori, $angkatan, $pengalaman, $foto, $status_awal, $kode_tiket, $anonim, $ip_address);

if ($stmt->execute()) {
    // Regenerasi CSRF token setelah sukses submit
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: terimakasih.html?tiket=" . urlencode($kode_tiket));
    exit();
} else {
    echo "Gagal menyimpan data: " . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>
