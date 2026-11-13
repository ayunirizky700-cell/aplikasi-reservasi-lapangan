<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $res_id = $_POST['reservation_id'];
    $amount = $_POST['amount'];
    $method = $_POST['method'];
    $bukti = '';

    // Proses Upload Bukti Transfer
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $bukti = time() . '_' . $_FILES['bukti_transfer']['name'];
        $target = "../uploads/" . basename($bukti);
        move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $target);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO payments (reservation_id, payment_date, amount, payment_method, payment_status, bukti_transfer) VALUES (?, NOW(), ?, ?, 'pending', ?)");
        $stmt->execute([$res_id, $amount, $method, $bukti]);
        $msg = '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">✅ Pembayaran berhasil dikirim! Menunggu konfirmasi admin.</div>';
    } catch (Exception $e) {
        $msg = '<div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// Ambil pesanan yang belum dibayar
$query_tagihan = "SELECT r.*, f.name as lapangan FROM reservations r JOIN fields f ON r.field_id = f.id WHERE r.customer_id = " . $_SESSION['user_id'] . " AND r.id NOT IN (SELECT reservation_id FROM payments WHERE reservation_id IS NOT NULL)";
$stmt = $pdo->query($query_tagihan);
$tagihan = $stmt->fetchAll();

/// Riwayat pembayaran (HANYA MILIK USER YANG LOGIN)
$query_riwayat = "SELECT p.*, r.reservation_code 
                  FROM payments p 
                  JOIN reservations r ON p.reservation_id = r.id 
                  WHERE r.customer_id = ? 
                  ORDER BY p.payment_date DESC";
$stmt2 = $pdo->prepare($query_riwayat);
$stmt2->execute([$user_id]); // <-- Filter berdasarkan user yang login
$riwayat = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - SportArena</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        .navbar {
            background: #1a2332;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            text-decoration: none;
        }

        .logo span {
            color: #4fc3f7;
        }

        .nav-links {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 5px;
        }

        .nav-links a {
            color: #aab8c5;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 15px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(79, 195, 247, 0.15);
            color: #4fc3f7;
        }

        .main {
            padding: 30px 5%;
            max-width: 1000px;
            margin: auto;
        }

        .header-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #1a2332;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
        }

        .card h3 {
            margin-bottom: 20px;
            font-size: 18px;
            color: #1a2332;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: #4fc3f7;
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            font-size: 15px;
        }

        .btn:hover {
            background: #43a047;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }

        .bg-pending {
            background: #fff3e0;
            color: #e65100;
        }

        .bg-paid {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .bg-failed {
            background: #fce4ec;
            color: #c62828;
        }

        /* Kotak QRIS */
        #qrisBox {
            display: none;
            text-align: center;
            background: #f8f9fa;
            border: 2px dashed #4fc3f7;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        #qrisBox img {
            max-width: 200px;
            margin-bottom: 10px;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="dashboard_users.php" class="logo">🏟️ Sport<span>Arena</span></a>
        <div class="nav-links">
            <a href="dashboard_users.php">Dashboard</a>
            <a href="pesanan.php">Pesanan</a>
            <a href="pembayaran.php" class="active">Pembayaran</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="profil.php">Profil</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 class="header-title">💰 Pembayaran</h2>
        <?= $msg ?>

        <div class="grid">
            <!-- Form Bayar -->
            <div class="card">
                <h3>Bayar Tagihan</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Pilih Pesanan</label>
                        <select name="reservation_id" required>
                            <option value="">-- Pilih Pesanan --</option>
                            <?php foreach ($tagihan as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= $t['reservation_code'] ?> - <?= $t['lapangan'] ?> (Rp
                                    <?= number_format($t['total_price'], 0, ',', '.') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jumlah Bayar (Rp)</label>
                        <input type="number" name="amount" required placeholder="Masukkan nominal">
                    </div>

                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="method" id="metode" required onchange="cekMetode()">
                            <option value="">-- Pilih Metode --</option>
                            <optgroup label="🏦 Bank Transfer">
                                <option value="Transfer BCA">Transfer BCA</option>
                                <option value="Transfer Mandiri">Transfer Mandiri</option>
                            </optgroup>
                            <optgroup label="📱 E-Wallet">
                                <option value="DANA">DANA</option>
                                <option value="OVO">OVO</option>
                            </optgroup>
                            <optgroup label="💳 Lainnya">
                                <option value="QRIS">QRIS (Scan Barcode)</option>
                                <option value="Tunai">Tunai (Bayar di Tempat)</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- BOX QRIS (Hanya muncul kalau pilih QRIS) -->
                    <div id="qrisBox">
                        <p style="font-size: 13px; font-weight: bold; margin-bottom: 10px;">Silakan Scan QRIS di bawah
                            ini:</p>
                        <img src="../uploads/qris.png" alt="QRIS">
                        <p style="font-size: 11px; color: #666;">(Setelah bayar, upload bukti di bawah)</p>
                    </div>

                    <!-- UPLOAD BUKTI -->
                    <div class="form-group" id="uploadBox" style="display: none;">
                        <label>Upload Bukti Pembayaran</label>
                        <input type="file" name="bukti_transfer" accept="image/*">
                        <small style="color: #999; font-size: 11px;">Wajib diisi jika memilih
                            Transfer/QRIS/E-Wallet</small>
                    </div>

                    <button type="submit" class="btn">Kirim Pembayaran</button>
                </form>
            </div>

            <!-- Riwayat -->
            <div class="card">
                <h3>Riwayat Pembayaran</h3>
                <?php if (count($riwayat) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayat as $r): ?>
                                    <tr>
                                        <td><?= date('d/m/y', strtotime($r['payment_date'])) ?></td>
                                        <td>Rp <?= number_format($r['amount'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($r['payment_method']) ?></td>
                                        <td><span
                                                class="badge bg-<?= strtolower($r['payment_status']) ?>"><?= strtoupper($r['payment_status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px 0;">Belum ada riwayat pembayaran.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function cekMetode() {
            var metode = document.getElementById('metode').value;
            var qrisBox = document.getElementById('qrisBox');
            var uploadBox = document.getElementById('uploadBox');

            if (metode === 'QRIS') {
                qrisBox.style.display = 'block';
                uploadBox.style.display = 'block';
            } else if (metode === 'Tunai') {
                qrisBox.style.display = 'none';
                uploadBox.style.display = 'none';
            } else if (metode !== '') {
                qrisBox.style.display = 'none';
                uploadBox.style.display = 'block';
            } else {
                qrisBox.style.display = 'none';
                uploadBox.style.display = 'none';
            }
        }
    </script>
</body>

</html>