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
}
