<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

// Получаем фильтры
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 999999;

// Получаем список купленных игр
$purchasedQuery = $conn->prepare("SELECT game_id FROM purchases WHERE user_id = ?");
$purchasedQuery->bind_param("i", $user_id);
$purchasedQuery->execute();
$purchasedResult = $purchasedQuery->get_result();

$purchasedIds = [];
while ($row = $purchasedResult->fetch_assoc()) {
    $purchasedIds[] = $row['game_id'];
}

// Формируем запрос с фильтрами
$query = "SELECT * FROM games WHERE price BETWEEN ? AND ?";
$params = [$min_price, $max_price];
$types = "dd";

if (!empty($genre_filter)) {
    $query .= " AND genre = ?";
    $params[] = $genre_filter;
    $types .= "s";
}

if (count($purchasedIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($purchasedIds), '?'));
    $query .= " AND id NOT IN ($placeholders)";
    $types .= str_repeat('i', count($purchasedIds));
    $params = array_merge($params, $purchasedIds);
}

$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Добавление в корзину
if (isset($_POST['add_to_cart'])) {
    $game_id = intval($_POST['game_id']);
    
    // Проверяем, не куплена ли уже игра
    $checkPurchase = $conn->prepare("SELECT id FROM purchases WHERE user_id = ? AND game_id = ?");
    $checkPurchase->bind_param("ii", $user_id, $game_id);
    $checkPurchase->execute();
    
    if ($checkPurchase->get_result()->num_rows == 0) {
        // Пытаемся добавить в корзину
        $addCart = $conn->prepare("INSERT IGNORE INTO cart (user_id, game_id) VALUES (?, ?)");
        $addCart->bind_param("ii", $user_id, $game_id);
        if ($addCart->execute()) {
            $message = "✅ Игра добавлена в корзину!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог — Mihari</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }

        header {
            background: #222;
            color: white;
            text-align: center;
            padding: 20px 0;
            font-size: 24px;
            font-weight: bold;
        }

        nav {
            background: #333;
            display: flex;
            justify-content: center;
            padding: 10px 0;
        }

        nav a {
            margin: 0 15px;
            text-decoration: none;
            color: white;
            opacity: 0.8;
            transition: 0.3s;
        }

        nav a:hover {
            opacity: 1;
        }

        nav a.active {
            opacity: 1;
            border-bottom: 2px solid #2575fc;
        }

        .filters {
            background: white;
            padding: 20px;
            margin: 20px auto;
            max-width: 1200px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters label {
            font-weight: bold;
            color: #333;
        }

        .filters select,
        .filters input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filters button {
            padding: 10px 20px;
            background: #2575fc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: bold;
        }

        .filters button:hover {
            background: #6a11cb;
        }

        .reset-btn {
            background: #666 !important;
        }

        .reset-btn:hover {
            background: #888 !important;
        }

        .section-title {
            text-align: center;
            font-size: 28px;
            margin: 40px 0 20px;
        }

        .games {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            padding: 0 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .game-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 220px;
            overflow: hidden;
            text-align: center;
            transition: 0.3s;
        }

        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .game-card img {
            width: 100%;
            height: 140px;
            object-fit: cover;
        }

        .game-card h3 {
            font-size: 18px;
            margin: 10px 10px 5px;
        }

        .game-genre {
            display: inline-block;
            padding: 4px 12px;
            background: #2575fc;
            color: white;
            border-radius: 15px;
            font-size: 12px;
            margin: 5px 0;
        }

        .game-card p {
            font-size: 14px;
            padding: 0 10px;
            color: #555;
            height: 50px;
            overflow: hidden;
        }

        .game-price {
            font-size: 18px;
            font-weight: bold;
            color: #00c853;
            margin: 10px 0;
        }

        .game-actions {
            display: flex;
            gap: 5px;
            padding: 10px;
        }

        .buy-btn, .cart-btn {
            flex: 1;
            padding: 8px 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .buy-btn {
            background: #2575fc;
            color: white;
        }

        .buy-btn:hover {
            background: #6a11cb;
        }

        .cart-btn {
            background: #00c853;
            color: white;
        }

        .cart-btn:hover {
            background: #009624;
        }

        .cart-icon {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #ff6b35;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: 0.3s;
            text-decoration: none;
        }

        .cart-icon:hover {
            transform: scale(1.1);
            background: #ff8c5a;
        }

        .no-games {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        footer {
            background: #222;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #00c853;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 1000;
            animation: slideIn 0.3s ease;
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
    <header>Mihari — Каталог игр</header>

    <nav>
        <a href="home.php">Главная</a>
        <a href="category.php" class="active">Категории</a>
        <a href="profile.php">Профиль</a>
        <a href="library.php">Библиотека</a>
        <a href="cart.php">🛒 Корзина</a>
        <?php if (isset($_SESSION['user_id'])): 
            $roleCheck = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $roleCheck->bind_param("i", $_SESSION['user_id']);
            $roleCheck->execute();
            $roleResult = $roleCheck->get_result()->fetch_assoc();
            if ($roleResult && $roleResult['role'] == 1):
        ?>
            <a href="admin.php">Панель</a>
        <?php endif; endif; ?>
    </nav>

    <form class="filters" method="get">
        <label>Жанр:</label>
        <select name="genre">
            <option value="">Все жанры</option>
            <option value="Песочница" <?= $genre_filter == 'Песочница' ? 'selected' : '' ?>>Песочница</option>
            <option value="РПГ" <?= $genre_filter == 'РПГ' ? 'selected' : '' ?>>РПГ</option>
            <option value="Стратегия" <?= $genre_filter == 'Стратегия' ? 'selected' : '' ?>>Стратегия</option>
            <option value="Шутер" <?= $genre_filter == 'Шутер' ? 'selected' : '' ?>>Шутер</option>
        </select>

        <label>Цена от:</label>
        <input type="number" name="min_price" step="0.01" value="<?= $min_price ?>" placeholder="0">

        <label>до:</label>
        <input type="number" name="max_price" step="0.01" value="<?= $max_price > 999998 ? '' : $max_price ?>" placeholder="∞">

        <button type="submit">🔍 Применить</button>
        <a href="category.php"><button type="button" class="reset-btn">↺ Сбросить</button></a>
    </form>

    <?php if (isset($message)): ?>
        <div class="message" id="message"><?= $message ?></div>
        <script>
            setTimeout(() => {
                const msg = document.getElementById('message');
                if (msg) msg.style.display = 'none';
            }, 3000);
        </script>
    <?php endif; ?>

    <h2 class="section-title">Найдено игр: <?= $result->num_rows ?></h2>

    <div class="games">
    <?php if ($result->num_rows > 0): 
        while ($row = $result->fetch_assoc()):
    ?>
        <div class="game-card">
            <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <?php if ($row['genre']): ?>
                <span class="game-genre"><?= htmlspecialchars($row['genre']) ?></span>
            <?php endif; ?>
            <p><?= htmlspecialchars(mb_substr($row['description'], 0, 60)) ?>...</p>
            <div class="game-price"><?= number_format($row['price'], 2) ?> ₽</div>
            <div class="game-actions">
                <a href="buy.php?id=<?= $row['id'] ?>" class="buy-btn">Купить</a>
                <form method="post" style="flex: 1; margin: 0;">
                    <input type="hidden" name="game_id" value="<?= $row['id'] ?>">
                    <button type="submit" name="add_to_cart" class="cart-btn">🛒</button>
                </form>
            </div>
        </div>
    <?php endwhile; else: ?>
        <div class="no-games">
            <h2>🎮 Игры не найдены</h2>
            <p>Попробуйте изменить параметры фильтров</p>
        </div>
    <?php endif; ?>
    </div>

    <a href="cart.php" class="cart-icon">🛒</a>

    <footer>
        <p>© 2025 Mihari</p>
    </footer>
</body>
</html>