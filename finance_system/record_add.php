<?php
session_start();

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

require 'db.php';

// 安全检查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 401, 'msg' => '未登录或登录已过期，请先登录！']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// 接收前端数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
$type = isset($data['type']) ? $data['type'] : '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$note = isset($data['note']) ? $data['note'] : '';
$record_time = (isset($data['record_time']) && !empty($data['record_time']))
               ? $data['record_time']
               : date('Y-m-d H:i:s');

if ($category_id <= 0 || empty($type) || $amount <= 0) {
    echo json_encode(['code' => 400, 'msg' => '参数不完整或金额错误！']);
    exit;
}

// 插入记录
$sql = "INSERT INTO records (user_id, category_id, record_time, type, amount, note) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("iissss", $user_id, $category_id, $record_time, $type, $amount, $note);
    if ($stmt->execute()) {
        // ========== 新增：计算当月预算超支状态 ==========
        $is_over_budget = false;
        $remaining_budget = 0;

        // 只有当这笔记录是支出时，才检查预算
        if ($type === 'expense') {
            $budget_month = date('Y-m', strtotime($record_time));

            // 查询该用户当月的预算额度
            $budget_sql = "SELECT amount FROM budgets WHERE user_id = $user_id AND budget_month = '$budget_month'";
            $budget_result = mysqli_query($conn, $budget_sql);

            if ($budget_result && mysqli_num_rows($budget_result) > 0) {
                $budget_row = mysqli_fetch_assoc($budget_result);
                $budget_amount = floatval($budget_row['amount']);

                // 查询当月总支出（包含刚插入的这条）
                $expense_sql = "SELECT SUM(amount) as total_expense FROM records 
                                WHERE user_id = $user_id 
                                AND type = 'expense' 
                                AND DATE_FORMAT(record_time, '%Y-%m') = '$budget_month'";
                $expense_result = mysqli_query($conn, $expense_sql);
                $expense_row = mysqli_fetch_assoc($expense_result);
                $total_expense = floatval($expense_row['total_expense']);

                $remaining_budget = $budget_amount - $total_expense;
                $is_over_budget = ($remaining_budget < 0);
            }
        }
        // ========== 超支计算结束 ==========

        echo json_encode([
            'code' => 200,
            'msg' => '记账成功！',
            'data' => [
                'record_time' => $record_time,
                'is_over_budget' => $is_over_budget,      // 新增
                'remaining_budget' => $remaining_budget    // 新增
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