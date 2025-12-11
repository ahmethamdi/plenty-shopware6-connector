<?php

namespace PlentyConnector\Storefront\Controller;

use PlentyConnector\Service\PackageProgressService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PackageProgressController extends StorefrontController
{
    private EntityRepository $packageRepository;
    private EntityRepository $packageProgressRepository;
    private PackageProgressService $packageProgressService;

    public function __construct(
        EntityRepository $packageRepository,
        EntityRepository $packageProgressRepository,
        PackageProgressService $packageProgressService
    ) {
        $this->packageRepository = $packageRepository;
        $this->packageProgressRepository = $packageProgressRepository;
        $this->packageProgressService = $packageProgressService;
    }

    #[Route(
        path: '/account/packages',
        name: 'frontend.account.packages',
        methods: ['GET']
    )]
    public function index(SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        /** @var CustomerEntity $customer */
        $customer = $context->getCustomer();
        $customerId = $customer->getId();

        // Get all active packages
        $packageCriteria = new Criteria();
        $packageCriteria->addFilter(new EqualsFilter('active', true));
        $packages = $this->packageRepository->search($packageCriteria, $context->getContext());

        // Filter packages by visibility
        $visiblePackages = [];
        foreach ($packages as $package) {
            $visibilityType = $package->getVisibilityType() ?? 'all';

            if ($visibilityType === 'all') {
                $visiblePackages[] = $package;
            } elseif ($visibilityType === 'whitelist') {
                $allowedCustomers = $package->getAllowedCustomerIds() ?? [];
                if (in_array($customerId, $allowedCustomers)) {
                    $visiblePackages[] = $package;
                }
            } elseif ($visibilityType === 'blacklist') {
                $excludedCustomers = $package->getExcludedCustomerIds() ?? [];
                if (!in_array($customerId, $excludedCustomers)) {
                    $visiblePackages[] = $package;
                }
            }
        }

        // Get progress for each visible package
        $packageData = [];
        foreach ($visiblePackages as $package) {
            $progressCriteria = new Criteria();
            $progressCriteria->addFilter(new EqualsFilter('customerId', $customerId));
            $progressCriteria->addFilter(new EqualsFilter('packageId', $package->getId()));

            $progress = $this->packageProgressRepository->search($progressCriteria, $context->getContext())->first();

            $currentAmount = $progress ? $progress->getCurrentAmount() : 0;
            $targetAmount = $package->getTargetAmount();
            $percentage = $targetAmount > 0 ? min(100, ($currentAmount / $targetAmount) * 100) : 0;

            $packageData[] = [
                'package' => $package,
                'progress' => $progress,
                'currentAmount' => $currentAmount,
                'targetAmount' => $targetAmount,
                'percentage' => round($percentage, 2),
                'completedCycles' => $progress ? $progress->getCompletedCycles() : 0,
            ];
        }

        // Get token balance
        $tokenBalance = $this->packageProgressService->getCustomerTokenBalance($customerId, $context->getContext());

        return $this->renderStorefront('@PlentyConnector/storefront/page/account/packages.html.twig', [
            'packages' => $packageData,
            'tokenBalance' => $tokenBalance,
        ]);
    }
}
