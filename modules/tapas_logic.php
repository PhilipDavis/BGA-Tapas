<?php
// © Copyright 2024, Philip Davis (mrphilipadavis AT gmail)

define('TAPAS_TILETYPE_CROQ_1', 1);
define('TAPAS_TILETYPE_CROQ_2', 2);
define('TAPAS_TILETYPE_CROQ_3', 3);
define('TAPAS_TILETYPE_CROQ_4', 4);
define('TAPAS_TILETYPE_JALA_1', 5);
define('TAPAS_TILETYPE_JALA_2', 6);
define('TAPAS_TILETYPE_JALA_3', 7);
define('TAPAS_TILETYPE_JALA_4', 8);
define('TAPAS_TILETYPE_TICKET', 9);
define('TAPAS_TILETYPE_NAPKIN', 10);
define('TAPAS_TILETYPE_KETCHUP', 11);
define('TAPAS_TILETYPE_MAYO', 12);
define('TAPAS_TILETYPE_WASABI', 13);
define('TAPAS_TILETYPE_TOAST', 14);


class Tapas
{
    private $data;

    private $TapasTiles = [
        [ 'type' => 'blank' ], // Start with a zero to represent blank / no tile
    ];

    private function __construct($data)
    {
        $this->data = $data;

        foreach (range(1, 12) as $i)
        {
            $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_JALA_1, 'tapas' => 'jala', 'value' => 1 ];
            $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_CROQ_1, 'tapas' => 'croq', 'value' => 1 ];
        }
        
        foreach (range(1, 3) as $i)
        {
            $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_JALA_2, 'tapas' => 'jala', 'value' => 2 ];
            $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_CROQ_2, 'tapas' => 'croq', 'value' => 2 ];
        }
        
        foreach (range(1, 2) as $i)
        {
            $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_JALA_3, 'tapas' => 'jala', 'value' => 3 ];
            $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_CROQ_3, 'tapas' => 'croq', 'value' => 3 ];
        }
        
        $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_JALA_4, 'tapas' => 'jala', 'value' => 4 ];
        $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_CROQ_4, 'tapas' => 'croq', 'value' => 4 ];
        
        $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_TOAST, 'tapas' => 'extra' ];
        $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_KETCHUP, 'tapas' => 'extra' ]; // 38
        $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_MAYO, 'tapas' => 'extra' ]; // 39
        $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_WASABI, 'tapas' => 'extra' ]; // 40
        $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_NAPKIN, 'tapas' => 'extra' ];
        $this->TapasTiles[] = [ 'type' => TAPAS_TILETYPE_TICKET, 'tapas' => 'extra' ];
    }

    static function fromJson($json)
    {
        return new Tapas(json_decode($json));
    }

    static function newGame($playerIds, $options)
    {
        // 6x6 Board with starting tiles for base game
        $board = [
              0, 101, 102, 103, 104,   0,
            405,   0,   0,   0,   0, 212,
            406,   0,   0,   0,   0, 211,
            407,   0,   0,   0,   0, 210,
            408,   0,   0,   0,   0, 209,
              0, 316, 315, 314, 313,   0,
        ];

        // Depending on board variant, assign random tiles
        // to the special slots (depends on the board)
        $specialSlots = [];
        switch ($options['layout'])
        {
            case 2:
                $specialSlots[] = [ 4, 3 ];
                $specialSlots[] = [ 3, 4 ];
                break;
            case 3:
                $specialSlots[] = [ 5, 2 ];
                $specialSlots[] = [ 2, 3 ];
                $specialSlots[] = [ 4, 5 ];
                break;
            case 4:
                $specialSlots[] = [ 4, 2 ];
                $specialSlots[] = [ 5, 3 ];
                $specialSlots[] = [ 2, 4 ];
                $specialSlots[] = [ 4, 4 ];
                $specialSlots[] = [ 3, 5 ];
                break;
        }

        $extraTiles = [ 37, 38, 39, 40, 41, 42 ];
        shuffle($extraTiles);
        
        foreach ($specialSlots as $coords)
        {
            $index = Tapas::_makeIndex($coords[0], $coords[1], 6);
            $rotations = [ 100, 200, 300, 400 ];
            //shuffle($rotations); // Nah, don't randomize the rotation on Extras tiles -- looks better if they're all upright at start
            $board[$index] = $rotations[2] + array_pop($extraTiles);
        }

        //
        // Set up the remaining player tiles and randomize who gets which
        //
        $startingInventory = [
            'jala' => [ 17, 19, 21, 23, 25, 27, 29, 31, 33, 35 ],
            'croq' => [ 18, 20, 22, 24, 26, 28, 30, 32, 34, 36 ],
        ];
        $assignment = [
            'jala',
            'croq',
        ];
        shuffle($assignment);

        return new Tapas((object)[
            'version' => 1, // Only need to increment for breaking changes after beta release
            'options' => (array)$options,
            'nextPlayer' => $playerIds[0],
            'order' => $playerIds,
            'players' => (object)[
                $playerIds[0] => (object)[
                    'tapas' => $assignment[0],
                    'inventory' => $startingInventory[$assignment[0]],
                    'captured' => [],
                ],
                $playerIds[1] => (object)[
                    'tapas' => $assignment[1],
                    'inventory' => $startingInventory[$assignment[1]],
                    'captured' => [],
                ],
            ],
            'lastMove' => [ 0, 0, 0, 0 ], // x, y, dx, dy
            'rotations' => 0, // For Burning Head variation
            'alreadyRotated' => false, // For Burning Head; indicates if we've already rotated for the first player
            'removed' => [],
            'board' => $board,
            'width' => 6,
            'height' => 6,
            'moves' => 0,
        ]);
    }
    
    //
    // We're going to define our top-left corner as (1, 1)
    // and the bottom-right corner is at (width, height)
    //
    private function makeIndex($x, $y)
    {
        return Tapas::_makeIndex($x, $y, $this->data->width); 
    }
    
    public static function _makeIndex($x, $y, $w)
    {
        return ($y - 1) * $w + $x - 1; 
    }

    public function getTileAt($x, $y)
    {
        if ($x < 1 || $x > $this->data->width || $y < 1 || $y > $this->data->height)
            return 0;
        $index = $this->makeIndex($x, $y);
        return $this->data->board[$index];
    }
    
    public function setTileAt($x, $y, $tileId)
    {
        if ($x < 1 || $x > $this->data->width || $y < 1 || $y > $this->data->height)
            return;
        $index = $this->makeIndex($x, $y);
        $this->data->board[$index] = $tileId;
    }

    public function isFirstPlayer($playerId)
    {
        return $this->data->order[0] == $playerId;
    }

    public function getNextPlayerId()
    {
        return $this->data->nextPlayer;
    }

    public function getOtherPlayerId()
    {
        $playerIds = array_keys((array)$this->data->players);
        $nextPlayerIndex = array_search($this->data->nextPlayer, $playerIds);
        return $playerIds[1 - $nextPlayerIndex];
    }

    //
    // Place a tile in or around the board.
    // Our board is (1,1) - (W,H) but the player can
    // place tiles outside the board e.g. at (0, 4)
    // or e.g. at (3, H + 1). The tile will be placed
    // at ($x, $y) and pushed in the ($dx, $dy) direction.
    // Cannot move diagonally.
    //
    public function placeTile($tileId, $x, $y, $dx, $dy, &$captured)
    {
        if ($dx > 1 || $dx < -1 || $dy > 1 || $dy < -1) return false;
        if (!$dx && !$dy) return false;

        $playerId = $this->getNextPlayerId();
        $otherPlayerId = $this->getOtherPlayerId();

        $legalMoves = $this->getLegalMoves($boardRotations);

        // First, rotate the board according to Burning Head variation rules
        if ($this->getOption('burningHead'))
        {
            $this->data->rotations += $boardRotations;

            // Ensure that the first player doesn't rotate the board on second
            // of two turns (e.g. if the player captured the napkin and got to
            // play an additional tile)
            $this->data->alreadyRotated = $this->isFirstPlayer($playerId);
        }
        
        if (!$this->isLegalMove($legalMoves, $x, $y, $dx, $dy))
            return false;

        $this->data->lastMove = [ $x, $y, $dx, $dy ];

        if (array_search($tileId, $this->data->players->$playerId->inventory) === false)
            return false;
        $this->data->players->$playerId->inventory = array_values(array_diff($this->data->players->$playerId->inventory, [ $tileId ]));

        $tileType = $this->TapasTiles[$tileId % 100];
        $distance = $tileType['value'];
        $spacesToCollapse = $distance;

        $displaced = [];

        for ($i = $x + $dx, $j = $y + $dy; $i >= 1 && $i <= $this->data->width && $j >= 1 && $j <= $this->data->height; $i += $dx, $j += $dy)
        {
            $tile = $this->getTileAt($i, $j);
            if (!$tile && $spacesToCollapse)
            {
                $spacesToCollapse--;
                continue; // space is collapsed
            }
            array_push($displaced, $tile);
        }

        // Place the new tile; set rotation of the tile based on side it's entering from
        $dir = [ $dx, $dy ];
        $rotation = Tapas::getTileRotationFromDirection($dir);

        // Add the placed tile at the front of the list
        array_unshift($displaced, $tileId + $rotation);

        // Add a space for every shift (minus one because spaces on the other side start from 2 pushes and up)
        for ($d = 2; $d <= $distance; $d++)
            array_unshift($displaced, 0);

        // Set the tiles in their new positions
        $i = $x + $dx;
        $j = $y + $dy;
        while (count($displaced) && $i >= 1 && $i <= $this->data->width && $j >= 1 && $j <= $this->data->height)
        {
            $this->setTileAt($i, $j, array_shift($displaced));
            $i += $dx;
            $j += $dy;
        }

        $capturedTicket = count(array_filter($displaced, fn($tileId) => $this->getTileType($tileId) == TAPAS_TILETYPE_TICKET)) > 0;
        $capturedNapkin = false;

        $captured = [
            'nobody' => [],
            $playerId => [],
            $otherPlayerId => [],
        ];

        // Any remaining displaced tiles are collected by the players
        while (!empty($displaced))
        {
            $capturedTile = array_shift($displaced) % 100;
            $capturedNapkin |= $this->TapasTiles[$capturedTile]['type'] == TAPAS_TILETYPE_NAPKIN;

            // Don't count any tiles removed during the same turn as the Ticket tile
            if ($capturedTicket)
            {
                array_push($this->data->removed, $capturedTile);
                array_push($captured['nobody'], $capturedTile);
                continue;
            }

            $capturingPlayerId = $this->captureTile($capturedTile);
            if ($capturingPlayerId)
                array_push($captured[$capturingPlayerId], $capturedTile);
        }

        // It's now the other player's turn (unless the napkin was captured)
        if (!$capturedNapkin)
            $this->data->nextPlayer = $this->getOtherPlayerId();

        // Switch to the other player if the next player has no inventory left to play
        // (e.g. if the second player to play played a napkin and took a double turn)
        // (e.g. if the player captures the napkin with their last tile)
        $nextPlayerId = $this->data->nextPlayer;
        if (!count($this->data->players->$nextPlayerId->inventory))
            $this->data->nextPlayer = $this->getOtherPlayerId();    

        $this->data->moves++;
        return true;
    }

    private function captureTile($tileId)
    {
        $tileId = $tileId % 100;
        $tile = $this->TapasTiles[$tileId];
        if ($tile['type'] == 'blank')
            return;
    
        $playerId = $this->data->nextPlayer;
        $player = $this->data->players->$playerId;
        if ($tile['tapas'] != $player->tapas)
        {
            $this->data->players->$playerId->captured[] = $tileId;
            return $playerId;
        }
        else
        {
            $otherPlayerId = $this->getOtherPlayerId();
            $this->data->players->$otherPlayerId->captured[] = $tileId;
            return $otherPlayerId;
        }
    }

    public function isLegalMove($legalMoves, $x, $y, $dx, $dy)
    {
        // We'll be lazy and just compute all legal moves and check against
        // the list. It's just a 6x6 grid which yields 4 + 4 + 4 + 4 checks.
        $matchingMoves = array_filter($legalMoves, fn($xy) => $xy[0] == $x && $xy[1] == $y && $xy[2] == gmp_sign($dx) && $xy[3] == gmp_sign($dy));
        return count($matchingMoves) > 0;
    }

    public function isPlayer($playerId)
    {
        return array_key_exists($playerId, $this->data->players);
    }

    public function getGameProgression()
    {
        $startingInventory = 20;
        $inventoryCount = 0;
        foreach ($this->data->players as $player)
            $inventoryCount += count($player->inventory);

        // According to new rules, the game is over immediately if
        // a player collects all three of ketchup, mayo, and wasabi.
        $this->getScores($playerIdWithKetchupMayoWasabi);
        if ($playerIdWithKetchupMayoWasabi)
            return 100;

        return 100 * ($startingInventory - $inventoryCount) / $startingInventory;
    }

    public function getOption($optionName)
    {
        return $this->data->options->$optionName;
    }

    public function getRotations()
    {
        return $this->data->rotations;
    }

    public function getLegalMoves(&$boardRotations)
    {
        $playerId = $this->data->nextPlayer;
        $tapas = $this->data->players->$playerId->tapas;

        $legalMoves = [];

        // Scan down the rows, first left side then right side
        for ($y = 2; $y <= $this->data->height - 1; $y++)
        {
            // For each row, look left to right until we find a tile
            for ($x = 0; $x < $this->data->width; $x++)
            {
                // Keep searching if the next tile is blank
                $nextTileId = $this->getTileAt($x + 1, $y) % 100;
                if (!$nextTileId) continue;

                // Can only push opponent's piece and the Extras tiles
                $nextTileType = $this->TapasTiles[$nextTileId];
                if ($nextTileType['tapas'] == $tapas) break;

                // We found a legal move on this line; add it and go to next line
                $legalMoves[] = [ $x, $y, 1, 0 ];
                break;
            }

            // Now, look right to left
            for ($x = $this->data->width + 1; $x > 1; $x--)
            {
                // Keep searching if the next tile is blank
                $nextTileId = $this->getTileAt($x - 1, $y) % 100;
                if (!$nextTileId) continue;

                // Can only push opponent's piece and the Extras tiles
                $nextTileType = $this->TapasTiles[$nextTileId];
                if ($nextTileType['tapas'] == $tapas) break;

                // We found a legal move on this line; add it and go to next line
                $legalMoves[] = [ $x, $y, -1, 0 ];
                break;
            }
        }

        // Scan across the columns, first top side then bottom side
        for ($x = 2; $x <= $this->data->width - 1; $x++)
        {
            // For each column, look top to bottom until we find a tile
            for ($y = 0; $y < $this->data->height; $y++)
            {
                // Keep searching if the next tile is blank
                $nextTileId = $this->getTileAt($x, $y + 1) % 100;
                if (!$nextTileId) continue;

                // Can only push opponent's piece and the Extras tiles
                $nextTileType = $this->TapasTiles[$nextTileId];
                if ($nextTileType['tapas'] == $tapas) break;

                // We found a legal move on this column; add it and go to next column
                $legalMoves[] = [ $x, $y, 0, 1 ];
                break;
            }

            // Now, look bottom to top
            for ($y = $this->data->height + 1; $y > 1; $y--)
            {
                // Keep searching if the next tile is blank
                $nextTileId = $this->getTileAt($x, $y - 1) % 100;
                if (!$nextTileId) continue;

                // Can only push opponent's piece and the Extras tiles
                $nextTileType = $this->TapasTiles[$nextTileId];
                if ($nextTileType['tapas'] == $tapas) break;

                // We found a legal move on this column; add it and go to next column
                $legalMoves[] = [ $x, $y, 0, -1 ];
                break;
            }
        }

        // Cannot push back on the line that was just pushed
        $legalMoves = array_values(array_filter($legalMoves, fn($move) => !$this->isPushingOppositeToLastMove($move)));

        if ($this->getOption('burningHead'))
        {
            // Active player may only play tiles from his/her facing side of the board
            $activePlayerId = $this->getNextPlayerId();
            $legalDirection =
                array_key_first((array)$this->data->players) == $activePlayerId
                    ? [ 1, 0 ]
                    : [ -1, 0 ];

            // Take current rotations into account
            for ($i = 0; $i < $this->data->rotations % 4; $i++)
                $legalDirection = Tapas::rotateDirectionCcw($legalDirection);

            // Rotate once for the start of the next turn if this is player 1 (except first move of the game).
            // Do not rotate the board if the first player is taking a second turn in a row.
            $boardRotations = 0;
            if ($this->data->moves && $this->isFirstPlayer($playerId) && !$this->data->alreadyRotated)
            {
                $legalDirection = Tapas::rotateDirectionCcw($legalDirection);
                $boardRotations++;
            }

            while ($boardRotations <= 4)
            {
                $burningHeadLegalMoves = array_filter($legalMoves, function($move) use ($legalDirection) {
                    return $move[2] == $legalDirection[0] && $move[3] == $legalDirection[1];
                });
                if (count($burningHeadLegalMoves))
                    break;
                $boardRotations++;

                // Rather than actually rotate the board 90deg clockwise,
                // we'll just rotate the legal direction -90deg and then
                // rely on the client UI to rotate the board.
                $legalDirection = Tapas::rotateDirectionCcw($legalDirection);
            }
            $legalMoves = array_values($burningHeadLegalMoves);
        }

        return $legalMoves;
    }

    public function isPushingOppositeToLastMove($move)
    {
        // Is pushing back on the same column (same x but opposite dy)
        if ($move[0] == $this->data->lastMove[0] && $move[3] && $move[3] == -$this->data->lastMove[3])
            return true;

        // Is pushing back on the same row (same y but opposite dx)
        if ($move[1] == $this->data->lastMove[1] && $move[2] && $move[2] == -$this->data->lastMove[2])
            return true;

        return false;
    }

    //
    // Return the tile rotation index given a tile direction [ dx, dy ]
    //
    static public function getTileRotationFromDirection($dir)
    {
        $rotation = 100;
        if ($dir[0] == 1) $rotation = 400;
        else if ($dir[0] == -1) $rotation = 200;
        else if ($dir[1] == -1) $rotation = 300;
        return $rotation;
    }

    static public function rotateDirectionCw($dir)
    {
        return [
            -$dir[1],
            $dir[0]
        ];
    }

    static public function rotateDirectionCcw($dir)
    {
        return [
            $dir[1],
            -$dir[0]
        ];
    }

    public function getTileType($tileId)
    {
        return $this->TapasTiles[$tileId % 100]['type'];
    }

    public function getTileValue($tileId)
    {
        $tile = $this->TapasTiles[$tileId % 100];
        return array_key_exists('value', $tile) ? $tile['value'] : 0;
    }

    public function getBoard()
    {
        return $this->data->board;
    }

    public function getScores(&$playerIdWithKetchupMayoWasabi)
    {
        $playerScores = [];
        foreach ($this->data->players as $playerId => $player)
        {
            $sum = 0;
            $countOfKetchupMayoWasabi = 0;
            foreach ($player->captured as $tileId)
            {
                $tile = $this->TapasTiles[$tileId % 100];
                if (array_key_exists('value', $tile))
                    $sum += $tile['value'];
                
                else if ($tile['type'] == TAPAS_TILETYPE_TOAST)
                    $sum += 5;

                else if ($tile['type'] == TAPAS_TILETYPE_KETCHUP || $tile['type'] == TAPAS_TILETYPE_MAYO || $tile['type'] == TAPAS_TILETYPE_WASABI)
                    $countOfKetchupMayoWasabi++;
            }
            switch ($countOfKetchupMayoWasabi)
            {
                case 1: $sum += 2; break;
                case 2: $sum += 5; break;
                case 3: $sum += 10;
                    // Note: the pre-release rules said Ketchup + Mayo + Wasabi was worth 10 points
                    // but the final rules don't say anything about points for this. Instead, the
                    // player who collects all three automatically wins the game.
                    $playerIdWithKetchupMayoWasabi = $playerId;
                    break;
            }
            $playerScores[$playerId] = $sum;
        }
        return $playerScores;
    }

    public function getTieBreakerScores()
    {
        $playerScores = array_map(function($player) {
            $sum = 0;
            foreach ($player->captured as $tileId)
            {
                $tile = $this->TapasTiles[$tileId % 100];
                if (!array_key_exists('value', $tile))
                    continue;
                $value = intval($tile['value']);
                $sum += pow(10, $value - 1);
            }
            return $sum;
        }, (array)$this->data->players);
        
        return $playerScores;
    }

    // Return only the public data and the data private to the given player 
    public function getPlayerData($playerId)
    {
        // All game state is public in Tapas
        return $this->data;
    }

    function toJson()
    {
        return json_encode($this->data);
    }
}
