<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

$balance = isset($user['balance']) ? $user['balance'] : 0;

$count_query = $conn->prepare("SELECT COUNT(*) AS total FROM purchases WHERE user_id = ?");
$count_query->bind_param("i", $user_id);
$count_query->execute();
$count_result = $count_query->get_result()->fetch_assoc();
$game_count = $count_result['total'];

// Получаем историю покупок
$history_query = $conn->prepare("
    SELECT p.purchase_date, g.title, g.price, g.image
    FROM purchases p
    JOIN games g ON p.game_id = g.id
    WHERE p.user_id = ?
    ORDER BY p.purchase_date DESC
");
$history_query->bind_param("i", $user_id);
$history_query->execute();
$history_result = $history_query->get_result();

$message = "";

if (isset($_POST['update'])) {
    $new_name = trim($_POST['name']);
    $avatar = $user['avatar'];

    if (!empty($_FILES['avatar']['name'])) {
        $target_dir = "uploads/";
        $file_name = time() . "_" . basename($_FILES["avatar"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed = ["jpg", "jpeg", "png", "gif"];
        if (in_array($file_type, $allowed)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar = $target_file;
            } else {
                $message = "⚠ Ошибка при загрузке файла.";
            }
        } else {
            $message = "⚠ Разрешены только JPG, PNG, GIF.";
        }
    }

    $update = $conn->prepare("UPDATE users SET name = ?, avatar = ? WHERE id = ?");
    $update->bind_param("ssi", $new_name, $avatar, $user_id);

    if ($update->execute()) {
        $message = "✅ Профиль обновлён!";
        $user['name'] = $new_name;
        $user['avatar'] = $avatar;
        $_SESSION['user_name'] = $new_name;
    } else {
        $message = "❌ Ошибка при обновлении!";
    }
}

// === Обработка пополнения баланса ===
if (isset($_POST['add_balance'])) {
    $amount = floatval($_POST['amount']);
    $card_name = trim($_POST['card_name']);
    $card_number = preg_replace('/\D/', '', $_POST['card_number']);
    $card_exp = trim($_POST['card_exp']);

    if (strlen($card_number) < 12 || strlen($card_number) > 16) {
        $message = "⚠ Некорректный номер карты.";
    } elseif ($amount <= 0) {
        $message = "⚠ Введите корректную сумму.";
    } else {
        // Сохраняем только последние 4 цифры
        $last4 = substr($card_number, -4);

        // Обновляем данные пользователя
        $new_balance = $balance + $amount;
        $update = $conn->prepare("UPDATE users SET balance = ?, card_number_last4 = ?, card_name = ?, card_exp = ? WHERE id = ?");
        $update->bind_param("dsssi", $new_balance, $last4, $card_name, $card_exp, $user_id);
        $update->execute();

        $balance = $new_balance;
        $user['card_number_last4'] = $last4;
        $user['card_name'] = $card_name;
        $user['card_exp'] = $card_exp;
        $message = "✅ Баланс пополнен на " . number_format($amount, 2) . " ₽!";
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль — <?= htmlspecialchars($user['name']) ?></title>
    <style>
        body {
            margin: 0;
            background: linear-gradient(135deg, #0e0e0e, #1a1a1a);
            font-family: "Segoe UI", Arial, sans-serif;
            color: #fff;
        }

        header {
            background: #111;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }

        header h1 {
            margin: 0;
            font-size: 22px;
            color: #00aaff;
        }

        header a {
            color: #fff;
            text-decoration: none;
            transition: 0.2s;
            font-weight: bold;
        }

        header a:hover {
            color: #00aaff;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #181818;
            border-radius: 16px;
            box-shadow: 0 0 25px rgba(0,0,0,0.6);
            text-align: center;
        }

        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 15px rgba(0,170,255,0.3);
            border: 3px solid #00aaff;
        }

        .edit-btn, .balance-btn, .history-btn {
            margin-top: 20px;
            background: #00aaff;
            border: none;
            padding: 10px 25px;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            font-size: 15px;
            margin-right: 10px;
        }

        .edit-btn:hover, .balance-btn:hover, .history-btn:hover {
            background: #0077cc;
        }

        .logout {
            display: inline-block;
            margin-top: 25px;
            background: #aa0000;
            color: white;
            text-decoration: none;
            padding: 10px 25px;
            border-radius: 8px;
            transition: 0.3s;
            font-weight: bold;
        }

        .logout:hover {
            background: #cc0000;
        }

        /* Инфо строка */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0 10px 0;
            background: #111;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: inset 0 0 10px rgba(0,170,255,0.2);
            font-size: 18px;
            font-weight: 500;
        }

        .balance { color: #00ffcc; }
        .games {
            color: #ffaa00;
            cursor: pointer;
            transition: 0.3s;
        }
        .games:hover {
            color: #ffcc00;
            text-decoration: underline;
        }

        /* Модальные окна */
        .modal {
            display: none;
            position: fixed;
            z-index: 10;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #222;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            max-height: 80vh;
            overflow-y: auto;
            text-align: center;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }

        .modal-content h3 {
            color: #00aaff;
            margin-top: 0;
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content input[type="file"] {
            width: calc(100% - 20px);
            margin: 10px 0;
            padding: 10px;
            background: #333;
            color: white;
            border: none;
            border-radius: 8px;
        }

        .modal-content button {
            background: #00aaff;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            font-size: 16px;
        }

        .modal-content button:hover {
            background: #0077cc;
        }

        .close {
            position: absolute;
            top: 8px;
            right: 12px;
            color: white;
            font-size: 22px;
            cursor: pointer;
        }

        .message {
            margin-top: 10px;
            color: #00ff88;
        }

        /* История покупок */
        .history-item {
            background: #111;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: left;
        }

        .history-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .history-info {
            flex: 1;
        }

        .history-info h4 {
            margin: 0 0 5px 0;
            color: #00aaff;
        }

        .history-info p {
            margin: 0;
            font-size: 14px;
            color: #aaa;
        }

        .history-price {
            color: #00ff90;
            font-weight: bold;
        }

        .no-history {
            text-align: center;
            color: #aaa;
            padding: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Профиль</h1>
        <a href="home.php">← На главную</a>
    </header>

    <div class="container">
        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="avatar">
        <h2><?= htmlspecialchars($user['name']) ?></h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['gmail']) ?></p>
        <p><strong>Дата регистрации:</strong> <?= htmlspecialchars($user['date']) ?></p>

        <div class="info-row">
            <div class="balance">💰 Баланс: <?= number_format($balance, 2, '.', ' ') ?> ₽</div>
            <div class="games" onclick="window.location.href='library.php'">🎮 Игр куплено: <?= $game_count ?></div>
        </div>

        <button class="balance-btn" id="openBalanceModal">Пополнить баланс</button>
        <button class="history-btn" id="openHistoryModal">История покупок</button>
        <button class="edit-btn" id="openProfileModal">Редактировать профиль</button>

        <p class="message"><?= $message ?></p>
        <a href="logout.php" class="logout">Выйти</a>
    </div>

    <div class="modal" id="profileModal">
        <div class="modal-content">
            <span class="close" id="closeProfileModal">&times;</span>
            <h3>Редактирование профиля</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                <input type="file" name="avatar" accept="image/*">
                <button type="submit" name="update">Сохранить</button>
            </form>
        </div>
    </div>

    <div class="modal" id="balanceModal">
        <div class="modal-content">
            <span class="close" id="closeBalanceModal">&times;</span>
            <h3>Пополнить баланс</h3>
            <form method="post">
                <input type="text" name="card_name" placeholder="Имя на карте" value="<?= htmlspecialchars($user['card_name'] ?? '') ?>" required>
                <input type="text" name="card_number" maxlength="16" placeholder="Номер карты (только цифры)" required>
                <input type="text" name="card_exp" maxlength="5" placeholder="MM/YY" value="<?= htmlspecialchars($user['card_exp'] ?? '') ?>" required>
                <input type="number" name="amount" step="0.01" placeholder="Сумма (₽)" required>
                <button type="submit" name="add_balance">Пополнить</button>
            </form>
        </div>
    </div>

    <!-- не ЙОУ -->

    <div class="modal" id="historyModal">
        <div class="modal-content">
            <span class="close" id="closeHistoryModal">&times;</span>
            <h3>История покупок</h3>
            <?php if ($history_result->num_rows > 0): ?>
                <?php while ($item = $history_result->fetch_assoc()): ?>
                    <div class="history-item">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        <div class="history-info">
                            <h4><?= htmlspecialchars($item['title']) ?></h4>
                            <p><?= date('d.m.Y H:i', strtotime($item['purchase_date'])) ?></p>
                        </div>
                        <div class="history-price"><?= number_format($item['price'], 2) ?> ₽</div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-history">Пока нет покупок</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const profileModal = document.getElementById('profileModal');
        const openProfile = document.getElementById('openProfileModal');
        const closeProfile = document.getElementById('closeProfileModal');

        const balanceModal = document.getElementById('balanceModal');
        const openBalance = document.getElementById('openBalanceModal');
        const closeBalance = document.getElementById('closeBalanceModal');

        const historyModal = document.getElementById('historyModal');
        const openHistory = document.getElementById('openHistoryModal');
        const closeHistory = document.getElementById('closeHistoryModal');

        openProfile.onclick = () => profileModal.style.display = 'flex';
        closeProfile.onclick = () => profileModal.style.display = 'none';

        openBalance.onclick = () => balanceModal.style.display = 'flex';
        closeBalance.onclick = () => balanceModal.style.display = 'none';

        openHistory.onclick = () => historyModal.style.display = 'flex';
        closeHistory.onclick = () => historyModal.style.display = 'none';

        window.onclick = (e) => {
            if (e.target === profileModal) profileModal.style.display = 'none';
            if (e.target === balanceModal) balanceModal.style.display = 'none';
            if (e.target === historyModal) historyModal.style.display = 'none';
        };
    </script>
</body>
</html>