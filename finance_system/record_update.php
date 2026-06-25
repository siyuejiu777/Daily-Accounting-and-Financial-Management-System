<?php
// ========== CORS 头部处理（必须在 session_start 之前） ==========
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

// ========== 正常业务逻辑 ==========
session_start();

// 登录验证
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

// 必填：定位记录需要的字段
$category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
$old_record_time = isset($data['old_record_time']) ? $data['old_record_time'] : '';

// 可更新的字段
$amount = isset($data['amount']) ? floatval($data['amount']) : null;
$type = isset($data['type']) ? $data['type'] : null;
$note = isset($data['note']) ? $data['note'] : null;
$new_record_time = isset($data['record_time']) ? $data['record_time'] : null;

// 参数校验
if ($category_id <= 0 || empty($old_record_time)) {
    echo json_encode(['code' => 400, 'msg' => '缺少定位参数（category_id 或旧时间）']);
    exit;
}

// 构建更新语句
$fields = [];
$params = [];
$types = '';

if ($amount !== null) {
    $fields[] = 'amount = ?';
    $params[] = $amount;
    $types .= 'd';
}
if ($type !== null && in_array($type, ['income', 'expense'])) {
    $fields[] = 'type = ?';
    $params[] = $type;
    $types .= 's';
}
if ($note !== null) {
    $fields[] = 'note = ?'; // 或 remark，根据你的数据库字段名调整
    $params[] = $note;
    $types .= 's';
}
if ($new_record_time !== null) {
    $fields[] = 'record_time = ?';
    $params[] = $new_record_time;
    $types .= 's';
}

if (empty($fields)) {
    echo json_encode(['code' => 400, 'msg' => '没有需要更新的字段']);
    exit;
}

// WHERE 条件：联合主键定位
$sql = "UPDATE records SET " . implode(', ', $fields) . " WHERE user_id = ? AND category_id = ? AND record_time = ?";
$params[] = $user_id;
$params[] = $category_id;
$params[] = $old_record_time;
$types .= 'iis';

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);
        } else {
            echo json_encode(['code' => 404, 'msg' => '记录未找到或无变化']);
        }
    } else {
        echo json_encode(['code' => 500, 'msg' => '更新失败: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['code' => 500, 'msg' => '数据库错误']);
}
$conn->close();