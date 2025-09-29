<?php

declare(strict_types=1);

namespace App\Tests\There\Resources;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @template T of object
 */
abstract class AbstractBuilder
{
    /**
     * @param class-string<T> $classFqcn
     */
    public function __construct(
        protected ContainerInterface $container,
        protected string $classFqcn,
    ) {
    }

    /**
     * @return T
     */
    public function build(): object
    {
        $em = $this->container->get('doctrine')->getManager();
        $entity = new $this->classFqcn(...$this->getParams());

        $this->afterBuild($entity);

        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    protected function getParams(): array
    {
        return [];
    }

    /**
     * @param T $entity
     */
    protected function afterBuild(object $entity): void
    {
    }
}
