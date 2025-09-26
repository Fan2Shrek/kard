export const sortByRank = (cards, rankOrder) => cards.sort((a, b) => rankOrder.indexOf(a.rank) - rankOrder.indexOf(b.rank));
export const sortBySuit = (cards) => cards.sort((a, b) => a.suit.localeCompare(b.suit));

export default { sortByRank, sortBySuit };
