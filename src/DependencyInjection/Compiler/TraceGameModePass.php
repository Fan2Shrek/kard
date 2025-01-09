<?php

namespace App\DependencyInjection\Compiler;

use App\Debug\TraceableGameMode;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TraceGameModePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ('dev' !== $container->getParameter('kernel.environment')) {
            return;
        }

        foreach ($container->findTaggedServiceIds('app.game_mode') as $id => $tags) {
            $definition = $container->findDefinition($id);

            $container->register($id.'.traceable', TraceableGameMode::class)
                ->setDecoratedService($id)
                ->setArguments([$definition, new Reference('debug.stopwatch')]);
        }
    }
}
