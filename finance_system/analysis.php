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

$current_user_id = $_SESSION['user_id'];

// 获取前端传来的月份参数（格式：YYYY-MM），如果不传，默认取当前年月
$month = isset($_GET['month']) && !empty($_GET['month']) ? trim($_GET['month']) : date('Y-m');

// =========================================================================
// 核心统计一：分类支出统计（用于前端【饼图】展示分类占比）
// =========================================================================
$pie_sql = "SELECT c.category_name, SUM(r.amount) as total_amount 
            FROM records r 
            JOIN categories c ON r.category_id = c.category_id 
            WHERE r.user_id = $current_user_id 
              AND r.type = 'expense' 
              AND r.record_date LIKE '$month%'
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
$trend_sql = "SELECT record_date,
                     SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as daily_income,
                     SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as daily_expense
              FROM records 
              WHERE user_id = $current_user_id 
                AND record_date LIKE '$month%'
              GROUP BY record_date 
              ORDER BY record_date ASC";

$trend_result = mysqli_query($conn, $trend_sql);
$daily_trend = [];

if ($trend_result) {
    while ($row = mysqli_fetch_assoc($trend_result)) {
        $daily_trend[] = [
            "date" => $row['record_date'],
            "income" => floatval($row['daily_income']),
            "expense" => floatval($row['daily_expense'])
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
        "daily_trend_line" => $daily_trend        // 折线图数据源
    ]
], JSON_UNESCAPED_UNICODE);