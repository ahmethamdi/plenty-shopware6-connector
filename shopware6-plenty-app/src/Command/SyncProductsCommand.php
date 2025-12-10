<?php

namespace PlentyConnector\Command;

use PlentyConnector\Service\ProductSyncService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncProductsCommand extends Command
{
    protected static $defaultName = 'plenty:sync:products';

    private ProductSyncService $productSyncService;

    public function __construct(ProductSyncService $productSyncService)
    {
        parent::__construct(self::$defaultName);
        $this->productSyncService = $productSyncService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Plentymarkets ürünlerini Shopware 6 ile senkronize eder');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();
        $this->productSyncService->syncProducts($context);
        $output->writeln('<info>Ürün senkronizasyonu tetiklendi.</info>');

        return Command::SUCCESS;
    }
}
