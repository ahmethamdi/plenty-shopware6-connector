<?php

namespace PlentyConnector\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class OrderSyncService
{
    private PlentyApiService $plentyApiService;
    private EntityRepository $orderRepository;
    private SystemConfigService $config;
    private LoggerInterface $logger;

    public function __construct(
        PlentyApiService $plentyApiService,
        EntityRepository $orderRepository,
        SystemConfigService $config,
        LoggerInterface $logger
    ) {
        $this->plentyApiService = $plentyApiService;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function syncOrderToPlenty(string $orderId, Context $context): void
    {
        $order = $this->fetchOrder($orderId, $context);
        if (!$order) {
            $this->logger->error('Sipariş bulunamadı, plenty gönderimi atlandı: ' . $orderId);
            return;
        }

        try {
            $this->plentyApiService->authenticate();
        } catch (\Throwable $e) {
            $this->logger->error('Plenty oturum açma başarısız, sipariş gönderimi atlandı: ' . $e->getMessage());
            return;
        }

        $orderData = $this->formatOrderForPlenty($order);
        $result = $this->plentyApiService->createOrder($orderData);

        if (isset($result['error'])) {
            $this->logger->error('Sipariş gönderilmesi başarısız: ' . $result['error']);
            return;
        }

        $this->logger->info('Sipariş başarıyla Plentye gönderildi: ' . $order->getOrderNumber());
    }

    private function fetchOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = (new Criteria([$orderId]))
            ->addAssociation('currency')
            ->addAssociation('lineItems')
            ->addAssociation('orderCustomer')
            ->addAssociation('billingAddress.country')
            ->addAssociation('deliveries.shippingOrderAddress.country');

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context)->first();
        return $order;
    }

    private function formatOrderForPlenty(OrderEntity $order): array
    {
        $mandantId = $this->config->get('PlentyConnectorPlugin.config.mandantId');

        return [
            'mandantId' => $mandantId,
            'orderNumber' => $order->getOrderNumber(),
            'customerId' => $order->getCustomerId(),
            'orderDate' => $order->getOrderDateTime()?->format('Y-m-d H:i:s') ?? $order->getCreatedAt()?->format('Y-m-d H:i:s'),
            'total' => $order->getAmountTotal(),
            'currency' => $order->getCurrency()?->getIsoCode(),
            'lineItems' => $this->formatLineItems($order),
            'shippingAddress' => $this->formatAddress($order->getDeliveries()->first()?->getShippingOrderAddress()),
            'billingAddress' => $this->formatAddress($order->getBillingAddress()),
            'customerEmail' => $order->getOrderCustomer()?->getEmail(),
        ];
    }

    private function formatLineItems(OrderEntity $order): array
    {
        $items = [];
        foreach ($order->getLineItems() as $lineItem) {
            /** @var OrderLineItemEntity $lineItem */
            $items[] = [
                'productId' => $lineItem->getProductId(),
                'productNumber' => $lineItem->getPayload()['productNumber'] ?? null,
                'quantity' => $lineItem->getQuantity(),
                'unitPrice' => $lineItem->getUnitPrice(),
                'name' => $lineItem->getLabel(),
            ];
        }
        return $items;
    }

    private function formatAddress(?OrderAddressEntity $address): array
    {
        if (!$address) {
            return [];
        }

        return [
            'firstName' => $address->getFirstName(),
            'lastName' => $address->getLastName(),
            'street' => $address->getStreet(),
            'zipCode' => $address->getZipCode(),
            'city' => $address->getCity(),
            'countryCode' => $address->getCountry()?->getIso(),
        ];
    }
}
