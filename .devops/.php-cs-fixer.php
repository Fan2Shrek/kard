<?php

$finder = (new PhpCsFixer\Finder())
    ->in(dirname(__DIR__))
    ->exclude('var')
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
