<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: pages/admin_payments.php');
    } else {
        header('Location: users/dashboard_users.php');
    }
    exit();
}

$error = '';
$success = '';

// Inisialisasi variabel untuk menghindari undefined array key
$username = '';
$name = '';
$email = '';
$phone = '';
$role = 'user';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($username) || empty($name) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm) {
        $error = 'Password tidak sama!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid!';
    } else {
        try {
            // PERBAIKAN 1: Menggunakan tabel 'users', bukan 'customers'
            $cek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $cek->execute([$username, $email]);
            if ($cek->fetchColumn() > 0) {
                $error = 'Username atau email sudah terdaftar!';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // PERBAIKAN 2: Menggunakan 'full_name' (sesuai database), bukan 'name'
                $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $name, $email, $phone, $hashed, $role]);

                $success = 'Registrasi berhasil! Silakan login.';

                // Reset form setelah sukses
                $username = $name = $email = $phone = '';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register - Reservasi Lapangan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .logo {
            text-align: center;
            font-size: 40px;
            margin-bottom: 10px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input,
        select {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            box-sizing: border-box;
            font-size: 14px;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        button:hover {
            transform: scale(1.02);
            opacity: 0.9;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 25px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 25px;
            margin-bottom: 20px;
            text-align: center;
        }

        .text-center {
            text-align: center;
            margin-top: 15px;
        }

        .text-center a {
            color: #667eea;
            text-decoration: none;
        }

        .text-center a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="box">
        <div class="logo">📝</div>
        <h2>Daftar Akun</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Username (untuk login)</label>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>"
                placeholder="Masukkan username" required>

            <label>Nama Lengkap</label>
            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Masukkan nama lengkap"
                required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Masukkan email"
                required>

            <label>Nomor HP</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="Masukkan nomor HP">

            <label>Password (min 6 karakter)</label>
            <input type="password" name="password" placeholder="Minimal 6 karakter" required>

            <label>Konfirmasi Password</label>
            <input type="password" name="confirm_password" placeholder="Ulangi password" required>

            <label>Role</label>
            <select name="role">
                <option value="user" <?= $role == 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>

            <button type="submit">Daftar</button>
        </form>

        <div class="text-center">
            <a href="login.php">Sudah punya akun? Login</a>
        </div>
    </div>
</body>

</html>