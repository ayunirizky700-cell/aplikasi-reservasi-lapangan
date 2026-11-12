<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Mengambil 1 jenis olahraga unik (DISTINCT) dari tabel fields
$kategori = $pdo->query("SELECT DISTINCT sport_type FROM fields")->fetchAll();

// Ambil total pesanan user untuk statistik
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE customer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_pesanan = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SportArena</title>
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            -webkit-overflow-scrolling: touch;
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
            max-width: 1200px;
            margin: auto;
        }

        .welcome-box {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-box h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .welcome-box p {
            opacity: 0.9;
            font-size: 14px;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box h2 {
            font-size: 28px;
        }

        .stat-box p {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: #1a2332;
            font-weight: 600;
            border-left: 5px solid #4fc3f7;
            padding-left: 10px;
        }

        .grid-kategori {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .card-kategori {
            background: #fff;
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: 0.3s;
            text-decoration: none;
            color: #333;
            display: block;
            border: 2px solid transparent;
        }

        .card-kategori:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #4fc3f7;
        }

        .icon-kategori {
            font-size: 60px;
            margin-bottom: 15px;
            display: block;
        }

        .card-kategori h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .btn-pesan {
            display: inline-block;
            padding: 10px 25px;
            background: #f4f6f9;
            color: #4fc3f7;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            transition: 0.3s;
        }

        .card-kategori:hover .btn-pesan {
            background: #4fc3f7;
            color: white;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .welcome-box {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="dashboard_users.php" class="logo">🏟️ Sport<span>Arena</span></a>
        <div class="nav-links">
            <a href="dashboard_users.php" class="active">Dashboard</a>
            <a href="pesanan.php">Pesanan</a>
            <a href="pembayaran.php">Pembayaran</a>
            <a href="pesanan.php">Riwayat</a>
            <a href="profil.php">Profil</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="welcome-box">
            <div>
                <h1>Halo, <?= htmlspecialchars($_SESSION['name']) ?>! 👋</h1>
                <p>Mau main apa hari ini? Silakan pilih jenis olahraganya di bawah.</p>
            </div>
            <div class="stat-box">
                <h2><?= $total_pesanan ?></h2>
                <p>Total Pesanan</p>
            </div>
        </div>

        <h2 class="section-title">🏆 Pilih Jenis Olahraga</h2>
        <div class="grid-kategori">
            <?php foreach ($kategori as $kat): ?>
                <?php
                // Memberikan icon berbeda berdasarkan nama olahraga
                $sport = strtolower($kat['sport_type']);
                $icon = '🏅';
                if (strpos($sport, 'futsal') !== false)
                    $icon = '⚽';
                elseif (strpos($sport, 'voli') !== false || strpos($sport, 'voly') !== false)
                    $icon = '🏐';
                elseif (strpos($sport, 'basket') !== false)
                    $icon = '🏀';
                elseif (strpos($sport, 'sepak') !== false || strpos($sport, 'bola') !== false)
                    $icon = '⚽';
                elseif (strpos($sport, 'badminton') !== false)
                    $icon = '🏸';
                ?>
                <a href="pesanan.php?sport=<?= urlencode($kat['sport_type']) ?>" class="card-kategori">
                    <span class="icon-kategori"><?= $icon ?></span>
                    <h3>Lapangan <?= htmlspecialchars($kat['sport_type']) ?></h3>
                    <span class="btn-pesan">Lihat Lapangan →</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>