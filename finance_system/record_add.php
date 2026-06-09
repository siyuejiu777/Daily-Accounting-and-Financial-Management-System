<?php
// 允许跨域请求和 JSON 格式返回
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // 视你的跨域配置而定
require 'db.php'; // 引入数据库连接文件

// 接收前端传来的 JSON 数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 兼容普通的 POST 表单请求
if (!$data) {
    $data = $_POST;
}

// 1. 提取参数
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
$type = isset($data['type']) ? $data['type'] : '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$note = isset($data['note']) ? $data['note'] : '';

// 2. 核心修改点：记账日期（精确到秒）
// 如果前端传了具体时间就用前端的，如果前端没传，系统自动抓取当前时间！
$record_time = (isset($data['record_time']) && !empty($data['record_time'])) 
               ? $data['record_time'] 
               : date('Y-m-d H:i:s');

// 3. 基础参数校验
if ($user_id <= 0 || $category_id <= 0 || empty($type) || $amount <= 0) {
    echo json_encode(['code' => 400, 'msg' => '参数不完整或金额错误！']);
    exit;
}

// 4. 准备 SQL 语句（完全去掉了记录 ID，插入其余 6 个字段）
$sql = "INSERT INTO records (user_id, category_id, record_time, type, amount, note) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // 绑定参数 (i=整型, s=字符串或小数类型)
    $stmt->bind_param("iissss", $user_id, $category_id, $record_time, $type, $amount, $note);
    
    // 执行插入
    if ($stmt->execute()) {
        echo json_encode([
            'code' => 200, 
            'msg' => '记账成功！',
            'data' => [
                'record_time' => $record_time // 将最终确定的时间返回给前端
            ]
        ]);
    } else {
        // 如果插入失败（比如联合主键冲突，同一用户在同一秒记了相同的账）
        echo json_encode(['code' => 500, 'msg' => '记账失败，请勿在同一秒内重复提交！错误信息: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['code' => 500, 'msg' => '数据库操作异常: ' . $conn->error]);
}

$conn->close();
?>