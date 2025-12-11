<?php

namespace PlentyConnector\EventListener;

use PlentyConnector\Service\PackageProgressService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderPlacedPackageListener implements EventSubscriberInterface
{
    private PackageProgressService $packageProgressService;
    private EntityRepository $orderRepository;
    private LoggerInterface $logger;

    public function __construct(
        PackageProgressService $packageProgressService,
        EntityRepository $orderRepository,
        LoggerInterface $logger
    ) {
        $this->packageProgressService = $packageProgressService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_transaction.state.paid' => 'onOrderPaid',
        ];
    }

    public function onOrderPaid(OrderStateMachineStateChangeEvent $event): void
    {
        try {
            $orderId = $event->getOrder()->getId();
            $context = $event->getContext();

            $this->logger->info("Order paid event received, processing package progress", [
                'orderId' => $orderId
            ]);

            // Load full order with line items and products
            $criteria = new Criteria([$orderId]);
            $criteria->addAssociation('lineItems.product.categories');
            $criteria->addAssociation('orderCustomer.customer');

            $order = $this->orderRepository->search($criteria, $context)->first();

            if (!$order) {
                $this->logger->warning("Order not found for package processing", [
                    'orderId' => $orderId
                ]);
                return;
            }

            // Process order for package progress
            $this->packageProgressService->processOrder($order, $context);

        } catch (\Exception $e) {
            $this->logger->error("Error processing order for package progress", [
                'orderId' => $event->getOrder()->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
