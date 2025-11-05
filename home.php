<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);
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
            opacity: 0.4;
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
            width: 200px;
            overflow: hidden;
            text-align: center;
            transition: 0.3s;
        }

        .game-card:hover {
            transform: translateY(-5px);
        }

        .game-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .game-card h3 {
            font-size: 18px;
            margin: 10px 0 5px;
        }

        .game-card p {
            font-size: 14px;
            padding: 0 10px;
            color: #555;
            height: 50px;
            overflow: hidden;
        }

        .buy-btn {
            display: block;
            margin: 10px auto 15px;
            padding: 8px 15px;
            border: none;
            background: #2575fc;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
        }

        .buy-btn:hover {
            background: #6a11cb;
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
        <?php if ($_SESSION['user_id'] == 1): ?>
            <a href="admin.php">Панель</a>
        <?php endif; ?>
    </nav>

    <div class="banner"></div>

    <h2 class="section-title">Лучшие игры</h2>

<div class="games">
<?php
require_once "db.php";

$purchasedQuery = $conn->prepare("SELECT game_id FROM purchases WHERE user_id = ?");
$purchasedQuery->bind_param("i", $user_id);
$purchasedQuery->execute();
$purchasedResult = $purchasedQuery->get_result();

$purchasedIds = [];
while ($row = $purchasedResult->fetch_assoc()) {
    $purchasedIds[] = $row['game_id'];
}

if (count($purchasedIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($purchasedIds), '?'));
    $query = "SELECT * FROM games WHERE id NOT IN ($placeholders) ORDER BY id DESC";
    $stmt = $conn->prepare($query);

    $types = str_repeat('i', count($purchasedIds));
    $stmt->bind_param($types, ...$purchasedIds);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Если ничего не куплено, показываем все игры
    $result = $conn->query("SELECT * FROM games ORDER BY id DESC");
}

if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
?>
    <div class="game-card">
        <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><?= htmlspecialchars(mb_substr($row['description'], 0, 60)) ?>...</p>
        <p><strong><?= number_format($row['price'], 2) ?> ₽</strong></p>
        <a href="buy.php?id=<?= $row['id'] ?>" class="buy-btn">Купить</a>
    </div>
<?php
    endwhile;
else:
    echo "<p style='text-align:center;'>🎮 Все игры уже куплены или пока нет доступных игр</p>";
endif;
?>
</div>

    <footer>
        <p>© 2025 Mihari</p>
        <a href="https://twitter.com">Twitter</a> |
        <a href="https://facebook.com">Facebook</a> |
        <a href="https://mangalib.me">MangaLib</a>
    </footer>
</body>
</html>