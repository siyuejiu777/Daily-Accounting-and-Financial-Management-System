<?php
// 引入我们刚刚写好的数据库连接文件（相当于把 db.php 的代码复制到这里）
require_once 'db.php';

// 1. 接收前端通过 POST 方式传过来的 JSON 数据
$inputData = file_get_contents("php://input");
$data = json_decode($inputData, true);

// 2. 提取出用户名和密码
$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

// 3. 基础校验：确保前端没有传空数据
if (empty($username) || empty($password)) {
    echo json_encode([
        "code" => 400,
        "msg" => "用户名或密码不能为空！"
    ], JSON_UNESCAPED_UNICODE);
    exit; // 终止程序
}

// 4. 核心逻辑一：校验用户名是否已经在数据库里存在了 
// （使用普通查询，基础薄弱时这样写最直观）
$check_sql = "SELECT user_id FROM users WHERE username = '$username'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // 如果查出来的行数 > 0，说明被别人注册过了 
    echo json_encode([
        "code" => 400,
        "msg" => "该用户名已被注册，请换一个试试！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. 核心逻辑二：对密码进行哈希加密，绝不存储明文！
// PASSWORD_DEFAULT 是 PHP 自带的强加密算法
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 6. 将新用户数据写入数据库
$insert_sql = "INSERT INTO users (username, password) VALUES ('$username', '$hashed_password')";

if (mysqli_query($conn, $insert_sql)) {
    // 插入成功，返回 200 状态码给前端
    echo json_encode([
        "code" => 200,
        "msg" => "恭喜你，注册成功！"
    ], JSON_UNESCAPED_UNICODE);
} else {
    // 数据库产生未知错误时报错
    echo json_encode([
        "code" => 500,
        "msg" => "注册失败，服务器数据库报错: " . mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
}