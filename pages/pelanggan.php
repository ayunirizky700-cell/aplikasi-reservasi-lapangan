<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// ==========================================
// AMBIL DATA PELANGGAN
// ==========================================
$customers = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();

// Statistik
$total = count($customers);
$total_admin = $pdo->query("SELECT COUNT(*) FROM customers WHERE role = 'admin'")->fetchColumn();
$total_user = $pdo->query("SELECT COUNT(*) FROM customers WHERE role = 'user'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
        }

        /* ==================== NAVBAR ==================== */
        .navbar {
            background: #1a2332;
            color: #fff;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 65px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar .logo {
            font-size: 20px;
            font-weight: 700;
        }

        .navbar .logo span {
            color: #4fc3f7;
        }

        .navbar .nav-links {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .navbar .nav-links a {
            color: #aab8c5;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: 0.3s;
            font-size: 14px;
        }

        .navbar .nav-links a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .navbar .nav-links a.active {
            background: rgba(79, 195, 247, 0.15);
            color: #4fc3f7;
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .navbar .user-info .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #4fc3f7;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }

        .navbar .user-info .logout-btn {
            color: #aab8c5;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 6px;
            transition: 0.3s;
            font-size: 14px;
        }

        .navbar .user-info .logout-btn:hover {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        /* ==================== MAIN ==================== */
        .main {
            padding: 25px 35px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* HEADER */
        .header {
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 600;
            color: #1a2332;
        }

        .header h1 small {
            font-size: 14px;
            font-weight: 400;
            color: #6a7a8a;
            display: block;
            margin-top: 2px;
        }

        /* STATS MINI */
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-mini {
            background: #fff;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #4fc3f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-mini .info h3 {
            font-size: 12px;
            color: #6a7a8a;
            font-weight: 400;
            margin-bottom: 3px;
        }

        .stat-mini .info .number {
            font-size: 24px;
            font-weight: 700;
            color: #1a2332;
        }

        .stat-mini .icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-mini.border-blue {
            border-left-color: #2196f3;
        }

        .stat-mini.border-green {
            border-left-color: #4caf50;
        }

        .stat-mini.border-orange {
            border-left-color: #ff9800;
        }

        .stat-mini .icon-box.blue {
            background: #e3f2fd;
        }

        .stat-mini .icon-box.green {
            background: #e8f5e9;
        }

        .stat-mini .icon-box.orange {
            background: #fff3e0;
        }

        /* CARD */
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1a2332;
        }

        .card-body {
            padding: 15px 20px 20px;
            overflow-x: auto;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #f5f5f5;
        }

        th {
            color: #6a7a8a;
            font-weight: 500;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        /* BADGE ROLE */
        .badge-role {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-role.admin {
            background: #e3f2fd;
            color: #0d47a1;
        }

        .badge-role.user {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .text-muted {
            color: #999;
        }

        .text-center {
            text-align: center;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-wrap: wrap;
                height: auto;
                padding: 10px 15px;
                gap: 10px;
            }

            .navbar .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .main {
                padding: 15px;
            }

            .stats-mini {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 6px 4px;
            }
        }
    </style>
</head>

<body>

    <!-- ==================== NAVBAR ==================== -->
    <div class="navbar">
        <div class="logo">🏟️ Sport<span>Arena</span></div>
        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="lapangan.php">🏟️ Lapangan</a>
            <a href="reservasi.php">📅 Reservasi</a>
            <a href="kelola_pembayaran.php">💰 Pembayaran</a>
            <a href="pelanggan.php" class="active">👥 Pelanggan</a>
            <a href="profil.php">👤 Profil</a>
        </div>
        <div class="user-info">
            <span style="color: #aab8c5; font-size: 13px;"><?= date('d/m/Y') ?></span>

            <a href="../logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </div>

    <!-- ==================== MAIN ==================== -->
    <div class="main">

        <!-- HEADER -->
        <div class="header">
            <h1>
                👥 Kelola Pelanggan
                <small>Kelola semua pelanggan yang terdaftar di sistem</small>
            </h1>
        </div>

        <!-- STATISTIK -->
        <div class="stats-mini">
            <div class="stat-mini border-blue">
                <div class="info">
                    <h3>Total Pelanggan</h3>
                    <div class="number"><?= $total ?></div>
                </div>
                <div class="icon-box blue">👥</div>
            </div>
            <div class="stat-mini border-green">
                <div class="info">
                    <h3>User</h3>
                    <div class="number"><?= $total_user ?></div>
                </div>
                <div class="icon-box green">👤</div>
            </div>
            <div class="stat-mini border-orange">
                <div class="info">
                    <h3>Admin</h3>
                    <div class="number"><?= $total_admin ?></div>
                </div>
                <div class="icon-box orange">👑</div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Daftar Pelanggan</h3>
                <span class="text-muted">Total: <?= $total ?> pelanggan</span>
            </div>
            <div class="card-body">
                <?php if (count($customers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>No HP</th>
                                <th>Role</th>
                                <th>Bergabung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td>#<?= $c['id'] ?></td>
                                    <td><?= htmlspecialchars($c['username']) ?></td>
                                    <td><?= htmlspecialchars($c['name']) ?></td>
                                    <td><?= htmlspecialchars($c['email']) ?></td>
                                    <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge-role <?= $c['role'] ?>">
                                            <?= strtoupper($c['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-muted" style="padding: 30px 0;">
                        <span style="font-size: 40px; display: block;">👥</span>
                        Belum ada pelanggan terdaftar
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </div>

</body>

</html>