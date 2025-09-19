import Phaser, { AUTO, Game } from 'phaser';

import { Preloader } from './scenes/test.js';

//  Find out more information about the Game Config at:
//  https://docs.phaser.io/api-documentation/typedef/types-core#gameconfig
const config = {
	type: AUTO,
	scale: {
		mode: Phaser.Scale.RESIZE,   // Dynamically resize
		autoCenter: Phaser.Scale.CENTER_BOTH,
		width: "100%",
		height: "100%",
	},
	parent: 'game-container',
	backgroundColor: '#028af8',
	scene: [
		Preloader
	]
};

const StartGame = (parent) => {

	return new Game({ ...config, parent });

}

export default StartGame;
