<?php
session_start();
include '../config.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

// ambil daftar kelas agar dropdown ada pada saat load halaman (GET maupun POST)
$kelas_list = mysqli_query($conn, "SELECT id, nama FROM kelas ORDER BY nama");
if (!$kelas_list) {
    $message = "Gagal mengambil daftar kelas: " . mysqli_error($conn);
}

if (isset($_POST['submit'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $gambar = null;

    // Folder upload (pastikan sudah dibuat)
    $targetDir = "../images/";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Jika ada file gambar di-upload
    if (!empty($_FILES['gambar']['name'])) {
        $fileName = basename($_FILES['gambar']['name']);
        $targetFile = $targetDir . $fileName;
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validasi jenis file
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $allowed)) {
            $message = "Format gambar tidak valid. Gunakan JPG, PNG, atau GIF.";
        } 
        // Validasi ukuran (maks 2MB)
        else if ($_FILES['gambar']['size'] > 2000000) {
            $message = "Ukuran gambar terlalu besar. Maksimal 2MB.";
        } 
        // simpan file—gunakan uniqid agar nama unik:
        else {
            // Upload file
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFile)) {
                $gambar = $fileName;
            } else {
                $message = "Gagal meng-upload gambar.";
            }
        }
    }

    // di form handler setelah validasi file:
    $kelas = isset($_POST['kelas']) && $_POST['kelas'] !== '' ? intval($_POST['kelas']) : null;
    $kelas_sql = is_null($kelas) ? "NULL" : $kelas;

    if ($message == "") {
        // Simpan NULL jika tidak ada gambar
        $gambar_sql = $gambar ? "'$gambar'" : "NULL";

        // Cek nama kolom kelas yang tersedia di tabel mata_pelajaran
        $candidates = ['kelas', 'kelas_id', 'id_kelas', 'kelasId', 'kelasID'];
        $kolom_kelas = null;
        $res_cols = mysqli_query($conn, "SHOW COLUMNS FROM mata_pelajaran");
        if ($res_cols) {
            while ($col = mysqli_fetch_assoc($res_cols)) {
                if (in_array($col['Field'], $candidates)) {
                    $kolom_kelas = $col['Field'];
                    break;
                }
            }
        }

        // Susun bagian kolom dan values secara dinamis
        $columns = "nama, deskripsi, gambar";
        $values = "'$nama', '$deskripsi', $gambar_sql";

        if ($kolom_kelas) {
            // jika kolom kelas ditemukan, sertakan nilainya (bisa NULL)
            $columns .= ", $kolom_kelas";
            $values .= ", $kelas_sql";
        }

        $query = "INSERT INTO mata_pelajaran ($columns) VALUES ($values)";
        if (mysqli_query($conn, $query)) {
            $message = "Mata pelajaran berhasil ditambahkan!";
        } else {
            $message = "Gagal menambahkan mata pelajaran: " . mysqli_error($conn);
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
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #2d3748; }

    .container { display: flex; min-height: 100vh; }

    /* SIDEBAR */
    .sidebar {
        width: 260px;
        background: #fff;
        border-right: 1px solid #e2e8f0;
        padding: 2rem 0;
        position: relative;
        transition: transform 0.3s;
    }
    .sidebar.closed { transform: translateX(-260px); }

    .toggle-btn {
        position: absolute;
        right: -40px;
        top: 20px;
        width: 40px;
        height: 40px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-left: none;
        border-radius: 0 8px 8px 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-header { padding: 0 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; }
    .sidebar-header h3 { font-size: 1.25rem; margin-bottom: 0.5rem; }

    .menu { list-style: none; padding: 1.5rem 0; }
    .menu a {
        display: block;
        padding: 0.75rem 1.5rem;
        color: #4a5568;
        text-decoration: none;
        transition: all 0.2s;
    }
    .menu a:hover, .menu a.active {
        background: #edf2f7;
        color: #2b6cb0;
        border-left: 3px solid #3182ce;
    }

    /* MAIN CONTENT */
    .main {
        flex: 1;
        padding: 2rem;
        transition: margin-left 0.3s;
    }
    .main.expanded { margin-left: -260px; }

    .form-card {
        background: #fff;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        max-width: 800px;
    }

    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    .form-control:focus { border-color: #3182ce; }

    .btn-primary {
        width: 100%;
        padding: 0.875rem;
        background: #3182ce;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }

    /* PREVIEW GAMBAR */
    #preview {
        display: none;
        margin-top: 10px;
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
    .alert-success { background: #c6f6d5; color: #22543d; }
    .alert-error { background: #fed7d7; color: #c53030; }
</style>
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

        <div class="sidebar-header">
            <h3>Examify</h3>
            <p>Admin: <?= $_SESSION['nama_lengkap'] ?? '' ?></p>
        </div>

        <ul class="menu">
            <li><a href="dashboard_admin.php">Dashboard</a></li>
            <li><a href="data_siswa.php">Data Siswa</a></li>
            <li><a href="daftar_ujian.php">Daftar Ujian</a></li>
            <li><a class="active" href="tambah_mata_pelajaran.php">Tambah Mata Pelajaran</a></li>
            <li><a href="tambah_soal.php">Tambah Soal</a></li>
            <li><a href="../siswa/profile.php">Profile</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </aside>

    <!-- MAIN -->
    <main class="main" id="main">
        <h1>Tambah Mata Pelajaran</h1>
        <p>Tambahkan mata pelajaran baru untuk ujian</p>

        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-error' ?>">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Nama Mata Pelajaran</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Deskripsi (opsional)</label>
                    <textarea name="deskripsi" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label>Upload Gambar (opsional)</label>
                    <input type="file" name="gambar" accept="image/*" class="form-control" onchange="previewImage(event)">
                    <img id="preview">
                </div>

                <div class="form-group">
                    <label>Kelas (opsional)</label>
                    <select name="kelas" class="form-control">
                        <option value="">-- Semua Kelas --</option>
                        <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                            <option value="<?= intval($k['id']); ?>"><?= htmlspecialchars($k['nama']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn-primary">Simpan</button>

            </form>

        </div>
    </main>

</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle('closed');
    document.getElementById("main").classList.toggle('expanded');
}

function previewImage(event) {
    const img = document.getElementById('preview');
    img.src = URL.createObjectURL(event.target.files[0]);
    img.style.display = 'block';
}
</script>

</body>
</html>