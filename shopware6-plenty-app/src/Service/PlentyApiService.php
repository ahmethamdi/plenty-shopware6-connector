<?php

namespace PlentyConnector\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PlentyApiService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private SystemConfigService $config;
    private ?string $apiToken = null;
    private ?\DateTimeImmutable $tokenExpiresAt = null;

    public function __construct(
        HttpClientInterface $httpClient,
        SystemConfigService $config,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function setApiToken(string $token): void
    {
        $this->apiToken = $token;
    }

    public function authenticate(bool $force = false): void
    {
        if (!$force && $this->apiToken && $this->tokenExpiresAt && $this->tokenExpiresAt > new \DateTimeImmutable('+5 minutes')) {
            return;
        }

        $username = $this->config->get('PlentyConnectorPlugin.config.username');
        $password = $this->config->get('PlentyConnectorPlugin.config.password');

        if (!$username || !$password) {
            throw new \RuntimeException('Plentymarkets kullanıcı adı veya şifre eksik (Plugin ayarlarını kontrol edin).');
        }

        try {
            $response = $this->httpClient->request('POST', $this->getBaseUrl() . '/login', [
                'json' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ]);

            $data = $response->toArray(false);
            $token = $data['accessToken'] ?? $data['token'] ?? null;
            if (!$token) {
                throw new \RuntimeException('Plentymarkets login yanıtında token bulunamadı.');
            }

            $this->apiToken = $token;
            $ttl = (int)($data['expiresIn'] ?? 0);
            $this->tokenExpiresAt = (new \DateTimeImmutable())->modify($ttl > 0 ? sprintf('+%d seconds', $ttl) : '+25 minutes');
        } catch (\Exception $e) {
            $this->logger->error('Plentymarkets oturum açma hatası: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getProducts(int $page = 0, int $itemsPerPage = 100): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->httpClient->request('GET', $this->getBaseUrl() . '/items', [
                'headers' => $this->getAuthHeaders(),
                'query' => [
                    'page' => $page,
                    'itemsPerPage' => $itemsPerPage,
                    'with' => 'variations,images,texts',
                ]
            ]);

            if ($response->getStatusCode() >= 400) {
                $body = $response->getContent(false);
                $this->logger->error('Plenty ürün çağrısı başarısız', [
                    'status' => $response->getStatusCode(),
                    'body' => $body,
                ]);
                return [];
            }

            return json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            $this->logger->error('Plentyden ürünler çekilemedi: ' . $e->getMessage());
            return [];
        }
    }

    public function getProductImages(string $itemId): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->httpClient->request('GET', $this->getBaseUrl() . '/items/' . $itemId . '/images', [
                'headers' => $this->getAuthHeaders(),
            ]);

            return json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            $this->logger->error('Ürün görselleri çekilemedi: ' . $e->getMessage());
            return [];
        }
    }

    public function createOrder(array $orderData): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->httpClient->request('POST', $this->getBaseUrl() . '/orders', [
                'headers' => $this->getAuthHeaders(),
                'json' => $orderData
            ]);

            return json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            $this->logger->error('Sipariş Plentye gönderilemedi: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    private function ensureAuthenticated(): void
    {
        if (!$this->apiToken) {
            $this->authenticate();
        }
    }

    private function getBaseUrl(): string
    {
        $configured = $this->config->get('PlentyConnectorPlugin.config.baseUrl') ?: 'https://p57085.my.plentysystems.com/rest';
        return rtrim($configured, '/');
    }

    private function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
