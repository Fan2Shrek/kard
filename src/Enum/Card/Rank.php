<?php

namespace App\Enum\Card;

enum Rank: string
{
    case ACE = '1';
    case TWO = '2';
    case THREE = '3';
    case FOUR = '4';
    case FIVE = '5';
    case SIX = '6';
    case SEVEN = '7';
    case EIGHT = '8';
    case NINE = '9';
    case TEN = '10';
    case JACK = 'j';
    case QUEEN = 'q';
    case KING = 'k';
	case JOKER = 'joker';

	/**
	* @return Rank[]
	*/
	public static function valueCases(): array
	{
		return [
			self::ACE,
			self::TWO,
			self::THREE,
			self::FOUR,
			self::FIVE,
			self::SIX,
			self::SEVEN,
			self::EIGHT,
			self::NINE,
			self::TEN,
			self::JACK,
			self::QUEEN,
			self::KING,
		];
	}
}
