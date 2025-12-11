<?php

namespace PlentyConnector\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class PackageProgressService
{
    private EntityRepository $packageRepository;
    private EntityRepository $progressRepository;
    private EntityRepository $tokenTransactionRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $packageRepository,
        EntityRepository $progressRepository,
        EntityRepository $tokenTransactionRepository,
        LoggerInterface $logger
    ) {
        $this->packageRepository = $packageRepository;
        $this->progressRepository = $progressRepository;
        $this->tokenTransactionRepository = $tokenTransactionRepository;
        $this->logger = $logger;
    }

    public function processOrder(OrderEntity $order, Context $context): void
    {
        $customerId = $order->getOrderCustomer()->getCustomerId();
        $orderAmount = $order->getAmountTotal();

        $this->logger->info("Processing order for package progress", [
            'orderId' => $order->getId(),
            'customerId' => $customerId,
            'amount' => $orderAmount
        ]);

        // Get all active packages
        $packageCriteria = new Criteria();
        $packageCriteria->addFilter(new EqualsFilter('active', true));
        $packages = $this->packageRepository->search($packageCriteria, $context);

        if ($packages->count() === 0) {
            $this->logger->debug("No active packages found");
            return;
        }

        // Get order line items with categories
        $lineItems = $order->getLineItems();
        if (!$lineItems) {
            $this->logger->debug("No line items in order");
            return;
        }

        // Process each package
        foreach ($packages as $package) {
            // Check visibility
            if (!$this->isPackageVisibleForCustomer($package, $customerId)) {
                $this->logger->debug("Package not visible for customer", [
                    'packageId' => $package->getId(),
                    'customerId' => $customerId
                ]);
                continue;
            }

            // Calculate eligible amount from this order for this package
            $eligibleAmount = $this->calculateEligibleAmount($lineItems, $package);

            if ($eligibleAmount <= 0) {
                $this->logger->debug("No eligible products for package", [
                    'packageId' => $package->getId()
                ]);
                continue;
            }

            // Update or create progress
            $this->updatePackageProgress($customerId, $package, $eligibleAmount, $order->getId(), $context);
        }
    }

    private function isPackageVisibleForCustomer($package, string $customerId): bool
    {
        $visibilityType = $package->getVisibilityType() ?? 'all';

        if ($visibilityType === 'all') {
            return true;
        }

        if ($visibilityType === 'whitelist') {
            $allowedCustomers = $package->getAllowedCustomerIds() ?? [];
            return in_array($customerId, $allowedCustomers);
        }

        if ($visibilityType === 'blacklist') {
            $excludedCustomers = $package->getExcludedCustomerIds() ?? [];
            return !in_array($customerId, $excludedCustomers);
        }

        return true;
    }

    private function calculateEligibleAmount($lineItems, $package): float
    {
        $packageCategoryIds = $package->getCategoryIds() ?? [];

        if (empty($packageCategoryIds)) {
            // No category filter, all products count
            $total = 0;
            foreach ($lineItems as $lineItem) {
                $total += $lineItem->getTotalPrice();
            }
            return $total;
        }

        // Calculate only products from package categories
        $eligibleAmount = 0;
        foreach ($lineItems as $lineItem) {
            $product = $lineItem->getProduct();
            if (!$product) {
                continue;
            }

            // Check if product is in any of the package categories
            $productCategories = $product->getCategoryIds() ?? [];
            $intersection = array_intersect($productCategories, $packageCategoryIds);

            if (!empty($intersection)) {
                $eligibleAmount += $lineItem->getTotalPrice();
            }
        }

        return $eligibleAmount;
    }

    private function updatePackageProgress(
        string $customerId,
        $package,
        float $amount,
        string $orderId,
        Context $context
    ): void {
        // Find existing progress
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addFilter(new EqualsFilter('packageId', $package->getId()));

        $existingProgress = $this->progressRepository->search($criteria, $context)->first();

        $currentAmount = $existingProgress ? $existingProgress->getCurrentAmount() : 0;
        $newAmount = $currentAmount + $amount;

        $targetAmount = $package->getTargetAmount();
        $tokenReward = $package->getTokenReward();

        $this->logger->info("Updating package progress", [
            'customerId' => $customerId,
            'packageId' => $package->getId(),
            'currentAmount' => $currentAmount,
            'addedAmount' => $amount,
            'newAmount' => $newAmount,
            'targetAmount' => $targetAmount
        ]);

        // Check if package completed
        if ($newAmount >= $targetAmount) {
            $completedCycles = $existingProgress ? $existingProgress->getCompletedCycles() : 0;
            $completedCycles++;

            // Award token
            $this->awardToken($customerId, $package->getId(), $tokenReward, $orderId, $context);

            // Reset progress
            $progressData = [
                'id' => $existingProgress ? $existingProgress->getId() : Uuid::randomHex(),
                'customerId' => $customerId,
                'packageId' => $package->getId(),
                'currentAmount' => $newAmount - $targetAmount, // Carry over excess
                'completedCycles' => $completedCycles,
                'lastResetAt' => new \DateTime(),
            ];

            $this->logger->info("Package completed! Token awarded", [
                'customerId' => $customerId,
                'packageId' => $package->getId(),
                'tokenReward' => $tokenReward,
                'completedCycles' => $completedCycles
            ]);
        } else {
            // Just update progress
            $progressData = [
                'id' => $existingProgress ? $existingProgress->getId() : Uuid::randomHex(),
                'customerId' => $customerId,
                'packageId' => $package->getId(),
                'currentAmount' => $newAmount,
                'completedCycles' => $existingProgress ? $existingProgress->getCompletedCycles() : 0,
            ];
        }

        $this->progressRepository->upsert([$progressData], $context);
    }

    private function awardToken(
        string $customerId,
        string $packageId,
        int $tokenAmount,
        string $orderId,
        Context $context
    ): void {
        $transactionData = [
            'id' => Uuid::randomHex(),
            'customerId' => $customerId,
            'amount' => $tokenAmount,
            'type' => 'earned',
            'packageId' => $packageId,
            'orderId' => $orderId,
        ];

        $this->tokenTransactionRepository->create([$transactionData], $context);

        $this->logger->info("Token transaction created", [
            'customerId' => $customerId,
            'amount' => $tokenAmount,
            'packageId' => $packageId
        ]);
    }

    public function getCustomerTokenBalance(string $customerId, Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));

        $transactions = $this->tokenTransactionRepository->search($criteria, $context);

        $balance = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->getType() === 'earned') {
                $balance += $transaction->getAmount();
            } else if ($transaction->getType() === 'spent') {
                $balance -= $transaction->getAmount();
            }
        }

        return $balance;
    }
}
