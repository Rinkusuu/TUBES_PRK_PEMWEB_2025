<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

if (isset($_POST['submit'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');

    if (empty($nama)) {
        $message = "Nama mata pelajaran wajib diisi.";
    } else {
        $query = "INSERT INTO mata_pelajaran (nama, deskripsi) VALUES ('$nama', '$deskripsi')";
        if (mysqli_query($conn, $query)) {
            $message = "Mata pelajaran berhasil ditambahkan.";
        } else {
            $message = "Gagal: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Mata Pelajaran</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        input, textarea, button { padding: 10px; width: 300px; }
        .message { padding: 10px; margin-bottom: 15px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Tambah Mata Pelajaran</h1>
    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'berhasil') !== false ? 'success' : 'error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Nama Mata Pelajaran *</label><br>
            <input type="text" name="nama" required>
        </div>
        <div class="form-group">
            <label>Deskripsi (opsional)</label><br>
            <textarea name="deskripsi"></textarea>
        </div>
        <button type="submit" name="submit">Simpan</button>
    </form>
    <br>
    <a href="dashboard_admin.php">Kembali ke Dashboard</a>
</body>
</html>