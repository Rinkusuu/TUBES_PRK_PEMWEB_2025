<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    // redirect siswa ke halaman daftar ujian atau dashboard siswa
    header("Location: daftar_ujian.php");
    exit();
}


$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'siswa') as total_siswa,
    (SELECT COUNT(*) FROM ujian) as total_ujian,
    (SELECT COUNT(*) FROM riwayat_ujian) as total_riwayat,
    (SELECT COUNT(*) FROM soal) as total_soal"));

$query_ujian = "SELECT u.*, u.kelas, COALESCE(k.nama, u.kelas) as kelas_nama, mp.nama as mata_pelajaran,
    COUNT(DISTINCT ru.user_id) AS total_peserta,
    IFNULL(AVG(ru.skor), 0) as rata_rata,
    IFNULL(MAX(ru.skor), 0) as skor_tertinggi,
    IFNULL(MIN(ru.skor), 0) as skor_terendah
    FROM ujian u
    JOIN mata_pelajaran mp ON u.mata_pelajaran_id = mp.id
    LEFT JOIN kelas k ON u.kelas = k.id
    LEFT JOIN riwayat_ujian ru ON u.id = ru.ujian_id
    GROUP BY u.id
    ORDER BY u.created_at DESC";
$result_ujian = mysqli_query($conn, $query_ujian);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
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
        .header h1 { font-size: 1.875rem; font-weight: 600; margin-bottom: 0.5rem; }
        .header p { color: #718096; font-size: 0.875rem; margin-bottom: 2rem; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; padding: 1.25rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-label { font-size: 0.875rem; color: #718096; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.875rem; font-weight: 700; color: #3182ce; }
        .stat-desc { font-size: 0.75rem; color: #a0aec0; margin-top: 0.25rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-header h2 { font-size: 1.5rem; }
        .search-box { position: relative; flex: 1; max-width: 400px; }
        .search-box input { width: 100%; padding: 0.625rem 0.625rem 0.625rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; }
        .search-box::before { content: "🔍"; position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); }
        .table-wrapper { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f7fafc; }
        th { padding: 1rem; text-align: left; font-size: 0.875rem; font-weight: 600; color: #4a5568; border-bottom: 1px solid #e2e8f0; }
        td { padding: 1rem; border-bottom: 1px solid #f7fafc; font-size: 0.875rem; }
        tbody tr:hover { background: #f7fafc; }
        .btn-detail { padding: 0.5rem 1rem; background: #3182ce; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; transition: background 0.2s; display: inline-block; }
        .btn-detail:hover { background: #2c5aa0; }
        .stat-inline { display: flex; gap: 1.5rem; font-size: 0.875rem; }
        .stat-item { display: flex; flex-direction: column; }
        .stat-item-label { color: #718096; font-size: 0.75rem; }
        .stat-item-value { font-weight: 600; color: #2d3748; }
        
        @media (max-width: 768px) {
            .sidebar { position: fixed; z-index: 100; height: 100vh; }
            .main { padding: 1.5rem; }
            .main.expanded { margin-left: 0; }
            .stats { grid-template-columns: 1fr 1fr; }
            .section-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            <div class="sidebar-header">
                <h3>Ujian Online</h3>
                <p>Admin: <?php echo $_SESSION['nama_lengkap']; ?></p>
            </div>
            <ul class="menu">
                <li><a href="dashboard_admin.php" class="active">Dashboard</a></li>
                <li><a href="data_siswa.php">Data Siswa</a></li>
                <li><a href="daftar_ujian.php">Daftar Ujian</a></li>
                <li><a href="tambah_mata_pelajaran.php">Tambah Mata Pelajaran</a></li>
                <li><a href="tambah_soal.php">Tambah Soal</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>
        
        <main class="main" id="main">
            <div class="header">
                <h1>Dashboard Admin</h1>
                <p>Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>!</p>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">Total Siswa</div>
                    <div class="stat-value"><?php echo $stats['total_siswa']; ?></div>
                    <div class="stat-desc">Terdaftar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Ujian</div>
                    <div class="stat-value"><?php echo $stats['total_ujian']; ?></div>
                    <div class="stat-desc">Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Pengerjaan</div>
                    <div class="stat-value"><?php echo $stats['total_riwayat']; ?></div>
                    <div class="stat-desc">Selesai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Soal</div>
                    <div class="stat-value"><?php echo $stats['total_soal']; ?></div>
                    <div class="stat-desc">Tersedia</div>
                </div>
            </div>
            
            <div class="section-header">
                <h2>Daftar Ujian</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari ujian...">
                </div>
            </div>
            
            <div class="table-wrapper">
                <table id="ujianTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mata Pelajaran</th>
                            <th>Judul Ujian</th>
                            <th>Soal/Waktu</th>
                            <th>Peserta</th>
                            <th>Statistik Nilai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($ujian = mysqli_fetch_assoc($result_ujian)): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo $ujian['mata_pelajaran']; ?></strong></td>
                            <td><?php echo $ujian['judul']; ?></td>
                            <td><?php echo $ujian['jumlah_soal']; ?> soal / <?php echo $ujian['waktu_pengerjaan']; ?> menit</td>
                            <td><strong><?php echo $ujian['total_peserta']; ?></strong> siswa</td>
                            <td>
                                <div class="stat-inline">
                                    <div class="stat-item">
                                        <span class="stat-item-label">Tertinggi</span>
                                        <span class="stat-item-value"><?php echo $ujian['skor_tertinggi']; ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-item-label">Rata-rata</span>
                                        <span class="stat-item-value"><?php echo number_format($ujian['rata_rata'], 1); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-item-label">Terendah</span>
                                        <span class="stat-item-value"><?php echo $ujian['skor_terendah']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="detail_ujian_admin.php?id=<?php echo $ujian['id']; ?>" class="btn-detail">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            sidebar.classList.toggle('closed');
            main.classList.toggle('expanded');
        }

        searchInput.oninput = function() {
            const term = this.value.toLowerCase();
            [...document.querySelectorAll('#ujianTable tbody tr')].forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        };
    </script>
</body>
</html>