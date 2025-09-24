<?php
require_once "db.php";

$message = "";

if (isset($_POST['register'])) {
    $gmail = trim($_POST['gmail']);
    $password = trim($_POST['password']);
    $name = trim($_POST['name']);

    if (!empty($gmail) && !empty($password) && !empty($name)) {
        $check = $conn->prepare("SELECT id FROM users WHERE gmail = ?");
        $check->bind_param("s", $gmail);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "⚠ Такой email уже зарегистрирован!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, gmail, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $gmail, $password); 
            if ($stmt->execute()) {
                $message = "✅ Регистрация успешна! Теперь войдите.";
            } else {
                $message = "Ошибка регистрации: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $message = "⚠ Заполните все поля!";
    }
}

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
            $message = "✅ Добро пожаловать, " . htmlspecialchars($user['name']) . "!";
        } else {
            $message = "⚠ Неверный логин или пароль!";
        }
        $stmt->close();
    } else {
        $message = "⚠ Введите email и пароль!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация и Вход</title>
</head>
<body>
    <h2>Регистрация</h2>
    <form method="post">
        <input type="text" name="name" placeholder="Ваше имя" required><br>
        <input type="email" name="gmail" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Пароль" required><br>
        <button type="submit" name="register">Зарегистрироваться</button>
    </form>

    <h2>Вход</h2>
    <form method="post">
        <input type="email" name="gmail" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Пароль" required><br>
        <button type="submit" name="login">Войти</button>
    </form>

    <p style="color:blue;">
        <?= $message ?>
    </p>
</body>
</html>
