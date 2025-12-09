<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f0f0f0;
        }
        .header {
            background: #007bff;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .logout {
            text-align: right;
        }
        .logout a {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard Siswa</h1>
        <p>Selamat datang, <?php echo $_SESSION['nama_lengkap'] ?? 'Siswa'; ?></p>
    </div>
    
    <div class="content">
        <h2>Halaman Dashboard</h2>
        <p>Ini adalah halaman dashboard siswa.</p>
        <p>Fitur akan ditambahkan kemudian.</p>
    </div>
    
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>