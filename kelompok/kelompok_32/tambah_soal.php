<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 'create_ujian') {
 
        $mata_pelajaran_id = intval($_POST['mata_pelajaran_id']);
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $jumlah_soal = intval($_POST['jumlah_soal']);
        $waktu = intval($_POST['waktu_pengerjaan']);
        // kelas wajib (atau set 0 jika "semua kelas")
        $kelas = isset($_POST['kelas']) && $_POST['kelas'] !== '' ? intval($_POST['kelas']) : 0;
        
        $query = "INSERT INTO ujian (mata_pelajaran_id, judul, jumlah_soal, waktu_pengerjaan, kelas) 
                  VALUES ($mata_pelajaran_id, '$judul', $jumlah_soal, $waktu, $kelas)";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['ujian_id'] = mysqli_insert_id($conn);
            $_SESSION['jumlah_soal'] = $jumlah_soal;
            $_SESSION['ujian_kelas'] = $kelas; // simpan di session agar ditampilkan saat menambah soal
            $_SESSION['success'] = "Detail ujian berhasil disimpan! Silakan tambahkan soal.";
        } else {
            $_SESSION['error'] = "Gagal menyimpan: " . mysqli_error($conn);
        }
        header("Location: tambah_soal.php");
        exit();
    }
    
    if (isset($_POST['step']) && $_POST['step'] == 'add_soal') {
        $ujian_id = $_SESSION['ujian_id'];
        $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
        $jawaban_benar = $_POST['jawaban_benar'];
        
        $pilihan = [];
        foreach ($_POST['pilihan'] as $p) {
            if (!empty($p)) {
                $pilihan[] = mysqli_real_escape_string($conn, $p);
            }
        }
        
    
        while (count($pilihan) < 4) {
            $pilihan[] = '';
        }
        
        $query = "INSERT INTO soal (ujian_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar) 
                  VALUES ($ujian_id, '$pertanyaan', '{$pilihan[0]}', '{$pilihan[1]}', '{$pilihan[2]}', '{$pilihan[3]}', '$jawaban_benar')";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Soal berhasil ditambahkan!";
            

            $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM soal WHERE ujian_id = $ujian_id"))['total'];
            if ($count >= $_SESSION['jumlah_soal']) {
                $_SESSION['success'] = "Semua soal berhasil ditambahkan! Ujian sudah lengkap.";
                unset($_SESSION['ujian_id'], $_SESSION['jumlah_soal']);
            }
        } else {
            $_SESSION['error'] = "Gagal menambahkan soal: " . mysqli_error($conn);
        }
        header("Location: tambah_soal.php");
        exit();
    }
}

// ambil daftar mapel & daftar kelas
$result_mapel = mysqli_query($conn, "SELECT * FROM mata_pelajaran ORDER BY nama");
$kelas_list = mysqli_query($conn, "SELECT id, nama FROM kelas ORDER BY nama");

