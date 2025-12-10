<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['ujian_id'])) {
    header("Location: dashboard_siswa.php");
    exit();
}

$ujian_id = intval($_GET['ujian_id']);
$user_id = $_SESSION['user_id'];
$user_kelas = isset($_SESSION['kelas']) ? intval($_SESSION['kelas']) : null;

$query_ujian = "SELECT u.*, u.kelas as kelas_id, COALESCE(k.nama, u.kelas) as kelas_nama, mp.nama as mata_pelajaran 
                 FROM ujian u 
                 JOIN mata_pelajaran mp ON u.mata_pelajaran_id = mp.id 
                 LEFT JOIN kelas k ON u.kelas = k.id
                 WHERE u.id = $ujian_id";
$result_ujian = mysqli_query($conn, $query_ujian);
$ujian = mysqli_fetch_assoc($result_ujian);

if (!$ujian) {
    header("Location: dashboard_siswa.php");
    exit();
}

if (!is_null($user_kelas) && intval($ujian['kelas_id']) !== $user_kelas) {
    unset($_SESSION['exam_start_time'], $_SESSION['exam_answers'], $_SESSION['current_question']);
    header("Location: dashboard_siswa.php");
    exit();
}

$result_soal = mysqli_query($conn, "SELECT * FROM soal WHERE ujian_id = $ujian_id ORDER BY id");
$soal_list = [];
while ($soal = mysqli_fetch_assoc($result_soal)) {
    $soal_list[] = $soal;
}

$total_soal = count($soal_list);

if (!isset($_SESSION['exam_start_time'])) {
    $_SESSION['exam_start_time'] = time();
    $_SESSION['exam_answers'] = array_fill(0, $total_soal, '');
    $_SESSION['current_question'] = 0;
}

$current_question = $_SESSION['current_question'] ?? 0;
$answers = $_SESSION['exam_answers'] ?? [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['answer'])) {
        $answers[$current_question] = $_POST['answer'];
        $_SESSION['exam_answers'] = $answers;
    }
    
    if (isset($_POST['navigate'])) {
        $current_question = intval($_POST['navigate']);
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] == 'next') {
            $current_question = min($current_question + 1, $total_soal - 1);
        } elseif ($_POST['action'] == 'prev') {
            $current_question = max($current_question - 1, 0);
        } elseif ($_POST['action'] == 'finish') {
            $end_time = time();
            $waktu_pengerjaan = $end_time - $_SESSION['exam_start_time'];
            $jawaban_benar = 0;
            
            for ($i = 0; $i < $total_soal; $i++) {
                if ($answers[$i] == $soal_list[$i]['jawaban_benar']) {
                    $jawaban_benar++;
                }
            }
            
            $skor = ($jawaban_benar / $total_soal) * 100;
            
            mysqli_query($conn, "INSERT INTO riwayat_ujian (user_id, ujian_id, skor, total_soal, jawaban_benar, waktu_pengerjaan) 
                  VALUES ($user_id, $ujian_id, $skor, $total_soal, $jawaban_benar, $waktu_pengerjaan)");
            
            $riwayat_id = mysqli_insert_id($conn);
            
            for ($i = 0; $i < $total_soal; $i++) {
                $soal_id = $soal_list[$i]['id'];
                $jawaban_user = $answers[$i] ?: 'NULL';
                $status = ($answers[$i] == $soal_list[$i]['jawaban_benar']) ? 'benar' : 'salah';
                
                mysqli_query($conn, "INSERT INTO detail_jawaban (riwayat_id, soal_id, jawaban_user, status) 
                         VALUES ($riwayat_id, $soal_id, '$jawaban_user', '$status')");
            }
            
            unset($_SESSION['exam_start_time'], $_SESSION['exam_answers'], $_SESSION['current_question']);
            header("Location: detail_ujian.php?id=$riwayat_id");
            exit();
        }
    }
    
    $_SESSION['current_question'] = $current_question;
    header("Location: kerjakan_ujian.php?ujian_id=$ujian_id");
    exit();
}

