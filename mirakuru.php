<?php
// mirakuru.php — ASFOUR v1.1.0 Logout Handler
session_start();

if (isset($_SESSION['admin'])) {
    $conn = @new mysqli("localhost", "root", "", "asfourmy_asfour");
    if ($conn && !$conn->connect_error) {
        $admin_user = $_SESSION['admin'];
        $stmt = $conn->prepare("INSERT INTO log_aktivitas (username, aktivitas, waktu) VALUES (?, 'Logout admin', NOW())");
        if ($stmt) {
            $stmt->bind_param("s", $admin_user);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
}

session_destroy();
header("Location: futaridakeno.php");
exit;
?>