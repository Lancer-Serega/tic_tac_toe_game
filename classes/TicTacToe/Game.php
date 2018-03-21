<?php

namespace TicTacToe;

/**
 * Логика игры "крестики-нолики"
 */
class Game
{

    /**
     * Идентификатор пустой ячейки
     */
    const TILE_EMPTY = 0;

    /**
     * Идентификаторы игроков
     */
    const PLAYER = ['HUMAN' => 1, 'AI' => 2];

    /**
     * @var array Критические (для AI) комбинации игрока
     */
    private static $rows = [
        [
            [0,0],
            [1,0],
            [2,0],
        ],
        [
            [0,1],
            [1,1],
            [2,1],
        ],
        [
            [0,2],
            [1,2],
            [2,2],
        ],

        [
            [0,0],
            [0,1],
            [0,2],
        ],
        [
            [1,0],
            [1,1],
            [1,2],
        ],
        [
            [2,0],
            [2,1],
            [2,2],
        ],

        [
            [0,0],
            [1,1],
            [2,2],
        ],
        [
            [2,0],
            [1,1],
            [0,2],
        ],
    ];

    /**
     * Размер игрового поля по оси X (ширина)
     */
    private $fieldWidth;

    /**
     * Размер игрового поля по оси Y (высота)
     */
    private $fieldHeight;

    /**
     * @var int число крестиков или ноликов в ряд для победы.
     */
    private $countToWin;

    /**
     * @var array массив сделанных ходов вида $field[$x][$y] = $player;
     */
    private $field = [];

    /**
     * @var array $winnerCells аналогичен $field, но хранит только клетки, которые
     * надо выделить при отображении победившей комбинации.
     */
    private $winnerCells = [];

    /**
     * @var int Идентификатор текущего игрока
     */
    private $currentPlayer;
    private $winner;

    /**
     * TicTacToe Game constructor.
     *
     * @param int $fieldWidth  Ширина поля для игры
     * @param int $fieldHeight Высота поля для игры
     * @param int $countToWin  Количество для выигрыша (сколько в ряду подряд должны стоять маркеры одного игрока для выигрыша)
     */
    public function __construct($fieldWidth = 3, $fieldHeight = 3, $countToWin = 3)
    {
        $this->fieldWidth = $fieldWidth ?: 3;
        $this->fieldHeight = $fieldHeight ?: 3;
        $this->countToWin = $countToWin ?: 3;
        $this->fieldConstruct();
        $this->currentPlayer = self::PLAYER['HUMAN'];
    }

    /**
     * Строим игровое поле
     */
    private function fieldConstruct()
    {
        for ($x = 0; $x < $this->getFieldHeight(); $x++) {
            for ($y = 0; $y < $this->getFieldWidth(); $y++) {
                $this->field[$x][$y] = self::TILE_EMPTY;
            }
        }
    }

    /**
     * Обрабатывает очередной ход. Ставит в указанные координаты на поле
     * символ текущего игрока. Передаёт ход другому игроку, а в случае победы
     * опреляет победителя.
     *
     * Это единственная функция, которая может менять состояние игры.
     *
     * @param int $x
     * @param int $y
     */
    public function makeMove($x, $y)
    {
        // Учитываем ход, если выполняются все условия:
        // 1) игра ещё идет,
        // 2) клетка находится в пределах игрового поля.
        // 3) в поле на указанном месте ещё пусто,
        if (
            $x >= 0 && $x < $this->fieldWidth
            && $y >= 0 && $y < $this->fieldHeight
            && $this->field[$x][$y] === self::TILE_EMPTY
        ) {
            $this->field[$x][$y] = $this->getCurrentPlayer();
            $this->setCurrentPlayer($this->getCurrentPlayer() === self::PLAYER['HUMAN'] ?self::PLAYER['AI'] :self::PLAYER['HUMAN']);

            $this->checkWinner();

            $bestRating = 0;
            $bestMoveAxix = $this->getBestMove(false,$bestRating);
            $this->setCurrentPlayer($this->getCurrentPlayer() === self::PLAYER['AI'] ?self::PLAYER['HUMAN'] :self::PLAYER['AI']);
            $this->field[$bestMoveAxix['x']][$bestMoveAxix['y']] = self::PLAYER['AI'];
            $this->checkWinner();
        }
    }

