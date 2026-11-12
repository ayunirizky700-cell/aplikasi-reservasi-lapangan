<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$sport = isset($_GET['sport']) ? $_GET['sport'] : '';

$pesan = '';
$tipe_pesan = '';

// Proses Pesan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pesan'])) {
    $field_id = $_POST['field_id'];
    $tgl = $_POST['booking_date'];
    $mulai = $_POST['start_time'];
    $selesai = $_POST['end_time'];

    // Hitung total
    $stmt = $pdo->prepare("SELECT price_per_hour FROM fields WHERE id = ?");
    $stmt->execute([$field_id]);
    $harga = $stmt->fetchColumn();

    $start = new DateTime($mulai);
    $end = new DateTime($selesai);
    $durasi = $end->diff($start)->h;
    if ($durasi <= 0)
        $durasi = 1;
    $total = $harga * $durasi;

    $kode = 'RES' . date('Ymd') . rand(100, 999);

    try {
        $stmt = $pdo->prepare("INSERT INTO reservations (reservation_code, customer_id, field_id, reservation_date, start_time, end_time, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'booked')");
        $stmt->execute([$kode, $user_id, $field_id, $tgl, $mulai, $selesai, $total]);
        $pesan = "✅ Pesanan berhasil! Kode: $kode. Silakan lanjut ke menu Pembayaran.";
        $tipe_pesan = "success";
    } catch (Exception $e) {
        $pesan = "❌ Gagal memesan: " . $e->getMessage();
        $tipe_pesan = "error";
    }
}

// Ambil lapangan berdasarkan jenis olahraga yang dipilih
$stmt = $pdo->prepare("SELECT * FROM fields WHERE sport_type = ?");
$stmt->execute([$sport]);
$lapangan_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan <?= htmlspecialchars($sport) ?> - SportArena</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #333; }
        .navbar { background: #1a2332; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .logo { color: #fff; font-size: 20px; font-weight: bold; text-decoration: none; }
        .logo span { color: #4fc3f7; }
        .nav-links { display: flex; gap: 10px; }
        .nav-links a { color: #aab8c5; text-decoration: none; font-size: 14px; padding: 8px 15px; border-radius: 8px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(79, 195, 247, 0.15); color: #4fc3f7; }
        .main { padding: 30px 5%; max-width: 1200px; margin: auto; }
        .header-title { font-size: 24px; margin-bottom: 20px; color: #1a2332; display: flex; align-items: center; gap: 10px; }
        .header-title span { color: #4fc3f7; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .card { background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eee; }
        
        /* CSS UNTUK GAMBAR */
        .card-img { width: 100%; height: 180px; object-fit: cover; border-bottom: 1px solid #eee; display: block; }
        .card-img-placeholder { width: 100%; height: 180px; background: #e0e0e0; display: flex; justify-content: center; align-items: center; font-size: 50px; border-bottom: 1px solid #eee; }
        
        .card-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { font-size: 18px; margin: 0; color: #1a2332; }
        .card-body { padding: 20px; }
        .price-box { background: #e3f2fd; color: #0d47a1; padding: 10px 15px; border-radius: 8px; font-weight: bold; font-size: 18px; display: inline-block; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #555; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .form-group input:focus { border-color: #4fc3f7; outline: none; }
        
        .btn { width: 100%; padding: 12px; background: #4caf50; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn:hover { background: #43a047; }
        
        .empty { text-align: center; padding: 50px; background: #fff; border-radius: 15px; color: #999; }
        @media (max-width: 768px) { .navbar { flex-direction: column; gap: 15px; } }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="dashboard_users.php" class="logo">🏟️ Sport<span>Arena</span></a>
        <div class="nav-links">
            <a href="dashboard_users.php">Dashboard</a>
            <a href="pesanan.php" class="active">Pesanan</a>
            <a href="pembayaran.php">Pembayaran</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="profil.php">Profil</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 class="header-title">Daftar Lapangan <span><?= htmlspecialchars($sport) ?></span></h2>

        <?php if ($pesan): ?>
            <div class="alert <?= $tipe_pesan ?>"><?= $pesan ?></div>
        <?php endif; ?>

        <?php if (count($lapangan_list) > 0): ?>
            <div class="grid">
                <?php foreach ($lapangan_list as $lap): ?>
                    <div class="card">
                        
                        <!-- BAGIAN MENAMPILKAN GAMBAR -->
                        <?php if (!empty($lap['gambar']) && file_exists('../uploads/lapangan/' . $lap['gambar'])): ?>
                            <img src="../uploads/lapangan/<?= htmlspecialchars($lap['gambar']) ?>" class="card-img" alt="Foto Lapangan">
                        <?php else: ?>
                            <div class="card-img-placeholder">🏟️</div>
                        <?php endif; ?>
                        <!-- BATAS GAMBAR -->

                        <div class="card-header">
                            <h3><?= htmlspecialchars($lap['name']) ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="price-box">Rp <?= number_format($lap['price_per_hour'], 0, ',', '.') ?> / jam</div>

                            <form method="POST">
                                <input type="hidden" name="field_id" value="<?= $lap['id'] ?>">

                                <div class="form-group">
                                    <label>📅 Tanggal Main</label>
                                    <input type="date" name="booking_date" required min="<?= date('Y-m-d') ?>">
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div class="form-group">
                                        <label>⏰ Jam Mulai</label>
                                        <input type="time" name="start_time" required>
                                    </div>
                                    <div class="form-group">
                                        <label>⏰ Jam Selesai</label>
                                        <input type="time" name="end_time" required>
                                    </div>
                                </div>

                                <button type="submit" name="pesan" class="btn">Pesan Sekarang</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <h2 style="font-size: 50px; margin-bottom: 10px;">🏟️</h2>
                <p>Belum ada lapangan untuk kategori <strong><?= htmlspecialchars($sport) ?></strong> saat ini.</p>
                <br>
                <a href="dashboard_users.php" style="color: #4fc3f7; text-decoration: none;">&larr; Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>