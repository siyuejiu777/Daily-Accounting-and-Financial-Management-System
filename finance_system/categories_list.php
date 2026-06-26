<?php
// 允许跨域请求和 JSON 格式返回
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173'); // 你的前端地址
header('Access-Control-Allow-Credentials: true');            // 允许携带 Cookie
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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