    /**
     * Ищем выигрешную комбинацию, проверяя 4 направления (горизонталь, вертикаль и 2 диагонали).
     */
    private function checkWinner()
    {
        for ($y = 0; $y < $this->fieldHeight; $y++) {
            for ($x = 0; $x < $this->fieldWidth; $x++) {
                $this->checkLine($x, $y, 1, 0);
                $this->checkLine($x, $y, 1, 1);
                $this->checkLine($x, $y, 0, 1);
                $this->checkLine($x, $y, -1, 1);
            }
        }

        if ($this->winner) {
            $this->setCurrentPlayer(null);
        }
    }

    /**
     * Проверяет, а не находится ли в этом месте поля выигрышная комбинация
     * из необходимого числа крестиков или ноликов.
     * Если выигрышная комбинация найдена, запоминает победителя
     * и саму выигрышную комбинацию в массиве winnerCells.
     *
     * @param int $startX начальная точка, от которой проверять наличие комбинации (по оси X)
     * @param int $startY начальная точка, от которой проверять наличие комбинации (по оси Y)
     * @param int $dx     направление, в котором искать комбинацию по оси X (ширине)
     * @param int $dy     направление, в котором искать комбинацию по оси Y (высоте)
     */
    private function checkLine($startX, $startY, $dx, $dy)
    {
        $x = $startX;
        $y = $startY;
        $field = $this->field;
        $value = isset($field[$x][$y]) ? $field[$x][$y] : null;
        $cells = [];
        $foundWinner = false;

        if ($value) {
            $cells[] = [$x,$y];
            $foundWinner = true;
            for ($i = 1; $i < $this->countToWin; $i++) {
                $x += $dx;
                $y += $dy;
                $value2 = isset($field[$x][$y]) ? $field[$x][$y] : null;
                if ($value2 === $value) {
                    $cells[] = [$x,$y];
                } else {
                    $foundWinner = false;
                    break;
                }
            }
        }

        if ($foundWinner) {
            foreach ($cells as $cell) {
                $this->winnerCells[$cell[0]][$cell[1]] = $value;
            }
            $this->winner = $value;
        }
    }

    /**
     * Проверка пользователя на валидность
     *
     * @param int $player Идентификатор пользователя
     *
     * @return bool true если пользователя валиден
     *
     */
    public function checkPlayer($player)
    {
        return in_array($player, [self::PLAYER['HUMAN'],self::PLAYER['AI']], true);
    }

    /**
     * Получить наилудшую позицию для хода
     *
     * @param bool $stopAI Попытка остановить AI для победы
     * @param int  $bestRating Лучший рейтинг
     *
     * @return array|bool Позиция лучшего хода
     *
     */
    public function getBestMove($stopAI = true, &$bestRating = 0)
    {
        // Проверка на валидного пользователя
        if (!$this->checkPlayer($this->getCurrentPlayer())) {
            return false;
        }

        // Проверка не завершилась ли игра
        if ($this->isEnded()) {
            return false;
        }

        // Небольшой хук для того что бы тормознуть AI (если не можете его выиграть)
        if ($stopAI) {
            $aiRating = 0;

            // Получить самые наилучшие координаты для хода
            $aiBestPos = $this->getBestMove(false, $aiRating);
        }

        $bestAxix = []; // Массив с наилучшими координатами для хода
        $bestRating = 0; // Рейтинг координаты (Когда есть несколько хороших ходов, выбираем лучший по этому рейтингу)
        foreach ($this->field as $posX => $col) {
            foreach ($col as $posY => $tile) {
                $rating = $this->rateMove($posX, $posY);
                // Отметка для хода доступна
                if ($rating !== -1) {
                    if ($rating > $bestRating) {
                        $bestAxix = [['x' => $posX,'y' => $posY]];
                        $bestRating = $rating;
                    } elseif ($rating == $bestRating) {
                        $bestAxix[] = ['x' => $posX,'y' => $posY];
                    }
                }
            }
        }

        // Остановка после выигрыша
        if ($stopAI && $aiRating >= 10 && $bestRating < 10) {
            return $aiBestPos;
        }

        // Выбираем случайные, лучшуе координаты для хода
        if (count($bestAxix) >= 0) {
            $choice = array_rand($bestAxix);

            return $bestAxix[$choice];
        }

        return false;
    }

