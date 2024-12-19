<?php

$ruleset = new TwigCsFixer\Ruleset\Ruleset();

$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer())
    ->addRule(new TwigCsFixer\Rules\Variable\VariableNameRule());

$config = new TwigCsFixer\Config\Config();
$config->allowNonFixableRules()
    ->setRuleset($ruleset);

return $config;
