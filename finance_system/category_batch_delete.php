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

$ids = isset($data['ids']) ? $data['ids'] : []; // 分类ID数组
if (empty($ids)) {
    echo json_encode(['code' => 400, 'msg' => '请选择要删除的分类']);
    exit;
}

$deleted = 0;
$failed = 0;
$errors = [];

// 检查每个分类是否有关联账目，有则禁止删除
foreach ($ids as $id) {
    $id = intval($id);
    // 检查是否被账目引用
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM records WHERE category_id = $id");
    $row = mysqli_fetch_assoc($check);
    if ($row['cnt'] > 0) {
        $failed++;
        $errors[] = "分类ID $id 下有账目记录，无法删除";
        continue;
    }
    $sql = "DELETE FROM categories WHERE category_id = $id";
    if (mysqli_query($conn, $sql) && mysqli_affected_rows($conn) > 0) {
        $deleted++;
    } else {
        $failed++;
        $errors[] = "分类ID $id 删除失败";
    }
}

echo json_encode([
    'code' => 200,
    'msg' => "成功删除 $deleted 个分类" . ($failed > 0 ? "，$failed 个失败" : ""),
    'data' => ['deleted' => $deleted, 'failed' => $failed, 'errors' => $errors]
]);

$conn->close();