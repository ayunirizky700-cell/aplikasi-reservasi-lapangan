<?php
session_start();
require_once 'config/database.php';

// Cek apakah sudah login
if (isset($_SESSION['user_id'])) {
    // Jika sudah login, redirect sesuai role
    if ($_SESSION['role'] == 'admin') {
        header('Location: pages/dashboard.php');
        exit();
    } else {
        header('Location: users/dashboard_users.php');
        exit();
    }
}

$error = '';

// Proses Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // PERBAIKAN: Menggunakan full_name sesuai database
                $_SESSION['name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                $_SESSION['login_time'] = time();

                // Redirect sesuai role
                if ($_SESSION['role'] == 'admin') {
                    header('Location: pages/dashboard.php');
                } else {
                    header('Location: users/dashboard_users.php');
                }
                exit();
            } else {
                $error = 'Username atau password salah!';
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
    <title>Login - Reservasi Lapangan</title>
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
            width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .logo {
            text-align: center;
            font-size: 50px;
            margin-bottom: 10px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 25px;
            box-sizing: border-box;
            font-size: 14px;
        }

        input:focus {
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
        <div class="logo">🏟️</div>
        <h2>Login Reservasi</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Tombol Logout Darurat (kalau nyangkut session) -->
        <form method="POST" action="logout.php" style="margin-bottom: 15px;">
            <button type="submit" style="background: #dc3545;">🚪 Logout (Clear Session)</button>
        </form>

        <hr style="margin: 15px 0;">

        <form method="POST">
            <label>Username atau Email</label>
            <input type="text" name="username" placeholder="Masukkan username/email" required autofocus>

            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password" required>

            <button type="submit">Login</button>
        </form>

        <div class="text-center">
            <a href="register.php">Belum punya akun? Daftar di sini</a>
        </div>
    </div>
</body>

</html>