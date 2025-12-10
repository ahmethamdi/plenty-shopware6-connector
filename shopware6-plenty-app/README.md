# Shopware 6 - Plentymarkets Connector

Shopware 6 ve Plentymarkets arasında senkronizasyon sağlayan plugin.

## Özellikler

- **Ürün Senkronizasyonu**: Plentyden ürünleri, fiyatları, kategorileri, görselleri ve içerikleri çekme
- **Sipariş Senkronizasyonu**: Shopware 6'dan gelen siparişleri Plentye gönderme
- **Otomatik Senkronizasyon**: Arka planda otomatik veriler senkronize edilir

## Kurulum

1. Plugin dosyalarını Shopware 6 plugins klasörüne kopyalayın
2. `composer install` çalıştırın
3. Plentymarkets API anahtarını yapılandırın
4. Plugin'i aktifleştirin

## Konfigürasyon

`.env` dosyasında ayarlayın:

cat > /Users/ahmethamdikilic/Desktop/plentyconnector/shopware6-plenty-app/src/Service/ProductSyncService.php << 'EOF'
<?php

namespace PlentyConnector\Service;

use Psr\Log\LoggerInterface;

class ProductSyncService
{
    private $plentyApiService;
    private $productRepository;
    private $logger;

    public function __construct(
        PlentyApiService $plentyApiService,
        $productRepository,
        LoggerInterface $logger
    ) {
        $this->plentyApiService = $plentyApiService;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function syncProducts(): void
    {
        $this->logger->info('Ürün senkronizasyonu başladı');

        $page = 0;
        do {
            $response = $this->plentyApiService->getProducts($page);
            
            if (empty($response['entries'])) {
                break;
            }

            foreach ($response['entries'] as $plentyProduct) {
                $this->importProduct($plentyProduct);
            }

            $page++;
        } while (isset($response['isLastPage']) && !$response['isLastPage']);

        $this->logger->info('Ürün senkronizasyonu tamamlandı');
    }

    private function importProduct(array $plentyProduct): void
    {
        try {
            $images = $this->plentyApiService->getProductImages($plentyProduct['id']);

            $shopwareProduct = [
                'name' => $plentyProduct['texts'][0]['name'] ?? 'Ürün',
                'description' => $plentyProduct['texts'][0]['description'] ?? '',
                'price' => [
                    [
                        'currencyId' => 'b7d2554b0ce847cd82f3ac73bd8e50d7',
                        'gross' => $plentyProduct['variations'][0]['prices'][0]['price'] ?? 0,
                        'net' => ($plentyProduct['variations'][0]['prices'][0]['price'] ?? 0) / 1.19,
                        'linked' => true
                    ]
                ],
                'externalId' => 'plenty_' . $plentyProduct['id'],
            ];

            $this->logger->info('Ürün içe aktarıldı: ' . $shopwareProduct['name']);
        } catch (\Exception $e) {
            $this->logger->error('Ürün içe aktarılırken hata: ' . $e->getMessage());
        }
    }
}
