import React from 'react';

import { HiddenHand } from '../components.js';
import './board.css';

const quotes = [
	'Le meilleur jeu de cartes',
	'Le jeu de cartes le plus fun',
	'Le jeu de cartes le plus stratégique',
	'Le jeu de cartes le plus addictif',
	'Le jeu de cartes le plus convivial',
	'Le jeu de cartes le plus innovant',
	'Le jeu de cartes le plus surprenant',
	'Le jeu de cartes le plus drôle',
	'Le jeu de cartes le plus compétitif',
	'Le jeu de cartes le plus collaboratif',
	"La plupart des jeux de cartes réunis en un seul",
	'Bonne partie :)',
	'Pourquoi faire simple quand on peut faire compliqué ?',
	'Une chance sur deux de montrer le bon mode de jeu: président',
	'Une chance sur deux de montrer le bon mode de jeu: 8 américain',
	'Je te vois',
	"Si tu lis ca c'est que les autres sont trop lents",
	"Avec ce projet j'ai eu 22/20",
	'Aller pause une carte on vas pas y passer la journée',
	'Merci à Buzz pour le soutien',
	'Merci à Phillipe pour le front',
	'Merci à Maeva pour le dos des cartes',
	'Merci à Copilot pour les citations',
	'Dos de la carte',
	'I use Arch btw',
	'I use Neovim btw',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	"Pierrot est vraiment trop fort",
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'Pierrot est vraiment trop fort',
	'',
];

export default ({ children, players }) => {
	const positions = ['top', 'left', 'right'];

	return <div className="board">
		<div className="board__players">
			{players.map((player, index) => {
				const position = positions[index];

				return (
					<div key={player.id} className={`player-hand player-hand--${position}`}>
						<HiddenHand count={player.cardsCount} id={player.id} />
					</div>
				);
			})}
		</div>
		<div className="game">
			<div className="game__background">
				<p class='title'>KARD</p>
				<p class='subtitle'>By Fan2Shrek &amp; Contributors</p>
				<p class='quote'>{quotes[Math.floor(Math.random() * quotes.length)]}</p>
			</div>
			{children}
		</div>
	</div>;
}
