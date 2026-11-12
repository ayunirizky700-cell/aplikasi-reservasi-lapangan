<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// ==========================================
// AMBIL STATISTIK (BAGIAN INI YANG TADI HILANG)
// ==========================================
$total_lapangan = $pdo->query("SELECT COUNT(*) FROM fields")->fetchColumn();
$total_reservasi = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$total_pending = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending' OR payment_status IS NULL")->fetchColumn();
// Mengambil total pendapatan dari total_price pesanan yang sudah PAID (Lunas)
$query_pendapatan = "SELECT SUM(r.total_price) FROM reservations r 
                     JOIN payments p ON r.id = p.reservation_id 
                     WHERE p.payment_status = 'paid'";
$total_pendapatan = $pdo->query($query_pendapatan)->fetchColumn();

// ==========================================
// AMBIL 5 RESERVASI TERBARU
// ==========================================
$query_res = "SELECT r.*, f.name as lapangan_name, u.full_name as customer_name 
              FROM reservations r 
              JOIN fields f ON r.field_id = f.id 
              LEFT JOIN users u ON r.customer_id = u.id
              ORDER BY r.created_at DESC LIMIT 5";
$reservasi_terbaru = $pdo->query($query_res)->fetchAll();

// ==========================================
// AMBIL 5 PEMBAYARAN TERBARU
// ==========================================
$query_pay = "SELECT p.*, r.reservation_code FROM payments p 
              LEFT JOIN reservations r ON p.reservation_id = r.id 
              ORDER BY p.payment_date DESC LIMIT 5";
$pembayaran_terbaru = $pdo->query($query_pay)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SportArena</title>
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
        .main { padding: 30px 5%; max-width: 1300px; margin: auto; }
        .header-title { font-size: 24px; margin-bottom: 25px; color: #1a2332; font-weight: 600; }
        
        /* STATISTIK CARDS */
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 20px; transition: 0.3s; border: 1px solid #eee; text-decoration: none; color: inherit; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .stat-icon { width: 65px; height: 65px; border-radius: 15px; display: flex; justify-content: center; align-items: center; font-size: 32px; }
        .stat-icon.blue { background: #e3f2fd; }
        .stat-icon.green { background: #e8f5e9; }
        .stat-icon.orange { background: #fff3e0; }
        .stat-icon.purple { background: #f3e5f5; }
        .stat-info h3 { font-size: 13px; color: #777; margin-bottom: 5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;}
        .stat-info h2 { font-size: 28px; color: #1a2332; font-weight: 700; }

        /* TABLE CARDS */
        .grid-tables { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .card { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #eee; overflow: hidden; }
        .card-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #fafbfc;}
        .card-header h3 { font-size: 16px; color: #1a2332; }
        .card-header a { font-size: 13px; color: #4fc3f7; text-decoration: none; font-weight: 600;}
        .card-header a:hover { text-decoration: underline; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { color: #777; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        
        /* BADGES */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .badge-booked { background: #e3f2fd; color: #0d47a1; }
        .badge-completed { background: #e8f5e9; color: #2e7d32; }
        .badge-cancelled { background: #fce4ec; color: #c62828; }
        .badge-pending { background: #fff3e0; color: #e65100; }
        .badge-paid { background: #e8f5e9; color: #2e7d32; }
        
        .empty { text-align: center; padding: 30px; color: #999; font-size: 14px;}
        
        @media (max-width: 900px) { .grid-tables { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .navbar { flex-direction: column; gap: 15px; } }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <div class="navbar">
        <a href="dashboard.php" class="logo">🏟️ Sport<span>Arena</span> (Admin)</a>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="kelola_lapangan.php">Lapangan</a>
            <a href="kelola_reservasi.php">Reservasi</a>
            <a href="kelola_pembayaran.php">Pembayaran</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 class="header-title">📊 Ringkasan Sistem</h2>
        
        <!-- KARTU STATISTIK -->
        <div class="grid-stats">
            <!-- Kotak Lapangan (Bisa diklik) -->
            <a href="kelola_lapangan.php" class="stat-card">
                <div class="stat-icon blue">🏟️</div>
                <div class="stat-info">
                    <h3>Total Lapangan</h3>
                    <h2><?= $total_lapangan ?></h2>
                </div>
            </a>
            
            <!-- Kotak Reservasi -->
            <a href="kelola_reservasi.php" class="stat-card">
                <div class="stat-icon green">📅</div>
                <div class="stat-info">
                    <h3>Total Reservasi</h3>
                    <h2><?= $total_reservasi ?></h2>
                </div>
            </a>

            <!-- Kotak Pembayaran -->
            <a href="kelola_pembayaran.php" class="stat-card">
                <div class="stat-icon orange">⏳</div>
                <div class="stat-info">
                    <h3>Pembayaran Pending</h3>
                    <h2><?= $total_pending ?></h2>
                </div>
            </a>

            <!-- Kotak Pendapatan -->
            <a href="kelola_pembayaran.php" class="stat-card">
                <div class="stat-icon purple">💰</div>
                <div class="stat-info">
                    <h3>Total Pendapatan</h3>
                    <h2>Rp <?= number_format($total_pendapatan ?? 0, 0, ',', '.') ?></h2>
                </div>
            </a>
        </div>

        <!-- TABEL TERBARU -->
        <div class="grid-tables">
            
            <!-- Tabel Reservasi -->
            <div class="card">
                <div class="card-header">
                    <h3>📅 Reservasi Terbaru</h3>
                    <a href="kelola_reservasi.php">Lihat Semua &rarr;</a>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Pelanggan</th>
                                <th>Lapangan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($reservasi_terbaru) > 0): ?>
                                <?php foreach ($reservasi_terbaru as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['customer_name'] ?? 'User Dihapus') ?></td>
                                    <td><?= htmlspecialchars($r['lapangan_name']) ?></td>
                                    <td><span class="badge badge-<?= $r['status'] ?>"><?= strtoupper($r['status']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="empty">Belum ada reservasi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Pembayaran -->
            <div class="card">
                <div class="card-header">
                    <h3>💰 Pembayaran Terbaru</h3>
                    <a href="kelola_pembayaran.php">Lihat Semua &rarr;</a>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pembayaran_terbaru) > 0): ?>
                                <?php foreach ($pembayaran_terbaru as $p): ?>
                                <?php 
                                    $status_asli = $p['payment_status'];
                                    $status_tampil = empty($status_asli) ? 'pending' : strtolower($status_asli); 
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['reservation_code'] ?? '-') ?></strong></td>
                                    <td>Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                                    <td><span class="badge badge-<?= $status_tampil ?>"><?= strtoupper($status_tampil) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="empty">Belum ada pembayaran.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>