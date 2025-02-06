<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Purchase\Order;
use App\Entity\User;
use App\Form\Admin\OrderType;
use App\Repository\UserRepository;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/purchase')]
final class PurchaseController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private OrderManager $orderManager,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/', name: 'purchase_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        $order = new Order($this->getUser());
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $purchases = $form->get('purchases')->getData();
            $purchases = array_map(fn ($purchase) => $purchase['purchase'], $purchases);

            foreach ($purchases as $purchase) {
                $order->addPurchase($purchase);
                $this->em->persist($purchase);
            }

            $this->em->persist($order);
            $this->em->flush();

            return $this->redirectToRoute('pay_purchase', [
                'id' => $order->getId(),
            ]);
        }

        return $this->render('purchase/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/pay', name: 'pay_purchase', methods: ['GET'])]
    public function pay(Order $order): Response
    {
        $this->orderManager->doPayment($order);

        $this->em->flush();

        return $this->redirectToRoute('purchase_index');
    }

    #[Route('/pigeon', name: 'pigeon_purchase', methods: ['GET'])]
    #[IsGranted('pigeon')]
    public function pigeon(): Response
    {
        return $this->render('purchase/pigeon.html.twig', [
            'pigeons' => $this->userRepository->findAllPigeon(),
        ]);
    }

    protected function getUser(): User
    {
        $user = parent::getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User must be an instance of User');
        }

        return $user;
    }
}
