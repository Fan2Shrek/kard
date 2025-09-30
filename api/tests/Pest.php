<?php

use App\Model\Player;
use App\Tests\Functional\FunctionalTestCase;
use Pest\Expectation;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\ResponseInterface;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');
pest()->extend(FunctionalTestCase::class)->group('Functional')->in('Functional');
pest()->group('GameMode')->in('Feature/GameMode');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeAction', function (string $type) {
	if (!\is_array($this->value)) {
		throw new InvalidArgumentException('The value must be a string');
	}

	$this->toHaveKey('action');
	expect($this->value['action'])->toBe($type);
});

expect()->extend('toBeHaveData', function (string $key, string $expected) {
	if (!\is_array($this->value)) {
		throw new InvalidArgumentException('The value must be a string');
	}

	$this->toHaveKey('data');
	expect($this->value['data'])->toHaveKey($key);
	expect($this->value['data'][$key])->toBe($expected);
});

expect()->extend('toHaveTurns', function (int $count) {
	expect($this->value->getRound()->getTurns())->toHaveCount($count);
});

expect()->extend('toHaveNewRound', function () {
	expect($this->value)->toHaveTurns(0);
});

expect()->extend('toHaveWinner', function (Player|string $player) {
	if ($player instanceof Player) {
		$player = $player->username;
	}

	expect($this->value->getWinner()->username)->toBe($player);
});

expect()->extend('toBeSuccessful', function () {
	if (!$this->value instanceof ResponseInterface) {
		throw new InvalidArgumentException('The value must be an instance of ResponseInterface');
	}

	expect($this->value->getStatusCode())->toBe(200);
});

expect()->extend('toHaveStatusCode', function (int $code) {
	if (!$this->value instanceof ResponseInterface) {
		throw new InvalidArgumentException('The value must be an instance of ResponseInterface');
	}

	expect($this->value->getStatusCode(false))->toBe($code);
});

expect()->extend('toHaveHeader', function (string $header) {
	if (!$this->value instanceof ResponseInterface) {
		throw new InvalidArgumentException('The value must be an instance of ResponseInterface');
	}

	expect($this->value->getHeaders(false))->toHaveKey($header);
});

expect()->intercept('toMatch', ResponseInterface::class, function (string $property, mixed $value) {
	if (!$this->value instanceof ResponseInterface) {
		throw new InvalidArgumentException('The value must be an instance of ResponseInterface');
	}

	expect(FunctionalTestCase::$propertyAccessor->getValue(
		$this->value->toArray(false)['member'],
		$property
	))->toBe($value);
});

expect()->intercept('toHaveCount', ResponseInterface::class, function (int $count) {
	if (!$this->value instanceof ResponseInterface) {
		throw new InvalidArgumentException('The value must be an instance of ResponseInterface');
	}

	expect($this->value->toArray()['member'])->toHaveCount($count);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function expectMercureMessage(Update $update): Expectation
{
	$data = $update->getData();

	return expect(json_decode($data, true));
}

function commitDTB(): never
{
	DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver::commit();
	exit;
}
