<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM fields WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode($data);
?>