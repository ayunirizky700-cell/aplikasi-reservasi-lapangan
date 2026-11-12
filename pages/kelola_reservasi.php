<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Ambil semua data reservasi
$query = "SELECT r.*, f.name as lapangan_name, u.full_name as customer_name 
          FROM reservations r 
          JOIN fields f ON r.field_id = f.id 
          LEFT JOIN users u ON r.customer_id = u.id
          ORDER BY r.created_at DESC";
$reservasi = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Reservasi - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #333; }
        .navbar { background: #1a2332; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .logo { color: #fff; font-size: 20px; font-weight: bold; text-decoration: none; }
        .logo span { color: #4fc3f7; }
        .nav-links { display: flex; gap: 10px; }
        .nav-links a { color: #aab8c5; text-decoration: none; font-size: 14px; padding: 8px 15px; border-radius: 8px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(79,195,247,0.15); color: #4fc3f7; }
        
        .main { padding: 30px 5%; max-width: 1200px; margin: auto; }
        .header-title { font-size: 24px; margin-bottom: 25px; color: #1a2332; }
        
        .card { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase;}
        .badge-booked { background: #e3f2fd; color: #0d47a1; }
        .badge-completed { background: #e8f5e9; color: #2e7d32; }
        .badge-cancelled { background: #fce4ec; color: #c62828; }
    </style>
</head>
<body>
   <div class="navbar">
        <a href="dashboard.php" class="logo">🏟️ Sport<span>Arena</span> (Admin)</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="kelola_lapangan.php">Lapangan</a>
            <a href="kelola_reservasi.php" class="active">Reservasi</a>
            <a href="kelola_pembayaran.php">Pembayaran</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 class="header-title">📅 Daftar Semua Reservasi</h2>
        <div class="card">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pelanggan</th>
                            <th>Lapangan</th>
                            <th>Tanggal Main</th>
                            <th>Waktu</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($reservasi) > 0): ?>
                            <?php foreach ($reservasi as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['reservation_code']) ?></strong></td>
                                <td><?= htmlspecialchars($r['customer_name'] ?? 'User Dihapus') ?></td>
                                <td><?= htmlspecialchars($r['lapangan_name']) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['reservation_date'])) ?></td>
                                <td><?= $r['start_time'] ?> - <?= $r['end_time'] ?></td>
                                <td>Rp <?= number_format($r['total_price'], 0, ',', '.') ?></td>
                                <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #999;">Belum ada data reservasi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>