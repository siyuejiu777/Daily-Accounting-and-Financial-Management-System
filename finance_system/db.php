<?php
// 1. 允许跨域（方便你前端队友本地调试页面时，能够顺利调用你的后端接口）
$allowed_origin = 'http://localhost:5173'; // 或根据需要动态获取
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowed_origin) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');

// 2. 数据库连接的基本配置（对应你 phpStudy 的默认设置）
$host = "127.0.0.1";     // 本地服务器地址 [cite: 164]
$db_user = "root";       // phpStudy 默认的数据库用户名 [cite: 165]
$db_pass = "root";       // phpStudy 默认的数据库密码 [cite: 166]
$db_name = "finance_db"; // 我们在 phpMyAdmin 里建好的数据库名

// 3. 建立与 MySQL 的物理连接 [cite: 168]
$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

// 4. 检查连接是否成功，如果失败就直接报错退出
if (!$conn) {
    die(json_encode([
        "code" => 500,
        "msg" => "数据库连接失败啦: " . mysqli_connect_error()
    ], JSON_UNESCAPED_UNICODE));
}

// 5. 设置数据库传输字符集为 utf8mb4，彻底杜绝中文乱码问题
mysqli_set_charset($conn, "utf8mb4");

// 走到这一步，说明连接成功，程序可以继续向下执行！