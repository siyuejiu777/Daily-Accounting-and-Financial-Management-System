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
$month = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');

// 查询该用户该月份的预算额度
$budget_sql = "SELECT amount FROM budgets WHERE user_id = $user_id AND budget_month = '$month'";
$budget_res = mysqli_query($conn, $budget_sql);
$budget_amount = 0;
if ($budget_res && mysqli_num_rows($budget_res) > 0) {
    $row = mysqli_fetch_assoc($budget_res);
    $budget_amount = floatval($row['amount']);
}

// 计算当月总支出
$expense_sql = "SELECT SUM(amount) as total_expense FROM records 
                WHERE user_id = $user_id AND type = 'expense' 
                AND DATE_FORMAT(record_time, '%Y-%m') = '$month'";
$expense_res = mysqli_query($conn, $expense_sql);
$total_expense = 0;
if ($expense_res && mysqli_num_rows($expense_res) > 0) {
    $row = mysqli_fetch_assoc($expense_res);
    $total_expense = floatval($row['total_expense'] ?? 0);
}

$remaining = $budget_amount - $total_expense;
$is_over = $budget_amount > 0 && $remaining < 0;

echo json_encode([
    'code' => 200,
    'msg' => 'success',
    'data' => [
        'month' => $month,
        'budget_amount' => $budget_amount,
        'total_expense' => $total_expense,
        'remaining_budget' => $remaining,
        'is_over_budget' => $is_over
    ]
], JSON_UNESCAPED_UNICODE);