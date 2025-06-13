<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    use ControllerTrait;

    #[Route('/', name: 'home')]
    public function index(RoomRepository $roomRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'currentGames' => $roomRepository->findAllCurrent(),
        ]);
    }
}
