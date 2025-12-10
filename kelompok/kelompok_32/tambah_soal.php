<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mata_pelajaran_id = intval($_POST['mata_pelajaran_id']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $pilihan_a = mysqli_real_escape_string($conn, $_POST['pilihan_a']);
    $pilihan_b = mysqli_real_escape_string($conn, $_POST['pilihan_b']);
    $pilihan_c = mysqli_real_escape_string($conn, $_POST['pilihan_c']);
    $pilihan_d = mysqli_real_escape_string($conn, $_POST['pilihan_d']);
    $jawaban_benar = $_POST['jawaban_benar'];
    
    // Insert ujian
    $query_ujian = "INSERT INTO ujian (mata_pelajaran_id, judul, jumlah_soal, waktu_pengerjaan) 
                    VALUES ($mata_pelajaran_id, '$judul', 1, 30)";
    mysqli_query($conn, $query_ujian);
    $ujian_id = mysqli_insert_id($conn);
    
    // Insert soal
    $query_soal = "INSERT INTO soal (ujian_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar) 
                   VALUES ($ujian_id, '$pertanyaan', '$pilihan_a', '$pilihan_b', '$pilihan_c', '$pilihan_d', '$jawaban_benar')";
    
    if (mysqli_query($conn, $query_soal)) {
        $success = "Soal berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan soal: " . mysqli_error($conn);
    }
}

$result_mapel = mysqli_query($conn, "SELECT * FROM mata_pelajaran ORDER BY nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Soal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 20px; color: #333; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { min-height: 100px; resize: vertical; }
        .radio-group { display: flex; gap: 20px; }
        .radio-item { display: flex; align-items: center; gap: 5px; }
        button { width: 100%; padding: 12px; background: #007bff; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tambah Soal Ujian</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Mata Pelajaran</label>
                <select name="mata_pelajaran_id" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    <?php while ($mapel = mysqli_fetch_assoc($result_mapel)): ?>
                        <option value="<?php echo $mapel['id']; ?>"><?php echo $mapel['nama']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Judul Ujian</label>
                <input type="text" name="judul" placeholder="Contoh: Matematika Dasar" required>
            </div>
            
            <div class="form-group">
                <label>Pertanyaan</label>
                <textarea name="pertanyaan" placeholder="Tuliskan pertanyaan..." required></textarea>
            </div>
            
            <div class="form-group">
                <label>Pilihan A</label>
                <input type="text" name="pilihan_a" required>
            </div>
            
            <div class="form-group">
                <label>Pilihan B</label>
                <input type="text" name="pilihan_b" required>
            </div>
            
            <div class="form-group">
                <label>Pilihan C</label>
                <input type="text" name="pilihan_c" required>
            </div>
            
            <div class="form-group">
                <label>Pilihan D</label>
                <input type="text" name="pilihan_d" required>
            </div>
            
            <div class="form-group">
                <label>Jawaban Benar</label>
                <div class="radio-group">
                    <div class="radio-item">
                        <input type="radio" name="jawaban_benar" value="a" id="a" required>
                        <label for="a">A</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="jawaban_benar" value="b" id="b">
                        <label for="b">B</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="jawaban_benar" value="c" id="c">
                        <label for="c">C</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="jawaban_benar" value="d" id="d">
                        <label for="d">D</label>
                    </div>
                </div>
            </div>
            
            <button type="submit">Tambah Soal</button>
        </form>
    </div>
</body>
</html>