$current_soal = $soal_list[$current_question];
$progress = (($current_question + 1) / $total_soal) * 100;
$jawaban_terisi = count(array_filter($answers));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mengerjakan Ujian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .progress-fill { width: <?php echo $progress; ?>%; transition: width 0.4s; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-blue-50 to-cyan-50 min-h-screen">
    
    <div class="fixed top-6 right-6 z-50">
        <button onclick="toggleNavPanel()" class="flex items-center gap-2 px-4 py-3 bg-white rounded-xl shadow-lg hover:shadow-xl transition-all border border-purple-200">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span class="font-semibold text-purple-900">Navigasi</span>
            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-bold"><?php echo $jawaban_terisi; ?>/<?php echo $total_soal; ?></span>
        </button>
    </div>

    <div id="navPanel" class="fixed inset-y-0 right-0 w-80 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-40 overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900">Navigasi Soal</h3>
                <button onclick="toggleNavPanel()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex items-center justify-between text-xs mb-6 pb-4 border-b">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded"></div>
                    <span class="text-gray-600">Terjawab</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-gray-200 rounded"></div>
                    <span class="text-gray-600">Belum</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-purple-600 rounded"></div>
                    <span class="text-gray-600">Aktif</span>
                </div>
            </div>

            <form method="POST" id="navForm">
                <input type="hidden" name="navigate" id="navigateInput">
                <div class="grid grid-cols-5 gap-2">
                    <?php for ($i = 0; $i < $total_soal; $i++): 
                        $isActive = $i == $current_question;
                        $isAnswered = !empty($answers[$i]);
                    ?>
                        <button type="button" 
                            class="aspect-square rounded-lg text-sm font-bold transition-all
                            <?php if ($isActive): ?>
                                bg-purple-600 text-white shadow-md
                            <?php elseif ($isAnswered): ?>
                                bg-green-500 text-white hover:bg-green-600
                            <?php else: ?>
                                bg-gray-200 text-gray-700 hover:bg-gray-300
                            <?php endif; ?>"
                            onclick="navigateTo(<?php echo $i; ?>)">
                            <?php echo $i + 1; ?>
                        </button>
                    <?php endfor; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8 md:py-12">
        
        <div class="bg-white rounded-3xl shadow-xl p-6 md:p-8 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <div class="inline-block px-3 py-1 bg-purple-100 text-purple-700 text-sm font-semibold rounded-full mb-3">
                        <?php echo htmlspecialchars($ujian['kelas_nama'] ?? $ujian['kelas_id']); ?>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">
                        <?php echo htmlspecialchars($ujian['mata_pelajaran']); ?>
                    </h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($ujian['judul']); ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-red-50 to-orange-50 rounded-xl border border-red-200">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="timeLeft" class="font-bold text-red-600 text-lg"><?php echo $ujian['waktu_pengerjaan']; ?>:00</span>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Progress: <span class="font-bold text-purple-600"><?php echo $current_question + 1; ?></span> dari <?php echo $total_soal; ?></span>
                    <span><?php echo number_format($progress, 0); ?>%</span>
                </div>
                <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div class="progress-fill h-full bg-gradient-to-r from-purple-500 via-blue-500 to-cyan-500 rounded-full"></div>
                </div>
            </div>
        </div>

        <form method="POST" id="mainForm">
            <input type="hidden" name="action" id="actionInput" value="">
            
            <div class="bg-white rounded-3xl shadow-xl p-6 md:p-8 mb-6">
                <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-600 text-white rounded-2xl flex items-center justify-center font-bold text-lg shadow-lg">
                            <?php echo $current_question + 1; ?>
                        </div>
                        
                    </div>
                    <?php if (!empty($answers[$current_question])): ?>
                        <div class="flex items-center gap-2 px-4 py-2 bg-green-100 text-green-700 rounded-xl font-semibold">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Terjawab
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-700 rounded-xl font-semibold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>
                            </svg>
                            Belum dijawab
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-8">
                    <p class="text-gray-800 text-lg leading-relaxed whitespace-pre-line">
                        <?php echo nl2br($current_soal['pertanyaan']); ?>
                    </p>
                </div>

                <div class="space-y-4">
                    <?php 
                    $pilihan = ['a' => $current_soal['pilihan_a'], 'b' => $current_soal['pilihan_b'], 
                                'c' => $current_soal['pilihan_c'], 'd' => $current_soal['pilihan_d']];
                    foreach ($pilihan as $key => $value): 
                        if (empty($value)) continue;
                        $isSelected = $answers[$current_question] == $key;
                    ?>
                        <label class="flex items-start gap-4 p-5 rounded-2xl cursor-pointer transition-all
                            <?php echo $isSelected 
                                ? 'bg-gradient-to-r from-purple-50 to-blue-50 border-2 border-purple-500 shadow-lg' 
                                : 'bg-gray-50 border-2 border-gray-200 hover:border-purple-300 hover:shadow-md'; ?>">
                            <input type="radio" name="answer" value="<?php echo $key; ?>" 
                                <?php echo $isSelected ? 'checked' : ''; ?> class="hidden">
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base transition-all
                                <?php echo $isSelected 
                                    ? 'bg-gradient-to-br from-purple-600 to-blue-600 text-white shadow-md' 
                                    : 'bg-white text-gray-600 border-2 border-gray-300'; ?>">
                                <?php echo strtoupper($key); ?>
                            </div>
                            <div class="flex-1 pt-1.5 text-gray-700 leading-relaxed">
                                <?php echo $value; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                <button type="submit" name="action" value="prev" 
                    class="flex items-center justify-center gap-2 px-6 py-4 bg-white border-2 border-gray-300 text-gray-700 rounded-2xl font-semibold shadow-md hover:shadow-lg transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    <?php echo $current_question == 0 ? 'disabled' : ''; ?>>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Sebelumnya
                </button>
                
                <?php if ($current_question < $total_soal - 1): ?>
                    <button type="submit" name="action" value="next" 
                        class="flex-1 flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-2xl font-semibold shadow-lg hover:shadow-xl transition-all">
                        Selanjutnya
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                <?php else: ?>
                    <button type="button" 
                        class="flex-1 flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-2xl font-semibold shadow-lg hover:shadow-xl transition-all"
                        onclick="showFinishModal()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Selesaikan Ujian
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm hidden items-center justify-center z-50 p-4" id="finishModal">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 text-center mb-3">Selesaikan Ujian?</h3>
            <p class="text-gray-600 text-center mb-6">
                Anda telah menjawab <span class="font-bold text-green-600"><?php echo $jawaban_terisi; ?></span> dari 
                <span class="font-bold text-gray-900"><?php echo $total_soal; ?></span> soal
            </p>
            <div class="flex gap-3">
                <button type="button" 
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-all"
                    onclick="closeFinishModal()">Kembali</button>
                <button type="button" 
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all"
                    onclick="confirmFinish()">Selesaikan</button>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm hidden items-center justify-center z-50 p-4" id="exitModal">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
            <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 text-center mb-3">Keluar dari Ujian?</h3>
            <p class="text-gray-600 text-center mb-6">
                Jawaban yang sudah Anda isi akan tersimpan, tetapi ujian belum selesai
            </p>
            <div class="flex gap-3">
                <button type="button" 
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-all"
                    onclick="closeExitModal()">Batal</button>
                <a href="dashboard_siswa.php" 
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl font-semibold text-center shadow-lg hover:shadow-xl transition-all">Ya, Keluar</a>
            </div>
        </div>
    </div>
    
    <script>
        let timeLeft = <?php echo ($ujian['waktu_pengerjaan'] * 60) - (time() - $_SESSION['exam_start_time']); ?>;
        if (timeLeft < 0) timeLeft = 0;
        let isSubmitting = false;

        function updateTimer() {
            if (timeLeft <= 0) {
                isSubmitting = true;
                document.getElementById('actionInput').value = 'finish';
                document.getElementById('mainForm').submit();
                return;
            }
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timeLeft').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            timeLeft--;
        }
        setInterval(updateTimer, 1000);
        updateTimer();

        function toggleNavPanel() {
            const panel = document.getElementById('navPanel');
            panel.classList.toggle('translate-x-full');
        }

        function navigateTo(index) {
            document.getElementById('navigateInput').value = index;
            isSubmitting = true;
            document.getElementById('navForm').submit();
        }
        
        function showFinishModal() {
            document.getElementById('finishModal').classList.remove('hidden');
            document.getElementById('finishModal').classList.add('flex');
        }
        
        function closeFinishModal() {
            document.getElementById('finishModal').classList.add('hidden');
            document.getElementById('finishModal').classList.remove('flex');
        }

        function confirmFinish() {
            isSubmitting = true;
            document.getElementById('actionInput').value = 'finish';
            document.getElementById('mainForm').submit();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const navPanel = document.getElementById('navPanel');
                if (!navPanel.classList.contains('translate-x-full')) {
                    toggleNavPanel();
                } else {
                    document.getElementById('exitModal').classList.remove('hidden');
                    document.getElementById('exitModal').classList.add('flex');
                }
            }
        });

        function closeExitModal() {
            document.getElementById('exitModal').classList.add('hidden');
            document.getElementById('exitModal').classList.remove('flex');
        }

        document.querySelectorAll('input[name="answer"]').forEach(radio => {
            radio.addEventListener('change', function() {
                setTimeout(() => {
                    isSubmitting = true;
                    document.getElementById('actionInput').value = '';
                    document.getElementById('mainForm').submit();
                }, 250);
            });
        });

        document.querySelectorAll('button[type="submit"]').forEach(btn => {
            btn.addEventListener('click', function() {
                isSubmitting = true;
            });
        });

        document.addEventListener('click', function(e) {
            const navPanel = document.getElementById('navPanel');
            const navButton = e.target.closest('button[onclick="toggleNavPanel()"]');
            if (!navPanel.contains(e.target) && !navButton && !navPanel.classList.contains('translate-x-full')) {
                toggleNavPanel();
            }
        });
    </script>
</body>
</html>