<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Exception\TranslatableException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslateExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof TranslatableException) {
            return;
        }

        $throwable->setMessage($this->translator->trans(
            $throwable->getTranslationCode(),
            $throwable->getParams(),
            $throwable->getDomain(),
        ));

        if ('json' === $event->getRequest()->attributes->get('_format')) {
            $event->setResponse(
                new JsonResponse([
                    'error' => $throwable->getMessage(),
                ], 400)
            );
        }
    }
}
