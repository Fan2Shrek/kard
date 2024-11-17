import React, { createContext, useCallback } from "react";

export const GameContext = createContext({
    gameContext: null,
    currentPlayer: null,
    getCardAsset: () => { },
    getBackAsset: () => { },
})

export const GameContextProvider = ({ children, gameContext, currentPlayer }) => {
    const getCardAsset = useCallback((card) => {
        return gameContext.deck[card.rank + card.suit];
    }, [gameContext]);

    const getBackAsset = useCallback(() => {
        return gameContext.deck['back'];
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
