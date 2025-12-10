<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$user_kelas = isset($_SESSION['kelas']) ? intval($_SESSION['kelas']) : null;

$query = "SELECT ru.*, u.judul, u.jumlah_soal, u.kelas as kelas_id, COALESCE(k.nama, u.kelas) as kelas_nama, mp.nama as mata_pelajaran 
          FROM riwayat_ujian ru 
          JOIN ujian u ON ru.ujian_id = u.id 
          JOIN mata_pelajaran mp ON u.mata_pelajaran_id = mp.id 
          LEFT JOIN kelas k ON u.kelas = k.id
          WHERE ru.id = $id AND ru.user_id = $user_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: riwayat_siswa.php");
    exit();
}

$riwayat = mysqli_fetch_assoc($result);


if (!is_null($user_kelas) && intval($riwayat['kelas_id']) !== $user_kelas) {
    
    header("Location: daftar_ujian.php");
    exit();
}

// Ambil detail jawaban
$query_detail = "SELECT dj.*, s.pertanyaan, s.pilihan_a, s.pilihan_b, s.pilihan_c, s.pilihan_d, s.jawaban_benar
                 FROM detail_jawaban dj
                 JOIN soal s ON dj.soal_id = s.id
                 WHERE dj.riwayat_id = $id
                 ORDER BY dj.soal_id";
$result_detail = mysqli_query($conn, $query_detail);

