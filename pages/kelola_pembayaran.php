<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$pesan = '';

// 1. PROSES KLIK TOMBOL (TERIMA / TOLAK)
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id_pembayaran = $_GET['id'];
    $status_baru = $_GET['status']; 

    try {
        // Update tabel payments
        $stmt = $pdo->prepare("UPDATE payments SET payment_status = ? WHERE id = ?");
        $stmt->execute([$status_baru, $id_pembayaran]);

        // Jika diterima, update status reservasi jadi completed
        if ($status_baru == 'paid') {
            $stmt2 = $pdo->prepare("SELECT reservation_id FROM payments WHERE id = ?");
            $stmt2->execute([$id_pembayaran]);
            $res_id = $stmt2->fetchColumn();
            if ($res_id) {
                $stmt3 = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE id = ?");
                $stmt3->execute([$res_id]);
            }
        }
        $pesan = '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">✅ Berhasil diproses!</div>';
    } catch (Exception $e) {
        $pesan = '<div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// 2. AMBIL DATA
$query = "SELECT p.*, r.reservation_code FROM payments p LEFT JOIN reservations r ON p.reservation_id = r.id ORDER BY p.payment_date DESC";
$pembayaran = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pembayaran - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #333; }
        .navbar { background: #1a2332; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .logo { color: #fff; font-size: 20px; font-weight: bold; text-decoration: none; }
        .logo span { color: #4fc3f7; }
        .nav-links { display: flex; gap: 10px; overflow-x: auto; white-space: nowrap; padding-bottom: 5px; }
        .nav-links a { color: #aab8c5; text-decoration: none; font-size: 14px; padding: 8px 15px; border-radius: 8px; }
        .nav-links a:hover, .nav-links a.active { background: rgba(79,195,247,0.15); color: #4fc3f7; }
        
        .main { padding: 30px 5%; max-width: 1200px; margin: auto; }
        .card { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: #fff3e0; color: #e65100; }
        .badge-paid { background: #e8f5e9; color: #2e7d32; }
        .badge-failed { background: #fce4ec; color: #c62828; }
        
        .btn { padding: 8px 15px; border-radius: 6px; font-size: 12px; font-weight: bold; text-decoration: none; display: inline-block; color: white; margin-right: 5px; margin-bottom: 5px;}
        .btn-primary { background: #4fc3f7; } /* Tombol Lihat Bukti */
        .btn-success { background: #4caf50; }
        .btn-danger { background: #f44336; }

        @media (max-width: 768px) { 
            .navbar { flex-direction: column; gap: 15px; } 
            table { font-size: 12px; } 
            th, td { padding: 8px; } 
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php" class="logo">🏟️ Sport<span>Arena</span> (Admin)</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="kelola_lapangan.php">Lapangan</a>
            <a href="kelola_reservasi.php">Reservasi</a>
            <a href="kelola_pembayaran.php" class="active">Pembayaran</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 style="margin-bottom: 20px;">💰 Kelola Pembayaran</h2>
        <?= $pesan ?>
        
        <div class="card">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode Pesanan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pembayaran) > 0): ?>
                            <?php foreach ($pembayaran as $p): ?>
                            <?php 
                                // LOGIKA: Jika kosong, anggap 'pending'
                                $status_asli = $p['payment_status'];
                                $status_tampil = empty($status_asli) ? 'pending' : strtolower($status_asli); 
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($p['payment_date'])) ?></td>
                                <td><strong><?= htmlspecialchars($p['reservation_code'] ?? '-') ?></strong></td>
                                <td>Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge badge-<?= $status_tampil ?>">
                                        <?= strtoupper($status_tampil) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status_tampil == 'pending'): ?>
                                        
                                        <!-- TOMBOL LIHAT BUKTI (Hanya muncul jika ada file yang di-upload) -->
                                        <?php if (!empty($p['bukti_transfer'])): ?>
                                            <a href="../uploads/<?= $p['bukti_transfer'] ?>" target="_blank" class="btn btn-primary">🖼️ Lihat Bukti</a>
                                        <?php endif; ?>

                                        <!-- Tombol Terima / Tolak -->
                                        <a href="kelola_pembayaran.php?id=<?= $p['id'] ?>&status=paid" class="btn btn-success">✅ Terima</a>
                                        <a href="kelola_pembayaran.php?id=<?= $p['id'] ?>&status=failed" class="btn btn-danger">❌ Tolak</a>
                                    
                                    <?php else: ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">Belum ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>