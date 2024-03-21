{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Tapas implementation : © Copyright 2024, Philip Davis (mrphilipadavis AT gmail)
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->

<div id="tap_surface">
    <div id="tap_table">
        <div id="tap_inventory-1"></div>
        <div id="tap_board-wrapper">
            <div id="tap_board"></div>
        </div>
        <div id="tap_inventory-2"></div>
    </div>
</div>

<script type="text/javascript">

const tapas_Templates = {
    inventory:
        '<div id="tap_inventory-${PID}" class="tap_inventory ${CLASS}">' +
            '<div id="tap_inventory-${PID}-4" class="tap_inventory-group"></div>' +
            '<div id="tap_inventory-${PID}-3" class="tap_inventory-group"></div>' +
            '<div id="tap_inventory-${PID}-2" class="tap_inventory-group"></div>' +
            '<div id="tap_inventory-${PID}-1" class="tap_inventory-group"></div>' +
        '</div>',

    tile:
        '<div ' +
            'id="${DIV_ID}" ' +
            'class="tap_tile tap_tile-${TYPE}" ' +
            'style="transform: translate(${X_EM}em, ${Y_EM}em) rotateZ(${DEG}deg)" ' +
        '>' +
        '</div>',

    slot:
        '<div ' +
            'id="${DIV_ID}" ' +
            'class="tap_slot tap_slot-${TYPE}" ' +
            'style="transform: translate(${X_EM}em, ${Y_EM}em)" ' +
        '></div>',

    arrow:
        '<div ' +
            'id="${DIV_ID}" ' +
            'class="tap_arrow tap_arrow-${DIR}" ' +
        '></div>',

    captured:
        '<div id="tap_captured-${PID}" class="tap_captured whiteblock">' +
            '<h2>' +
                '<span class="tap_icon tap_icon-${TAPAS}"></span>' +
                '<span class="tap_name">${TITLE}</span>' +
                '<span id="tap_score-${PID}" class="tap_score"></span>' +
            '</h2>' +
        '</div>',

    logTile:
        '<span class="tap_log-tile tap_log-tile-${TYPE}"></span>',
};

</script>  

{OVERALL_GAME_FOOTER}
