<?php
// futaridakeno.php — ASFOUR v1.0.1
session_start();

// --- [SEC] Jika sudah login, langsung ke dashboard ---
if (isset($_SESSION['admin'])) {
    header("Location: dorimutaimu.php");
    exit;
}

// --- [SEC] Rate limiting berbasis sesi/IP — tanpa tabel DB baru ---
// Simpan counter di session: ['login_fails' => int, 'lock_until' => timestamp]
$ip        = $_SERVER['REMOTE_ADDR'];
$lock_key  = 'login_lock_' . md5($ip);   // kunci per-IP di session
$fail_key  = 'login_fails_' . md5($ip);

$locked_until = $_SESSION[$lock_key] ?? 0;
$fail_count   = $_SESSION[$fail_key] ?? 0;

$is_locked = (time() < $locked_until);
$message   = "";

$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- [SEC] Tolak jika sedang dikunci ---
    if ($is_locked) {
        $sisa = ceil(($locked_until - time()) / 60);
        $message = "⏳ Terlalu banyak percobaan. Coba lagi dalam {$sisa} menit.";
    } else {
        $username = trim($_POST["username"] ?? '');
        $password = $_POST["password"]      ?? '';

        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        // --- [SEC] Pesan error generik — tidak membocorkan username/password ---
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION[$fail_key] = $fail_count + 1;

            // Kunci 10 menit setelah 5 kali gagal
            if ($_SESSION[$fail_key] >= 5) {
                $_SESSION[$lock_key] = time() + 600; // 10 menit
                $_SESSION[$fail_key] = 0;
                $message = "⏳ Terlalu banyak percobaan. Coba lagi dalam 10 menit.";
            } else {
                $sisa_coba = 5 - $_SESSION[$fail_key];
                $message   = "❌ Username atau password salah. Sisa percobaan: {$sisa_coba}.";
            }
        } else {
            // --- [SEC] Reset counter, regenerate session ID, lalu redirect ---
            $_SESSION[$fail_key] = 0;
            unset($_SESSION[$lock_key]);
            session_regenerate_id(true);            // cegah session fixation
            $_SESSION["admin"] = $user["username"];
            $conn->close();
            header("Location: dorimutaimu.php");
            exit;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #2980b9, #8e44ad);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            animation: backgroundAnimation 5s infinite alternate;
        }
        
        @keyframes backgroundAnimation {
            0% {
                background: linear-gradient(135deg, #2980b9, #8e44ad);
            }
            100% {
                background: linear-gradient(135deg, #16a085, #f39c12);
            }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 380px;
            width: 100%;
            animation: scaleUp 0.5s ease-in-out;
        }

        @keyframes scaleUp {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            margin-bottom: 15px;
            transition: border 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #2980b9;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: #2980b9;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #3498db;
        }

        .error {
            color: red;
            margin-top: 10px;
            text-align: center;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #aaa;
            margin-top: 25px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login Admin</h2>
    <?php if ($message): ?>
        <div class="error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!$is_locked): ?>
    <form method="post">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Masuk</button>
    </form>
    <?php endif; ?>
    <div class="footer">© 2025 ASFOUR. All Rights Reserved.</div>
</div>

</body>
</html>
