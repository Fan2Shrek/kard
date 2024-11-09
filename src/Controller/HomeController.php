<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    public function __construct(
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
        $this->hub->publish(new Update('http://example.com/game'), 'Game started!');

        return $this->render('home/game.html.twig');
    }
}
