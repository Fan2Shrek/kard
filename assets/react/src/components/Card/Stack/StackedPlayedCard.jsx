import React, { forwardRef } from "react";

import Stack from "./Stack.js";

import './stackedPlayedCard.css';

export default forwardRef(({ turns }, ref) => {
    const lastTurn = turns[turns.length - 1];
    turns = turns.slice(0, -1);

    const cards = turns.map(t => t.cards).flat();

	return <div>
		<Stack cards={cards} />
		<Stack className="played__card--stack" ref={ref} cards={lastTurn.cards} />
	</div>;
})
