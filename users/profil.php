<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';

// Proses Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profil'])) {
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$nama, $email, $phone, $user_id])) {
            $_SESSION['name'] = $nama;
            $msg = '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">✅ Profil berhasil diperbarui!</div>';
        }
    }
    if (isset($_POST['update_pass'])) {
        $new_pass = $_POST['new_password'];
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed, $user_id])) {
            $msg = '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">✅ Password berhasil diubah!</div>';
        }
    }
}

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SportArena</title>
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

        /* NAVBAR */
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

        /* MAIN */
        .main {
            padding: 30px 5%;
            max-width: 800px;
            margin: auto;
        }

        .header-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #1a2332;
        }

        /* CARD */
        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .card-body {
            padding: 30px;
        }

        /* FORM */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #444;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
        }

        .form-group input:focus {
            border-color: #4fc3f7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2);
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            color: white;
        }

        .btn-primary {
            background: #4fc3f7;
        }

        .btn-primary:hover {
            background: #29b6f6;
        }

        .btn-success {
            background: #4caf50;
        }

        .btn-success:hover {
            background: #43a047;
        }

        .divider {
            border: none;
            border-top: 2px dashed #eee;
            margin: 30px 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1a2332;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .main {
                padding: 20px 15px;
            }
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
           <a href="riwayat.php">Riwayat</a>
            <a href="profil.php" class="active">Profil</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 class="header-title">👤 Profil Saya</h2>
        <?= $msg ?>

        <!-- KARTU PROFIL -->
        <div class="card">
            <div class="card-header">
                <div class="avatar"><?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?></div>
                <h3><?= htmlspecialchars($user['full_name'] ?? 'User') ?></h3>
                <p style="font-size: 13px; opacity: 0.8; margin-top: 5px;">
                    @<?= htmlspecialchars($user['username'] ?? '-') ?></p>
            </div>
            <div class="card-body">

                <!-- FORM EDIT PROFIL -->
                <form method="POST">
                    <h4 class="section-title">📝 Edit Data Diri</h4>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor HP</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
                </form>

                <hr class="divider">

                <!-- FORM GANTI PASSWORD -->
                <form method="POST">
                    <h4 class="section-title">🔐 Ganti Password</h4>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" placeholder="Minimal 6 karakter" minlength="6"
                            required>
                    </div>
                    <button type="submit" name="update_pass" class="btn btn-success">Ubah Password</button>
                </form>

            </div>
        </div>
    </div>
</body>

</html>