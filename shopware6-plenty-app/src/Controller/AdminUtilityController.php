<?php

namespace PlentyConnector\Controller;

use PlentyConnector\Service\ProductSyncService;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminUtilityController extends AbstractController
{
    private CacheClearer $cacheClearer;
    private ProductSyncService $productSyncService;

    public function __construct(CacheClearer $cacheClearer, ProductSyncService $productSyncService)
    {
        $this->cacheClearer = $cacheClearer;
        $this->productSyncService = $productSyncService;
    }

    public function clearCache(): JsonResponse
    {
        $this->cacheClearer->clear();
        return new JsonResponse(['status' => 'ok', 'message' => 'Cache cleared']);
    }

    public function syncProducts(): JsonResponse
    {
        $context = Context::createDefaultContext();
        $this->productSyncService->syncProducts($context);

        return new JsonResponse(['status' => 'ok', 'message' => 'Product sync triggered']);
    }
}
