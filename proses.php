<?php
// proses.php — ASFOUR v1.0.1
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

// --- [SEC] 1. Validasi CSRF Token ---
$token_in = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_in)) {
    die("❌ Token keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.");
}

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- [SEC] 2. Whitelist & Sanitasi Kategori (makanan) ---
$allowed_kategori = ['Guru', 'Warga Sekolah', 'Siswa', 'Umum'];
$kategori_raw = $_POST['makanan'] ?? '';
if (is_array($kategori_raw)) {
    $kategori_raw = reset($kategori_raw);
}
$kategori = trim($kategori_raw);

if (!in_array($kategori, $allowed_kategori, true)) {
    die("❌ Kategori pengisi tidak valid.");
}

// --- [SEC] 3. Whitelist & Validasi Angkatan (makan_perhari) ---
$allowed_angkatan = ["A'23", "A'24", "A'25"];
$angkatan = '';
if ($kategori === 'Siswa') {
    $angkatan_in = trim($_POST['makan_perhari'] ?? '');
    if (!in_array($angkatan_in, $allowed_angkatan, true)) {
        die("❌ Pilihan angkatan tidak valid untuk kategori Siswa.");
    }
    $angkatan = $angkatan_in;
}

// --- [SEC] 4. Sanitasi Aspirasi (pengalaman) ---
$pengalaman = trim($_POST['pengalaman'] ?? '');
if ($pengalaman === '') {
    die("❌ Isi aspirasi tidak boleh kosong.");
}
if (mb_strlen($pengalaman) > 5000) {
    die("❌ Teks aspirasi melebihi batas maksimum 5000 karakter.");
}

// --- [SEC] 5. Validasi Upload Foto (Ekstensi + finfo MIME Asli + Ukuran Maks 5MB) ---
$foto = '';
if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        die("❌ Terjadi kesalahan saat mengunggah file (Kode: " . (int)$_FILES['foto']['error'] . ").");
    }

    // Batas ukuran 5 MB (5 * 1024 * 1024 bytes)
    $max_size = 5 * 1024 * 1024;
    if ($_FILES['foto']['size'] > $max_size) {
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
        die("❌ Ekstensi file tidak didukung! Hanya JPG, JPEG, PNG, dan GIF.");
    }

    // Pemeriksaan MIME type asli menggunakan finfo (bukan dari header client)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
    finfo_close($finfo);

    if ($real_mime !== $allowed_mimes[$ext]) {
        die("❌ Isi berkas tidak valid atau tidak sesuai dengan format gambar yang diizinkan.");
    }

    // Validasi tambahan: verifikasi struktur gambar
    if (@getimagesize($_FILES['foto']['tmp_name']) === false) {
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
        die("❌ Gagal menyimpan file lampiran.");
    }
}

// Simpan data ke database via Prepared Statement
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$sql = "INSERT INTO hasil_survei (makanan, makan_perhari, pengalaman, foto, ip_address) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $kategori, $angkatan, $pengalaman, $foto, $ip_address);

if ($stmt->execute()) {
    // Regenerasi CSRF token setelah sukses submit
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: terimakasih.html");
    exit();
} else {
    echo "Gagal menyimpan data: " . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>
