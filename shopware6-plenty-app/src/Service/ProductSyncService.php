<?php

namespace PlentyConnector\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductSyncService
{
    private PlentyApiService $plentyApiService;
    private EntityRepository $productRepository;
    private EntityRepository $taxRepository;
    private EntityRepository $mediaRepository;
    private MediaService $mediaService;
    private SystemConfigService $config;
    private LoggerInterface $logger;

    public function __construct(
        PlentyApiService $plentyApiService,
        EntityRepository $productRepository,
        EntityRepository $taxRepository,
        EntityRepository $mediaRepository,
        MediaService $mediaService,
        SystemConfigService $config,
        LoggerInterface $logger
    ) {
        $this->plentyApiService = $plentyApiService;
        $this->productRepository = $productRepository;
        $this->taxRepository = $taxRepository;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
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
        if (!empty($salesPrices) && isset($salesPrices[0]['price']) && $salesPrices[0]['price'] !== null) {
            return (float)$salesPrices[0]['price'];
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
        if (!empty($variation['stock']) || !empty($variation['stockNet'])) {
            return (int)($variation['stock'] ?? $variation['stockNet'] ?? 0);
        }

        if (!empty($variation['id'])) {
            $stock = $this->plentyApiService->getVariationStock((string)$variation['id']);
            if ($stock !== null) {
                return (int)$stock;
            }
        }

        return 0;
    }

    private function importFirstImageAsMedia(?string $itemId, Context $context): ?string
    {
        if (!$itemId) {
            return null;
        }

        $images = $this->plentyApiService->getProductImages($itemId);
        $entries = $images['entries'] ?? $images ?? [];
        if (empty($entries) || !is_array($entries)) {
            return null;
        }

        $first = reset($entries);
        $url = $this->resolveImageUrl($first);
        if (!$url) {
            return null;
        }

        $mediaId = Uuid::randomHex();
        $this->mediaRepository->create([['id' => $mediaId]], $context);

        try {
            $fileName = basename(parse_url($url, PHP_URL_PATH)) ?: Uuid::randomHex();
            $this->mediaService->saveFileFromUrl($url, $fileName, null, $context, $mediaId);
            return $mediaId;
        } catch (\Throwable $e) {
            $this->logger->warning('Görsel içe alırken hata: ' . $e->getMessage());
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