$persentase = ($riwayat['total_soal'] > 0) ? ($riwayat['jawaban_benar'] / $riwayat['total_soal']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Ujian</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #2d3748; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; }
        .back-btn { display: inline-block; padding: 0.75rem 1.5rem; background: #fff; color: #3182ce; text-decoration: none; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s; }
        .back-btn:hover { background: #3182ce; color: #fff; }
        .header-card { background: #fff; padding: 2rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header-card h1 { font-size: 1.875rem; margin-bottom: 0.5rem; }
        .header-card .subtitle { color: #718096; margin-bottom: 1.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .stat-box { background: #f7fafc; padding: 1rem; border-radius: 8px; text-align: center; }
        .stat-label { font-size: 0.875rem; color: #718096; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #3182ce; }
        .result-badge { display: inline-block; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 1rem; }
        .result-success { background: #c6f6d5; color: #22543d; }
        .result-warning { background: #feebc8; color: #7c2d12; }
        .result-error { background: #fed7d7; color: #742a2a; }
        .soal-card { background: #fff; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .soal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .soal-number { font-weight: 700; color: #3182ce; font-size: 1.125rem; }
        .soal-status { padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.875rem; font-weight: 600; }
        .status-benar { background: #c6f6d5; color: #22543d; }
        .status-salah { background: #fed7d7; color: #742a2a; }
        .pertanyaan { font-weight: 600; margin-bottom: 1rem; line-height: 1.6; }
        .pilihan { padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 8px; border: 2px solid #e2e8f0; }
        .pilihan.benar { background: #c6f6d5; border-color: #48bb78; }
        .pilihan.salah { background: #fed7d7; border-color: #f56565; }
        .pilihan.user-salah { background: #feebc8; border-color: #ed8936; }
        .pilihan-label { font-weight: 700; margin-right: 0.5rem; }
        @media print {
            .back-btn { display: none; }
            body { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="riwayat_siswa.php" class="back-btn">← Kembali ke Riwayat</a>
        
        <div class="header-card">
            <h1><?php echo $riwayat['mata_pelajaran']; ?></h1>
            <div class="subtitle"><?php echo $riwayat['judul']; ?></div>
            <div style="margin-top:0.5rem; color:#718096;">
                Kelas: <?php echo htmlspecialchars($riwayat['kelas_nama'] ?? $riwayat['kelas_id']); ?>
            </div>
            
            <div style="margin: 1.5rem 0;">
                <?php 
                $badge_class = $persentase >= 80 ? 'result-success' : ($persentase >= 60 ? 'result-warning' : 'result-error');
                $badge_text = $persentase >= 80 ? '🎉 Excellent!' : ($persentase >= 60 ? '👍 Good Job!' : '💪 Keep Learning!');
                ?>
                <span class="result-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Skor</div>
                    <div class="stat-value"><?php echo $riwayat['skor']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Jawaban Benar</div>
                    <div class="stat-value"><?php echo $riwayat['jawaban_benar']; ?>/<?php echo $riwayat['total_soal']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Persentase</div>
                    <div class="stat-value"><?php echo number_format($persentase, 1); ?>%</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Waktu</div>
                    <div class="stat-value"><?php echo floor($riwayat['waktu_pengerjaan'] / 60); ?> mnt</div>
                </div>
            </div>
        </div>
        
        <h2 style="margin-bottom: 1rem; color: #2d3748;">Pembahasan Soal</h2>
        
        <?php 
        $no = 1;
        while ($detail = mysqli_fetch_assoc($result_detail)): 
        ?>
        <div class="soal-card">
            <div class="soal-header">
                <span class="soal-number">Soal #<?php echo $no++; ?></span>
                <span class="soal-status <?php echo $detail['status'] == 'benar' ? 'status-benar' : 'status-salah'; ?>">
                    <?php echo $detail['status'] == 'benar' ? '✓ Benar' : '✗ Salah'; ?>
                </span>
            </div>
            
            <div class="pertanyaan"><?php echo $detail['pertanyaan']; ?></div>
            
            <div class="pilihan <?php echo $detail['jawaban_benar'] == 'a' ? 'benar' : ($detail['jawaban_user'] == 'a' && $detail['status'] == 'salah' ? 'user-salah' : ''); ?>">
                <span class="pilihan-label">A.</span> <?php echo $detail['pilihan_a']; ?>
                <?php if ($detail['jawaban_benar'] == 'a') echo ' <strong>(Jawaban Benar)</strong>'; ?>
                <?php if ($detail['jawaban_user'] == 'a' && $detail['status'] == 'salah') echo ' <strong>(Jawaban Anda)</strong>'; ?>
            </div>
            
            <div class="pilihan <?php echo $detail['jawaban_benar'] == 'b' ? 'benar' : ($detail['jawaban_user'] == 'b' && $detail['status'] == 'salah' ? 'user-salah' : ''); ?>">
                <span class="pilihan-label">B.</span> <?php echo $detail['pilihan_b']; ?>
                <?php if ($detail['jawaban_benar'] == 'b') echo ' <strong>(Jawaban Benar)</strong>'; ?>
                <?php if ($detail['jawaban_user'] == 'b' && $detail['status'] == 'salah') echo ' <strong>(Jawaban Anda)</strong>'; ?>
            </div>
            
            <div class="pilihan <?php echo $detail['jawaban_benar'] == 'c' ? 'benar' : ($detail['jawaban_user'] == 'c' && $detail['status'] == 'salah' ? 'user-salah' : ''); ?>">
                <span class="pilihan-label">C.</span> <?php echo $detail['pilihan_c']; ?>
                <?php if ($detail['jawaban_benar'] == 'c') echo ' <strong>(Jawaban Benar)</strong>'; ?>
                <?php if ($detail['jawaban_user'] == 'c' && $detail['status'] == 'salah') echo ' <strong>(Jawaban Anda)</strong>'; ?>
            </div>
            
            <div class="pilihan <?php echo $detail['jawaban_benar'] == 'd' ? 'benar' : ($detail['jawaban_user'] == 'd' && $detail['status'] == 'salah' ? 'user-salah' : ''); ?>">
                <span class="pilihan-label">D.</span> <?php echo $detail['pilihan_d']; ?>
                <?php if ($detail['jawaban_benar'] == 'd') echo ' <strong>(Jawaban Benar)</strong>'; ?>
                <?php if ($detail['jawaban_user'] == 'd' && $detail['status'] == 'salah') echo ' <strong>(Jawaban Anda)</strong>'; ?>
            </div>
        </div>
        <?php endwhile; ?>
        
        <div style="text-align: center; margin: 2rem 0;">
            <a href="riwayat_siswa.php" class="back-btn">Kembali ke Riwayat</a>
            <button onclick="window.print()" class="back-btn" style="margin-left: 1rem;">🖨️ Cetak Halaman</button>
        </div>
    </div>
</body>
</html>