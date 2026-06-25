<?php
// 1. 开启 Session（一定要放在文件最开头）
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
require 'db.php'; 

// 2. 【安全校验】直接从 Session 获取 user_id，杜绝前端伪造！
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 401, 'msg' => '未登录或登录已过期，请先登录！']);
    exit;
}
// 从 Session 中拿走真实的用户 ID
$user_id = intval($_SESSION['user_id']);

// 接收前端传来的 JSON 数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 3. 提取其他参数（不再需要提取 user_id 了）
$category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
$type = isset($data['type']) ? $data['type'] : '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$note = isset($data['note']) ? $data['note'] : '';

// 记账日期（精确到秒）
$record_time = (isset($data['record_time']) && !empty($data['record_time'])) 
               ? $data['record_time'] 
               : date('Y-m-d H:i:s');

// 基础参数校验
if ($category_id <= 0 || empty($type) || $amount <= 0) {
    echo json_encode(['code' => 400, 'msg' => '参数不完整或金额错误！']);
    exit;
}

// 准备 SQL 语句
$sql = "INSERT INTO records (user_id, category_id, record_time, type, amount, note) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("iissss", $user_id, $category_id, $record_time, $type, $amount, $note);
    if ($stmt->execute()) {
        echo json_encode([
            'code' => 200, 
            'msg' => '记账成功！',
            'data' => [
                'record_time' => $record_time
            ]
        ]);
    } else {
        echo json_encode(['code' => 500, 'msg' => '记账失败，错误信息: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['code' => 500, 'msg' => '数据库操作异常: ' . $conn->error]);
}
$conn->close();
?>