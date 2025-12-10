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
                    'with' => 'variations.stock,texts',
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

            if ($response->getStatusCode() >= 400) {
                $this->logger->error('Plenty image API çağrısı başarısız', [
                    'status' => $response->getStatusCode(),
                    'itemId' => $itemId,
                    'body' => $response->getContent(false),
                ]);
                return [];
            }

            $data = json_decode($response->getContent(), true);
            $this->logger->debug("Plenty image API response: itemId={$itemId}", [
                'response_keys' => array_keys($data ?? []),
                'entries_count' => isset($data['entries']) && is_array($data['entries']) ? count($data['entries']) : 0
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Ürün görselleri çekilemedi: ' . $e->getMessage(), [
                'itemId' => $itemId,
                'exception' => get_class($e)
            ]);
            return [];
        }
    }

    public function getVariationSalesPrices(string $variationId): array
    {
        $this->ensureAuthenticated();

        try {
            // Try variation detail with salesPrices relation first (more stable than nested endpoint)
            $response = $this->httpClient->request('GET', $this->getBaseUrl() . '/items/variations/' . $variationId, [
                'headers' => $this->getAuthHeaders(),
                'query' => ['with' => 'salesPrices'],
            ]);

            if ($response->getStatusCode() >= 400) {
                // 404 is normal for first endpoint, try fallback
                if ($response->getStatusCode() !== 404) {
                    $this->logger->debug('Plenty variation detail çağrısı başarısız, fallback deneniyor', [
                        'status' => $response->getStatusCode(),
                        'variation' => $variationId,
                    ]);
                }

                // fallback to legacy nested endpoint
                $fallback = $this->httpClient->request('GET', $this->getBaseUrl() . '/items/variations/' . $variationId . '/salesprices', [
                    'headers' => $this->getAuthHeaders(),
                ]);
                if ($fallback->getStatusCode() >= 400) {
                    // 404 is normal - not all variations have sales prices, just use fallback price
                    // Only log if it's a real error (not 404)
                    if ($fallback->getStatusCode() !== 404) {
                        $body = $fallback->getContent(false);
                        $this->logger->warning('Plenty sales price çağrısı başarısız', [
                            'status' => $fallback->getStatusCode(),
                            'body' => $body,
                            'variation' => $variationId,
                        ]);
                    }
                    return [];
                }

                return $fallback->toArray(false);
            }

            $data = $response->toArray(false);
            if (isset($data['salesPrices'])) {
                return $data['salesPrices'];
            }

            // If not embedded, try legacy structure
            return $data;
        } catch (\Throwable $e) {
            // Only log real errors, not 404s
            if (!str_contains($e->getMessage(), '404')) {
                $this->logger->warning('Plenty sales price çağrısı hata', ['msg' => $e->getMessage(), 'variation' => $variationId]);
            }
            return [];
        }
    }

    public function getVariationStock(string $variationId): ?float
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->httpClient->request('GET', $this->getBaseUrl() . '/stockmanagement/stock', [
                'headers' => $this->getAuthHeaders(),
                'query' => ['variationId' => $variationId],
            ]);

            if ($response->getStatusCode() >= 400) {
                $this->logger->warning('Plenty stok çağrısı başarısız', [
                    'status' => $response->getStatusCode(),
                    'variation' => $variationId,
                    'body' => $response->getContent(false),
                ]);
                return null;
            }

            $data = $response->toArray(false);
            $entries = $data['entries'] ?? $data ?? [];
            if (is_array($entries) && count($entries) > 0) {
                $sum = 0;
                foreach ($entries as $entry) {
                    $sum += (float)($entry['stockNet'] ?? $entry['stockPhysical'] ?? 0);
                }
                return $sum;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Plenty stok çağrısı hata', ['msg' => $e->getMessage(), 'variation' => $variationId]);
        }

        return null;
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

    public function getBaseUrl(): string
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
