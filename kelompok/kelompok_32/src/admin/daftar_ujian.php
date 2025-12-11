<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_ujian'])) {
    $ujian_id = intval($_POST['ujian_id']);
    

    $soal_query = mysqli_query($conn, "SELECT id FROM soal WHERE ujian_id = $ujian_id");
    $soal_ids = [];
    while ($row = mysqli_fetch_assoc($soal_query)) {
        $soal_ids[] = $row['id'];
    }

    if (!empty($soal_ids)) {
        $soal_ids_str = implode(',', $soal_ids);
        mysqli_query($conn, "DELETE FROM detail_jawaban WHERE soal_id IN ($soal_ids_str)");
    }
    
    $riwayat_query = mysqli_query($conn, "SELECT id FROM riwayat_ujian WHERE ujian_id = $ujian_id");
    $riwayat_ids = [];
    while ($row = mysqli_fetch_assoc($riwayat_query)) {
        $riwayat_ids[] = $row['id'];
    }
    
    if (!empty($riwayat_ids)) {
        $riwayat_ids_str = implode(',', $riwayat_ids);
        mysqli_query($conn, "DELETE FROM detail_jawaban WHERE riwayat_id IN ($riwayat_ids_str)");
    }
    
    mysqli_query($conn, "DELETE FROM riwayat_ujian WHERE ujian_id = $ujian_id");
    

    mysqli_query($conn, "DELETE FROM soal WHERE ujian_id = $ujian_id");
    

    if (mysqli_query($conn, "DELETE FROM ujian WHERE id = $ujian_id")) {
        $_SESSION['success'] = "Ujian berhasil dihapus beserta semua data terkait!";
    } else {
        $_SESSION['error'] = "Gagal menghapus ujian: " . mysqli_error($conn);
    }
    
    header("Location: daftar_ujian.php");
    exit();
}


$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;


$total_ujian_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM ujian");
$total_ujian = mysqli_fetch_assoc($total_ujian_query)['total'];
$total_pages = ceil($total_ujian / $per_page);


