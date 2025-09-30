<?php

declare(strict_types=1);

namespace App\Tests\There;

use App\Tests\There\Resources\AbstractBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ThereIs
{
	private static ContainerInterface $container;

	private AbstractBuilder $builder;

	public function __construct(
		private int $count,
	) {}

	public static function setContainer(ContainerInterface $container): void
	{
		self::$container = $container;
	}

	public static function a(): self
	{
		return new self(1);
	}

	public static function an(): self
	{
		return self::a();
	}

	public static function some(int $count): self
	{
		return new self($count);
	}

	public function GameMode()
	{
		return $this->assignBuilder(new Resources\GameModeBuilder(self::$container));
	}

	public function User()
	{
		return $this->assignBuilder(new Resources\UserBuilder(self::$container));
	}

	public function Result()
	{
		return $this->assignBuilder(new Resources\ResultBuilder(self::$container));
	}

	public function Room()
	{
		return $this->assignBuilder(new Resources\RoomBuilder(self::$container));
	}

	public function build(): object
	{
		for ($i = 1; $i < $this->count; $i++) {
			$clone = clone $this->builder;
			$clone->build();
		}

		return $this->builder->build();
	}

	public function __call(string $name, array $arguments): self
	{
		if (!isset($this->builder)) {
			throw new \LogicException('You must call a builder method before calling any with* method.');
		}

		if (!method_exists($this->builder, $name)) {
			throw new \BadMethodCallException(\sprintf('Method "%s" does not exist on builder "%s".', $name, get_class($this->builder)));
		}

		$this->builder->$name(...$arguments);

		return $this;
	}

	private function assignBuilder(AbstractBuilder $builder): self
	{
		$this->builder = $builder;

		return $this;
	}
}
