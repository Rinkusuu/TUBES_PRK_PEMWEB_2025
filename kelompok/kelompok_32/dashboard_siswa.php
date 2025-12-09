<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_kelas = isset($_SESSION['kelas']) ? intval($_SESSION['kelas']) : null;

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

$stats = [
    'ujian_selesai' => 3,
    'rata_rata' => 78.5,
    'tertinggi' => 95
];

$ujian_tersedia = 1;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_pages = 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ujian Online</title>
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
            --gradient-2: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            --gradient-3: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
            --gradient-4: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
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
            min-height: 100vh;
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
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-left : 15px}
        .header h1 { font-size: 1.75rem; font-weight: 800; }
        .header p { color: var(--text-secondary); margin-top: 0.25rem; }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
        .stat-card:nth-child(2)::before { background: var(--gradient-2); }
        .stat-card:nth-child(3)::before { background: var(--gradient-3); }
        .stat-card:nth-child(4)::before { background: var(--gradient-4); }
        
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
        .stat-card:nth-child(4) .stat-icon { background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(239,68,68,0.15)); }
        
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
        
        .filter-select:focus { outline: none; border-color: var(--accent); }
        
        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .exam-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .exam-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); }
        
        .card-image { position: relative; height: 160px; overflow: hidden; }
        .card-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .card-image::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 50%;
            background: linear-gradient(to top, var(--bg-card), transparent);
        }
        
        .card-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.375rem 0.75rem;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(8px);
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            color: #fff;
            z-index: 1;
        }
        
        .card-body { padding: 1.5rem; }
        .card-title { font-size: 1rem; font-weight: 700; margin-bottom: 0.375rem; }
        
        .card-desc {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 0.875rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .card-meta { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .meta-item { font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.375rem; }
        
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .score-display { display: flex; align-items: center; gap: 0.75rem; }
        
        .score-ring {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: conic-gradient(var(--accent) calc(var(--score) * 1%), var(--bg-hover) 0);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .score-ring::before { content: ''; position: absolute; inset: 4px; background: var(--bg-card); border-radius: 50%; }
        .score-ring span { position: relative; font-size: 0.8rem; font-weight: 700; }
        
        .score-info .label { font-size: 0.65rem; color: var(--text-secondary); }
        .score-info .value { font-size: 0.8rem; font-weight: 600; }
        
        .attempts-badge { padding: 0.375rem 0.625rem; background: var(--bg-hover); border-radius: 6px; font-size: 0.7rem; color: var(--text-secondary); }
        
        .start-btn {
            padding: 0.625rem 1.25rem;
            background: var(--gradient);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        
        .page-btn {
            width: 40px;
            height: 40px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .page-btn:hover { border-color: var(--accent); color: var(--accent); }
        .page-btn.active { background: var(--gradient); border-color: transparent; color: #fff; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .page-info { padding: 0 1rem; font-size: 0.875rem; color: var(--text-secondary); }
        
        @media (max-width: 1200px) {
            .stats-section { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .toggle-btn { left: 1rem; }
            .main { margin-left: 0; padding: 1.5rem; }
            .stats-section { grid-template-columns: 1fr 1fr; }
            .exam-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon">📚</div>
                <span class="logo-text">Examify</span>
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
                <a href="dashboard_siswa.php" class="nav-item active"><span>◉</span> Dashboard</a>
                <a href="riwayat_siswa.php" class="nav-item"><span>◫</span> Riwayat Ujian</a>
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
                <div>
                    <h1>Selamat Datang! 👋</h1>
                    <p>Siap untuk ujian hari ini?</p>
                </div>
            </div>
            
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-value"><?php echo $stats['ujian_selesai']; ?></div>
                    <div class="stat-label">Ujian Selesai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?php echo number_format($stats['rata_rata'], 0); ?></div>
                    <div class="stat-label">Nilai Rata-rata</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-value"><?php echo number_format($stats['tertinggi'], 0); ?></div>
                    <div class="stat-label">Nilai Tertinggi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value"><?php echo $ujian_tersedia; ?></div>
                    <div class="stat-label">Ujian Tersedia</div>
                </div>
            </div>
            
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari ujian berdasarkan nama atau mata pelajaran...">
                </div>
                <select class="filter-select" id="sortSelect">
                    <option value="newest">Terbaru</option>
                    <option value="name">Nama A-Z</option>
                    <option value="score">Nilai Tertinggi</option>
                </select>
                <select class="filter-select" id="statusSelect">
                    <option value="all">Semua Status</option>
                    <option value="done">Sudah Dikerjakan</option>
                    <option value="new">Belum Dikerjakan</option>
                </select>
            </div>
            
            <div class="exam-grid" id="examGrid">
                <div class="exam-card" 
                     data-name="matematika ujian tengah semester matematika"
                     data-score="82"
                     data-status="done"
                     onclick="window.location.href='mulai_ujian.php?ujian_id=1'">
                    <div class="card-image">
                       
                        <span class="card-badge">Matematika</span>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">Ujian Tengah Semester Matematika</h3>
                        <p class="card-desc">Ujian .</p>
                        <div class="card-meta">
                            <div class="meta-item"><span>📝</span> 25 Soal</div>
                            <div class="meta-item"><span>⏱️</span> 90 Menit</div>
                        </div>
                        <div class="card-footer">
                            <div class="score-display">
                                <div class="score-ring" style="--score: 82">
                                    <span>82</span>
                                </div>
                                <div class="score-info">
                                    <div class="label">Nilai Terbaik</div>
                                    <div class="value">Personal Best</div>
                                </div>
                            </div>
                            <div class="attempts-badge">2x</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="pagination">
                <button class="page-btn" onclick="changePage(1)" disabled>←</button>
                <button class="page-btn active" onclick="changePage(1)">1</button>
                <button class="page-btn" onclick="changePage(2)" disabled>→</button>
                <span class="page-info">Halaman 1 dari 1</span>
            </div>
        </main>
    </div>
    
    <script>
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
        
        if (localStorage.getItem('theme') === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('themeToggle').classList.add('active');
        }
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('closed');
        }
        
        function changePage(page) {
            window.location.href = 'dashboard_siswa.php?page=' + page;
        }
        
        document.getElementById('searchInput').addEventListener('input', filterExams);
        document.getElementById('sortSelect').addEventListener('change', filterExams);
        document.getElementById('statusSelect').addEventListener('change', filterExams);
        
        function filterExams() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const sort = document.getElementById('sortSelect').value;
            const status = document.getElementById('statusSelect').value;
            
            const cards = Array.from(document.querySelectorAll('.exam-card'));
            
            cards.sort((a, b) => {
                if (sort === 'name') return a.dataset.name.localeCompare(b.dataset.name);
                if (sort === 'score') return parseFloat(b.dataset.score) - parseFloat(a.dataset.score);
                return 0;
            });
            
            const grid = document.getElementById('examGrid');
            cards.forEach(card => {
                const matchSearch = card.dataset.name.includes(search);
                const matchStatus = status === 'all' || card.dataset.status === status;
                
                card.style.display = (matchSearch && matchStatus) ? '' : 'none';
                grid.appendChild(card);
            });
        }
    </script>
</body>
</html>