<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$query = $conn->prepare("
    SELECT g.*
    FROM games g
    INNER JOIN purchases p ON g.id = p.game_id
    WHERE p.user_id = ?
    ORDER BY p.purchase_date DESC
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$games = [];
while ($row = $result->fetch_assoc()) {
    $games[] = $row;
}

$selected_game = isset($_GET['game_id']) ? intval($_GET['game_id']) : ($games[0]['id'] ?? null);

$game_details = null;
if ($selected_game) {
    $detailQuery = $conn->prepare("
        SELECT g.*
        FROM games g
        INNER JOIN purchases p ON g.id = p.game_id
        WHERE g.id = ? AND p.user_id = ?
    ");
    $detailQuery->bind_param("ii", $selected_game, $user_id);
    $detailQuery->execute();
    $game_details = $detailQuery->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Библиотека — Mihari</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #1b2838;
            color: #c7d5e0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Хедер */
        header {
            background: #171a21;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        header h1 {
            color: #66c0f4;
            font-size: 24px;
        }

        header a {
            color: #c7d5e0;
            text-decoration: none;
            transition: 0.3s;
            font-size: 14px;
        }

        header a:hover {
            color: #66c0f4;
        }

        /* Основной контейнер */
        .library-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Левая панель со списком игр */
        .games-sidebar {
            width: 320px;
            background: #16202d;
            overflow-y: auto;
            border-right: 1px solid #000;
        }

        .games-sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .games-sidebar::-webkit-scrollbar-track {
            background: #0e1419;
        }

        .games-sidebar::-webkit-scrollbar-thumb {
            background: #3d4f5c;
            border-radius: 4px;
        }

        .games-sidebar::-webkit-scrollbar-thumb:hover {
            background: #4a5f6f;
        }

        .sidebar-header {
            padding: 20px;
            background: #171d25;
            border-bottom: 1px solid #000;
        }

        .sidebar-header h2 {
            font-size: 18px;
            color: #66c0f4;
        }

        .game-item {
            padding: 15px 20px;
            border-bottom: 1px solid #0e1419;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .game-item:hover {
            background: #1e2836;
        }

        .game-item.active {
            background: #2a475e;
            border-left: 3px solid #66c0f4;
        }

        .game-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .game-item-info {
            flex: 1;
        }

        .game-item-info h3 {
            font-size: 14px;
            color: #c7d5e0;
            margin-bottom: 4px;
        }

        .game-item-info p {
            font-size: 12px;
            color: #8f98a0;
        }

        /* Правая панель с деталями игры */
        .game-details {
            flex: 1;
            background: #1b2838;
            overflow-y: auto;
            padding: 40px;
        }

        .game-details::-webkit-scrollbar {
            width: 8px;
        }

        .game-details::-webkit-scrollbar-track {
            background: #0e1419;
        }

        .game-details::-webkit-scrollbar-thumb {
            background: #3d4f5c;
            border-radius: 4px;
        }

        .game-banner {
            width: 100%;
            max-width: 900px;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            margin-bottom: 30px;
        }

        .game-title {
            font-size: 36px;
            color: #fff;
            margin-bottom: 20px;
        }

        .game-meta {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            padding: 20px;
            background: #16202d;
            border-radius: 8px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 12px;
            color: #8f98a0;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 16px;
            color: #c7d5e0;
        }

        .game-description {
            background: #16202d;
            padding: 25px;
            border-radius: 8px;
            line-height: 1.8;
            font-size: 15px;
            color: #acb2b8;
        }

        .game-description h3 {
            color: #66c0f4;
            margin-bottom: 15px;
        }

        .no-games {
            text-align: center;
            padding: 100px 20px;
            color: #8f98a0;
        }

        .no-games h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .no-games a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #66c0f4;
            color: #1b2838;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: 0.3s;
        }

        .no-games a:hover {
            background: #4a9fd8;
        }
    </style>
</head>
<body>
    <header>
        <h1>📚 Библиотека игр</h1>
        <a href="home.php">← Вернуться в магазин</a>
    </header>

    <div class="library-container">
        <?php if (count($games) > 0): ?>
            <div class="games-sidebar">
                <div class="sidebar-header">
                    <h2>Ваши игры (<?= count($games) ?>)</h2>
                </div>
                <?php foreach ($games as $game): ?>
                    <div class="game-item <?= ($game['id'] == $selected_game) ? 'active' : '' ?>"
                         onclick="window.location.href='library.php?game_id=<?= $game['id'] ?>'">
                        <img src="<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['title']) ?>">
                        <div class="game-item-info">
                            <h3><?= htmlspecialchars($game['title']) ?></h3>
                            <p><?= htmlspecialchars($game['genre'] ?? 'Игра') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ЙОУ -->

            <div class="game-details">
                <?php if ($game_details): ?>
                    <img src="<?= htmlspecialchars($game_details['image']) ?>" alt="<?= htmlspecialchars($game_details['title']) ?>" class="game-banner">

                    <h1 class="game-title"><?= htmlspecialchars($game_details['title']) ?></h1>

                    <div class="game-meta">
                        <?php if ($game_details['genre']): ?>
                        <div class="meta-item">
                            <div class="meta-label">Жанр</div>
                            <div class="meta-value"><?= htmlspecialchars($game_details['genre']) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($game_details['release_date']): ?>
                        <div class="meta-item">
                            <div class="meta-label">Дата выхода</div>
                            <div class="meta-value"><?= date('d.m.Y', strtotime($game_details['release_date'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="meta-item">
                            <div class="meta-label">Цена покупки</div>
                            <div class="meta-value"><?= number_format($game_details['price'], 2) ?> ₽</div>
                        </div>
                    </div>

                    <div class="game-description">
                        <h3>Об игре</h3>
                        <p><?= nl2br(htmlspecialchars($game_details['description'])) ?></p>
                    </div>
                <?php else: ?>
                    <div class="no-games">
                        <h2>Выберите игру из списка</h2>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="game-details">
                <div class="no-games">
                    <h2>📭 У вас пока нет игр</h2>
                    <p>Перейдите в магазин и приобретите свою первую игру!</p>
                    <a href="home.php">Перейти в магазин</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>