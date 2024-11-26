import React, { createContext, useCallback } from "react";

export const GameContext = createContext({
    gameContext: null,
    currentPlayer: null,
    getCardAsset: () => { },
    getBackAsset: () => { },
})

export const GameContextProvider = ({ children, gameContext, currentPlayer }) => {
    const getCardAsset = useCallback((card) => {
        return gameContext.assets[card.rank + card.suit];
    }, [gameContext]);

    const getBackAsset = useCallback(() => {
        return gameContext.assets['back'];
    }, [gameContext]);

    return <GameContext.Provider value={{
        gameContext,
        currentPlayer,
        getCardAsset,
        getBackAsset,
    }}>
        {children}
    </GameContext.Provider>
}

export default GameContextProvider;
