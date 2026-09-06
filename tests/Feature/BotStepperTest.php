<?php

use App\Entity\GameMode;
use App\Entity\Room;
use App\Game\Mode\GameModeEnum;
use App\Service\Bot\GameAI;
use Ramsey\Uuid\Uuid;
use Twig\Environment;

// KERNEL_CLASS isn't set in phpunit.xml.dist, so KernelTestCase can't boot itself
function testTwig(): Environment
{
    static $twig = null;

    if (null === $twig) {
        $kernel = new App\Kernel('test', true);
        $kernel->boot();
        $twig = $kernel->getContainer()->get('test.service_container')->get('twig');
    }

    return $twig;
}

function renderStepper(Room $room): string
{
    return testTwig()->render('components/bot-stepper.html.twig', ['room' => $room]);
}

test('le - est desactive quand il n y a aucun bot', function () {
    $room = new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4());

    $html = renderStepper($room);

    expect($html)->toContain('disabled');
    expect($html)->toContain('>0<');
});

test('le compteur suit le nombre de bots et le - se reactive', function () {
    $room = new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4());

    foreach (range(1, 3) as $i) {
        $bot = GameAI::create();
        $room->addBot($bot->id, $bot);
    }

    $html = renderStepper($room);

    expect($html)->not->toContain('disabled');
    expect($html)->toContain('>3<');
});

test('le wrapper turbo cible bien le stepper', function () {
    $html = testTwig()->render(
        'components/turbo/bot-stepper.html.twig',
        ['room' => new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4())],
    );

    // a replace stream is a no-op unless the target id matches the rendered element
    expect($html)->toContain('action="replace" target="bot-stepper"');
    expect($html)->toContain('id="bot-stepper"');
});
