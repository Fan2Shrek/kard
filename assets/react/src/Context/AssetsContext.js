import React, { createContext, useCallback } from "react";

export const AssetsContext = createContext({
    getCardAsset: () => { },
    getBackAsset: () => { },
})

export const AssetsContextProvider = ({ children, assets }) => {
    const getCardAsset = useCallback((card) => {
        return assets[card.rank + card.suit];
    }, [assets]);

    const getBackAsset = useCallback(() => {
        return assets['back'];
    }, [assets]);

    return <AssetsContext.Provider value={{
        getCardAsset,
        getBackAsset,
    }}>
        {children}
    </AssetsContext.Provider>
}

export default AssetsContextProvider;
