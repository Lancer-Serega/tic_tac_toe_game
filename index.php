<?php
// Подключаем объявление класса игры.
require_once __DIR__ . '/classes/TicTacToe/Game.php';
use TicTacToe\Game;

session_start();

// Получаем из сессии текущую игру.
$game = isset($_SESSION['game'])
    ? $_SESSION['game']
    : null;

// Если игры еще нет, создаём новую.
if(!$game || !is_object($game)) {
    $game = new Game();
}

// Обрабатываем запрос пользователя, выполняя нужное действие.
$params = array_merge($_GET, $_POST);
if(isset($params['action'])) {
    $action = $params['action'];

    if($action === 'move') {
        // Обрабатываем ход пользователя.
        $game->makeMove((int)$params['x'], (int)$params['y']);
    } elseif($action === 'newGame') {
        // Пользователь решил начать новую игру.
        $game = new Game();
    }
}

// Добавляем вновь созданную игру в сессию.
$_SESSION['game'] = $game;

// Отображаем текущее состояние игры в виде HTML страницы.
$width = $game->getFieldWidth();
$height = $game->getFieldHeight();
$field = $game->getField();
$winnerCells = $game->getWinnerCells();

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Игра "крестики - нолики"</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php if($game->getWinner()) { ?>
    <!-- Отображаем сообщение о победителе -->
    Победил игрок
    <div class="icon player<?= $game->getWinner(); ?>"></div>!
<?php } ?>

<!-- Рисуем игровое поле, отображая сделанные ходы и подсвечивая победившую комбинацию. -->
<div class="ticTacField">
    <?php for($y = 0; $y < $height; $y++) { ?>
        <div class="ticTacRow">
            <?php for($x = 0; $x < $width; $x++) {
                // $player - игрок, сходивший в эту клетку :), или null, если клетка свободна.
                $player = isset($field[$x][$y])
                    ? $field[$x][$y]
                    : null;

                // $winner - флаг, означающий, что эта клетка должна быть подсвечена при победе.
                $winner = isset($winnerCells[$x][$y]);
                $class = ($player ?' player' . $player :'') . ($winner ?' winner' :'');
                ?>
                <div class="ticTacCell<?= $class; ?>">
                    <?php if(!$player) { ?>
                        <!-- Клетка свободна. Отображаем здесь ссылку,
                        на которую нужно кликнуть для совершения хода. -->
                        <a href="?action=move&amp;x=<?= $x; ?>&amp;y=<?= $y; ?>"></a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<br/><a href="?action=newGame">Начать новую игру</a>

</body>
</html>
