<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Tapas implementation : © Copyright 2024, Philip Davis (mrphilipadavis AT gmail)
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 */

class action_tapas extends APP_GameAction
{ 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if (self::isArg('notifwindow'))
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
  	    }
  	    else
  	    {
            $this->view = "tapas_tapas";
            self::trace("Complete reinitialization of board game");
        }
  	}
  	

    public function placeTile()
    {
        self::setAjaxMode();     
        
        $tileId = intval(self::getArg('tile', AT_posint, true));
        $x = intval(self::getArg('x', AT_posint, true));
        $y = intval(self::getArg('y', AT_posint, true));
        $dx = intval(self::getArg('dx', AT_int, true));
        $dy = intval(self::getArg('dy', AT_int, true));
        $this->game->action_placeTile($tileId, $x, $y, $dx, $dy);

        self::ajaxResponse();
    }
}
