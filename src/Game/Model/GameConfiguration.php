<?php

declare(strict_types=1);

namespace App\Game\Model;

use App\Enum\DeckSkinEnum;

class GameConfiguration implements \JsonSerializable
{
	/** @param array<string, mixed> $options */
	public function __construct(private array $options) {}

	public function hasJokers(): bool
	{
		return $this->options['withJokers'] ?? false;
	}

	public function deckCount(): int
	{
		return $this->options['deckCount'];
	}

	public function getSkin(): DeckSkinEnum
	{
		return $this->options['skin'];
	}

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->options[$key] ?? $default;
	}

	/** @return array<string, mixed> */
	public function toArray(): array
	{
		return $this->options;
	}

	/** @return array<string, mixed> */
	public function jsonSerialize(): array
	{
		return $this->options;
	}

	/** @param array<string, mixed> $data */
	public static function fromArray(array $data): self
	{
		$data['skin'] = DeckSkinEnum::from($data['skin'] ?? DeckSkinEnum::DEFAULT->value);

		return new self($data);
	}
}