    /**
     * Расчитать положение хода
     *
     * @param int $posX Позиция X
     * @param int $posY Позиция Y
     *
     * @return int Общий рейтинг (чем выше, тем лучше)
     *
     */
    public function rateMove($posX, $posY)
    {
        // Не правильно сходили (возможно вышли из-за предела игрового поля)
        if (!$this->isAvailable($posX, $posY)) {
            return -1;
        }

        // Проверка на валидность игрока
        if (!$this->checkPlayer($this->getCurrentPlayer())) {
            return -1;
        }

        // всего положений
        $total = 0;
        foreach ($this::$rows as $row) {
            // Пропускаем несвязанные строки
            if (!in_array([$posX, $posY], $row, true)) {
                continue;
            }

            $countAI = 0;
            $countPlayer = 0;
            // количество плиток игрока/противника в строке
            foreach ($row as $tileAxis) {
                list($tileX, $tileY) = $tileAxis;
                $tile = $this->field[$tileX][$tileY];
                if ($tile === $this->getCurrentPlayer()) {
                    $countPlayer++;
                } elseif ($tile && $tile !== $this->getCurrentPlayer()) {
                    $countAI++;
                }
            }

            if ($countPlayer === 2 && !$countAI) {
                $total += 10;
            } elseif ($countPlayer === 1 && !$countAI) {
                $total += 2;
            } elseif (!$countPlayer && !$countAI) {
                ++$total;
            }
        }

        return $total;
    }

    /**
     * Сделать ход если доступен
     *
     * @param int $posX Позиция X
     * @param int $posY Позиция Y
     *
     * @return bool true если ход доступен
     *
     */
    public function isAvailable($posX, $posY)
    {
        $posX = (int)$posX;
        $posY = (int)$posY;
        if (!$this->checkTile($posX, $posY)) {
            return false;
        }

        if (!$this->field[$posX][$posY]) {
            return true;
        }

        return false;
    }

    /**
     * Проверка не завершилась ли игра
     *
     * @return bool true если игра окончена
     *
     */
    public function isEnded()
    {
        if ($this->getWinner()) {
            return true;
        }

        $ctaken = 0;
        foreach ($this->field as $col) {
            foreach ($col as $tile) {
                if ($tile) {
                    $ctaken++;
                }
            }
        }

        return $ctaken == 9;
    }

    /**
     * Проверка валидности если
     *
     * @param int $posX Позиция X
     * @param int $posY Позиция Y
     *
     * @return bool true если валидно
     *
     */
    public function checkTile($posX, $posY)
    {
        $resPosX = in_array($posX, [self::TILE_EMPTY,self::PLAYER['HUMAN'],self::PLAYER['AI']], true);
        $resPosY = in_array($posY, [self::TILE_EMPTY,self::PLAYER['HUMAN'],self::PLAYER['AI']], true);

        return !(!$resPosX || !$resPosY);
    }

    public function getCurrentPlayer()
    {
        return $this->currentPlayer;
    }

    public function setCurrentPlayer($player = null)
    {
        $this->currentPlayer = $player;
    }

    public function getWinner()
    {
        return $this->winner;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getWinnerCells()
    {
        return $this->winnerCells;
    }

    public function getFieldWidth()
    {
        return $this->fieldWidth;
    }

    public function getFieldHeight()
    {
        return $this->fieldHeight;
    }

}
