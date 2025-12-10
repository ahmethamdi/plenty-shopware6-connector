<?php

namespace PlentyConnector\EventListener;

use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use PlentyConnector\Service\OrderSyncService;
use Shopware\Core\Framework\Context;

class OrderPlacedListener implements EventSubscriberInterface
{
    private OrderSyncService $orderSyncService;

    public function __construct(OrderSyncService $orderSyncService)
    {
        $this->orderSyncService = $orderSyncService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_PLACED_EVENT => 'onOrderPlaced',
        ];
    }

    public function onOrderPlaced(EntityWrittenEvent $event): void
    {
        $context = $event->getContext();

        foreach ($event->getIds() as $orderId) {
            $this->orderSyncService->syncOrderToPlenty($orderId, $context);
        }
    }
}
