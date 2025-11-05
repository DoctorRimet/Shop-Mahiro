<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$userQuery = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();
$balance = $user['balance'] ?? 0;

// Проверяем id игры
if (!isset($_GET['id'])) {
    echo "Ошибка: не указана игра.";
    exit;
}

$game_id = intval($_GET['id']);
$gameQuery = $conn->prepare("SELECT * FROM games WHERE id = ?");
$gameQuery->bind_param("i", $game_id);
$gameQuery->execute();
$game = $gameQuery->get_result()->fetch_assoc();

if (!$game) {
    echo "Игра не найдена.";
    exit;
}

$message = "";
$show_modal = false;

if (isset($_POST['add_balance'])) {
    $amount = floatval($_POST['amount']);
    $card_name = trim($_POST['card_name']);
    $card_number = preg_replace('/\D/', '', $_POST['card_number']);
    $card_exp = trim($_POST['card_exp']);

    if (strlen($card_number) < 12 || strlen($card_number) > 16) {
        $message = "⚠ Некорректный номер карты.";
        $show_modal = true;
    } elseif ($amount <= 0) {
        $message = "⚠ Введите корректную сумму.";
        $show_modal = true;
    } else {

        $last4 = substr($card_number, -4);
        $new_balance = $balance + $amount;

        $update = $conn->prepare("UPDATE users SET balance = ?, card_number_last4 = ?, card_name = ?, card_exp = ? WHERE id = ?");
        $update->bind_param("dsssi", $new_balance, $last4, $card_name, $card_exp, $user_id);
        $update->execute();

        $balance = $new_balance;
        $message = "✅ Баланс успешно пополнен на " . number_format($amount, 2) . " ₽! Карта **** {$last4} сохранена.";
    }
}

if (isset($_POST['buy_game'])) {
    if ($balance < $game['price']) {
        $message = "❌ Недостаточно средств. Пожалуйста, пополните баланс.";
        $show_modal = true;
    } else {
        $new_balance = $balance - $game['price'];
        $update = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $update->bind_param("di", $new_balance, $user_id);
        $update->execute();

        $purchaseQuery = $conn->prepare("INSERT INTO purchases (user_id, game_id, purchase_date) VALUES (?, ?, NOW())");
        $purchaseQuery->bind_param("ii", $user_id, $game_id);
        $purchaseQuery->execute();

        $message = "✅ Вы успешно купили игру: " . htmlspecialchars($game['title']);
        $balance = $new_balance;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($game['title']) ?> — Купить</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #111, #222);
            color: #fff;
        }
        .container {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: flex-start;
            gap: 40px;
            padding: 60px;
        }
        .game-image {
            flex: 0 0 40%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.6);
        }
        .game-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .info { flex: 0 0 50%; }
        h1 {
            font-size: 36px;
            margin-bottom: 10px;
            color: #00aaff;
        }
        p {
            line-height: 1.6;
            font-size: 16px;
            color: #ddd;
        }
        .price {
            font-size: 22px;
            color: #00ff90;
            margin: 20px 0;
            font-weight: bold;
        }
        .buy-btn {
            display: inline-block;
            background: #00aaff;
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }
        .buy-btn:hover { background: #0088cc; }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover { color: #fff; }

        .message {
            margin-top: 15px;
            padding: 10px 15px;
            background: #333;
            border-radius: 8px;
            font-size: 15px;
        }

        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #222;
            padding: 25px;
            border-radius: 12px;
            width: 350px;
            position: relative;
        }
        .modal-content h2 {
            color: #00aaff;
            margin-bottom: 15px;
            text-align: center;
        }
        .modal-content input {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 6px;
            border: none;
            outline: none;
            background: #333;
            color: white;
        }
        .modal-content button {
            background: #00aaff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: 0.3s;
        }
        .modal-content button:hover {
            background: #0088cc;
        }
        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: 0.3s;
        }
        .close:hover {
            color: #ff5555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="game-image">
            <img src="<?= htmlspecialchars($game['image'] ?: 'uploads/noimage.png') ?>" alt="<?= htmlspecialchars($game['title']) ?>">
        </div>

        <div class="info">
            <h1><?= htmlspecialchars($game['title']) ?></h1>
            <p><?= nl2br(htmlspecialchars($game['description'])) ?></p>
            <div class="price">Цена: <?= number_format($game['price'], 2) ?> ₽</div>

            <form method="post">
                <button type="submit" name="buy_game" class="buy-btn">Оформить покупку</button>
            </form>

            <?php if ($message): ?>
                <div class="message"><?= $message ?></div>
            <?php endif; ?>

            <br><a href="home.php" class="back-link">← Вернуться на главную</a>
        </div>
    </div>

    <div id="modal" class="modal <?= $show_modal ? 'active' : '' ?>">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Пополнить баланс</h2>
            <form method="post">
                <input type="text" name="card_name" placeholder="Имя на карте" required>
                <input type="text" name="card_number" maxlength="16" placeholder="Номер карты (только цифры)" required>
                <input type="text" name="card_exp" maxlength="5" placeholder="MM/YY" required>
                <input type="number" name="amount" step="0.01" placeholder="Сумма пополнения (₽)" required>
                <button type="submit" name="add_balance">Пополнить</button>
            </form>
        </div>
    </div>

    <script>
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>