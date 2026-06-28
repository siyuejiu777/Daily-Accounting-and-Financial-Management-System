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

$records = isset($data['records']) ? $data['records'] : [];
$new_category_id = isset($data['new_category_id']) ? intval($data['new_category_id']) : 0;

if (empty($records) || $new_category_id <= 0) {
    echo json_encode(['code' => 400, 'msg' => '参数错误：缺少记录或新分类ID']);
    exit;
}

// 获取新分类的类型（收入/支出）
$cat_sql = "SELECT type FROM categories WHERE category_id = $new_category_id";
$cat_res = mysqli_query($conn, $cat_sql);
$new_type = '';
if ($cat_res && $row = mysqli_fetch_assoc($cat_res)) {
    $new_type = $row['type'];
}

$updated = 0;
$failed = 0;

$sql = "UPDATE records SET category_id = ?, type = ? WHERE user_id = ? AND category_id = ? AND record_time = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    foreach ($records as $record) {
        $old_cat_id = intval($record['category_id']);
        $rec_time = $record['record_time'];
        $stmt->bind_param("isiis", $new_category_id, $new_type, $user_id, $old_cat_id, $rec_time);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $updated++;
        } else {
            $failed++;
        }
    }
    $stmt->close();
}

echo json_encode([
    'code' => 200,
    'msg' => "成功更新 $updated 条记录" . ($failed > 0 ? "，$failed 条失败" : ""),
    'data' => ['updated' => $updated, 'failed' => $failed]
]);

$conn->close();