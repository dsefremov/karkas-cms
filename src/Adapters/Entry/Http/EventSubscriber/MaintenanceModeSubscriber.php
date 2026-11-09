<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\EventSubscriber;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\MaintenanceModeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

final class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
        private readonly MaintenanceModeRepository $maintenance,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 20]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $path = $event->getRequest()->getPathInfo();
        if (str_starts_with($path, '/health') || str_starts_with($path, '/_')) {
            return;
        }
        if (str_starts_with($path, '/api/v1/admin')) {
            return;
        }
        if (!$this->connections->isConfigured()) {
            return;
        }
        try {
            if (!$this->maintenance->isEnabled()) {
                return;
            }
        } catch (Throwable) {
            return;
        }
        if ('POST' !== $event->getRequest()->getMethod()) {
            return;
        }
        $event->setResponse(new JsonResponse([
            'error' => 'maintenance_mode',
            'message' => 'Writes are frozen while platform maintenance is active.',
        ], 503));
    }
}
