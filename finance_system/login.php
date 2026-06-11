<?php
// 1. 开启服务器 Session 会话保持，用于身份鉴权（必须放在代码最顶部！）
session_start();

// 引入公用的数据库连接文件
require_once 'db.php';

// 2. 接收前端通过 POST 方式传过来的 JSON 数据
$inputData = file_get_contents("php://input");
$data = json_decode($inputData, true);

// 提取用户名和密码
$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

// 3. 基础非空校验
if (empty($username) || empty($password)) {
    echo json_encode([
        "code" => 400,
        "msg" => "用户名或密码不能为空！"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. 核心逻辑一：根据用户名去数据库查找用户记录
$sql = "SELECT user_id, username, password FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $sql);

// 检查是否查到了这个用户
if (mysqli_num_rows($result) === 0) {
    echo json_encode([
        "code" => 400,
        "msg" => "用户名或密码错误！" // 提示模糊一点，防止恶意撞库，保障安全
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 取出数据库里的这行数据
$user = mysqli_fetch_assoc($result);

// 5. 核心逻辑二：比对密码。使用 password_verify 验证明文和数据库里的哈希密文
if (password_verify($password, $user['password'])) {
    
    // 6. 验证通过，将关键身份标识 user_id 锁进服务器 Session 中
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    
    // 返回登录成功状态及用户信息
    echo json_encode([
        "code" => 200,
        "msg" => "登录成功！",
        "data" => [
            'user_id' => $id,
            "username" => $user['username']
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} else {
    // 密码不匹配
    echo json_encode([
        "code" => 400,
        "msg" => "用户名或密码错误！"
    ], JSON_UNESCAPED_UNICODE);
}