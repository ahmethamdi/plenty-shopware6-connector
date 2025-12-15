<?php

namespace PlentyConnector\Controller;

use PlentyConnector\Service\ProductSyncService;
use PlentyConnector\Service\PlentyApiService;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminUtilityController extends AbstractController
{
    private CacheClearer $cacheClearer;
    private ProductSyncService $productSyncService;
    private PlentyApiService $plentyApiService;

    public function __construct(
        CacheClearer $cacheClearer,
        ProductSyncService $productSyncService,
        PlentyApiService $plentyApiService
    )
    {
        $this->cacheClearer = $cacheClearer;
        $this->productSyncService = $productSyncService;
        $this->plentyApiService = $plentyApiService;
    }

    public function clearCache(): JsonResponse
    {
        $this->cacheClearer->clear();
        return new JsonResponse(['status' => 'ok', 'message' => 'Cache cleared']);
    }

    public function syncProducts(): JsonResponse
    {
        $context = Context::createDefaultContext();
        $processed = $this->productSyncService->syncProducts($context);

        return new JsonResponse([
            'status' => 'ok',
            'message' => 'Product sync finished',
            'processed' => $processed,
        ]);
    }

    public function testConnection(): JsonResponse
    {
        try {
            $this->plentyApiService->authenticate();
            return new JsonResponse(['status' => 'ok', 'message' => 'Connection successful']);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function syncProductsWithCount(): JsonResponse
    {
        $context = Context::createDefaultContext();
        $processed = $this->productSyncService->syncProducts($context);

        return new JsonResponse([
            'status' => 'ok',
            'message' => 'Sync completed',
            'processed' => $processed,
        ]);
    }
}
