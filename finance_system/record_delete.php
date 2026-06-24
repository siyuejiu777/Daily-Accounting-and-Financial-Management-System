<?php
// ========== CORS 配置（必须在最前面） ==========
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173'); // 你的前端地址
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========== 开始业务逻辑 ==========
session_start();

// 验证登录状态
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 401, 'msg' => '未登录，请先登录']);
    exit;
}

$user_id = $_SESSION['user_id'];
require 'db.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
$record_time = isset($data['record_time']) ? $data['record_time'] : '';

if ($category_id <= 0 || empty($record_time)) {
    echo json_encode(['code' => 400, 'msg' => '参数错误：缺少分类ID或记录时间']);
    exit;
}

$sql = "DELETE FROM records WHERE user_id = ? AND category_id = ? AND record_time = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("iis", $user_id, $category_id, $record_time);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
        } else {
            echo json_encode(['code' => 404, 'msg' => '记录不存在或已删除']);
        }
    } else {
        echo json_encode(['code' => 500, 'msg' => '删除失败: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['code' => 500, 'msg' => '数据库操作异常: ' . $conn->error]);
}

$conn->close();