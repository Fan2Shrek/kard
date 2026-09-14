<?php

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Mode\SolitaireGameMode;
use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Card\Hand;
use App\Game\Model\Card\Pile;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Service\GameEventApplier;
use App\Tests\AAA\Act\Act;

covers(SolitaireGameMode::class);

pest()->group('Solitaire');

/**
 * Solitaire has no hand and no opponent, so the shared Arrange/Act harness
 * (built around turns and hands) has nothing to offer here - states are built
 * straight from piles instead.
 *
 * @param array<string, string[]> $piles
 * @param string[]                $stock
 * @param string[]                $waste
 */
function solitaireState(array $piles = [], array $stock = [], array $waste = []): GameState
{
    $cards = [];

    foreach (Suit::cases() as $suit) {
        foreach (Rank::valueCases() as $rank) {
            $card = Act::card($rank->value, $suit->value);
            $cards[$card->id] = $card;
        }
    }

    return new GameState(
        [new PlayerState('p1', 'Solo', 0, new Hand([]))],
        ['p1'],
        'p1',
        [],
        new DiscardPile($waste),
        new DrawPile(array_combine($stock, $stock)),
        $cards,
        array_map(fn (array $ids): Pile => new Pile($ids), $piles),
    );
}

function solitairePlay(GameState $state, array $cardIds, string $to): GameState
{
    $mode = new SolitaireGameMode();
    $applier = new GameEventApplier();
    $ctx = new GameContext($state);

    $mode->play($cardIds, $ctx, 'p1', ['to' => $to]);

    foreach ($ctx->flushEvents() as $event) {
        $state = $applier->apply($event, $state);
    }

    return $state;
}

describe('Solitaire: distribution', function () {
    test('Les 7 colonnes reçoivent 28 cartes et la dernière de chaque colonne est visible', function () {
        $stock = [];

        foreach (Suit::cases() as $suit) {
            foreach (Rank::valueCases() as $rank) {
                $stock[] = Act::card($rank->value, $suit->value)->id;
            }
        }

        $state = solitaireState([], $stock);
        $ctx = new GameContext($state);
        $applier = new GameEventApplier();

        (new SolitaireGameMode())->setup($ctx);

        foreach ($ctx->flushEvents() as $event) {
            $state = $applier->apply($event, $state);
        }

        $dealt = 0;

        for ($column = 0; $column < SolitaireGameMode::COLUMNS; ++$column) {
            expect($state->getPile("tableau_{$column}_up")->count())->toBe(1);
            // column 0 has no face-down half at all - no cards, so no pile was ever created
            expect(($state->piles["tableau_{$column}_down"] ?? new Pile())->count())->toBe($column);

            $dealt += $column + 1;
        }

        expect($dealt)->toBe(28);
        expect($state->drawPile->count())->toBe(52 - 28);
    });
});

describe('Solitaire: fondations', function () {
    test('Un as part sur sa fondation vide', function () {
        $state = solitaireState(['tableau_0_up' => ['1h']]);

        $state = solitairePlay($state, ['1h'], 'foundation_h');

        expect($state->getPile('foundation_h')->cards)->toBe(['1h']);
        expect($state->getPile('tableau_0_up')->count())->toBe(0);
    });

    test('Une carte qui ne suit pas l\'as est refusée', function () {
        $state = solitaireState(['tableau_0_up' => ['3h'], 'foundation_h' => ['1h']]);

        solitairePlay($state, ['3h'], 'foundation_h');
    })->throws('foundation.sequence');

    test('Une carte ne peut pas aller sur la fondation d\'une autre couleur', function () {
        $state = solitaireState(['tableau_0_up' => ['1h']]);

        solitairePlay($state, ['1h'], 'foundation_s');
    })->throws('foundation.wrong_suit');
});

