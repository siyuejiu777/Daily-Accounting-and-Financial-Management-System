<?php
// 允许跨域请求和 JSON 格式返回
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173'); // 你的前端地址
header('Access-Control-Allow-Credentials: true');            // 允许携带 Cookie
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 401, 'msg' => '未登录']);
    exit;
}

require 'db.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$category_name = isset($data['category_name']) ? trim($data['category_name']) : '';
$type = isset($data['type']) ? $data['type'] : '';

if ($category_name === '' || !in_array($type, ['income', 'expense'])) {
    echo json_encode(['code' => 400, 'msg' => '分类名称或类型无效']);
    exit;
}

$sql = "INSERT INTO categories (category_name, type) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ss", $category_name, $type);
    if ($stmt->execute()) {
        echo json_encode(['code' => 200, 'msg' => '新增分类成功', 'data' => ['category_id' => $stmt->insert_id]]);
    } else {
        echo json_encode(['code' => 500, 'msg' => '添加失败: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['code' => 500, 'msg' => '数据库错误']);
}
$conn->close();