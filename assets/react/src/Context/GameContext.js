import React, { createContext, useCallback, useMemo } from "react";

export const GameContext = createContext({
    roomId: null,
    gameContext: null,
    currentPlayer: null,
    player: null,
    isPlayerTurn: () => { },
})

export const GameContextProvider = ({ children, gameContext, player, roomId }) => {
    const isPlayerTurn = useCallback(() => {
        return gameContext.currentPlayerId === player.id;
    }, [gameContext, player]);

    const currentPlayer = useMemo(
        () => gameContext.currentPlayerId,
        [gameContext],
    );

    return <GameContext.Provider value={{
        roomId,
        gameContext,
        currentPlayer,
        player,
        isPlayerTurn,
    }}>
        {children}
    </GameContext.Provider>
}

export default GameContextProvider;
