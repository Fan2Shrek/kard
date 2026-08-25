<?php

declare(strict_types=1);

namespace App\Game\Builder;

use App\Enum\DeckSkinEnum;
use App\Game\Mode\GameModeInterface;
use App\Game\Model\GameConfiguration;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GameConfigurationBuilder
{
	public const MAX_DECK_COUNT = 4;

    public function build(GameModeInterface $mode, array $rawOptions): GameConfiguration
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'withJokers' => false,
            'deckCount' => 1,
			'skin' => DeckSkinEnum::DEFAULT,
        ]);
        $resolver->setAllowedTypes('withJokers', 'bool');
        $resolver->setAllowedTypes('deckCount', 'int');
		$resolver->setAllowedValues('deckCount', fn ($value) => $value > 0 && $value <= self::MAX_DECK_COUNT);

        $mode->configureOptions($resolver);

        return new GameConfiguration($resolver->resolve($rawOptions));
	}
}
