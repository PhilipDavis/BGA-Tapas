<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * TapasPD implementation : © Copyright 2024, Philip Davis (mrphilipadavis AT gmail)
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

//    !! It is not a good idea to modify this file when a game is running !!

if (!defined('BGA_STATE_START'))
{
    define('BGA_STATE_START', 1);
    define('STATE_PLAYER_TURN', 10);
    define('STATE_NEXT_TURN', 20);
    define('BGA_STATE_END', 99);
}

 
$machinestates = [

    // The initial state. Please do not modify.
    BGA_STATE_START => [
        'name' => 'gameSetup',
        'description' => '',
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => [
            '' => STATE_PLAYER_TURN,
        ],
    ],

    STATE_PLAYER_TURN => [
        'name' => 'playerTurn',
        'description' => clienttranslate('${actplayer} must place a Tapas'),
        'descriptionmyturn' => clienttranslate('${you} must place a Tapas'),
        'type' => 'activeplayer',
        'possibleactions' => [ 'placeTile' ],
        'args' => 'argsPlayerTurn',
        'transitions' => [
            'endTurn' => STATE_NEXT_TURN,
        ],
    ],
    
    // This is just here to update the game progression.
    STATE_NEXT_TURN => [
        'name' => 'nextTurn',
        'description' => '',
        'type' => 'game',
        'action' => 'stNextTurn',
        'updateGameProgression' => true,   
        'transitions' => [
            'nextTurn' => STATE_PLAYER_TURN,
            'gameOver' => BGA_STATE_END,
        ],
    ],
    
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    BGA_STATE_END => [
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    ],
];
