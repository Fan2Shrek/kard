import React, { createContext, useCallback, useMemo } from "react";

export const GameContext = createContext({
    roomId: null,
    gameContext: null,
    currentPlayer: null,
    player: null,
    isPlayerTurn: () => { },
})

export const GameContextProvider = ({ children, gameContext, player }) => {
    const getCardAsset = useCallback((card) => {
        return gameContext.assets[card.rank + card.suit];
    }, [gameContext]);

    const getBackAsset = useCallback(() => {
        return gameContext.assets['back'];
    }, [gameContext]);

    const isPlayerTurn = useCallback(() => {
        return gameContext.currentPlayer === player.id;
    }, [gameContext, player]);

    const currentPlayer = useMemo(() => gameContext.currentPlayer, [gameContext]);

    return <GameContext.Provider value={{
        roomId: gameContext.id,
        gameContext,
        currentPlayer,
        player,
        getCardAsset,
        getBackAsset,
        isPlayerTurn,
    }}>
        {children}
    </GameContext.Provider>
}

export default GameContextProvider;
