<?php
// 1. 开启 Session，获取当前登录用户
session_start();

// 引入公用的数据库连接文件
require_once 'db.php';

// 【安全校验】未登录用户禁止设置预算
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "code" => 401,
        "msg" => "登录已过期或未登录，请先登录！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// 2. 接收前端传过来的 JSON 数据
$inputData = file_get_contents("php://input");
$data = json_decode($inputData, true);

// 提取前端参数
$budget_month = isset($data['budget_month']) ? trim($data['budget_month']) : ''; // 格式如: "2026-06"
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;

// 3. 基础非空和合法性校验
if (empty($budget_month) || !preg_match('/^\d{4}-\d{2}$/', $budget_month)) {
    echo json_encode([
        "code" => 400,
        "msg" => "月份格式不正确，必须为 YYYY-MM 格式（如 2026-06）"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($amount <= 0) {
    echo json_encode([
        "code" => 400,
        "msg" => "预算额度必须大于 0 ！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4.【核心逻辑】检查该用户这笔月份的预算是否已经存在
$check_sql = "SELECT budget_id FROM budgets WHERE user_id = $current_user_id AND budget_month = '$budget_month'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // 4.1 如果已经存在了，执行更新操作 (UPDATE)
    $save_sql = "UPDATE budgets SET amount = $amount WHERE user_id = $current_user_id AND budget_month = '$budget_month'";
    $action_msg = "预算修改成功！";
} else {
    // 4.2 如果不存在，执行插入操作 (INSERT)
    $save_sql = "INSERT INTO budgets (user_id, budget_month, amount) VALUES ($current_user_id, '$budget_month', $amount)";
    $action_msg = "预算设置成功！";
}

// 5. 执行 SQL 并返回结果
if (mysqli_query($conn, $save_sql)) {
    echo json_encode([
        "code" => 200,
        "msg" => $action_msg,
        "data" => [
            "budget_month" => $budget_month,
            "amount" => $amount
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "code" => 500,
        "msg" => "保存预算失败，数据库报错: " . mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
}