<?php

namespace PlentyConnector\Storefront\Controller;

use PlentyConnector\Service\PackageProgressService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class TokenMarketController extends StorefrontController
{
    private EntityRepository $tokenProductRepository;
    private EntityRepository $tokenOrderRepository;
    private EntityRepository $tokenTransactionRepository;
    private PackageProgressService $packageProgressService;

    public function __construct(
        EntityRepository $tokenProductRepository,
        EntityRepository $tokenOrderRepository,
        EntityRepository $tokenTransactionRepository,
        PackageProgressService $packageProgressService
    ) {
        $this->tokenProductRepository = $tokenProductRepository;
        $this->tokenOrderRepository = $tokenOrderRepository;
        $this->tokenTransactionRepository = $tokenTransactionRepository;
        $this->packageProgressService = $packageProgressService;
    }

    #[Route(
        path: '/token-market',
        name: 'frontend.token.market',
        methods: ['GET']
    )]
    public function index(SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        /** @var CustomerEntity $customer */
        $customer = $context->getCustomer();

        // Get all active token products
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $products = $this->tokenProductRepository->search($criteria, $context->getContext());

        // Get customer token balance
        $tokenBalance = $this->packageProgressService->getCustomerTokenBalance(
            $customer->getId(),
            $context->getContext()
        );

        return $this->renderStorefront('@PlentyConnector/storefront/page/token-market/index.html.twig', [
            'products' => $products,
            'tokenBalance' => $tokenBalance,
        ]);
    }

    #[Route(
        path: '/token-market/purchase',
        name: 'frontend.token.market.purchase',
        methods: ['POST'],
        defaults: ['XmlHttpRequest' => true]
    )]
    public function purchase(Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn();

        /** @var CustomerEntity $customer */
        $customer = $context->getCustomer();
        $customerId = $customer->getId();

        $productId = $request->request->get('productId');
        $shippingData = $request->request->all('shipping');

        if (!$productId) {
            return new JsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
        }

        // Get product
        $product = $this->tokenProductRepository->search(new Criteria([$productId]), $context->getContext())->first();

        if (!$product) {
            return new JsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }

        if (!$product->getActive()) {
            return new JsonResponse(['success' => false, 'message' => 'Product not available'], 400);
        }

        if ($product->getStock() !== null && $product->getStock() <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Product out of stock'], 400);
        }

        // Check customer balance
        $tokenBalance = $this->packageProgressService->getCustomerTokenBalance($customerId, $context->getContext());
        $tokenPrice = $product->getTokenPrice();

        if ($tokenBalance < $tokenPrice) {
            return new JsonResponse(['success' => false, 'message' => 'Insufficient token balance'], 400);
        }

        try {
            // Create token order
            $orderData = [
                'id' => Uuid::randomHex(),
                'customerId' => $customerId,
                'tokenProductId' => $productId,
                'tokenAmount' => $tokenPrice,
                'status' => 'pending',
                'customerAddress' => [
                    'address' => $shippingData['address'] ?? '',
                    'city' => $shippingData['city'] ?? '',
                    'postalCode' => $shippingData['postalCode'] ?? '',
                    'phone' => $shippingData['phone'] ?? '',
                ],
            ];

            $this->tokenOrderRepository->create([$orderData], $context->getContext());

            // Deduct tokens
            $transactionData = [
                'id' => Uuid::randomHex(),
                'customerId' => $customerId,
                'amount' => $tokenPrice,
                'type' => 'spent',
                'tokenProductId' => $productId,
            ];

            $this->tokenTransactionRepository->create([$transactionData], $context->getContext());

            // Update stock if applicable
            if ($product->getStock() !== null) {
                $this->tokenProductRepository->update([
                    [
                        'id' => $productId,
                        'stock' => $product->getStock() - 1,
                    ]
                ], $context->getContext());
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Order placed successfully',
                'newBalance' => $tokenBalance - $tokenPrice
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Order failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
