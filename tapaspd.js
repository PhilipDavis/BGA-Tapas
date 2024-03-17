/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * TapasPD implementation : © Copyright 2024, Philip Davis (mrphilipadavis AT gmail)
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 define([
    "dojo","dojo/_base/declare",
    "dojo/aspect", // To facilitate the insertion of HTML markup into log entries
    "ebg/core/gamegui",
    "ebg/counter",
],
function (dojo, declare, aspect) {
    const BgaGameId = `tapaspd`;
    const BasePath = `${BgaGameId}/${BgaGameId}`;

    const Tiles = [
        {}, // blank entry to adjust for 1-based indexes of cards
    ];

    const TileType = {
        Croq1: 1,
        Croq2: 2,
        Croq3: 3,
        Croq4: 4,
        Jala1: 5,
        Jala2: 6,
        Jala3: 7,
        Jala4: 8,
        Ticket: 9,
        Napkin: 10,
        Ketchup: 11,
        Mayo: 12,
        Wasabi: 13,
        Toast: 14,
    };

    const TapasTiles = [ 0 ]; // Start with a zero to represent blank / no tile
    for (let i = 0; i < 12; i++) {
        TapasTiles.push(TileType.Jala1);
        TapasTiles.push(TileType.Croq1);
    }
    for (let i = 0; i < 3; i++) {
        TapasTiles.push(TileType.Jala2);
        TapasTiles.push(TileType.Croq2);
    }
    for (let i = 0; i < 2; i++) {
        TapasTiles.push(TileType.Jala3);
        TapasTiles.push(TileType.Croq3);
    }
    TapasTiles.push(TileType.Jala4);
    TapasTiles.push(TileType.Croq4);
    
    TapasTiles.push(TileType.Toast);
    TapasTiles.push(TileType.Ketchup);
    TapasTiles.push(TileType.Mayo);
    TapasTiles.push(TileType.Wasabi);
    TapasTiles.push(TileType.Napkin);
    TapasTiles.push(TileType.Ticket);

    const ValueByTileType = {
        [TileType.Croq1]: 1,
        [TileType.Croq2]: 2,
        [TileType.Croq3]: 3,
        [TileType.Croq4]: 4,
        [TileType.Jala1]: 1,
        [TileType.Jala2]: 2,
        [TileType.Jala3]: 3,
        [TileType.Jala4]: 4,
    };

    const TypeByTileType = {
        [TileType.Croq1]: 'croq',
        [TileType.Croq2]: 'croq',
        [TileType.Croq3]: 'croq',
        [TileType.Croq4]: 'croq',
        [TileType.Jala1]: 'jala',
        [TileType.Jala2]: 'jala',
        [TileType.Jala3]: 'jala',
        [TileType.Jala4]: 'jala',
    };

    const DegFromRotation = {
        100: 180,
        200: -90,
        300: 0,
        400: 90,
    };

    // How many milliseconds for a tile to slide one position
    const SlideDuration = 400;

    const Preference = {
        // KILL: ShowColumnSums: 300,
    };

    return declare(`bgagame.${BgaGameId}`, ebg.core.gamegui, {
        constructor() {
            console.log(`${BgaGameId} constructor`);
            this.clientStateArgs = {
            };

            // Enable function calling in string substitution (for writing HTML in log entries)
            aspect.before(dojo.string, "substitute", (template, map, transform) => {
                return [ template, map, transform, this ];
            });

            this.scoreCounter = {};

            Object.defineProperties(this, {
                currentState: {
                    get() {
                        return this.gamedatas.gamestate.name;
                    },
                },
                amIActive: {
                    get() {
                        return parseInt(this.gamedatas.gamestate.active_player, 10);
                        // This is not a multiactiveplayer game
                        //return this.gamedatas.gamestate.multiactive.some(id => id == this.myPlayerId);
                    },
                },
            });
        },
        
        setup(gamedata) {
            console.log('Starting game setup', gamedata);

            this.initPreferencesObserver();

            const { tapas, rotations } = gamedata;
            this.tapas = tapas;

            this.dontPreloadImage('tapas_tiles-high.png');
            this.dontPreloadImage('tapas_board-1-med.png');
            this.dontPreloadImage('tapas_board-1-high.png');
            this.dontPreloadImage('tapas_board-2-med.png');
            this.dontPreloadImage('tapas_board-2-high.png');
            this.dontPreloadImage('tapas_board-3-med.png');
            this.dontPreloadImage('tapas_board-3-high.png');
            this.dontPreloadImage('tapas_board-4-med.png');
            this.dontPreloadImage('tapas_board-4-high.png');
            this.ensureSpecificGameImageLoading([
                `tapas_board-${tapas.options.layout}-med.png`,
            ]);

            const boardDiv = document.getElementById('tap_board');
            boardDiv.classList.add(`tap_board-${tapas.options.layout}`);

            // Set the rotation
            const wrapperDiv = document.getElementById('tap_board-wrapper');
            if (tapas.options.burningHead) {
                wrapperDiv.style.transform = `rotateZ(${rotations * 90}deg)`;
            }
            this.clientStateArgs.rotations = rotations;

            this.myPlayerId = this.player_id;
            const playerIds = Object.keys(tapas.players).map(s => parseInt(s, 10));
            this.otherPlayerId = playerIds.filter(id => id !== this.myPlayerId).shift();

            this.createCapturedArea(this.myPlayerId);
            this.createCapturedArea(this.otherPlayerId);

            let index = 1;
            for (const [ playerId, player ] of Object.entries(tapas.players))
            {
                const tapasType = this.tapas.players[playerId].tapas;
                this.createInventory(playerId, index++, tapasType);
                for (const tileId of [ ...player.inventory ].sort((a, b) => b - a)) {
                    this.createTileInInventory(tileId, playerId);
                }

                for (const tileId of [ ...player.captured ].sort((a, b) => b - a)) {
                    this.createTileInCaptured(tileId, playerId);
                }

                this.scoreCounter[playerId] = new ebg.counter();
                this.scoreCounter[playerId].create(`player_score_${playerId}`);
                // Note: can't set the value immediately... perhaps the div isn't created yet.
                // Let events happen first and then set the value.
                setTimeout(() => this.setPlayerScore(playerId, 0));
            }

            for (let y = 1; y <= tapas.height; y++) {
                for (let x = 1; x <= tapas.width; x++) {
                    const index = this.makeIndex(x, y);
                    const tileId = tapas.board[index];
                    this.createTileOnBoard(tileId, x, y);
                }
            }

            if (tapas.removed.length) {
                this.createCapturedArea('nobody');
                for (const tileId of tapas.removed) {
                    this.createTileInCaptured(tileId, 'nobody');
                }
            }

            for (let i = 1; i <= 4; i++) {
                document.getElementById(`tap_inventory-${this.myPlayerId}-${i}`).addEventListener('click', () => this.onClickInventory(i));
            }

            this.setupNotifications();
        },

        initPreferencesObserver() {
            dojo.query('.preference_control').on('change', e => {
                const match = e.target?.id.match(/^preference_[cf]ontrol_(\d+)$/);
                if (!match) return;
                const prefId = match[1];
                const { value } = e.target;
                this.prefs[prefId].value = parseInt(value, 10);
                this.onPreferenceChange(this.prefs[prefId]);
            });
        },
        
        onPreferenceChange(pref) {
            // Apply the CSS of the chosen preference value
            // (Unless it's a default pref, which appears to be
            // delivered as an array and without CSS class names)
            if (typeof pref.values === 'object' && typeof pref.values.length === 'number') return;
            const html = document.getElementsByTagName('html')[0];
            for (const [ value, settings ] of Object.entries(pref.values)) {
                if (typeof settings.cssPref !== 'string') continue;
                if (value == pref.value) {
                    html.classList.add(settings.cssPref);
                }
                else {
                    html.classList.remove(settings.cssPref);
                }
            }
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        onEnteringState(stateName, state) {
            console.log(`Entering state: ${stateName}`, state);

            if (state.active_player != this.myPlayerId) return;

            document.getElementById('tap_surface').classList.add(`tap_state-${stateName}`);
            
            switch (stateName) {
                case 'playerTurn':
                    const { moves } = state.args;
                    this.clientStateArgs.legalMoves = moves;
                    document.getElementById(`tap_inventory-${this.myPlayerId}`).classList.add('tap_selectable');
                    break;

                case 'client_selectSlot':
                    this.createSlotsForLegalMoves(this.clientStateArgs.legalMoves);
                    break;

                case 'client_selectDirection':
                    const { selectedMoves } = this.clientStateArgs;
                    for (const [ x, y, dx, dy ] of selectedMoves) {
                        this.createDirectionSlot(x, y, dx, dy);
                    }
                    break;
            }
        },

        onLeavingState(stateName) {
            console.log(`Leaving state: ${stateName}`);

            document.getElementById('tap_surface').classList.remove(`tap_state-${stateName}`);

            switch (stateName) {
                case 'playerTurn':
                    this.destroyAllSlots();
                    break;

                case 'client_selectSlot':
                    document.getElementById(`tap_inventory-${this.myPlayerId}`).classList.remove('tap_selectable');
                    this.destroyAllSlots();
                    break;

                case 'client_selectDirection':
                    this.destroyAllSlots();
                    break;
            }
        }, 

        onUpdateActionButtons(stateName, args) {
            console.log(`onUpdateActionButtons: ${stateName}`, args);
            
            if (!this.isCurrentPlayerActive()) return;

            switch (stateName) {
                case 'client_selectSlot':
                case 'client_selectDirection':
                    this.addActionButton(`tap_button-cancel`, _('Cancel'), () => this.onClickCancel(), null, false, 'red'); 
                    break;
            }
        },        


        ///////////////////////////////////////////////////
        //// Utility methods

        invokeServerActionAsync(actionName, args) {
            return new Promise((resolve, reject) => {
                try {
                    if (!this.checkAction(actionName)) {
                        console.error(`Action '${actionName}' not allowed in ${this.currentState}`, args);
                        return reject('Invalid');
                    }
                    if (!this.amIActive) {
                        console.error(`Action '${actionName}' not allowed for inactive player`, args);
                        return reject('Invalid');
                    }
                    this.ajaxcall(`${BasePath}/${actionName}.html`, { lock: true, ...args }, () => {}, result => {
                        result?.valid ? resolve() : reject(`${actionName} failed`);
                    });
                }
                catch (err) {
                    reject(err);
                }
            });
        },

        reflow(element = document.documentElement) {
            void(element.offsetHeight);
        },

        // Board is from (1,1) - (W,H)
        // Convert to 2d array index from (0 ... (w*h-1))
        // Returns -1 for (x,y) coords outside the board
        makeIndex(x, y) {
            if (x < 1 || x > this.tapas.width) return -1;
            if (y < 1 || y > this.tapas.height) return -1;
            return ((y - 1) * this.tapas.width + x - 1);
        },

        createCapturedArea(playerId) {
            const divId = document.getElementById(`tap_captured-${playerId}`);
            if (divId) return;
            dojo.place(this.format_block('tapas_Templates.captured', {
                PID: parseInt(playerId, 10) || 'nobody',
                TITLE: playerId === 'nobody' ? _('Nobody') : this.gamedatas.players[playerId].name,
                TAPAS: playerId === 'nobody' ? 'ticket' : this.tapas.players[playerId].tapas,
            }), 'tap_surface');
        },

        createInventory(playerId, index, tapasType) {
            dojo.place(this.format_block('tapas_Templates.inventory', {
                PID: parseInt(playerId, 10),
                CLASS: `tap_inventory-${tapasType}`,
            }), `tap_inventory-${index}`, 'replace');
        },

        createTileOnBoard(tileId, x, y) {
            if (!tileId) return;
            const divId = `tap_tile-${tileId % 100}`;
            const rotation = tileId - (tileId % 100);
            dojo.place(this.format_block('tapas_Templates.tile', {
                DIV_ID: divId,
                TYPE: TapasTiles[tileId % 100],
                X_EM: x * 5,
                Y_EM: y * 5,
                DEG: DegFromRotation[rotation],
            }), 'tap_board-wrapper');
            return document.getElementById(divId);
        },

        createTileOnTable(tileId, x, y) {
            if (!tileId) return;
            const divId = `tap_tile-${tileId % 100}`;
            const rotation = tileId - (tileId % 100);
            dojo.place(this.format_block('tapas_Templates.tile', {
                DIV_ID: divId,
                TYPE: TapasTiles[tileId % 100],
                X_EM: x * 5,
                Y_EM: y * 5,
                DEG: DegFromRotation[rotation],
            }), 'tap_table');
            return document.getElementById(divId);
        },

        createTileInInventory(tileId, playerId) {
            if (!tileId) return;
            const tileType = TapasTiles[tileId % 100];
            const value = ValueByTileType[tileType];
            const divId = `tap_tile-${tileId % 100}`;
            dojo.place(this.format_block('tapas_Templates.tile', {
                DIV_ID: divId,
                TYPE: TapasTiles[tileId % 100],
                X_EM: 0,
                Y_EM: 0,
                DEG: 0,
            }), `tap_inventory-${playerId}-${value}`);
            return document.getElementById(divId);
        },

        createTileInCaptured(tileId, playerId) {
            if (!tileId) return;
            const tileType = TapasTiles[tileId % 100];
            const value = ValueByTileType[tileType];
            const divId = `tap_tile-${tileId % 100}`;
            // TODO: sort in order of tileId?
            dojo.place(this.format_block('tapas_Templates.tile', {
                DIV_ID: divId,
                TYPE: TapasTiles[tileId % 100],
                X_EM: 0,
                Y_EM: 0,
                DEG: 0,
            }), `tap_captured-${playerId}`);
            return document.getElementById(divId);
        },

        createSlot(x, y, dx, dy) {
            const divId = `tap_slot-${x}-${y}`;
            const existing = document.getElementById(divId);
            if (!existing) {
                dojo.place(this.format_block('tapas_Templates.slot', {
                    DIV_ID: divId,
                    X_EM: x * 5,
                    Y_EM: y * 5,
                    TYPE: this.tapas.players[this.myPlayerId].tapas,
                }), 'tap_board-wrapper');
                const slotDiv = document.getElementById(divId);
                slotDiv.addEventListener('click', () => this.onClickSlot(x, y));
            }
            const slotDiv = document.getElementById(divId);
            this.createArrow(x, y, dx, dy, slotDiv);
            return document.getElementById(divId);
        },

        createDirectionSlot(x, y, dx, dy) {
            const divId = `tap_dir-slot-${x}-${y}-${dx}-${dy}`;
            const existing = document.getElementById(divId);
            if (!existing) {
                dojo.place(this.format_block('tapas_Templates.slot', {
                    DIV_ID: divId,
                    X_EM: (x + dx) * 5,
                    Y_EM: (y + dy) * 5,
                    TYPE: this.tapas.players[this.myPlayerId].tapas,
                }), 'tap_board-wrapper');
                const dirDiv = document.getElementById(divId);
                dirDiv.addEventListener('click', () => this.onClickDirection(dx, dy));
            }
            const dirDiv = document.getElementById(divId);
            this.createArrow(x + dx, y + dy, dx, dy, dirDiv);
            dirDiv.classList.add('tap_dir-slot');
        },

        createArrow(x, y, dx, dy, parentDiv) {
            if (!dx && !dy) return;
            const divId = `tap_arrow-${x}-${y}-${dx}-${dy}`;
            const existing = document.getElementById(divId);
            if (existing) {
                return existing;
            }
            dojo.place(this.format_block('tapas_Templates.arrow', {
                DIV_ID: divId,
                DIR: dx === -1 ? 'left' : dx === 1 ? 'right' : dy === -1 ? 'up' : 'down',
            }), parentDiv);
            return document.getElementById(divId);
        },

        createSlotsForLegalMoves(moves) {
            // Note: some (x,y) pair can be duplicated with different values for dx and dy
            for (const [ x, y, dx, dy ] of moves) {
                this.createSlot(x, y, dx, dy);
            }
        },

        destroyAllSlots() {
            const arrowDivs = [ ...document.getElementsByClassName('tap_arrow') ];
            for (const element of arrowDivs) {
                element.parentElement.removeChild(element);
            }
            const slotDivs = [ ...document.getElementsByClassName('tap_slot') ];
            for (const element of slotDivs) {
                element.parentElement.removeChild(element);
            }
        },

        deselectAll() {
            const selectedDivs = [ ...document.querySelectorAll('.tap_selected') ];
            for (const div of selectedDivs) {
                div.classList.remove('tap_selected');
            }
        },

        delayAsync(duration) {
            return new Promise(resolve => setTimeout(resolve, duration));
        },

        getTileDiv(tileId) {
            return document.getElementById(`tap_tile-${tileId % 100}`);
        },

        rotateCoords({ x, y }) {
            return {
                x: this.tapas.width + 1 - y,
                y: x,
            };
        },

        async animateTilePlacementAsync(activePlayerId, tileId, x, y, dx, dy, ignoreCaptured) {
            tileId = tileId % 100;
            const tileDiv = this.getTileDiv(tileId);
            tileDiv.id = `tap_temp-tile-${tileId}`;

            const srcRect = tileDiv.getBoundingClientRect();
            const srcMidX = Math.round(srcRect.x + srcRect.width / 2);
            const srcMidY = Math.round(srcRect.y + srcRect.height / 2);

            const destDiv = this.createTileOnBoard(tileId + 300, x, y); // 300 is upright rotation
            destDiv.style.visibility = 'hidden';

            const destRect = destDiv.getBoundingClientRect();
            const destMidX = Math.round(destRect.x + destRect.width / 2);
            const destMidY = Math.round(destRect.y + destRect.height / 2);

            const deltaX = destMidX - srcMidX;
            const deltaY = destMidY - srcMidY;

            const rotation = dy === 1 ? 100 : dx === -1 ? 200 : dy === -1 ? 300 : 400;
            const deg = DegFromRotation[rotation];

            destDiv.style.transform = `translate(calc(${x * 5}em - ${deltaX}px), calc(${y * 5}em - ${deltaY}px)) rotateZ(0deg)`;
            destDiv.style.visibility = '';
            tileDiv.style.visibility = '';
            tileDiv.parentElement.removeChild(tileDiv);

            const animateToSlot = destDiv.animate({
                transform: [
                    `translate(calc(${x * 5}em - ${deltaX}px), calc(${y * 5}em - ${deltaY}px)) rotateZ(0deg)`,
                    `translate(${x * 5}em, ${y * 5}em) rotateZ(${deg}deg)`,
                ],
            }, {
                duration: 800,
                easing: 'ease-out',
                fill: 'forwards',
            });

            await animateToSlot.finished;

            //
            // Start sliding tiles
            //
            const tileType = TapasTiles[tileId];
            const value = ValueByTileType[tileType];
            let distanceToMove = value;
            let spacesCollapsed = 0;
            const animationPromises = [];

            // Slide the first tile, which may start out of bounds
            animationPromises.push(
                this.animateTileSlideAsync(activePlayerId, tileId + rotation, 0, x, y, dx, dy, distanceToMove, ignoreCaptured)
            );

            // Start sliding the pre-existing tiles
            for (let i = 1; ; i++) {
                const x1 = x + i * dx;
                const y1 = y + i * dy;
                const delay = spacesCollapsed * SlideDuration;
                const index = this.makeIndex(x1, y1);
                if (index === -1) break; // Bail out if we're off the board now
                const tileId = this.tapas.board[index];
                if (!tileId) {
                    spacesCollapsed++;
                    if (--distanceToMove < 1) {
                        break;
                    }
                    continue;
                }
                animationPromises.push(
                    this.animateTileSlideAsync(activePlayerId, tileId, delay, x1, y1, dx, dy, distanceToMove, ignoreCaptured)
                );
            }

            await Promise.all(animationPromises);
        },

        async animateBurningHeadTilePlacementAsync(activePlayerId, tileId, x, y, dx, dy, ignoreCaptured) {
            const tileDiv = this.getTileDiv(tileId);
            const srcRect = tileDiv.getBoundingClientRect();
            const srcMidX = Math.round(srcRect.x + srcRect.width / 2);
            const srcMidY = Math.round(srcRect.y + srcRect.height / 2);
            tileDiv.id = tileDiv.id + '-leaving';

            const rotations = [ 100, 200, 300, 400 ];
            const trueRotationIndex = dy === 1 ? 0 : dx === -1 ? 1 : dy === -1 ? 2 : 3;
            const trueRotation = rotations[trueRotationIndex];
            let apparentRotationIndex = trueRotationIndex;
            let c2 = { x, y };
            for (let i = 1; i <= this.clientStateArgs.rotations % 4; i++) {
                c2 = this.rotateCoords(c2);
                apparentRotationIndex = (apparentRotationIndex + 1) % 4;
            }
            const apparentRotation = rotations[apparentRotationIndex];
            const deg = DegFromRotation[apparentRotation];
            const destDiv = this.createTileOnTable(tileId + trueRotation, c2.x, c2.y);
            destDiv.style.visibility = 'hidden';
            destDiv.style.zIndex = 10;

            // Offset the destination by the offet of the board because
            // the tile we animate is going to be a child of the table,
            // not the board wrapper (because board wrapper is rotated)
            const { offsetLeft, offsetTop } = document.getElementById('tap_board-wrapper');

            const destRect = destDiv.getBoundingClientRect();
            let destMidX = Math.round(destRect.x + destRect.width / 2);
            let destMidY = Math.round(destRect.y + destRect.height / 2);
            
            const deltaX = destMidX - srcMidX;
            const deltaY = destMidY - srcMidY;

            const tableDiv = document.getElementById('tap_table');
            tableDiv.appendChild(destDiv);
            destDiv.style.left = `calc(${c2.x * 5}em + 1.5em + ${offsetLeft}px)`;
            destDiv.style.top = `calc(${c2.y * 5}em + 1.5em + ${offsetTop}px)`;
            destDiv.style.transform = `translate(${-deltaX - offsetLeft}px, ${-deltaY - offsetTop}px) rotateZ(0deg)`;
            destDiv.style.visibility = '';
            tileDiv.style.visibility = 'hidden';
            tileDiv.parentElement.removeChild(tileDiv);
            
            const animateToSlot = destDiv.animate({
                transform: [
                    `translate(${-deltaX - offsetLeft}px, ${-deltaY - offsetTop}px) rotateZ(0deg)`,
                    `translate(0, 0) rotateZ(${deg}deg)`,
                ],
            }, {
                duration: 800,
                easing: 'ease-out',
                fill: 'forwards',
            });

            await animateToSlot.finished;

            // Now replace the animated tile with one actually on the board
            this.createTileOnBoard(tileId + trueRotation, x, y);
            destDiv.parentElement.removeChild(destDiv);

            //
            // Start sliding tiles
            //
            const tileType = TapasTiles[tileId % 100];
            const value = ValueByTileType[tileType];
            let distanceToMove = value;
            let spacesCollapsed = 0;
            const animationPromises = [];

            // Slide the first tile, which may start out of bounds
            animationPromises.push(
                this.animateTileSlideAsync(activePlayerId, tileId + trueRotation, 0, x, y, dx, dy, distanceToMove, ignoreCaptured)
            );

            // Start sliding the pre-existing tiles
            for (let i = 1; ; i++) {
                const x1 = x + i * dx;
                const y1 = y + i * dy;
                const delay = spacesCollapsed * SlideDuration;
                const index = this.makeIndex(x1, y1);
                if (index === -1) break; // Bail out if we're off the board now
                const tileId = this.tapas.board[index];
                if (!tileId) {
                    spacesCollapsed++;
                    if (--distanceToMove < 1) {
                        break;
                    }
                    continue;
                }
                animationPromises.push(
                    this.animateTileSlideAsync(activePlayerId, tileId, delay, x1, y1, dx, dy, distanceToMove, ignoreCaptured)
                );
            }

            await Promise.all(animationPromises);
        },

        async animateTileSlideAsync(activePlayerId, tileId, delay, x1, y1, dx, dy, distanceToMove, ignoreCaptured) {
            const tileDiv = this.getTileDiv(tileId);
            const rotation = tileId - tileId % 100;
            const deg = DegFromRotation[rotation];

            const _x2 = x1 + distanceToMove * dx;
            const _y2 = y1 + distanceToMove * dy;
            const x2 = Math.min(Math.max(0, _x2), this.tapas.width + 1);
            const y2 = Math.min(Math.max(0, _y2), this.tapas.height + 1);
            const actualDistanceMoved = distanceToMove - (Math.abs(x2 - _x2) || Math.abs(y2 - _y2));
            await tileDiv.animate({
                transform: [
                    `translate(${x1 * 5}em, ${y1 * 5}em) rotateZ(${deg}deg)`,
                    `translate(${x2 * 5}em, ${y2 * 5}em) rotateZ(${deg}deg)`,
                ],
            }, {
                delay,
                duration: SlideDuration * actualDistanceMoved,
                // Gotta go linear because tiles are moving at different times
                // and we can't easily predict the ease-out curve at the point
                // in time when the earlier tiles would bump into this tile.
                easing: 'linear',
                fill: 'forwards',
            }).finished;

            // Make this tile disappear if it was pushed off the game board
            if (x2 < 1 || x2 > this.tapas.width || y2 < 1 || y2 > this.tapas.height) {
                await this.animateTileIntoOblivion(activePlayerId, tileId, x2, y2, deg, ignoreCaptured);
            }
        },

        async animateTileIntoOblivion(activePlayerId, tileId, x, y, deg, ignoreCaptured) {
            let tileDiv = this.getTileDiv(tileId);
            await tileDiv.animate({
                opacity: [ 1, 0 ],
                zIndex: [ -1, -1 ],
                transform: [
                    `translate(${x * 5}em, ${y * 5}em) rotateZ(${deg}deg) scale(1)`,
                    `translate(${x * 5}em, ${y * 5}em) rotateZ(${deg}deg) scale(.8)`,
                ],
            }, {
                duration: SlideDuration / 2,
                easing: 'ease-out',
                fill: 'forwards',
            }).finished;

            tileDiv.parentElement.removeChild(tileDiv);

            if (ignoreCaptured) {
                this.createCapturedArea('nobody');
            }

            const tileType = TapasTiles[tileId % 100];
            const type = TypeByTileType[tileType];

            const capturingPlayerId =
                ignoreCaptured
                    ? 'nobody'
                    : this.tapas.players[activePlayerId].tapas !== type
                        ? activePlayerId
                        : activePlayerId == this.myPlayerId ? this.otherPlayerId : this.myPlayerId;

            tileDiv = this.createTileInCaptured(tileId, capturingPlayerId);
            await tileDiv.animate({
                opacity: [ 0, 1, 1, 1 ],
                transform: [
                    `scale(0)`,
                    `scale(1)`,
                    `scale(1.15)`,
                    `scale(1)`,
                ],
            }, {
                duration: SlideDuration / 2,
                easing: 'ease-out',
                fill: 'forwards',
            }).finished;
        },

        async animateBoardRotationAsync(rotations) {
            const wrapperDiv = document.getElementById('tap_board-wrapper');
            await wrapperDiv.animate({
                transform: [
                    `rotateZ(${(rotations - 1) * 90}deg)`,
                    `rotateZ(${rotations * 90}deg)`,
                ],
            }, {
                duration: 800,
                easing: 'ease-out',
                fill: 'forwards',
            }).finished;
        },

        // Similar to placeOnObject, except it sets the child of
        // the parent instead of just setting the coordinates.
        placeInElement(childIdOrElement, parentIdOrElement) {
            const child = typeof childIdOrElement === 'string' ? document.getElementById(childIdOrElement) : childIdOrElement;
            const parent = typeof parentIdOrElement=== 'string' ? document.getElementById(parentIdOrElement) : parentIdOrElement;
            child.style.position = '';
            child.style.left = '';
            child.style.top = '';
            child.style.bottom = '';
            child.style.right = '';
            child.style.zIndex = '';
            parent.appendChild(child);
        },

        resetPosition(div) {
            div.style = '';
        },

        async applySelectionIfPossible() {
            let { selectedCoords, selectedValue, selectedDirection } = this.clientStateArgs;

            if (!selectedValue) {
                // Player needs to pick a slot
                return;
            }
            else if (!selectedCoords) {
                // Player needs to pick a tile
                return;
            }
            else if (!selectedDirection) {
                const matchingMoves = this.clientStateArgs.legalMoves.filter(move =>
                    move[0] === selectedCoords.x &&
                    move[1] === selectedCoords.y
                );
                if (matchingMoves.length > 1) {
                    // Player needs to pick a direction
                    this.clientStateArgs.selectedMoves = matchingMoves;
                    this.setClientState('client_selectDirection', {
                        descriptionmyturn: _('${you} must select a direction to push your Tapas'),
                    });
                    return;
                }
                selectedDirection = {
                    dx: matchingMoves[0][2],
                    dy: matchingMoves[0][3],
                };
            }

            const selectedMoves = this.clientStateArgs.legalMoves.filter(move =>
                move[0] === selectedCoords.x &&
                move[1] === selectedCoords.y &&
                move[2] === selectedDirection.dx &&
                move[3] === selectedDirection.dy
            );
            if (selectedMoves.length !== 1) {
                throw new Error('Invalid legal moves!');
            }

            const { x, y } = selectedCoords;
            const { dx, dy } = selectedDirection;

            const tileId = this.tapas.players[this.myPlayerId].inventory.find(tileId => {
                const tileType = TapasTiles[tileId];
                const value = ValueByTileType[tileType];
                return value === selectedValue;
            });            

            // Optimistically clean up
            delete this.clientStateArgs.selectedCoords;
            delete this.clientStateArgs.selectedValue;
            delete this.clientStateArgs.selectedDirection;

            try {
                this.destroyAllSlots();
                await this.invokeServerActionAsync('placeTile', { tile: tileId, x, y, dx, dy });
                this.deselectAll();
            }
            catch (err) {
                await this.onClickCancel();
            }
        },

        setPlayerScore(playerId, score) {
            this.scoreCounter[playerId].setValue(score);

            const scoreDiv = document.getElementById(`tap_score-${playerId}`);
            scoreDiv.innerText =
                score === 1
                    ? _('(1 point)')
                    : _('({n} points)').replace(/\{n\}/i, score);
        },


        ///////////////////////////////////////////////////
        //// Player's action

        async onClickSlot(x, y) {
            if (!this.amIActive) return;
            if (this.currentState !== 'client_selectSlot') return;
            console.log(`onClickSlot(${x}, ${y})`);

            // Select the slot that the player clicked
            const slotDiv = document.getElementById(`tap_slot-${x}-${y}`);
            slotDiv.classList.add('tap_selected');

            // Deselect any currently selected slot (except the one just clicked)
            const selectedSlotDivs = [ ...document.querySelectorAll('.tap_slot.tap_selected') ];
            for (const div of selectedSlotDivs) {
                if (div === slotDiv) continue;
                div.classList.remove('tap_selected');
            }

            this.clientStateArgs.selectedCoords = { x, y };
            await this.applySelectionIfPossible();
        },

        async onClickDirection(dx, dy) {
            if (!this.amIActive) return;
            if (this.currentState !== 'client_selectDirection') return;
            console.log(`onClickDirection(${dx}, ${dy})`);

            this.clientStateArgs.selectedDirection = { dx, dy };
            await this.applySelectionIfPossible();
        },

        async onClickInventory(i) {
            if (!this.amIActive) return;
            if (this.currentState !== 'playerTurn' && this.currentState !== 'client_selectSlot') return;
            console.log(`onClickInventory(${i})`);

            // Select the inventory group that the player clicked
            const groupDiv = document.getElementById(`tap_inventory-${this.myPlayerId}-${i}`);
            groupDiv.classList.add('tap_selected');

            // Deselect any currently selected groups (except the one just clicked)
            const selectedGroupDivs = [ ...document.querySelectorAll('.tap_inventory-group.tap_selected') ];
            for (const div of selectedGroupDivs) {
                if (div === groupDiv) continue;
                div.classList.remove('tap_selected');
            }

            this.clientStateArgs.selectedValue = i;

            if (this.currentState !== 'client_selectSlot') {
                this.setClientState('client_selectSlot', {
                    descriptionmyturn: _('${you} must place your selected Tapas'),
                });
            }
        },

        async onClickCancel() {
            // Deselect everything
            const selectedDivs = [ ...document.querySelectorAll('.tap_selected') ];
            for (const div of selectedDivs) {
                div.classList.remove('tap_selected');
            }

            delete this.clientStateArgs.selectedCoords;
            delete this.clientStateArgs.selectedValue;
            delete this.clientStateArgs.selectedDirection;

            this.restoreServerGameState();
        },


        ///////////////////////////////////////////////////
        //// Log message helpers

        getTileHtml(tileId) {
            return this.format_block('tapas_Templates.logTile', {
                TYPE: TapasTiles[tileId % 100],
            });
        },

        getTilesHtml(tileIds) {
            return tileIds.reduce((html, tileId) => {
                return html + this.getTileHtml(tileId);
            }, '');
        },


        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        setupNotifications() {
            console.log('notifications subscriptions setup');
            const eventNames = [
                'tilePlayed',
                'boardRotated',
            ];
            for (const eventName of eventNames) {
                dojo.subscribe(eventName, this, async data => {
                    const fnName = `notify_${eventName}`;
                    if (!this[fnName]) {
                        throw new Error (`Missing notification function named ${fnName}`);
                    }
                    console.log(`Entering ${fnName}`, data.args);
                    await this[fnName].call(this, data.args);
                    console.log(`Exiting ${fnName}`);
                    this.notifqueue.setSynchronousDuration(0);
                });
                this.notifqueue.setSynchronous(eventName);
            }
        },

        async notify_tilePlayed({ playerId, tileId, x, y, dx, dy, board, scores }) {
            // Update the internal game state
            this.tapas.players[playerId].inventory = this.tapas.players[playerId].inventory.filter(id => id != tileId);

            // We ignore captures this round if the ticket tile was pushed off the board
            const wasTicketRemoved =
                this.tapas.board.find(tileId => TapasTiles[tileId % 100] === TileType.Ticket) &&
                !board.find(tileId => TapasTiles[tileId % 100] === TileType.Ticket);

            if (this.tapas.options.burningHead) {
                await this.animateBurningHeadTilePlacementAsync(playerId, tileId, x, y, dx, dy, wasTicketRemoved);
            }
            else {
                await this.animateTilePlacementAsync(playerId, tileId, x, y, dx, dy, wasTicketRemoved);
            }

            for (const [ playerId, score ] of Object.entries(scores)) {
                this.setPlayerScore(playerId, score);
            }

            this.tapas.board = board; // lazy... just have server tell us the new board
        },

        async notify_boardRotated({ n, total }) {
            for (let i = 1; i <= n; i++) {
                await this.animateBoardRotationAsync(total - n + i);
                await this.delayAsync(400);
            }
            this.clientStateArgs.rotations = total;
        },
    });
});
