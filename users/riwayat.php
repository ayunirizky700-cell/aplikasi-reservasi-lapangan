<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }

$user_id = $_SESSION['user_id'];
$notif = '';

// Proses Pembatalan Pesanan
if (isset($_GET['batal'])) {
    $id_batal = $_GET['batal'];
    
    // Pastikan yang dibatalkan benar-benar pesanan milik user ini dan statusnya masih 'booked'
    $cek = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND customer_id = ? AND status = 'booked'");
    $cek->execute([$id_batal, $user_id]);
    
    if ($cek->rowCount() > 0) {
        $update = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
        if ($update->execute([$id_batal])) {
            $notif = '<div class="alert alert-success">✅ Pesanan berhasil dibatalkan. Silakan cek riwayat di bawah.</div>';
        }
    } else {
        $notif = '<div class="alert alert-danger">❌ Gagal membatalkan. Pesanan tidak ditemukan atau sudah diproses.</div>';
    }
}

// Ambil semua riwayat pesanan
$stmt = $pdo->prepare("SELECT r.*, f.name as nama_lapangan FROM reservations r JOIN fields f ON r.field_id = f.id WHERE r.customer_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$user_id]);
$riwayat = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - SportArena</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #333; }
        
        /* NAVBAR */
        .navbar { background: #1a2332; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .logo { color: #fff; font-size: 20px; font-weight: bold; text-decoration: none; }
        .logo span { color: #4fc3f7; }
        .nav-links { display: flex; gap: 10px; }
        .nav-links a { color: #aab8c5; text-decoration: none; font-size: 14px; padding: 8px 15px; border-radius: 8px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(79,195,247,0.15); color: #4fc3f7; }
        
        /* MAIN */
        .main { padding: 30px 5%; max-width: 1000px; margin: auto; }
        .header-title { font-size: 24px; margin-bottom: 20px; color: #1a2332; display: flex; justify-content: space-between; align-items: center; }
        
        /* NOTIFIKASI */
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* CARD & TABLE */
        .card { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f8f9fa; font-size: 12px; text-transform: uppercase; color: #666; letter-spacing: 0.5px; font-weight: 600; }
        tr:hover { background: #fcfcfc; }
        
        /* BADGE STATUS */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; display: inline-block; }
        .bg-booked { background: #e3f2fd; color: #0d47a1; }
        .bg-completed { background: #e8f5e9; color: #2e7d32; }
        .bg-cancelled { background: #fce4ec; color: #c62828; }
        
        /* TOMBOL BATAL */
        .btn-batal { padding: 6px 12px; background: #ffebee; color: #c62828; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: bold; transition: 0.3s; border: 1px solid #ffcdd2; display: inline-block; }
        .btn-batal:hover { background: #c62828; color: white; }
        
        .empty { padding: 50px; text-align: center; color: #999; }
        .empty-icon { font-size: 50px; margin-bottom: 10px; display: block; }
        
        @media (max-width: 768px) { 
            .navbar { flex-direction: column; gap: 15px; } 
            table { font-size: 12px; } 
            th, td { padding: 10px; } 
            .header-title { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <div class="navbar">
        <a href="dashboard_users.php" class="logo">🏟️ Sport<span>Arena</span></a>
        <div class="nav-links">
            <a href="dashboard_users.php">Dashboard</a>
            <a href="pesanan.php">Pesanan</a>
            <a href="pembayaran.php">Pembayaran</a>
           <a href="riwayat.php"class="active"                 >Riwayat</a>
            <a href="profil.php">Profil</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 class="header-title">📋 Riwayat Pemesanan</h2>
        
        <!-- Tampilkan Notifikasi -->
        <?= $notif ?>

        <div class="card">
            <?php if (count($riwayat) > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Lapangan</th>
                            <th>Jadwal Main</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['reservation_code']) ?></strong></td>
                            <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($r['reservation_date'])) ?><br>
                                <span style="font-size: 12px; color: #666;"><?= $r['start_time'] ?> - <?= $r['end_time'] ?></span>
                            </td>
                            <td>Rp <?= number_format($r['total_price'], 0, ',', '.') ?></td>
                            <td>
                                <?php 
                                    $status = strtolower($r['status']);
                                    $badge_class = 'bg-booked';
                                    if ($status == 'completed') $badge_class = 'bg-completed';
                                    elseif ($status == 'cancelled') $badge_class = 'bg-cancelled';
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= strtoupper($status) ?></span>
                            </td>
                            <td>
                                <?php if ($status == 'booked'): ?>
                                    <a href="riwayat.php?batal=<?= $r['id'] ?>" class="btn-batal" onclick="return confirm('Yakin ingin membatalkan pesanan ini?')">Batalkan</a>
                                <?php else: ?>
                                    <span style="color: #ccc; font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty">
                    <span class="empty-icon">📭</span>
                    <h3>Belum ada riwayat pesanan</h3>
                    <p style="margin-top: 10px; font-size: 14px;">Yuk pesan lapangan sekarang di menu Dashboard.</p>
                    <br>
                    <a href="dashboard_users.php" style="background: #4fc3f7; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold;">Pesan Lapangan</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>