describe('Solitaire: colonnes', function () {
    test('Une carte se pose sur un rang supérieur de couleur opposée', function () {
        $state = solitaireState(['tableau_0_up' => ['9h'], 'tableau_1_up' => ['10s']]);

        $state = solitairePlay($state, ['9h'], 'tableau_1_up');

        expect($state->getPile('tableau_1_up')->cards)->toBe(['10s', '9h']);
    });

    test('Une carte de même couleur est refusée', function () {
        $state = solitaireState(['tableau_0_up' => ['9h'], 'tableau_1_up' => ['10d']]);

        solitairePlay($state, ['9h'], 'tableau_1_up');
    })->throws('column.sequence');

    test('Un rang qui ne décroît pas est refusé', function () {
        $state = solitaireState(['tableau_0_up' => ['8h'], 'tableau_1_up' => ['10s']]);

        solitairePlay($state, ['8h'], 'tableau_1_up');
    })->throws('column.sequence');

    test('Seul un roi démarre une colonne vide', function () {
        $state = solitaireState(['tableau_0_up' => ['9h'], 'tableau_1_up' => []]);

        solitairePlay($state, ['9h'], 'tableau_1_up');
    })->throws('column.king_only');

    test('Une suite alternée se déplace en bloc', function () {
        $state = solitaireState([
            'tableau_0_up' => ['ks', 'qh', 'js'],
            'tableau_1_up' => ['kc'],
        ]);

        $state = solitairePlay($state, ['qh', 'js'], 'tableau_1_up');

        expect($state->getPile('tableau_1_up')->cards)->toBe(['kc', 'qh', 'js']);
        expect($state->getPile('tableau_0_up')->cards)->toBe(['ks']);
    });

    test('Une suite non alternée ne se déplace pas', function () {
        $state = solitaireState([
            'tableau_0_up' => ['ks', 'qh', 'jh'],
            'tableau_1_up' => ['10h'],
        ]);

        solitairePlay($state, ['qh', 'jh'], 'tableau_1_up');
    })->throws('move.run_invalid');

    test('Vider une colonne retourne la carte cachée du dessus', function () {
        $state = solitaireState([
            'tableau_0_down' => ['2c', '5d'],
            'tableau_0_up' => ['9h'],
            'tableau_1_up' => ['10s'],
        ]);

        $state = solitairePlay($state, ['9h'], 'tableau_1_up');

        expect($state->getPile('tableau_0_up')->cards)->toBe(['5d']);
        expect($state->getPile('tableau_0_down')->cards)->toBe(['2c']);
    });
});

describe('Solitaire: pioche', function () {
    test('Piocher retourne la carte du dessus sur la défausse', function () {
        $state = solitaireState([], ['1h', '2h']);

        $state = solitairePlay($state, [], 'stock');

        expect($state->discardPile->cards)->toBe(['1h']);
        expect($state->drawPile->count())->toBe(1);
    });

    test('Une pioche vide recycle la défausse en gardant le même ordre de tirage', function () {
        $state = solitaireState([], [], ['1h', '2h', '3h']);

        $state = solitairePlay($state, [], 'stock');

        expect($state->discardPile->count())->toBe(0);
        expect(array_values($state->drawPile->cards))->toBe(['1h', '2h', '3h']);
    });

    test('Sans pioche ni défausse il n\'y a plus rien à tirer', function () {
        solitairePlay(solitaireState(), [], 'stock');
    })->throws('draw.empty');
});

describe('Solitaire: terminer', function () {
    test('Terminer est refusé tant qu\'une carte est face cachée', function () {
        $state = solitaireState([
            'tableau_0_down' => ['2c'],
            'tableau_0_up' => ['1h'],
        ]);

        solitairePlay($state, [], 'auto');
    })->throws('auto.face_down_remaining');

    test('Terminer déroule tout le plateau sur les fondations', function () {
        $piles = [];
        $column = 0;

        // deal every card face up across the columns, highest rank at the bottom
        // so each column is emptied from the top down
        foreach (Suit::cases() as $suit) {
            $piles["tableau_{$column}_up"] = array_map(
                fn (Rank $rank): string => Act::card($rank->value, $suit->value)->id,
                array_reverse(Rank::valueCases()),
            );
            ++$column;
        }

        $state = solitairePlay(solitaireState($piles), [], 'auto');

        foreach (Suit::cases() as $suit) {
            expect($state->getPile("foundation_{$suit->value}")->count())->toBe(13);
        }

        expect((new SolitaireGameMode())->isGameFinished($state))->toBeTrue();
    });

    test('Terminer va chercher les cartes restées dans la pioche', function () {
        $stock = [];
        $piles = [];
        $column = 0;

        foreach (Suit::cases() as $suit) {
            $ranks = Rank::valueCases();
            // leave the aces in the stock: they can only come out by drawing
            $ace = array_shift($ranks);
            $stock[] = Act::card($ace->value, $suit->value)->id;

            $piles["tableau_{$column}_up"] = array_map(
                fn (Rank $rank): string => Act::card($rank->value, $suit->value)->id,
                array_reverse($ranks),
            );
            ++$column;
        }

        $state = solitairePlay(solitaireState($piles, $stock), [], 'auto');

        expect((new SolitaireGameMode())->isGameFinished($state))->toBeTrue();
        expect($state->drawPile->count())->toBe(0);
        expect($state->discardPile->count())->toBe(0);
    });
});

describe('Solitaire: fin de partie', function () {
    test('La partie est finie quand les 4 fondations sont complètes', function () {
        $piles = [];

        foreach (Suit::cases() as $suit) {
            $piles["foundation_{$suit->value}"] = array_map(
                fn (Rank $rank): string => Act::card($rank->value, $suit->value)->id,
                Rank::valueCases(),
            );
        }

        expect((new SolitaireGameMode())->isGameFinished(solitaireState($piles)))->toBeTrue();
        expect((new SolitaireGameMode())->isGameFinished(solitaireState()))->toBeFalse();
    });
});
