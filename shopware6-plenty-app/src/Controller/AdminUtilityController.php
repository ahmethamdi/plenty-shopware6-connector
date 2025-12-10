<?php

namespace PlentyConnector\Controller;

use PlentyConnector\Service\ProductSyncService;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"administration"})
 */
class AdminUtilityController extends AbstractController
{
    private CacheClearer $cacheClearer;
    private ProductSyncService $productSyncService;

    public function __construct(CacheClearer $cacheClearer, ProductSyncService $productSyncService)
    {
        $this->cacheClearer = $cacheClearer;
        $this->productSyncService = $productSyncService;
    }

    /**
     * @Route("/api/_action/plenty/cache-clear", name="api.action.plenty.cache_clear", methods={"POST"})
     */
    public function clearCache(): JsonResponse
    {
        $this->cacheClearer->clear();
        return new JsonResponse(['status' => 'ok', 'message' => 'Cache cleared']);
    }

    /**
     * @Route("/api/_action/plenty/sync-products", name="api.action.plenty.sync_products", methods={"POST"})
     */
    public function syncProducts(): JsonResponse
    {
        $context = Context::createDefaultContext();
        $this->productSyncService->syncProducts($context);

        return new JsonResponse(['status' => 'ok', 'message' => 'Product sync triggered']);
    }
}
