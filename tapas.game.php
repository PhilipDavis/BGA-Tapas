<?php
 /**
  *------
  * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
  * Tapas implementation : © Copyright 2024, Philip Davis (mrphilipadavis AT gmail)
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  */

require_once(APP_GAMEMODULE_PATH.'module/table/table.game.php');
require_once('modules/tapas_logic.php');

define('TAPAS_VARIANT_BURNINGHEAD', 101);
define('TAPAS_BURNINGHEAD_NO', 1);
define('TAPAS_BURNINGHEAD_YES', 2);


class Tapas extends Table
{
	function __construct()
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels([
            // Game Options
            "redMojo" => 100,
            "burningHead" => 101,
        ]);
	}
	
    protected function getGameName()
    {
		// Used for translations and stuff. Please do not modify.
        return "tapas";
    }	

    //
    // This method is called only once, when a new game is launched.
    // In this method, you must setup the game according to the game
    // rules, so that the game is ready to be played.
    //
    protected function setupNewGame($players, $options = [])
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfo = $this->getGameinfos();
        $defaultColors = $gameinfo['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach($players as $playerId => $player)
        {
            $color = array_shift($defaultColors);
            $values[] = "('".$playerId."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."')";
        }
        $sql .= implode(',', $values);
        $this->DbQuery($sql);
        $this->reattributeColorsBasedOnPreferences($players, $gameinfo['player_colors']);
        $this->reloadPlayersBasicInfos();
        
        $playerIds = array_keys($players);
        $playerCount = count($playerIds);

        /************ Start the game initialization *****/

        //
        // Init game statistics
        //
        $this->initStat('player', 'max_capture', 0);
        $this->initStat('player', 'num_captures', 0);
        
        $this->initStat('player', 'points_capturing_1', 0);
        $this->initStat('player', 'points_capturing_2', 0);
        $this->initStat('player', 'points_capturing_3', 0);
        $this->initStat('player', 'points_capturing_4', 0);
        $this->initStat('player', 'points_capturing_extras', 0);
        $this->initStat('player', 'points_from_opponent', 0);
        
        $this->initStat('player', 'points_playing_1', 0);
        $this->initStat('player', 'points_playing_2', 0);
        $this->initStat('player', 'points_playing_3', 0);
        $this->initStat('player', 'points_playing_4', 0);
        $this->initStat('player', 'points_surrendered', 0);
            

        $tapasOptions = [
            'layout' => intval($this->getGameStateValue('redMojo')),
            'burningHead' => $this->getGameStateValue('burningHead') == TAPAS_BURNINGHEAD_YES,
        ];
        
        $tapas = TapasLogic::newGame($playerIds, $tapasOptions);
        $this->initializeGameState($tapas);

        // Must set the first active player
        $this->activeNextPlayer();
    }

    //
    // Gather all informations about current game situation (visible by the current player).
    // The method is called each time the game interface is displayed to a player,
    // i.e. when the game starts and when a player refreshes the game page (F5).
    //
    protected function getAllDatas()
    {
        $currentPlayerId = $this->getCurrentPlayerId();
        $tapas = $this->loadGameState();
        $tapas->getLegalMoves($boardRotations);
        return [
            'tapas' => $tapas->getPlayerData($currentPlayerId),
            'scores' => $tapas->getScores($playerIdWithKetchupMayoWasabi),
            'rotations' => $tapas->getRotations() + $boardRotations,
        ];
    }

    //
    // Compute and return the current game progression. The number returned must be
    // an integer beween 0 (the game just started) and 100 (the game is finished).
    //
    function getGameProgression()
    {
        $tapas = $this->loadGameState();
        return $tapas->getGameProgression();
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Database functions
////////////

    protected function initializeGameState($tapas)
    {
        $json = $tapas->toJson();
        $this->DbQuery("INSERT INTO game_state (doc) VALUES ('$json')");
    }

    protected function loadGameState()
    {
        $json = $this->getObjectFromDB("SELECT id, doc FROM game_state LIMIT 1")['doc'];
        return TapasLogic::fromJson($json);
    }

    protected function saveGameState($tapas)
    {
        $json = $tapas->toJson();
        $this->DbQuery("UPDATE game_state SET doc = '$json'");
    }

    protected function getPlayerScores()
    {
        return array_map(fn($s) => intval($s), $this->getCollectionFromDB('SELECT player_id, player_score FROM player', true));
    }

    protected function setPlayerScore($playerId, $score, $scoreAux)
    {
        $this->DbQuery(<<<SQL
            UPDATE player
            SET player_score = '$score',
                player_score_aux = '$scoreAux'
            WHERE player_id = '$playerId'
        SQL);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    protected function validateCaller()
    {
        // Get the function name of the caller -- https://stackoverflow.com/a/11238046
        $fnName = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $actionName = explode('_', $fnName)[1];
        self::checkAction($actionName);

        // Active player is whose turn it is
        $activePlayerId = self::getActivePlayerId();

        // Current player is who made the AJAX call to us
        $currentPlayerId = self::getCurrentPlayerId();

        // Bail out if the current player is not the active player
        if ($activePlayerId != $currentPlayerId)
            throw new BgaVisibleSystemException(self::_("It is not your turn"));

        return $activePlayerId;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    function action_placeTile($tileId, $x, $y, $dx, $dy)
    {
        $playerId = $this->validateCaller();

        $captured = [];
        $tapas = $this->loadGameState();
        if (!$tapas->placeTile($tileId, $x, $y, $dx, $dy, $captured))
        {
            $refId = uniqid();
            self::trace(implode(', ', [
                'Ref #' . $refId . ': placeTile failed',
                'player: ' . $playerId,
                'tileId: ' . $tileId,
                'x: ' . $x,
                'y: ' . $y,
                'dx: ' . $dx,
                'dy: ' . $dy,
                'state: ' . $tapas->toJson()
            ]));
            throw new BgaVisibleSystemException("Invalid operation - Ref #" . $refId); // NOI18N
        }
        $this->saveGameState($tapas);

        $this->afterPlaceTile($tapas, $playerId, $tileId, $x, $y, $dx, $dy, $captured);
    }

    //
    // This functionality is the same whether called by a real player
    // or a zombie player. The logic is extracted into a shared function
    // to ensure same behaviour for both cases.
    //
    function afterPlaceTile($tapas, $activePlayerId, $tileId, $x, $y, $dx, $dy, $captured)
    {
        //
        // Update score in the database
        //
        $playerIdWithKetchupMayoWasabi = 0;
        $scores = $tapas->getScores($playerIdWithKetchupMayoWasabi);
        $scoresAux = $tapas->getTieBreakerScores();
        $previousScores = $this->getPlayerScores();

        // Adjust for Ketchup, Mayo, Wasabi -- which ends the game
        // but it's possible to collect all three and still have a 
        // lower score. So we need to hack the score to ensure the
        // winner is correctly identified by BGA.
        if ($playerIdWithKetchupMayoWasabi)
        {
            $loserPlayerIds = array_filter(array_keys((array)$scores), fn($id) => $id != $playerIdWithKetchupMayoWasabi);
            $loserPlayerId = $loserPlayerIds[array_key_first($loserPlayerIds)]; // There must be a better way...
            $loserScore = $scores[$loserPlayerId];
            $winnerScore = $scores[$playerIdWithKetchupMayoWasabi];
            if ($winnerScore < $loserScore)
                $scores[$playerIdWithKetchupMayoWasabi] = $loserScore + 1;
        }

        foreach ($scores as $playerId => $score)
            $this->setPlayerScore($playerId, $score, $scoresAux[$playerId]);

        //
        // Update the player stats
        //
        $activePlayerPointsThisTile = $scores[$activePlayerId] - $previousScores[$activePlayerId];
        $otherPlayerIds = array_filter(array_keys((array)$scores), fn($id) => $id != $activePlayerId);
        $otherPlayerId = $otherPlayerIds[array_key_first($otherPlayerIds)]; // There must be a better way...
        $otherPlayerPointsThisTile = $scores[$otherPlayerId] - $previousScores[$otherPlayerId];

        if ($otherPlayerPointsThisTile)
        {
            $this->incStat($otherPlayerPointsThisTile, 'points_surrendered', $activePlayerId);
            $this->incStat($otherPlayerPointsThisTile, 'points_from_opponent', $otherPlayerId);
        }

        if ($activePlayerPointsThisTile)
        {
            $maxCapture = $this->getStat('max_capture', $activePlayerId);
            $this->setStat(max([ $maxCapture, $activePlayerPointsThisTile ]), 'max_capture', $activePlayerId);

            $this->incStat(1, 'num_captures', $activePlayerId);
        }

        $value = $tapas->getTileValue($tileId);
        if ($value)
            $this->incStat($activePlayerPointsThisTile, 'points_playing_' . $value, $activePlayerId);

        foreach ($captured[$activePlayerId] as $capturedTileId)
        {
            $value = $tapas->getTileValue($capturedTileId);
            if ($value)
                $this->incStat($value, 'points_capturing_' . $value, $activePlayerId);
        }

        $extrasPoints = $scores[$activePlayerId] - (
            $this->getStat('points_capturing_1', $activePlayerId) +
            $this->getStat('points_capturing_2', $activePlayerId) +
            $this->getStat('points_capturing_3', $activePlayerId) +
            $this->getStat('points_capturing_4', $activePlayerId) +
            $this->getStat('points_from_opponent', $activePlayerId)
        );
        $this->setStat($extrasPoints, 'points_capturing_extras', $activePlayerId);


        //
        // Send notifications to players
        //
        $this->notifyAllPlayers('tilePlayed', clienttranslate('${playerName} plays ${tileId:getTileHtml}'), [
            'playerName' => $this->getPlayerNameById($activePlayerId),
            'playerId' => $activePlayerId,
            'tileId' => $tileId,
            'x' => $x,
            'y' => $y,
            'dx' => $dx,
            'dy' => $dy,
            'board' => $tapas->getBoard(),
            'scores' => $scores,
            'preserve' => [ 'playerId', 'x', 'y', 'dx', 'dy', 'board', 'scores' ],
        ]);

        foreach ($captured as $playerId => $tileIds)
        {
            if (!count($tileIds))
                continue;
            $this->notifyAllPlayers('tilesCaptured', clienttranslate('${playerName} captures ${tileIds:getTilesHtml}'), [
                'playerName' => $playerId == 'nobody' ? clienttranslate('Nobody') : $this->getPlayerNameById($playerId),
                'tileIds' => $tileIds,
            ]);
        }

        // Capturing the napkin tile will cause the player to have another turn
        // (unless the game is over now from ketchup, mayo, and wasabi)
        if ($playerIdWithKetchupMayoWasabi)
        {
            $this->notifyAllPlayers('earlyFinish', clienttranslate('${playerName} has captured all three of ${tileId1:getTileHtml}, ${tileId2:getTileHtml}, and ${tileId3:getTileHtml}'), [
                'playerName' => $this->getPlayerNameById($activePlayerId),
                'tileId1' => 38, // ketchup
                'tileId2' => 39, // mayo
                'tileId3' => 40, // wasabi
            ]);
        }
        else if ($tapas->getNextPlayerId() == $activePlayerId)
        {
            $this->notifyAllPlayers('playAgain', clienttranslate('${playerName} gets to play again'), [
                'playerName' => $this->getPlayerNameById($activePlayerId),
            ]);
        }

        $this->gamestate->nextState('endTurn');
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argsPlayerTurn()
    {
        $tapas = $this->loadGameState();

        $rotations = 0;
        $moves = $tapas->getLegalMoves($rotations);

        $args = [
            'moves' => $moves,
        ];

        if ($tapas->getOption('burningHead'))
            $args['rotations'] = $rotations;

        return $args;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stNextTurn()
    {
        $tapas = $this->loadGameState();

        if ($tapas->getGameProgression() >= 100)
        {
            $this->gamestate->nextState('gameOver');
            return;
        }

        if ($tapas->getOption('burningHead'))
        {
            $tapas->getLegalMoves($boardRotations);
            if ($boardRotations)
            {
                $msg =
                    $boardRotations == 1
                        ? clienttranslate('The board rotates')
                        : clienttranslate('The board rotates ${n} times');
                $this->notifyAllPlayers('boardRotated', $msg, [
                    'n' => $boardRotations,
                    'total' => $tapas->getRotations() + $boardRotations,
                ]);
            }
        }            

        $playerId = $tapas->getNextPlayerId();

        $this->giveExtraTime($playerId);
        $this->gamestate->changeActivePlayer($playerId);

        $this->gamestate->nextState('nextTurn');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    function zombieTurn($state, $zombiePlayerId)
    {
    	$stateName = $state['name'];

        if ($state['type'] !== "activeplayer")
            throw new feException("Zombie mode not supported at this game state: " . $stateName); // NOI18N

        $tapas = $this->loadGameState();

        switch ($stateName)
        {
            case 'playerTurn':
                // Randomly choose a card and a legal move
                $legalMoves = $tapas->getLegalMoves($rotations);
                $data = $tapas->getPlayerData($zombiePlayerId);
                $tileIds = $data->players->$zombiePlayerId->inventory;
                shuffle($tileIds);
                shuffle($legalMoves);
                $tileId = array_pop($tileIds);
                $move = array_pop($legalMoves);
                $captured = [];
                if (!$tapas->placeTile($tileId, $move[0], $move[1], $move[2], $move[3], $captured))
                {
                    $refId = uniqid();
                    self::trace(implode(', ', [
                        'Ref #' . $refId . ': placeTile failed',
                        'zombie player: ' . $zombiePlayerId,
                        'tileId: ' . $tileId,
                        'x: ' . $move[0],
                        'y: ' . $move[1],
                        'dx: ' . $move[2],
                        'dy: ' . $move[3],
                        'state: ' . $tapas->toJson()
                    ]));
                    throw new BgaVisibleSystemException("Invalid operation - Ref #" . $refId); // NOI18N
                }
                $this->saveGameState($tapas);
        
                $this->afterPlaceTile($tapas, $zombiePlayerId, $tileId, $move[0], $move[1], $move[2], $move[3], $captured);
                return;
        }
    }

    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if($from_version <= 1404301345)
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB($sql);
//        }
//        if($from_version <= 1405061421)
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB($sql);
//        }
//        // Please add your future database scheme changes here
    }    
}
