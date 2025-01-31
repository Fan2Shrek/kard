<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(event: KernelEvents::REQUEST)]
final class BannedUserListener
{
    public function __construct(
        private RouterInterface $router,
        private Security $security,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->security->getUser() || !$this->security->isGranted('ROLE_BANNED')) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');
        if ('app_logout' === $route || 'banned' === $route) {
            return;
        }

        // Redirect to the banned page
        $event->setResponse(new RedirectResponse($this->router->generate('banned')));
    }
}
