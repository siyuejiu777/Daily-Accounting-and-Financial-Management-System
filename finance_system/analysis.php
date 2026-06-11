<?php
// 1. 开启 Session，获取当前登录的用户
session_start();

// 引入公用的数据库连接文件
require_once 'db.php';

// 【安全校验】未登录用户禁止查询统计数据
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "code" => 401,
        "msg" => "登录已过期或未登录，请先登录！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$current_user_id = intval($_SESSION['user_id']); // 加 intval 更安全

// 获取前端传来的月份参数（格式：YYYY-MM），如果不传，默认取当前年月
$month = isset($_GET['month']) && !empty($_GET['month']) ? trim($_GET['month']) : date('Y-m');

// =========================================================================
// 核心统计一：分类支出统计（用于前端【饼图】展示分类占比）
// =========================================================================
// 注意：将 record_date 改为了 record_time
$pie_sql = "SELECT c.category_name, SUM(r.amount) as total_amount 
            FROM records r 
            JOIN categories c ON r.category_id = c.category_id 
            WHERE r.user_id = $current_user_id 
              AND r.type = 'expense' 
              AND r.record_time LIKE '$month%'
            GROUP BY r.category_id 
            ORDER BY total_amount DESC";

$pie_result = mysqli_query($conn, $pie_sql);
$category_stats = [];

if ($pie_result) {
    while ($row = mysqli_fetch_assoc($pie_result)) {
        $category_stats[] = [
            "category_name" => $row['category_name'],
            "total_amount" => floatval($row['total_amount'])
        ];
    }
}

// =========================================================================
// 核心统计二：每日收支趋势统计（用于前端【折线图】展示每日动态）
// =========================================================================
// 注意：因为 record_time 包含秒，所以必须用 DATE(record_time) 截取到“天”才能分组汇总
$trend_sql = "SELECT DATE(record_time) as day_date,
                     SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as daily_income,
                     SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as daily_expense
              FROM records 
              WHERE user_id = $current_user_id 
                AND record_time LIKE '$month%'
              GROUP BY DATE(record_time) 
              ORDER BY day_date ASC";

$trend_result = mysqli_query($conn, $trend_sql);
$daily_trend = [];

if ($trend_result) {
    while ($row = mysqli_fetch_assoc($trend_result)) {
        $daily_trend[] = [
            "date" => $row['day_date'],
            "income" => floatval($row['daily_income']),
            "expense" => floatval($row['daily_expense'])
        ];
    }
}

// =========================================================================
// 🌟新增模块三：当月账单明细列表（专门为了前端能拿到联合主键做删除/修改用）
// =========================================================================
$list_sql = "SELECT r.user_id, r.category_id, r.record_time, r.type, r.amount, r.note, c.category_name 
             FROM records r 
             JOIN categories c ON r.category_id = c.category_id 
             WHERE r.user_id = $current_user_id 
               AND r.record_time LIKE '$month%'
             ORDER BY r.record_time DESC";

$list_result = mysqli_query($conn, $list_sql);
$record_list = [];

if ($list_result) {
    while ($row = mysqli_fetch_assoc($list_result)) {
        $record_list[] = [
            "user_id" => intval($row['user_id']),
            "category_id" => intval($row['category_id']),
            "category_name" => $row['category_name'],
            "record_time" => $row['record_time'], // 精确到秒的时间（联合主键之一）
            "type" => $row['type'],
            "amount" => floatval($row['amount']),
            "note" => $row['note']
        ];
    }
}

// =========================================================================
// 3. 整合数据并打包返回 JSON
// =========================================================================
echo json_encode([
    "code" => 200,
    "msg" => "success",
    "data" => [
        "month" => $month,
        "category_expense_pie" => $category_stats, // 饼图数据源
        "daily_trend_line" => $daily_trend,        // 折线图数据源
        "record_list" => $record_list              // 🌟 新增：明细列表（包含完整的主键）
    ]
], JSON_UNESCAPED_UNICODE);
?>