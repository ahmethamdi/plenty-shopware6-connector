<?php

namespace PlentyConnector\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use finfo;

class ProductSyncService
{
    private PlentyApiService $plentyApiService;
    private EntityRepository $productRepository;
    private EntityRepository $taxRepository;
    private EntityRepository $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;
    private SystemConfigService $config;
    private LoggerInterface $logger;

    public function __construct(
        PlentyApiService $plentyApiService,
        EntityRepository $productRepository,
        EntityRepository $taxRepository,
        EntityRepository $mediaRepository,
        MediaService $mediaService,
        FileSaver $fileSaver,
        SystemConfigService $config,
        LoggerInterface $logger
    ) {
        $this->plentyApiService = $plentyApiService;
        $this->productRepository = $productRepository;
        $this->taxRepository = $taxRepository;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function syncProducts(Context $context): void
    {
        $this->logger->info('Ürün senkronizasyonu başladı');

        try {
            $this->plentyApiService->authenticate();
        } catch (\Throwable $e) {
            $this->logger->error('Plenty oturum açma başarısız, sync iptal: ' . $e->getMessage());
            return;
        }

        $page = 0;
        $itemsPerPage = (int)($this->config->get('PlentyConnectorPlugin.config.itemsPerPage') ?? 100);

        do {
            $response = $this->plentyApiService->getProducts($page, $itemsPerPage);

            if (empty($response['entries'])) {
                break;
            }

            foreach ($response['entries'] as $plentyProduct) {
                $this->upsertProduct($plentyProduct, $context);
            }

            $page++;
        } while (isset($response['isLastPage']) && !$response['isLastPage']);

        $this->logger->info('Ürün senkronizasyonu tamamlandı');
    }

    private function upsertProduct(array $plentyProduct, Context $context): void
    {
        try {
            $text = $plentyProduct['texts'][0] ?? [];
            $name = $text['name1'] ?? $text['name2'] ?? $text['name3'] ?? 'Ürün';
            $description = $text['description'] ?? $text['shortDescription'] ?? '';

            $variation = $plentyProduct['variations'][0] ?? [];
            $productNumber = $variation['number'] ?? ('plenty-' . ($plentyProduct['id'] ?? uniqid()));

            $salesPrices = [];
            if (!empty($variation['id'])) {
                $salesPrices = $this->plentyApiService->getVariationSalesPrices((string)$variation['id']);
            }

            $priceGross = $this->extractPriceGross($variation, $salesPrices);
            if ($priceGross === null) {
                $this->logger->warning('Fiyat bulunamadı, ürün atlandı: ' . $productNumber);
                return;
            }
            $priceNet = $priceGross > 0 ? $priceGross / 1.19 : 0;
            $stock = $this->resolveStock($variation);

            $currencyId = $context->getCurrencyId();
            $taxId = $this->resolveTaxId($context);
            if (!$taxId) {
                $this->logger->warning('Vergi bulunamadı, ürün atlandı: ' . $productNumber);
                return;
            }

            $existingId = $this->findProductIdByNumber($productNumber, $context);
            $coverId = $this->importFirstImageAsMedia($plentyProduct['id'] ?? null, $context);

            $payload = [
                'id' => $existingId,
                'productNumber' => $productNumber,
                'stock' => $stock,
                'name' => $name,
                'description' => $description,
                'active' => true,
                'taxId' => $taxId,
                'price' => [
                    [
                        'currencyId' => $currencyId,
                        'gross' => $priceGross,
                        'net' => $priceNet,
                        'linked' => true,
                    ],
                ],
            ];

            if ($coverId) {
                $payload['coverId'] = $coverId;
                $payload['media'] = [
                    [
                        'id' => Uuid::randomHex(),
                        'mediaId' => $coverId,
                        'position' => 1,
                    ],
                ];
            }

            $this->productRepository->upsert([$payload], $context);

            $this->logger->info(sprintf(
                'Ürün %s (plenty id: %s) %s',
                $productNumber,
                $plentyProduct['id'] ?? '-',
                $existingId ? 'güncellendi' : 'oluşturuldu'
            ));
        } catch (\Exception $e) {
            $this->logger->error('Ürün içe aktarılırken hata: ' . $e->getMessage());
        }
    }

    private function findProductIdByNumber(string $productNumber, Context $context): ?string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('productNumber', $productNumber))->setLimit(1);
        $result = $this->productRepository->search($criteria, $context)->first();
        return $result ? $result->getId() : null;
    }

    private function resolveTaxId(Context $context): ?string
    {
        $criteria = (new Criteria())->setLimit(1);
        $result = $this->taxRepository->search($criteria, $context)->first();
        return $result ? $result->getId() : null;
    }

    private function extractPriceGross(array $variation, array $salesPrices = []): ?float
    {
        // Prefer sales price id 25 if available
        if (!empty($salesPrices)) {
            foreach ($salesPrices as $price) {
                if ((int)($price['salesPriceId'] ?? 0) === 25 && isset($price['price'])) {
                    return (float)$price['price'];
                }
            }
            // fallback first
            if (isset($salesPrices[0]['price']) && $salesPrices[0]['price'] !== null) {
                return (float)$salesPrices[0]['price'];
            }
        }

        $prices = $variation['prices'] ?? [];
        if (isset($prices[0]['price']) && $prices[0]['price'] !== null) {
            return (float)$prices[0]['price'];
        }

        $purchasePrice = $variation['purchasePrice'] ?? null;
        if ($purchasePrice !== null) {
            return (float)$purchasePrice;
        }

        return null;
    }

    private function resolveStock(array $variation): int
    {
        $variationId = $variation['id'] ?? 'unknown';

        // İlk olarak variation datasında stok var mı kontrol et
        if (!empty($variation['stock']) || !empty($variation['stockNet'])) {
            $stockValue = (int)($variation['stock'] ?? $variation['stockNet'] ?? 0);
            $this->logger->info("Stok variation datasından alındı: variationId={$variationId}, stock={$stockValue}");
            return $stockValue;
        }

        // variation.variationStock yapısını kontrol et
        if (!empty($variation['variationStock'])) {
            if (is_array($variation['variationStock'])) {
                $stockValue = (int)($variation['variationStock']['stockNet'] ?? $variation['variationStock']['stockPhysical'] ?? 0);
                $this->logger->info("Stok variationStock datasından alındı: variationId={$variationId}, stock={$stockValue}");
                return $stockValue;
            }
        }

        // Eğer variation datasında stok yoksa, ayrı API çağrısı yap
        if (!empty($variation['id'])) {
            $this->logger->info("Stok için ayrı API çağrısı yapılıyor: variationId={$variationId}");
            $stock = $this->plentyApiService->getVariationStock((string)$variation['id']);
            if ($stock !== null) {
                $this->logger->info("Stok API'den alındı: variationId={$variationId}, stock=" . (int)$stock);
                return (int)$stock;
            } else {
                $this->logger->warning("Stok API çağrısı null döndü: variationId={$variationId}");
            }
        }

        $this->logger->warning("Stok bulunamadı, varsayılan 0 kullanılıyor: variationId={$variationId}", [
            'available_keys' => array_keys($variation)
        ]);
        return 0;
    }

    private function importFirstImageAsMedia(?string $itemId, Context $context): ?string
    {
        if (!$itemId) {
            $this->logger->debug('Görsel import atlandı: itemId yok');
            return null;
        }

        $this->logger->info("Ürün görselleri getiriliyor: itemId={$itemId}");
        $images = $this->plentyApiService->getProductImages($itemId);
        $entries = $images['entries'] ?? $images ?? [];

        if (empty($entries) || !is_array($entries)) {
            $this->logger->warning("Ürün için görsel bulunamadı: itemId={$itemId}", [
                'response_structure' => array_keys($images),
                'entries_count' => is_array($entries) ? count($entries) : 0
            ]);
            return null;
        }

        $this->logger->info("Ürün için " . count($entries) . " görsel bulundu: itemId={$itemId}");
        $first = reset($entries);
        $url = $this->resolveImageUrl($first);

        if (!$url) {
            $this->logger->warning("Görsel URL'i çözümlenemedi: itemId={$itemId}", [
                'image_data_keys' => array_keys($first)
            ]);
            return null;
        }

        $this->logger->info("Görsel indiriliyor: {$url}");
        $mediaId = Uuid::randomHex();
        $this->mediaRepository->create([['id' => $mediaId]], $context);

        try {
            $fileName = basename(parse_url($url, PHP_URL_PATH)) ?: Uuid::randomHex();

            // Error suppression kaldırıldı - gerçek hataları göreceğiz
            $binary = file_get_contents($url);

            if ($binary === false) {
                $error = error_get_last();
                $this->logger->error("Görsel indirilemedi: {$url}", [
                    'error' => $error['message'] ?? 'Bilinmeyen hata',
                    'itemId' => $itemId
                ]);
                return null;
            }

            $pathInfo = pathinfo($fileName);
            $extension = $pathInfo['extension'] ?? 'jpg';

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($binary) ?: 'application/octet-stream';

            $tmpFile = tempnam(sys_get_temp_dir(), 'plenty_img_');
            file_put_contents($tmpFile, $binary);

            $this->logger->info("Görsel Shopware'e kaydediliyor: mediaId={$mediaId}, size=" . filesize($tmpFile) . " bytes");

            $mediaFile = new MediaFile(
                $tmpFile,
                $mimeType,
                $extension,
                filesize($tmpFile)
            );

            // Her ürün için benzersiz bir dosya ismi oluştur
            $uniqueFileName = 'plenty_' . $itemId . '_' . uniqid();

            $this->fileSaver->persistFileToMedia(
                $mediaFile,
                'product',
                $mediaId,
                $context,
                $uniqueFileName,
                false
            );

            $this->logger->info("Görsel başarıyla kaydedildi: mediaId={$mediaId}, url={$url}");

            // Temp dosyayı temizle
            @unlink($tmpFile);

            return $mediaId;
        } catch (\Throwable $e) {
            $this->logger->error('Görsel içe alırken hata: ' . $e->getMessage(), [
                'itemId' => $itemId,
                'url' => $url ?? 'N/A',
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function resolveImageUrl(array $image): ?string
    {
        foreach (['url', 'urlMiddle', 'urlPreview', 'urlSecondPreview'] as $key) {
            if (!empty($image[$key])) {
                return $image[$key];
            }
        }

        if (!empty($image['path'])) {
            $path = $image['path'];
            if (str_starts_with($path, 'http')) {
                return $path;
            }
            return rtrim($this->plentyApiService->getBaseUrl(), '/') . '/' . ltrim($path, '/');
        }

        return null;
    }
}
