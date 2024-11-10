<?php

namespace App\Controller;

use App\Service\Card\CardGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly CardGenerator $cardGenerator,
        private readonly HubInterface $hub,
    ) {
    }


    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/game', name: 'game')]
    public function game(): Response
    {
        $hands = $this->cardGenerator->generateHands(2, 5);

        return $this->render('home/game.html.twig', [
            'hands' => $hands,
        ]);
    }
}
