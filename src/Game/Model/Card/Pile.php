<?php

declare(strict_types=1);

namespace App\Game\Model\Card;

/**
 * A generic, ordered stack of cards addressed by a key in GameState::$piles.
 * Used by layout-based modes (solitaire's tableau columns and foundations)
 * that need more than the single draw/discard pair.
 */
final readonly class Pile extends AbstractCardStack
{
}