$progress = null;
if (isset($_SESSION['ujian_id'])) {
    $ujian_id = $_SESSION['ujian_id'];
    $ujian_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.judul, mp.nama as mapel, u.kelas as kelas_id, COALESCE(k.nama, u.kelas) as kelas_nama FROM ujian u JOIN mata_pelajaran mp ON u.mata_pelajaran_id = mp.id LEFT JOIN kelas k ON u.kelas = k.id WHERE u.id = $ujian_id"));
    $soal_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM soal WHERE ujian_id = $ujian_id"))['total'];
    $progress = [
        'current' => $soal_count,
        'target' => $_SESSION['jumlah_soal'],
        'judul' => $ujian_info['judul'],
        'mapel' => $ujian_info['mapel'],
        'kelas_nama' => $ujian_info['kelas_nama'] ?? $ujian_info['kelas_id']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Soal</title>
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
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #c6f6d5; color: #22543d; }
        .alert-error { background: #fed7d7; color: #c53030; }
        .progress-card { background: #fff; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .progress-title { font-weight: 600; font-size: 1.125rem; }
        .progress-badge { background: #3182ce; color: #fff; padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.875rem; font-weight: 600; }
        .progress-info { font-size: 0.875rem; color: #718096; margin-bottom: 1rem; }
        .progress-bar { width: 100%; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 0.5rem; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #48bb78, #3182ce); transition: width 0.3s; }
        .progress-text { font-size: 0.875rem; color: #4a5568; text-align: right; }
        .form-card { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem; color: #2d3748; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; transition: border 0.2s; }
        .form-control:focus { outline: none; border-color: #3182ce; }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .pilihan-container { margin-bottom: 1rem; }
        .pilihan-item { display: flex; gap: 0.75rem; margin-bottom: 0.75rem; align-items: center; }
        .pilihan-item input { flex: 1; }
        .btn-remove { padding: 0.5rem 0.75rem; background: #fed7d7; color: #c53030; border: none; border-radius: 6px; cursor: pointer; font-size: 0.875rem; }
        .btn-remove:hover { background: #fc8181; }
        .btn-add-pilihan { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; background: #e6fffa; color: #234e52; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem; font-weight: 500; margin-bottom: 1rem; }
        .btn-add-pilihan:hover { background: #b2f5ea; }
        .radio-group { display: flex; gap: 1rem; flex-wrap: wrap; }
        .radio-item { display: flex; align-items: center; gap: 0.5rem; }
        .radio-item input { width: auto; }
        .btn-primary { width: 100%; padding: 0.875rem; background: #3182ce; color: #fff; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-primary:hover { background: #2c5aa0; }
        .btn-secondary { width: 100%; padding: 0.875rem; background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 1rem; }
        .btn-secondary:hover { background: #cbd5e0; }
        .step-indicator { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
        .step { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: #f7fafc; border-radius: 8px; font-weight: 600; color: #718096; }
        .step.active { background: #3182ce; color: #fff; }
        .step-number { width: 28px; height: 28px; border-radius: 50%; background: #fff; color: #3182ce; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 700; }
        .step.active .step-number { background: #fff; color: #3182ce; }
        
        @media (max-width: 768px) {
            .sidebar { position: fixed; z-index: 100; height: 100vh; }
            .main { padding: 1.5rem; }
            .main.expanded { margin-left: 0; }
            .form-row { grid-template-columns: 1fr; }
            .step-indicator { overflow-x: auto; }
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
                <li><a href="dashboard_admin.php">Dashboard</a></li>
                <li><a href="data_siswa.php">Data Siswa</a></li>
                <li><a href="daftar_ujian.php">Daftar Ujian</a></li>
                <li><a href="tambah_mata_pelajaran.php">Tambah Mata Pelajaran</a></li>
                <li><a href="tambah_soal.php" class="active">Tambah Soal</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>
        
        <main class="main" id="main">
            <div class="header">
                <h1>Tambah Soal Ujian</h1>
                <p>Buat ujian baru dengan soal-soal yang berkualitas</p>
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
            
            <div class="step-indicator">
                <div class="step <?php echo !isset($_SESSION['ujian_id']) ? 'active' : ''; ?>">
                    <span class="step-number">1</span>
                    <span>Detail Ujian</span>
                </div>
                <div class="step <?php echo isset($_SESSION['ujian_id']) ? 'active' : ''; ?>">
                    <span class="step-number">2</span>
                    <span>Tambah Soal</span>
                </div>
            </div>
            
            <?php if ($progress): ?>
            <div class="progress-card">
                <div class="progress-header">
                    <span class="progress-title">📚 <?php echo $progress['judul']; ?></span>
                    <span class="progress-badge"><?php echo $progress['mapel']; ?></span>
                </div>
                <div class="progress-info">
                    Kelas: <?php echo htmlspecialchars($progress['kelas_nama'] ?? '—'); ?> — Progress: <?php echo $progress['current']; ?> dari <?php echo $progress['target']; ?> soal
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo ($progress['current']/$progress['target']*100); ?>%"></div>
                </div>
                <div class="progress-text">
                    <?php echo number_format($progress['current']/$progress['target']*100, 1); ?>% selesai
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-card">
                <?php if (!isset($_SESSION['ujian_id'])): ?>
           
                <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Detail Ujian</h2>
                <form method="POST">
                    <input type="hidden" name="step" value="create_ujian">
                    
                    <div class="form-group">
                        <label>Mata Pelajaran</label>
                        <select name="mata_pelajaran_id" class="form-control" required>
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php while ($mapel = mysqli_fetch_assoc($result_mapel)): ?>
                                <option value="<?php echo $mapel['id']; ?>"><?php echo $mapel['nama']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Judul Ujian</label>
                        <input type="text" name="judul" class="form-control" placeholder="Contoh: Matematika Dasar Kelas 10" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jumlah Soal</label>
                            <input type="number" name="jumlah_soal" class="form-control" min="1" placeholder="20" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Waktu Pengerjaan (menit)</label>
                            <input type="number" name="waktu_pengerjaan" class="form-control" min="1" placeholder="30" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Kelas</label>
                        <select name="kelas" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?php echo intval($k['id']); ?>"><?php echo htmlspecialchars($k['nama']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">Lanjut ke Tambah Soal →</button>
                </form>
                
                <?php else: ?>
           
                <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Soal #<?php echo $progress['current'] + 1; ?></h2>
                <form method="POST" id="soalForm">
                    <input type="hidden" name="step" value="add_soal">
                    
                    <div class="form-group">
                        <label>Pertanyaan</label>
                        <textarea name="pertanyaan" class="form-control" placeholder="Tuliskan pertanyaan di sini..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan Jawaban (minimal 2, maksimal 5)</label>
                        <div class="pilihan-container" id="pilihanContainer">
                            <div class="pilihan-item">
                                <input type="text" name="pilihan[]" class="form-control" placeholder="Pilihan A" required>
                                <button type="button" class="btn-remove" onclick="removePilihan(this)" style="visibility: hidden;">✕</button>
                            </div>
                            <div class="pilihan-item">
                                <input type="text" name="pilihan[]" class="form-control" placeholder="Pilihan B" required>
                                <button type="button" class="btn-remove" onclick="removePilihan(this)">✕</button>
                            </div>
                        </div>
                        <button type="button" class="btn-add-pilihan" onclick="addPilihan()" id="btnAddPilihan">
                            <span style="font-size: 1.25rem;">+</span> Tambah Pilihan
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>Jawaban Benar</label>
                        <div class="radio-group" id="jawabanGroup">
                            <div class="radio-item">
                                <input type="radio" name="jawaban_benar" value="a" id="jawaban_a" required>
                                <label for="jawaban_a">A</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="jawaban_benar" value="b" id="jawaban_b">
                                <label for="jawaban_b">B</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">Tambah Soal</button>
                    <button type="button" class="btn-secondary" onclick="selesaiUjian()">Selesai & Kembali</button>
                </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            sidebar.classList.toggle('closed');
            main.classList.toggle('expanded');
        }

        let pilihanCount = 2;
        const maxPilihan = 5;
        const labels = ['A', 'B', 'C', 'D', 'E'];

        function addPilihan() {
            if (pilihanCount >= maxPilihan) {
                alert('Maksimal 5 pilihan jawaban');
                return;
            }
            
            const container = document.getElementById('pilihanContainer');
            const newItem = document.createElement('div');
            newItem.className = 'pilihan-item';
            newItem.innerHTML = `
                <input type="text" name="pilihan[]" class="form-control" placeholder="Pilihan ${labels[pilihanCount]}" required>
                <button type="button" class="btn-remove" onclick="removePilihan(this)">✕</button>
            `;
            container.appendChild(newItem);
            
   
            const jawabanGroup = document.getElementById('jawabanGroup');
            const newRadio = document.createElement('div');
            newRadio.className = 'radio-item';
            newRadio.innerHTML = `
                <input type="radio" name="jawaban_benar" value="${labels[pilihanCount].toLowerCase()}" id="jawaban_${labels[pilihanCount].toLowerCase()}">
                <label for="jawaban_${labels[pilihanCount].toLowerCase()}">${labels[pilihanCount]}</label>
            `;
            jawabanGroup.appendChild(newRadio);
            
            pilihanCount++;
            
            if (pilihanCount >= maxPilihan) {
                document.getElementById('btnAddPilihan').style.display = 'none';
            }
        }

        function removePilihan(btn) {
            if (pilihanCount <= 2) {
                alert('Minimal 2 pilihan jawaban');
                return;
            }
            
            btn.parentElement.remove();

            const jawabanGroup = document.getElementById('jawabanGroup');
            jawabanGroup.removeChild(jawabanGroup.lastElementChild);
            
            pilihanCount--;
            document.getElementById('btnAddPilihan').style.display = 'inline-flex';
        }

        function selesaiUjian() {
            if (confirm('Apakah Anda yakin ingin menyelesaikan pembuatan ujian ini?')) {
                window.location.href = 'selesai_ujian.php';
            }
        }
    </script>
</body>
</html>