$result_ujian = mysqli_query($conn, "
    SELECT u.*, mp.nama as mata_pelajaran,
    (SELECT COUNT(*) FROM soal WHERE ujian_id = u.id) as total_soal_dibuat
    FROM ujian u 
    JOIN mata_pelajaran mp ON u.mata_pelajaran_id = mp.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Ujian - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #2d3748; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #fff; border-right: 1px solid #e2e8f0; padding: 2rem 0; transition: transform 0.3s; position: relative; }
        .sidebar.closed { transform: translateX(-260px); }
        .toggle-btn { position: absolute; right: -40px; top: 20px; width: 40px; height: 40px; background: #fff; border: 1px solid #e2e8f0; border-left: none; border-radius: 0 8px 8px 0; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .sidebar-header { padding: 0 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; }
        .sidebar-header h3 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .sidebar-header p { font-size: 0.875rem; color: #718096; }
        .menu { list-style: none; padding: 1.5rem 0; }
        .menu a { display: block; padding: 0.75rem 1.5rem; color: #4a5568; text-decoration: none; transition: all 0.2s; }
        .menu a:hover, .menu a.active { background: #edf2f7; color: #2b6cb0; border-left: 3px solid #3182ce; }
        .main { flex: 1; padding: 2rem; transition: margin-left 0.3s; }
        .main.expanded { margin-left: -260px; }
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 1.875rem; font-weight: 600; margin-bottom: 0.5rem; }
        .header p { color: #718096; font-size: 0.875rem; }
        .table-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f7fafc; }
        th { padding: 1rem; text-align: left; font-size: 0.875rem; font-weight: 600; color: #4a5568; }
        td { padding: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.875rem; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-warning { background: #feebc8; color: #7c2d12; }
        .badge-info { background: #bee3f8; color: #2c5aa0; }
        .btn-edit { padding: 0.5rem 0.75rem; background: #3182ce; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; transition: background 0.2s; margin-right: 0.5rem; }
        .btn-edit:hover { background: #2c5aa0; }
        .btn-delete { padding: 0.5rem 0.75rem; background: #fc8181; color: #fff; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-delete:hover { background: #f56565; }
        .action-btns { display: flex; gap: 0.5rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #c6f6d5; color: #22543d; }
        .alert-error { background: #fed7d7; color: #c53030; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; }
        .page-btn { width: 40px; height: 40px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; color: #4a5568; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .page-btn:hover { border-color: #3182ce; color: #3182ce; }
        .page-btn.active { background: #3182ce; border-color: #3182ce; color: #fff; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .stats-bar { display: flex; gap: 0.5rem; align-items: center; }
        .stat-number { font-weight: 700; color: #2d3748; }
        .stat-label { color: #718096; font-size: 0.75rem; }
        .progress-bar { width: 80px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin-top: 0.25rem; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #48bb78, #3182ce); transition: width 0.3s; }
        
        @media (max-width: 768px) {
            .sidebar { position: fixed; z-index: 100; height: 100vh; }
            .main { padding: 1.5rem; }
            .main.expanded { margin-left: 0; }
            table { font-size: 0.75rem; }
            th, td { padding: 0.75rem 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            <div class="sidebar-header">
                <h3>examify</h3>
                <p>Admin: <?php echo $_SESSION['nama_lengkap']; ?></p>
            </div>
            <ul class="menu">
                <li><a href="dashboard_admin.php">Dashboard</a></li>
                <li><a href="data_siswa.php">Data Siswa</a></li>
                <li><a href="daftar_ujian.php" class="active">Daftar Ujian</a></li>
                <li><a href="tambah_mata_pelajaran.php">Tambah Mata Pelajaran</a></li>
                <li><a href="tambah_soal.php">Tambah Soal</a></li>
                <li><a href="../siswa/profile.php">Profile</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </aside>
        
        <main class="main" id="main">
            <div class="header">
                <h1>Daftar Ujian</h1>
                <p>Kelola dan edit ujian yang sudah dibuat</p>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    ✓ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    ✗ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Ujian</th>
                            <th>Mata Pelajaran</th>
                            <th>Soal</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        while ($ujian = mysqli_fetch_assoc($result_ujian)): 
                            $progress = ($ujian['total_soal_dibuat'] / $ujian['jumlah_soal']) * 100;
                            $status = $ujian['total_soal_dibuat'] >= $ujian['jumlah_soal'] ? 'complete' : 'incomplete';
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <strong><?php echo $ujian['judul']; ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $ujian['mata_pelajaran']; ?></span>
                            </td>
                            <td>
                                <div class="stats-bar">
                                    <span class="stat-number"><?php echo $ujian['total_soal_dibuat']; ?></span>
                                    <span class="stat-label">/ <?php echo $ujian['jumlah_soal']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, $progress); ?>%"></div>
                                </div>
                            </td>
                            <td><?php echo $ujian['waktu_pengerjaan']; ?> menit</td>
                            <td>
                                <?php if ($status == 'complete'): ?>
                                    <span class="badge badge-success">✓ Lengkap</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">⚠ Perlu Soal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit_soal.php?ujian_id=<?php echo $ujian['id']; ?>" class="btn-edit">
                                        ✏️ Edit
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('⚠️ PERINGATAN!\n\nMenghapus ujian ini akan menghapus:\n• Semua soal dalam ujian\n• Semua riwayat ujian siswa\n• Semua jawaban siswa\n\nApakah Anda yakin ingin menghapus ujian \'<?php echo addslashes($ujian['judul']); ?>\'?')">
                                        <input type="hidden" name="ujian_id" value="<?php echo $ujian['id']; ?>">
                                        <button type="submit" name="delete_ujian" class="btn-delete">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <button class="page-btn" onclick="changePage(<?php echo $page - 1; ?>)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>←</button>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <button class="page-btn <?php echo $i == $page ? 'active' : ''; ?>" onclick="changePage(<?php echo $i; ?>)">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                
                <button class="page-btn" onclick="changePage(<?php echo $page + 1; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>→</button>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('closed');
            document.getElementById('main').classList.toggle('expanded');
        }
        
        function changePage(page) {
            window.location.href = 'daftar_ujian.php?page=' + page;
        }
    </script>
</body>
</html>