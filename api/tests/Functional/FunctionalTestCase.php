<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use App\Tests\Functional\User\UserTrait;
use App\Tests\There\ThereIs;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class FunctionalTestCase extends ApiTestCase
{
	use JsonAssertionTrait;
	use UserTrait;

	public static PropertyAccessor $propertyAccessor;

	protected static ?bool $alwaysBootKernel = true;
	protected static bool $requestsWithAuthentication = false;
	protected Client $client;

	private ?User $currentUser = null;

	protected function setUp(): void
	{
		$this->client = self::createClient();
		$this->client->disableReboot();
		self::$propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
			->enableExceptionOnInvalidIndex()
			->getPropertyAccessor()
		;

		static::getContainer()->get(Connection::class)->beginTransaction();
		ThereIs::setContainer(static::getContainer());

		if (static::$requestsWithAuthentication) {
			$this->client->loginUser($this->currentUser = $this->createUser());
		}

		parent::setUp();
	}

	protected function tearDown(): void
	{
		static::getContainer()->get(Connection::class)->rollBack();

		parent::tearDown();
	}

	protected static function getEM(): EntityManagerInterface
	{
		return static::getContainer()->get(ManagerRegistry::class)->getManager();
	}

	protected function getCurrentUser(): ?User
	{
		return $this->currentUser;
	}
}
