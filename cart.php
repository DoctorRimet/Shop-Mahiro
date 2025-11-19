<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Получаем баланс пользователя
$userQuery = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();
$balance = $user['balance'] ?? 0;

// Удаление из корзины
if (isset($_POST['remove_from_cart'])) {
    $cart_id = intval($_POST['cart_id']);
    $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delete->bind_param("ii", $cart_id, $user_id);
    if ($delete->execute()) {
        $message = "✅ Игра удалена из корзины";
    }
}

// Покупка всех игр из корзины
if (isset($_POST['checkout'])) {
    // Получаем все игры из корзины
    $cartQuery = $conn->prepare("
        SELECT c.id as cart_id, g.id as game_id, g.price, g.title 
        FROM cart c 
        JOIN games g ON c.game_id = g.id 
        WHERE c.user_id = ?
    ");
    $cartQuery->bind_param("i", $user_id);
    $cartQuery->execute();
    $cartItems = $cartQuery->get_result();
    
    $totalPrice = 0;
    $games = [];
    
    while ($item = $cartItems->fetch_assoc()) {
        $totalPrice += $item['price'];
        $games[] = $item;
    }
    
    if (count($games) == 0) {
        $message = "⚠ Корзина пуста!";
    } elseif ($balance < $totalPrice) {
        $message = "❌ Недостаточно средств. Необходимо: " . number_format($totalPrice, 2) . " ₽";
    } else {
        // Начинаем транзакцию
        $conn->begin_transaction();
        
        try {
            // Списываем деньги
            $newBalance = $balance - $totalPrice;
            $updateBalance = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $updateBalance->bind_param("di", $newBalance, $user_id);
            $updateBalance->execute();
            
            // Добавляем покупки
            $addPurchase = $conn->prepare("INSERT INTO purchases (user_id, game_id, purchase_date) VALUES (?, ?, NOW())");
            foreach ($games as $game) {
                $addPurchase->bind_param("ii", $user_id, $game['game_id']);
                $addPurchase->execute();
            }
            
            // Очищаем корзину
            $clearCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearCart->bind_param("i", $user_id);
            $clearCart->execute();
            
            $conn->commit();
            $balance = $newBalance;
            $message = "✅ Покупка успешно завершена! Куплено игр: " . count($games);
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ Ошибка при оформлении покупки: " . $e->getMessage();
        }
    }
}

// Получаем игры из корзины
$query = $conn->prepare("
    SELECT c.id as cart_id, g.* 
    FROM cart c 
    JOIN games g ON c.game_id = g.id 
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$cartItems = [];
$totalPrice = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $totalPrice += $row['price'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина — Mihari</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        header {
            background: #222;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        header h1 {
            font-size: 24px;
        }

        header a {
            color: white;
            text-decoration: none;
            transition: 0.3s;
            font-weight: bold;
        }

        header a:hover {
            color: #2575fc;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .balance-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .balance-info h3 {
            color: #333;
        }

        .balance {
            color: #00c853;
            font-size: 24px;
            font-weight: bold;
        }

        .cart-items {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #eee;
            align-items: center;
            transition: 0.3s;
        }

        .cart-item:hover {
            background: #f9f9f9;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-info {
            flex: 1;
        }

        .item-info h3 {
            margin-bottom: 8px;
            color: #2575fc;
        }

        .item-genre {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #2575fc;
            border-radius: 15px;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 20px;
            font-weight: bold;
            color: #00c853;
            margin-right: 20px;
        }

        .remove-btn {
            padding: 8px 16px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: bold;
        }

        .remove-btn:hover {
            background: #d32f2f;
        }

        .cart-summary {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-price {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .total-amount {
            color: #00c853;
        }

        .checkout-btn {
            padding: 15px 40px;
            background: #2575fc;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: 0.3s;
        }

        .checkout-btn:hover {
            background: #6a11cb;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37,117,252,0.3);
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .empty-cart {
            text-align: center;
            padding: 100px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .empty-cart h2 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #666;
        }

        .empty-cart a {
            display: inline-block;
            margin-top: 30px;
            padding: 15px 40px;
            background: #2575fc;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: 0.3s;
        }

        .empty-cart a:hover {
            background: #6a11cb;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        .message.success {
            background: #00c853;
            color: white;
        }

        .message.error {
            background: #f44336;
            color: white;
        }

        .message.warning {
            background: #ff9800;
            color: white;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>🛒 Корзина</h1>
        <a href="home.php">← Вернуться в магазин</a>
    </header>

    <?php if ($message): 
        $msgClass = 'success';
        if (strpos($message, '❌') !== false) $msgClass = 'error';
        if (strpos($message, '⚠') !== false) $msgClass = 'warning';
    ?>
        <div class="message <?= $msgClass ?>" id="message"><?= $message ?></div>
        <script>
            setTimeout(() => {
                const msg = document.getElementById('message');
                if (msg) msg.style.display = 'none';
            }, 4000);
        </script>
    <?php endif; ?>

    <div class="container">
        <div class="balance-info">
            <h3>Ваш баланс:</h3>
            <div class="balance"><?= number_format($balance, 2) ?> ₽</div>
        </div>

        <?php if (count($cartItems) > 0): ?>
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        <div class="item-info">
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <?php if ($item['genre']): ?>
                                <span class="item-genre"><?= htmlspecialchars($item['genre']) ?></span>
                            <?php endif; ?>
                            <p><?= htmlspecialchars(mb_substr($item['description'], 0, 100)) ?>...</p>
                        </div>
                        <div class="item-price"><?= number_format($item['price'], 2) ?> ₽</div>
                        <form method="post">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <button type="submit" name="remove_from_cart" class="remove-btn">Удалить</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="total-price">
                    Итого: <span class="total-amount"><?= number_format($totalPrice, 2) ?> ₽</span>
                </div>
                <form method="post">
                    <button type="submit" name="checkout" class="checkout-btn" 
                            <?= $balance < $totalPrice ? 'disabled' : '' ?>>
                        <?= $balance < $totalPrice ? '💰 Недостаточно средств' : '✓ Оформить покупку' ?>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <h2>🛒 Корзина пуста</h2>
                <p>Добавьте игры из каталога, чтобы купить их</p>
                <a href="category.php">Перейти в каталог</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>