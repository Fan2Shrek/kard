import React, { createContext, useCallback, useState } from "react";

export const GameContext = createContext({
    gameContext: null,
    getCardAsset: () => { },
})

export const GameContextProvider = ({ children, gameContext }) => {
    const getCardAsset = useCallback((card) => {
        return gameContext.deck[card.rank + card.suit];
    }, [gameContext]);

    return <GameContext.Provider value={{
        gameContext,
        getCardAsset,
    }}>
        {children}
    </GameContext.Provider>
}

export default GameContextProvider;
