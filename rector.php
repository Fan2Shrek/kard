<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withSets([
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::PHP_84,
        SetList::CODING_STYLE,
    ])
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withPaths([
        __DIR__.'/src',
    ]);
