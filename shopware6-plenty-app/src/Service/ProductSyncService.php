<?php

namespace PlentyConnector\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductSyncService
{
    private PlentyApiService $plentyApiService;
    private EntityRepository $productRepository;
    private EntityRepository $taxRepository;
    private SystemConfigService $config;
    private LoggerInterface $logger;

    public function __construct(
        PlentyApiService $plentyApiService,
        EntityRepository $productRepository,
        EntityRepository $taxRepository,
        SystemConfigService $config,
        LoggerInterface $logger
    ) {
        $this->plentyApiService = $plentyApiService;
        $this->productRepository = $productRepository;
        $this->taxRepository = $taxRepository;
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
            $productNumber = 'plenty-' . ($plentyProduct['id'] ?? uniqid());
            $name = $plentyProduct['texts'][0]['name'] ?? 'Ürün';
            $description = $plentyProduct['texts'][0]['description'] ?? '';

            $priceGross = $this->extractPriceGross($plentyProduct);
            if ($priceGross === null) {
                $this->logger->warning('Fiyat bulunamadı, ürün atlandı: ' . $productNumber);
                return;
            }
            $priceNet = $priceGross > 0 ? $priceGross / 1.19 : 0;
            $stock = (int)($plentyProduct['variations'][0]['stock'] ?? 0);

            $currencyId = 'b7d2554b0ce847cd82f3ac73bd8e50d7'; // Varsayılan EUR
            $taxId = $this->resolveTaxId($context);
            if (!$taxId) {
                $this->logger->warning('Vergi bulunamadı, ürün atlandı: ' . $productNumber);
                return;
            }

            $existingId = $this->findProductIdByNumber($productNumber, $context);

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

    private function extractPriceGross(array $plentyProduct): ?float
    {
        $prices = $plentyProduct['variations'][0]['prices'] ?? [];
        if (isset($prices[0]['price']) && $prices[0]['price'] !== null) {
            return (float)$prices[0]['price'];
        }

        return null;
    }
}
