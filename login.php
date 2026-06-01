<?php

session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

header('Content-Type: text/html; charset=UTF-8');

if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

$host = 'localhost';
$dbname = 'u82813';           
$username_db = 'u82813';
$password_db = '4313992';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username_db,
        $password_db,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die("Ошибка подключения к базе данных. Попробуйте позже.");
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Ошибка безопасности: неверный CSRF-токен.';
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = trim($_POST['pass'] ?? '');
        
        if (empty($login) || empty($password)) {
            $error_message = 'Заполните логин и пароль';
        } else {
            $stmt = $pdo->prepare("SELECT id, login, password_hash FROM task5_applications WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['login'] = $user['login'];
                $_SESSION['uid'] = $user['id'];
                header('Location: index.php');
                exit();
            } else {
                $error_message = 'Неверный логин или пароль';
            }
        }
    }
}

// Генерация CSRF-токена для формы
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход для изменения данных</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-box {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);
            text-align: center;
        }
        h1 { margin-bottom: 20px; color: #1f2937; }
        input {
            width: 100%;
            padding: 12px 16px;
            margin: 10px 0;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .error { 
            color: #dc2626; 
            margin-bottom: 15px; 
            padding: 10px; 
            background: #fee2e2; 
            border-radius: 12px; 
        }
        a { 
            display: block; 
            margin-top: 20px; 
            color: #667eea; 
        }
    </style>
</head>
<body>
<div class="login-box">
    <h1>🔐 Вход</h1>
    
    <?php if ($error_message): ?>
        <div class="error">❌ <?= h($error_message) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <!-- ============================================ -->
        <!-- ЗАЩИТА ОТ CSRF: скрытое поле с токеном -->
        <!-- ============================================ -->
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
        
        <input type="text" name="login" placeholder="Логин" required>
        <input type="password" name="pass" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>
    
    <a href="index.php">← Вернуться к анкете</a>
</div>
</body>
</html>