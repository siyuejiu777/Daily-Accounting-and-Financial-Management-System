<?php
// 允许跨域请求
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
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

$category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;

if ($category_id <= 0) {
    echo json_encode(['code' => 400, 'msg' => '缺少分类ID']);
    exit;
}

// 检查是否存在关联账目（如果需要保护数据，可以改成 RESTRICT 检查）
$check = $conn->query("SELECT COUNT(*) as cnt FROM records WHERE category_id = $category_id");
$row = $check->fetch_assoc();
if ($row['cnt'] > 0) {
    echo json_encode(['code' => 400, 'msg' => '该分类下有账目记录，无法删除']);
    exit;
}

$sql = "DELETE FROM categories WHERE category_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $category_id);
    if ($stmt->execute()) {
        echo json_encode(['code' => 200, 'msg' => '删除成功']);
    } else {
        echo json_encode(['code' => 500, 'msg' => '删除失败: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['code' => 500, 'msg' => '数据库错误']);
}
$conn->close();