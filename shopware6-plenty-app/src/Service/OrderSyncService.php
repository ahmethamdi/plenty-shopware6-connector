<?php

namespace PlentyConnector\Service;

use Psr\Log\LoggerInterface;

class OrderSyncService
{
    private $plentyApiService;
    private $orderRepository;
    private $logger;

    public function __construct(
        PlentyApiService $plentyApiService,
        $orderRepository,
        LoggerInterface $logger
    ) {
        $this->plentyApiService = $plentyApiService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function syncOrderToPlenty($order): void
    {
        $orderData = $this->formatOrderForPlenty($order);
        $result = $this->plentyApiService->createOrder($orderData);

        if (isset($result['error'])) {
            $this->logger->error('Sipariş gönderilmesi başarısız: ' . $result['error']);
        } else {
            $this->logger->info('Sipariş başarıyla Plentye gönderildi: ' . $order->getOrderNumber());
        }
    }

    private function formatOrderForPlenty($order): array
    {
        return [
            'orderNumber' => $order->getOrderNumber(),
            'customerId' => $order->getCustomerId(),
            'orderDate' => $order->getOrderDateTime()->format('Y-m-d'),
            'total' => $order->getAmountTotal(),
            'currency' => $order->getCurrency()->getIsoCode(),
            'lineItems' => $this->formatLineItems($order),
            'shippingAddress' => $this->formatAddress($order->getDeliveries()[0]->getShippingOrderAddress() ?? null),
            'billingAddress' => $this->formatAddress($order->getBillingAddress()),
        ];
    }

    private function formatLineItems($order): array
    {
        $items = [];
        foreach ($order->getLineItems() as $lineItem) {
            $items[] = [
                'productId' => $lineItem->getProductId(),
                'quantity' => $lineItem->getQuantity(),
                'unitPrice' => $lineItem->getUnitPrice(),
                'name' => $lineItem->getLabel(),
            ];
        }
        return $items;
    }

    private function formatAddress($address): array
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
            'countryCode' => $address->getCountry()->getIso(),
        ];
    }
}
