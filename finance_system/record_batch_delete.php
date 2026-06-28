<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 401, 'msg' => '未登录，请先登录']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 接收记录数组：[{category_id, record_time}, ...]
$records = isset($data['records']) ? $data['records'] : [];
if (empty($records)) {
    echo json_encode(['code' => 400, 'msg' => '请选择要删除的记录']);
    exit;
}

$deleted = 0;
$failed = 0;
$errors = [];

$sql = "DELETE FROM records WHERE user_id = ? AND category_id = ? AND record_time = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    foreach ($records as $record) {
        $cat_id = intval($record['category_id']);
        $rec_time = $record['record_time'];
        $stmt->bind_param("iis", $user_id, $cat_id, $rec_time);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $deleted++;
        } else {
            $failed++;
            $errors[] = "删除失败: category_id=$cat_id, time=$rec_time";
        }
    }
    $stmt->close();
}

echo json_encode([
    'code' => 200,
    'msg' => "成功删除 $deleted 条记录" . ($failed > 0 ? "，$failed 条失败" : ""),
    'data' => ['deleted' => $deleted, 'failed' => $failed, 'errors' => $errors]
]);

$conn->close();