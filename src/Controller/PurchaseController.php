<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Purchase\Order;
use App\Repository\Purchase\DurationPurchaseRepository;
use App\Repository\Purchase\OneTimePurchaseRepository;
use App\Repository\UserRepository;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/purchase')]
final class PurchaseController extends AbstractController
{
    public function __construct(
        private OneTimePurchaseRepository $oneTimePurchaseRepository,
        private DurationPurchaseRepository $durationPurchaseRepository,
        private UserRepository $userRepository,
        private OrderManager $orderManager,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/', name: 'purchase_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('purchase/index.html.twig', [
            'oneTimePurchases' => $this->oneTimePurchaseRepository->findAll(),
            'durationPurchases' => $this->durationPurchaseRepository->findAll(),
        ]);
    }

    #[Route('/{id}/pay', name: 'pay_purchase', methods: ['GET'])]
    public function pay(Order $order): Response
    {
        $this->orderManager->doPayment($order);

        $this->em->flush();

        return $this->redirectToRoute('purchase_index');
    }

    #[Route('/pigeon', name: 'pay_purchase', methods: ['GET'])]
    #[IsGranted('pigeon')]
    public function pigeon(): Response
    {
        $this->userRepository->findAllPigeon();
        // TODO: display

        $this->em->flush();

        return $this->redirectToRoute('purchase_index');
    }
}
