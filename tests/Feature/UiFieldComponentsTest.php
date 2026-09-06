<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

uses(KernelTestCase::class, InteractsWithTwigComponents::class);

it('renders the toggle checked', function () {
    $html = (string) $this->renderTwigComponent('ui:Toggle', [
        'name' => 'withJokers',
        'label' => 'Jokers',
        'icon' => 'lucide:sparkles',
        'checked' => true,
    ]);

    expect($html)->toContain('class="switch"')->toContain('name="withJokers"')->toContain('checked');
});

it('renders the number field bounds', function () {
    $html = (string) $this->renderTwigComponent('ui:NumberField', [
        'name' => 'deckCount',
        'label' => 'Decks',
        'min' => 1,
        'max' => 4,
        'value' => 2,
    ]);

    expect($html)->toContain('type="number"')->toContain('min="1"')->toContain('max="4"')->toContain('value="2"');
});

it('marks the current select option', function () {
    $html = (string) $this->renderTwigComponent('ui:SelectField', [
        'name' => 'skin',
        'label' => 'Skin',
        'choices' => ['Default' => 'default', 'Beta' => 'beta'],
        'value' => 'beta',
    ]);

    expect($html)->toContain('<option value="beta" selected')->not->toContain('<option value="default" selected');
});
