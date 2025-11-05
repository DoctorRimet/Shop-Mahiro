<?php
require_once "db.php";

$message = "";



if (isset($_POST['login'])) {
    $gmail = trim($_POST['gmail']);
    $password = trim($_POST['password']);

    if (!empty($gmail) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE gmail = ? AND password = ?");
        $stmt->bind_param("ss", $gmail, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            header("Location: home.php");
            exit;
        } else {
            $message = "⚠ Неверный логин или пароль!";
        }
        $stmt->close();
    } else {
        $message = "⚠ Введите email и пароль!";
    }
}

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "✅ Регистрация успешна! Теперь войдите.";
}


?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            font-family: Arial, sans-serif;
        }

        .circle-container {
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: conic-gradient(from 180deg, #ff6a00, #ee0979, #ff6a00);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 30px rgba(0,0,0,0.3);
            color: white;
            padding: 20px;
            text-align: center;
        }

        h2 {
            margin: 10px 0;
        }

        form {
            width: 80%;
            display: flex;
            flex-direction: column;
        }

        input {
            margin: 8px 0;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
        }

        button {
            margin-top: 12px;
            padding: 10px;
            border: none;
            border-radius: 15px;
            background: #ffffff;
            color: #ee0979;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #ee0979;
            color: #fff;
        }

        .message {
            margin-top: 15px;
            color: #fff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="circle-container">
        <h2>Вход</h2>
        <form method="post">
            <input type="email" name="gmail" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit" name="login">Войти</button>
        </form>

        <p class="message">
            <?= $message ?>
        </p>

        <p>
            Нет аккаунта? <a href="index.php" style="color:yellow;">Регистрация</a>
        </p>
    </div>
</body>
</html>
