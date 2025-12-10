<?php

namespace PlentyConnector\Service;

use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class PlentyApiService
{
    private $httpClient;
    private $logger;
    private $apiToken;
    private $baseUrl = 'https://www.plentymarkets-cloud-de.com/rest';

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->httpClient = HttpClient::create();
    }

    public function setApiToken(string $token): void
    {
        $this->apiToken = $token;
    }

    public function getProducts(int $page = 0, int $itemsPerPage = 100): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/items', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                ],
                'query' => [
                    'page' => $page,
                    'itemsPerPage' => $itemsPerPage,
                ]
            ]);

            return json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            $this->logger->error('Plentyden ürünler çekilemedi: ' . $e->getMessage());
            return [];
        }
    }

    public function getProductImages(string $itemId): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/items/' . $itemId . '/images', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                ]
            ]);

            return json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            $this->logger->error('Ürün görselleri çekilemedi: ' . $e->getMessage());
            return [];
        }
    }

    public function createOrder(array $orderData): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/orders', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $orderData
            ]);

            return json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            $this->logger->error('Sipariş Plentye gönderilemedi: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
