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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 401, 'msg' => '未登录，请先登录']);
    exit;
}

require 'db.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$ids = isset($data['ids']) ? $data['ids'] : [];
$new_type = isset($data['type']) ? $data['type'] : '';

if (empty($ids) || !in_array($new_type, ['income', 'expense'])) {
    echo json_encode(['code' => 400, 'msg' => '参数错误：缺少分类ID或类型不正确']);
    exit;
}

$updated = 0;
$failed = 0;

foreach ($ids as $id) {
    $id = intval($id);
    // 检查是否有关联账目（如果该分类下已有账目，修改类型可能导致数据不一致，可提示不允许修改）
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM records WHERE category_id = $id");
    $row = mysqli_fetch_assoc($check);
    if ($row['cnt'] > 0) {
        $failed++;
        continue; // 有关联账目的分类不允许修改类型
    }
    $sql = "UPDATE categories SET type = '$new_type' WHERE category_id = $id";
    if (mysqli_query($conn, $sql) && mysqli_affected_rows($conn) > 0) {
        $updated++;
    } else {
        $failed++;
    }
}

echo json_encode([
    'code' => 200,
    'msg' => "成功更新 $updated 个分类" . ($failed > 0 ? "，$failed 个失败" : ""),
    'data' => ['updated' => $updated, 'failed' => $failed]
]);

$conn->close();