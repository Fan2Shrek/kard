<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

abstract class AbstractFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $r = new \ReflectionClass($this->getEntityClass());

        foreach ($this->getData() as $key => $data) {
            $entity = $r->newInstanceWithoutConstructor();

            foreach ($data as $property => $value) {
                $setter = 'set'.ucfirst($property);

                if (method_exists($entity, $setter)) {
                    $entity->$setter($value);
                }
            }

            $this->postInstantiate($entity);
            $manager->persist($entity);
            ++$key;
            $this->addReference($r->getShortName().'_'.$key, $entity);
        }

        $manager->flush();
    }

    protected function postInstantiate(object $entity): void
    {
    }

    abstract protected function getData(): iterable;

    abstract protected function getEntityClass(): string;
}
