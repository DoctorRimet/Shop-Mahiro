<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

require_once "db.php";

// Добавление в корзину
if (isset($_POST['add_to_cart'])) {
    $game_id = intval($_POST['game_id']);

    // Проверяем, не куплена ли уже игра
    $checkPurchase = $conn->prepare("SELECT id FROM purchases WHERE user_id = ? AND game_id = ?");
    $checkPurchase->bind_param("ii", $user_id, $game_id);
    $checkPurchase->execute();

    if ($checkPurchase->get_result()->num_rows == 0) {
        $addCart = $conn->prepare("INSERT IGNORE INTO cart (user_id, game_id) VALUES (?, ?)");
        $addCart->bind_param("ii", $user_id, $game_id);
        $addCart->execute();
    }
}

// Получаем список купленных игр
$purchasedQuery = $conn->prepare("SELECT game_id FROM purchases WHERE user_id = ?");
$purchasedQuery->bind_param("i", $user_id);
$purchasedQuery->execute();
$purchasedResult = $purchasedQuery->get_result();

$purchasedIds = [];
while ($row = $purchasedResult->fetch_assoc()) {
    $purchasedIds[] = $row['game_id'];
}

// Получаем игры
if (count($purchasedIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($purchasedIds), '?'));
    $query = "SELECT * FROM games WHERE id NOT IN ($placeholders) ORDER BY id DESC";
    $stmt = $conn->prepare($query);

    $types = str_repeat('i', count($purchasedIds));
    $stmt->bind_param($types, ...$purchasedIds);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM games ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Mihari — Магазин игр</title>
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
            position: relative;
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

        .banner {
            width: 100%;
            height: 300px;
            background: url('banner.jpg') no-repeat center center/cover;
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
            display: block;
            text-align: center;
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

        footer {
            background: #222;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }

        footer a {
            color: #00aced;
            margin: 0 10px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header>Mihari</header>

    <nav>
        <a href="home.php" class="<?= ($current_page == 'home.php') ? 'active' : '' ?>">Главная</a>
        <a href="category.php" class="<?= ($current_page == 'category.php') ? 'active' : '' ?>">Категории</a>
        <a href="profile.php" class="<?= ($current_page == 'profile.php') ? 'active' : '' ?>">Профиль</a>
        <a href="library.php" class="<?= ($current_page == 'library.php') ? 'active' : '' ?>">Библиотека</a>
        <a href="cart.php">🛒 Корзина</a>
        <?php
        $roleCheck = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $roleCheck->bind_param("i", $_SESSION['user_id']);
        $roleCheck->execute();
        $roleResult = $roleCheck->get_result()->fetch_assoc();
        if ($roleResult && $roleResult['role'] == 1):
        ?>
            <a href="admin.php">Панель</a>
        <?php endif; ?>
    </nav>

    <div class="banner"></div>

    <h2 class="section-title">Лучшие игры</h2>

<div class="games">
<?php
if ($result && $result->num_rows > 0):
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
<?php
    endwhile;
else:
    echo "<p style='text-align:center;'>🎮 Все игры уже куплены или пока нет доступных игр</p>";
endif;
?>
</div>

    <a href="cart.php" class="cart-icon">🛒</a>

    <footer>
        <p>© 2025 Mihari</p>
        <a href="https://twitter.com">Twitter</a> |
        <a href="https://facebook.com">Facebook</a> |
        <a href="https://mangalib.me">MangaLib</a>
    </footer>
</body>
</html>