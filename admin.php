<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Проверка роли администратора
$roleCheck = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleCheck->bind_param("i", $user_id);
$roleCheck->execute();
$roleResult = $roleCheck->get_result()->fetch_assoc();

if (!$roleResult || $roleResult['role'] != 1) {
    header("Location: home.php");
    exit;
}

$message = "";
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'games';

// === УПРАВЛЕНИЕ ИГРАМИ ===

// Добавление игры
if (isset($_POST['add_game'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $genre = trim($_POST['genre']);
    $release_date = trim($_POST['release_date']);
    $image = $_POST['image_url'];

    if (!empty($_FILES['image_file']['name'])) {
        $target_dir = "uploads/";
        $file_name = time() . "_" . basename($_FILES["image_file"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
            $image = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO games (title, description, price, genre, release_date, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsss", $title, $description, $price, $genre, $release_date, $image);
    
    if ($stmt->execute()) {
        $message = "✅ Игра успешно добавлена!";
    } else {
        $message = "❌ Ошибка добавления игры";
    }
}

// Удаление игры
if (isset($_POST['delete_game'])) {
    $game_id = intval($_POST['game_id']);
    $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    
    if ($stmt->execute()) {
        $message = "✅ Игра удалена";
    } else {
        $message = "❌ Ошибка удаления игры";
    }
}

// Редактирование игры
if (isset($_POST['edit_game'])) {
    $game_id = intval($_POST['game_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $genre = trim($_POST['genre']);
    $release_date = trim($_POST['release_date']);
    $image = $_POST['current_image'];

    if (!empty($_FILES['image_file']['name'])) {
        $target_dir = "uploads/";
        $file_name = time() . "_" . basename($_FILES["image_file"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
            $image = $target_file;
        }
    } elseif (!empty($_POST['image_url'])) {
        $image = $_POST['image_url'];
    }

    $stmt = $conn->prepare("UPDATE games SET title = ?, description = ?, price = ?, genre = ?, release_date = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdsssi", $title, $description, $price, $genre, $release_date, $image, $game_id);
    
    if ($stmt->execute()) {
        $message = "✅ Игра обновлена!";
    } else {
        $message = "❌ Ошибка обновления игры";
    }
}

// === УПРАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯМИ ===

// Удаление пользователя
if (isset($_POST['delete_user'])) {
    $delete_user_id = intval($_POST['user_id']);
    if ($delete_user_id != $user_id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_user_id);
        
        if ($stmt->execute()) {
            $message = "✅ Пользователь удален";
        } else {
            $message = "❌ Ошибка удаления пользователя";
        }
    } else {
        $message = "⚠ Нельзя удалить самого себя!";
    }
}

// Изменение баланса
if (isset($_POST['change_balance'])) {
    $change_user_id = intval($_POST['user_id']);
    $new_balance = floatval($_POST['balance']);
    
    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->bind_param("di", $new_balance, $change_user_id);
    
    if ($stmt->execute()) {
        $message = "✅ Баланс пользователя обновлен!";
    } else {
        $message = "❌ Ошибка обновления баланса";
    }
}

// Редактирование пользователя
if (isset($_POST['edit_user'])) {
    $edit_user_id = intval($_POST['user_id']);
    $name = trim($_POST['name']);
    $gmail = trim($_POST['gmail']);
    $role = intval($_POST['role']);
    
    $stmt = $conn->prepare("UPDATE users SET name = ?, gmail = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssii", $name, $gmail, $role, $edit_user_id);
    
    if ($stmt->execute()) {
        $message = "✅ Данные пользователя обновлены!";
    } else {
        $message = "❌ Ошибка обновления данных";
    }
}

// Получаем все игры
$gamesQuery = $conn->query("SELECT * FROM games ORDER BY id DESC");
$games = [];
while ($row = $gamesQuery->fetch_assoc()) {
    $games[] = $row;
}

// Получаем всех пользователей
$usersQuery = $conn->query("SELECT u.*, COUNT(p.id) as games_count FROM users u LEFT JOIN purchases p ON u.id = p.user_id GROUP BY u.id ORDER BY u.id ASC");
$users = [];
while ($row = $usersQuery->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель — Mihari</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
        }

        header {
            background: #111;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        header h1 {
            color: #ff6b35;
        }

        header a {
            color: white;
            text-decoration: none;
            transition: 0.3s;
            font-weight: bold;
        }

        header a:hover {
            color: #ff6b35;
        }

        .tabs {
            background: #222;
            display: flex;
            justify-content: center;
            padding: 0;
        }

        .tab {
            padding: 15px 40px;
            background: #222;
            color: #aaa;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: 0.3s;
            font-weight: bold;
        }

        .tab:hover {
            color: #fff;
            background: #2a2a2a;
        }

        .tab.active {
            color: #ff6b35;
            border-bottom-color: #ff6b35;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .panel {
            background: #222;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }

        .panel h2 {
            color: #ff6b35;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #aaa;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: #333;
            border: 1px solid #444;
            border-radius: 8px;
            color: white;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: 0.3s;
            margin-right: 10px;
        }

        .btn-primary {
            background: #2575fc;
            color: white;
        }

        .btn-primary:hover {
            background: #1a5ec4;
        }

        .btn-success {
            background: #00c853;
            color: white;
        }

        .btn-success:hover {
            background: #009624;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .btn-warning {
            background: #ff9800;
            color: white;
        }

        .btn-warning:hover {
            background: #e68900;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .table th {
            background: #2a2a2a;
            color: #ff6b35;
            font-weight: bold;
        }

        .table tr:hover {
            background: #2a2a2a;
        }

        .table img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-admin {
            background: #ff6b35;
            color: white;
        }

        .badge-user {
            background: #2575fc;
            color: white;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        .message.success {
            background: #00c853;
        }

        .message.error {
            background: #f44336;
        }

        .message.warning {
            background: #ff9800;
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

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #222;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-content h3 {
            color: #ff6b35;
            margin-bottom: 20px;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: white;
            font-size: 28px;
            cursor: pointer;
            transition: 0.3s;
        }

        .close:hover {
            color: #ff6b35;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #aaa;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <h1>⚙️ Админ-панель</h1>
        <a href="home.php">← На главную</a>
    </header>

    <div class="tabs">
        <a href="?tab=games" class="tab <?= $activeTab == 'games' ? 'active' : '' ?>">🎮 Игры</a>
        <a href="?tab=users" class="tab <?= $activeTab == 'users' ? 'active' : '' ?>">👥 Пользователи</a>
    </div>

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
        <?php if ($activeTab == 'games'): ?>
            <!-- Статистика -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value"><?= count($games) ?></div>
                    <div class="stat-label">Всего игр</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $conn->query("SELECT COUNT(*) as cnt FROM purchases")->fetch_assoc()['cnt'] ?></div>
                    <div class="stat-label">Продано копий</div>
                </div>
            </div>

            <!-- Добавление игры -->
            <div class="panel">
                <h2>➕ Добавить новую игру</h2>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Название игры:</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Цена (₽):</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Жанр:</label>
                        <select name="genre" required>
                            <option value="">Выберите жанр</option>
                            <option value="Песочница">Песочница</option>
                            <option value="РПГ">РПГ</option>
                            <option value="Стратегия">Стратегия</option>
                            <option value="Шутер">Шутер</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата выхода:</label>
                        <input type="date" name="release_date">
                    </div>
                    <div class="form-group">
                        <label>URL изображения:</label>
                        <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="form-group">
                        <label>Или загрузить файл:</label>
                        <input type="file" name="image_file" accept="image/*">
                    </div>
                    <button type="submit" name="add_game" class="btn btn-success">Добавить игру</button>
                </form>
            </div>

            <!-- Список игр -->
            <div class="panel">
                <h2>📋 Все игры</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Изображение</th>
                            <th>Название</th>
                            <th>Жанр</th>
                            <th>Цена</th>
                            <th>Дата выхода</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($games as $game): ?>
                            <tr>
                                <td><?= $game['id'] ?></td>
                                <td><img src="<?= htmlspecialchars($game['image']) ?>" alt=""></td>
                                <td><?= htmlspecialchars($game['title']) ?></td>
                                <td><?= htmlspecialchars($game['genre'] ?? 'Не указан') ?></td>
                                <td><?= number_format($game['price'], 2) ?> ₽</td>
                                <td><?= $game['release_date'] ? date('d.m.Y', strtotime($game['release_date'])) : '-' ?></td>
                                <td>
                                    <button onclick="openEditGameModal(<?= htmlspecialchars(json_encode($game)) ?>)" class="btn btn-warning">Изменить</button>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                                        <button type="submit" name="delete_game" class="btn btn-danger" onclick="return confirm('Удалить эту игру?')">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- Статистика пользователей -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value"><?= count($users) ?></div>
                    <div class="stat-label">Всего пользователей</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count(array_filter($users, function($u) { return $u['role'] == 1; })) ?></div>
                    <div class="stat-label">Администраторов</div>
                </div>
            </div>

            <!-- Список пользователей -->
            <div class="panel">
                <h2>👥 Все пользователи</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Баланс</th>
                            <th>Игр куплено</th>
                            <th>Регистрация</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><?= htmlspecialchars($u['name']) ?></td>
                                <td><?= htmlspecialchars($u['gmail']) ?></td>
                                <td>
                                    <span class="badge <?= $u['role'] == 1 ? 'badge-admin' : 'badge-user' ?>">
                                        <?= $u['role'] == 1 ? 'Админ' : 'Пользователь' ?>
                                    </span>
                                </td>
                                <td><?= number_format($u['balance'], 2) ?> ₽</td>
                                <td><?= $u['games_count'] ?></td>
                                <td><?= date('d.m.Y', strtotime($u['date'])) ?></td>
                                <td>
                                    <button onclick="openEditUserModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="btn btn-warning">Изменить</button>
                                    <button onclick="openBalanceModal(<?= $u['id'] ?>, <?= $u['balance'] ?>)" class="btn btn-primary">Баланс</button>
                                    <?php if ($u['id'] != $user_id): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Удалить пользователя?')">Удалить</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Модальное окно редактирования игры -->
    <div id="editGameModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editGameModal')">&times;</span>
            <h3>Редактировать игру</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="game_id" id="edit_game_id">
                <input type="hidden" name="current_image" id="edit_game_current_image">
                <div class="form-group">
                    <label>Название:</label>
                    <input type="text" name="title" id="edit_game_title" required>
                </div>
                <div class="form-group">
                    <label>Описание:</label>
                    <textarea name="description" id="edit_game_description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Цена (₽):</label>
                    <input type="number" name="price" id="edit_game_price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Жанр:</label>
                    <select name="genre" id="edit_game_genre" required>
                        <option value="Песочница">Песочница</option>
                        <option value="РПГ">РПГ</option>
                        <option value="Стратегия">Стратегия</option>
                        <option value="Шутер">Шутер</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата выхода:</label>
                    <input type="date" name="release_date" id="edit_game_release">
                </div>
                <div class="form-group">
                    <label>URL изображения:</label>
                    <input type="text" name="image_url" id="edit_game_image_url">
                </div>
                <div class="form-group">
                    <label>Или загрузить новое:</label>
                    <input type="file" name="image_file" accept="image/*">
                </div>
                <button type="submit" name="edit_game" class="btn btn-success">Сохранить</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования пользователя -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editUserModal')">&times;</span>
            <h3>Редактировать пользователя</h3>
            <form method="post">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>Имя:</label>
                    <input type="text" name="name" id="edit_user_name" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="gmail" id="edit_user_gmail" required>
                </div>
                <div class="form-group">
                    <label>Роль:</label>
                    <select name="role" id="edit_user_role" required>
                        <option value="0">Пользователь</option>
                        <option value="1">Администратор</option>
                    </select>
                </div>
                <button type="submit" name="edit_user" class="btn btn-success">Сохранить</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно изменения баланса -->
    <div id="balanceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('balanceModal')">&times;</span>
            <h3>Изменить баланс</h3>
            <form method="post">
                <input type="hidden" name="user_id" id="balance_user_id">
                <div class="form-group">
                    <label>Новый баланс (₽):</label>
                    <input type="number" name="balance" id="balance_amount" step="0.01" required>
                </div>
                <button type="submit" name="change_balance" class="btn btn-success">Сохранить</button>
            </form>
        </div>
    </div>

    <script>
        function openEditGameModal(game) {
            document.getElementById('edit_game_id').value = game.id;
            document.getElementById('edit_game_title').value = game.title;
            document.getElementById('edit_game_description').value = game.description;
            document.getElementById('edit_game_price').value = game.price;
            document.getElementById('edit_game_genre').value = game.genre || '';
            document.getElementById('edit_game_release').value = game.release_date || '';
            document.getElementById('edit_game_image_url').value = game.image || '';
            document.getElementById('edit_game_current_image').value = game.image || '';
            document.getElementById('editGameModal').classList.add('active');
        }

        function openEditUserModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_user_name').value = user.name;
            document.getElementById('edit_user_gmail').value = user.gmail;
            document.getElementById('edit_user_role').value = user.role;
            document.getElementById('editUserModal').classList.add('active');
        }

        function openBalanceModal(userId, currentBalance) {
            document.getElementById('balance_user_id').value = userId;
            document.getElementById('balance_amount').value = currentBalance;
            document.getElementById('balanceModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>