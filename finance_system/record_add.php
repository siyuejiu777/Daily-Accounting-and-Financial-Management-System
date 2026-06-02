<?php
// 1. 开启 Session，用来获取当前是谁在记账
session_start();

// 引入公用的数据库连接文件
require_once 'db.php';

// 【安全校验】检查用户是否登录，没登录不准记账
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "code" => 401,
        "msg" => "登录已过期或未登录，请先登录！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$current_user_id = $_SESSION['user_id']; // 获取当前登录成功的用户ID

// 2. 接收前端传过来的 JSON 数据
$inputData = file_get_contents("php://input");
$data = json_decode($inputData, true);

// 提取前端参数
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
$remark = isset($data['remark']) ? trim($data['remark']) : '';
// 如果前端没传日期，后端默认填充服务器当天日期
$record_date = isset($data['record_date']) && !empty($data['record_date']) ? trim($data['record_date']) : date('Y-m-d');

// 3. 入库前数据校验
if ($amount <= 0) {
    echo json_encode([
        "code" => 400,
        "msg" => "记账金额必须大于 0 ！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($category_id <= 0) {
    echo json_encode([
        "code" => 400,
        "msg" => "请选择有效的收支分类！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4.【业务规则优化】根据前端传的 category_id，去 categories 表查出真实的收支类型(type)
// 这样做可以完全防止前端恶意篡改数据
$cat_sql = "SELECT type FROM categories WHERE category_id = $category_id";
$cat_result = mysqli_query($conn, $cat_sql);

if (mysqli_num_rows($cat_result) === 0) {
    echo json_encode([
        "code" => 400,
        "msg" => "所选分类不存在！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$cat_data = mysqli_fetch_assoc($cat_result);
$type = $cat_data['type']; // 自动获取该分类合法的 'income' 或 'expense'

// 5. 将账目数据正式插入到流水表 records 中
$insert_sql = "INSERT INTO `records` (`user_id`, `category_id`, `amount`, `type`, `record_date`, `remark`) 
               VALUES ($current_user_id, $category_id, $amount, '$type', '$record_date', '$remark')";

if (!mysqli_query($conn, $insert_sql)) {
    echo json_encode([
        "code" => 500,
        "msg" => "记账失败，数据库写入异常: " . mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 6.【核心亮点】超支预警逻辑动态计算
// 提取当前记账的月份（格式：YYYY-MM）
$current_month = substr($record_date, 0, 7);

// A. 查询该用户当月的预算额度
$budget_sql = "SELECT amount FROM budgets WHERE user_id = $current_user_id AND budget_month = '$current_month'";
$budget_result = mysqli_query($conn, $budget_sql);
$month_budget = 0;
if (mysqli_num_rows($budget_result) > 0) {
    $budget_data = mysqli_fetch_assoc($budget_result);
    $month_budget = floatval($budget_data['amount']);
}

// B. 统计该用户当月累计发生的总支出
$expense_sql = "SELECT SUM(amount) as total FROM records 
                WHERE user_id = $current_user_id AND type = 'expense' AND record_date LIKE '$current_month%'";
$expense_result = mysqli_query($conn, $expense_sql);
$total_expense = 0;
if ($expense_result) {
    $expense_data = mysqli_fetch_assoc($expense_result);
    $total_expense = floatval($expense_data['total']);
}

// C. 判断是否超支
$is_over_budget = false;
if ($month_budget > 0 && $total_expense > $month_budget) {
    $is_over_budget = true; // 如果支出超出了设置的预算，触发超支预警
}

// 7. 成功返回给前端，并带上超支状态信号
echo json_encode([
    "code" => 200,
    "msg" => "记账成功！",
    "data" => [
        "is_over_budget" => $is_over_budget, // 通知前端是否需要变红警告
        "remaining_budget" => $month_budget - $total_expense // 剩余预算
    ]
], JSON_UNESCAPED_UNICODE);