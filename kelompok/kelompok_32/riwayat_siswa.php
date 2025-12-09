<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$nama_kelas_display = '—';
if (!empty($_SESSION['kelas'])) {
    $sess_kelas = $_SESSION['kelas'];
    if (is_numeric($sess_kelas)) {
        $q = mysqli_query($conn, "SELECT nama FROM kelas WHERE id = " . intval($sess_kelas) . " LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            $nama_kelas_display = $row['nama'];
        }
    } else {
        $nama_kelas_display = $sess_kelas;
    }

    if (stripos($nama_kelas_display, 'kelas') === false) {
        $nama_kelas_display = 'Kelas ' . $nama_kelas_display;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Ujian</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-hover: #f1f5f9;
            --border: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.08), 0 8px 32px rgba(0,0,0,0.06);
            --accent: #6366f1;
            --accent-light: #eef2ff;
            --gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --success: #10b981;
            --warning: #f59e0b;
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --border: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --shadow: 0 1px 3px rgba(0,0,0,0.3), 0 4px 12px rgba(0,0,0,0.2);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.4), 0 8px 32px rgba(0,0,0,0.3);
            --accent-light: rgba(99, 102, 241, 0.15);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: background 0.3s, color 0.3s;
        }
        
        .layout { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s, background 0.3s;
            z-index: 100;
        }
        
        .sidebar.closed { transform: translateX(-100%); }
        
        .toggle-btn {
            position: fixed;
            left: 280px;
            top: 1.5rem;
            width: 40px;
            height: 40px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            z-index: 101;
            font-size: 1.1rem;
        }
        
        .sidebar.closed + .toggle-btn { left: 1rem; }
        .toggle-btn:hover { background: var(--accent); color: #fff; }
        
        .logo { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem; }
        
        .logo-icon {
            width: 44px;
            height: 44px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .logo-text { font-size: 1.25rem; font-weight: 800; }
        
        .user-card {
            background: var(--accent-light);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.125rem;
            color: #fff;
        }
        
        .user-details h4 { font-size: 0.9rem; font-weight: 600; }
        .user-details p { font-size: 0.75rem; color: var(--text-secondary); }
        
        .nav-menu { flex: 1; }
        .nav-label { font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.75rem; }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 0.875rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .nav-item:hover { background: var(--bg-hover); color: var(--text-primary); }
        .nav-item.active { background: var(--gradient); color: #fff; }
        
        .theme-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem;
            background: var(--bg-hover);
            border-radius: 12px;
            margin-top: auto;
        }
        
        .theme-toggle span { font-size: 0.875rem; color: var(--text-secondary); }
        
        .toggle-switch {
            width: 50px;
            height: 26px;
            background: var(--border);
            border-radius: 13px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
            margin-left: auto;
        }
        
        .toggle-switch.active { background: var(--accent); }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            background: #fff;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle-switch.active::after { transform: translateX(24px); }
        
        .main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: margin 0.3s;
        }
        
        .sidebar.closed ~ .main { margin-left: 0; }
        
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; }
        .header p { color: var(--text-secondary); }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        
        .stat-card:nth-child(1)::before { background: var(--gradient); }
        .stat-card:nth-child(2)::before { background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); }
        .stat-card:nth-child(3)::before { background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%); }
        
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 0.875rem;
        }
        
        .stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.15)); }
        .stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(99,102,241,0.15)); }
        .stat-card:nth-child(3) .stat-icon { background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(20,184,166,0.15)); }
        
        .stat-value { font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; }
        .stat-label { font-size: 0.8rem; color: var(--text-secondary); }
        
        .filter-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 280px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .search-box input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .search-box::before { content: '🔍'; position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); }
        
        .filter-select {
            padding: 0.875rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        .table-wrapper {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--bg-hover); }
        th { padding: 1rem; text-align: left; font-size: 0.875rem; font-weight: 600; color: var(--text-primary); border-bottom: 1px solid var(--border); }
        td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.875rem; }
        tbody tr:hover { background: var(--bg-hover); }
        tbody tr:last-child td { border-bottom: none; }
        
        .score-display { display: flex; align-items: center; gap: 0.75rem; }
        .score-value { font-weight: 700; font-size: 1.125rem; }
        .score-bar { flex: 1; max-width: 100px; height: 6px; background: var(--bg-hover); border-radius: 3px; overflow: hidden; }
        .score-progress { height: 100%; background: var(--gradient); transition: width 0.3s; }
        
        .status-badge { display: inline-block; padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .status-success { background: #d1fae5; color: #065f46; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-error { background: #fee2e2; color: #991b1b; }
        
        .action-buttons { display: flex; gap: 0.5rem; }
        .btn-action {
            padding: 0.5rem 0.875rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-view { background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(99,102,241,0.15)); color: var(--accent); }
        .btn-view:hover { background: linear-gradient(135deg, rgba(59,130,246,0.25), rgba(99,102,241,0.25)); }
        .btn-download { background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(20,184,166,0.15)); color: var(--success); }
        .btn-download:hover { background: linear-gradient(135deg, rgba(16,185,129,0.25), rgba(20,184,166,0.25)); }
        
        .empty-state { text-align: center; padding: 4rem 2rem; color: var(--text-secondary); }
        .empty-state div:first-child { font-size: 3.5rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state h3 { margin-bottom: 0.5rem; color: var(--text-primary); }
        
        .btn-primary {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.875rem 1.75rem;
            background: var(--gradient);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        
        .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.modal.active { display: flex; }

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-content {
    background: var(--bg-card);
    border-radius: 24px;
    padding: 2.5rem;
    max-width: 500px;
    width: 90%;
    box-shadow: var(--shadow-lg);
    animation: slideUp 0.3s ease;
}

.modal-header { text-align: center; margin-bottom: 1.5rem; }

.modal-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
}

.modal-icon.success {
    background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(20,184,166,0.15));
}

.modal-icon.error {
    background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(220,38,38,0.15));
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.modal-subtitle {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.modal-body { margin-bottom: 2rem; }

.score-display-large {
    text-align: center;
    padding: 1.5rem;
    background: var(--bg-hover);
    border-radius: 16px;
    margin-bottom: 1.5rem;
}

.score-display-large .score {
    font-size: 3rem;
    font-weight: 800;
    background: var(--gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.score-display-large .label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.5rem;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: var(--bg-hover);
    border-radius: 10px;
}

.info-item .label {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.info-item .value {
    font-weight: 600;
    font-size: 0.875rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
}

.modal-btn {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.modal-btn-cancel {
    background: var(--bg-hover);
    color: var(--text-primary);
}

.modal-btn-cancel:hover { background: var(--border); }

.modal-btn-primary {
    background: var(--gradient);
    color: #fff;
    box-shadow: 0 4px 12px rgba(99,102,241,0.3);
}

.modal-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99,102,241,0.4);
}

.requirement-box {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: 12px;
    padding: 1rem;
    margin: 1.5rem 0;
}

.requirement-box p {
    color: var(--danger);
    font-size: 0.875rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 0.5rem;
}

.requirement-box small {
    display: block;
    text-align: center;
    color: var(--text-secondary);
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .toggle-btn { left: 1rem; }
    .main { margin-left: 0; padding: 1.5rem; }
    .stats-section { grid-template-columns: 1fr; }
    .filter-section { flex-direction: column; align-items: stretch; }
    .search-box { min-width: 100%; }
}

@media (max-width: 640px) {
    .modal-content { padding: 1.5rem; }
    .modal-actions { flex-direction: column; }
}
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon">📚</div>
                <span class="logo-text">Uian Online</span>
            </div>
            
            <div class="user-card">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?></div>
                <div class="user-details">
                    <h4><?php echo $_SESSION['nama_lengkap']; ?></h4>
                    <p><?php echo htmlspecialchars($nama_kelas_display); ?></p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <div class="nav-label">Menu Utama</div>
                <a href="dashboard_siswa.php" class="nav-item"><span>◉</span> Dashboard</a>
                <a href="riwayat_siswa.php" class="nav-item active"><span>◫</span> Riwayat Ujian</a>
                <div class="nav-label" style="margin-top: 1.5rem;">Lainnya</div>
                <a href="logout.php" class="nav-item"><span>↪</span> Keluar</a>
            </nav>
            
            <div class="theme-toggle">
                <span>🌙</span>
                <span>Dark Mode</span>
                <div class="toggle-switch" id="themeToggle" onclick="toggleTheme()"></div>
            </div>
        </aside>
        
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        
        <main class="main">
            <div class="header">
                <h1>Riwayat Ujian</h1>
                <p>Lihat perkembangan dan hasil ujian Anda</p>
            </div>
            
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value">3</div>
                    <div class="stat-label">Total Ujian</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value">78</div>
                    <div class="stat-label">Rata-rata Skor</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-value">95</div>
                    <div class="stat-label">Skor Tertinggi</div>
                </div>
            </div>
            
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari ujian...">
                </div>
                <select id="filterMataPelajaran" class="filter-select">
                    <option value="">Semua Mata Pelajaran</option>
                    <option value="Matematika">Matematika</option>
                    <option value="IPA">IPA</option>
                    <option value="IPS">IPS</option>
                    <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                    <option value="Bahasa Inggris">Bahasa Inggris</option>
                </select>
                <select id="filterSkor" class="filter-select">
                    <option value="">Semua Skor</option>
                    <option value="90-100">90-100 (Excellent)</option>
                    <option value="80-89">80-89 (Good)</option>
                    <option value="70-79">70-79 (Average)</option>
                    <option value="0-69">0-69 (Need Improvement)</option>
                </select>
            </div>
            
            <div class="table-wrapper">
                <table id="riwayatTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mata Pelajaran</th>
                            <th>Judul Ujian</th>
                            <th>Skor</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><strong>Matematika</strong></td>
                            <td>Ujian Tengah Semester</td>
                            <td>
                                <div class="score-display">
                                    <span class="score-value">82</span>
                                    <div class="score-bar">
                                        <div class="score-progress" style="width: 82%"></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="status-badge status-success">Excellent</span></td>
                            <td>45 menit</td>
                            <td>10/12/2024 14:30</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-view" onclick="lihatDetail(1)">👁️ Detail</button>
                                    <button class="btn-action btn-download" onclick="handleDownload(1, 82, 'Ujian Tengah Semester', 'Matematika', '10/12/2024', 1)">📥 Sertifikat</button>
                                </div>
                            </td>
                        </tr>
                       
                    </tbody>
                </table>
            </div>
            
            <div class="modal" id="modalSuccess">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-icon success">🎉</div>
                        <h3 class="modal-title">Selamat!</h3>
                        <p class="modal-subtitle">Anda memenuhi syarat untuk mendapatkan sertifikat</p>
                    </div>
                    
                    <div class="modal-body">
                        <div class="score-display-large">
                            <div class="score" id="modalScoreSuccess">0</div>
                            <div class="label">Nilai Ujian Anda</div>
                        </div>
                        
                        <div class="info-list">
                            <div class="info-item">
                                <span class="label">Ujian</span>
                                <span class="value" id="modalUjianSuccess">-</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Mata Pelajaran</span>
                                <span class="value" id="modalMapelSuccess">-</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Tanggal</span>
                                <span class="value" id="modalTanggalSuccess">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button class="modal-btn modal-btn-cancel" onclick="closeModal()">✕ Tutup</button>
                        <button class="modal-btn modal-btn-primary" onclick="downloadCertificate()">📥 Download Sertifikat</button>
                    </div>
                </div>
            </div>

            <div class="modal" id="modalError">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-icon error">😔</div>
                        <h3 class="modal-title">Nilai Belum Memenuhi</h3>
                        <p class="modal-subtitle">Anda belum memenuhi syarat untuk mendapatkan sertifikat</p>
                    </div>
                    
                    <div class="modal-body">
                        <div class="score-display-large">
                            <div class="score" id="modalScoreError" style="background: linear-gradient(135deg, #ef4444, #dc2626); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">0</div>
                            <div class="label">Nilai Ujian Anda</div>
                        </div>
                        
                        <div class="requirement-box">
                            <p>⚠️ Nilai minimal untuk sertifikat: 70</p>
                            <small>Silakan coba lagi untuk mendapatkan sertifikat</small>
                        </div>
                        
                        <div class="info-list">
                            <div class="info-item">
                                <span class="label">Ujian</span>
                                <span class="value" id="modalUjianError">-</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Mata Pelajaran</span>
                                <span class="value" id="modalMapelError">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button class="modal-btn modal-btn-cancel" onclick="closeModal()">✕ Tutup</button>
                        <button class="modal-btn modal-btn-primary" onclick="retryExam()">🔄 Coba Lagi</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let currentExamId = null;
        let currentUjianId = null;
        
        function toggleTheme() {
            const body = document.body;
            const toggle = document.getElementById('themeToggle');
            
            if (body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                toggle.classList.remove('active');
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                toggle.classList.add('active');
                localStorage.setItem('theme', 'dark');
            }
        }
        
        function handleDownload(riwayatId, skor, ujian, mapel, tanggal, ujianId) {
            currentExamId = riwayatId;
            currentUjianId = ujianId;
            
            if (skor >= 70) {
                document.getElementById('modalScoreSuccess').textContent = skor;
                document.getElementById('modalUjianSuccess').textContent = ujian;
                document.getElementById('modalMapelSuccess').textContent = mapel;
                document.getElementById('modalTanggalSuccess').textContent = tanggal;
                document.getElementById('modalSuccess').classList.add('active');
            } else {
                document.getElementById('modalScoreError').textContent = skor;
                document.getElementById('modalUjianError').textContent = ujian;
                document.getElementById('modalMapelError').textContent = mapel;
                document.getElementById('modalError').classList.add('active');
            }
        }

        if (localStorage.getItem('theme') === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('themeToggle').classList.add('active');
        }
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('closed');
        }

        function lihatDetail(id) {
            window.location.href = 'detail_ujian.php?id=' + id;
        }

        document.getElementById('searchInput').oninput = function() {
            const term = this.value.toLowerCase();
            [...document.querySelectorAll('#riwayatTable tbody tr')].forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        };
        
        function downloadCertificate() {
            if (currentExamId) {
                window.open('download_sertifikat.php?id=' + currentExamId, '_blank');
                closeModal();
            }
        }

        function retryExam() {
            if (currentUjianId) {
                window.location.href = 'mulai_ujian.php?ujian_id=' + currentUjianId;
            }
        }

        function closeModal() {
            document.getElementById('modalSuccess').classList.remove('active');
            document.getElementById('modalError').classList.remove('active');
            currentExamId = null;
            currentUjianId = null;
        }

        document.getElementById('modalSuccess').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('modalError').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        function filterTable() {
            const mp = document.getElementById('filterMataPelajaran').value;
            const skor = document.getElementById('filterSkor').value;
            
            [...document.querySelectorAll('#riwayatTable tbody tr')].forEach(row => {
                if (row.cells.length < 8) return;
                
                const rowMp = row.cells[1].textContent.trim();
                const scoreEl = row.cells[3].querySelector('.score-value');
                if (!scoreEl) return;
                
                const score = parseInt(scoreEl.textContent);
                let show = true;
                
                if (mp && !rowMp.includes(mp)) show = false;
                if (skor) {
                    const [min, max] = skor.split('-').map(Number);
                    if (score < min || score > max) show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }

        document.getElementById('filterMataPelajaran').onchange = filterTable;
        document.getElementById('filterSkor').onchange = filterTable;
    </script>
</body>
</html>