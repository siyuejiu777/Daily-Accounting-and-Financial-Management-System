<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 登录验证（可选，如果系统分类对所有登录用户可见）
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 401, 'msg' => '未登录']);
    exit;
}

require 'db.php';

$sql = "SELECT category_id, category_name, type FROM categories ORDER BY category_id";
$result = $conn->query($sql);

if ($result) {
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode(['code' => 200, 'data' => $categories]);
} else {
    echo json_encode(['code' => 500, 'msg' => '查询失败']);
}

$conn->close();