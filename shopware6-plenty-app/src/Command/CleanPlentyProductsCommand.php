<?php

namespace PlentyConnector\Command;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanPlentyProductsCommand extends Command
{
    protected static $defaultName = 'plenty:clean:products';

    private EntityRepository $productRepository;
    private EntityRepository $mediaRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $productRepository,
        EntityRepository $mediaRepository,
        LoggerInterface $logger
    ) {
        parent::__construct(self::$defaultName);
        $this->productRepository = $productRepository;
        $this->mediaRepository = $mediaRepository;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Tüm Plentymarkets ürünlerini ve medya dosyalarını siler');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();

        $output->writeln('<info>Plenty ürünleri temizleniyor...</info>');

        // Tüm ürünleri toplu sil (sayfalayarak)
        $productDeleted = 0;
        do {
            $criteria = (new Criteria())->setLimit(500);
            $products = $this->productRepository->search($criteria, $context);
            $batch = $products->count();

            if ($batch > 0) {
                $deleteIds = [];
                foreach ($products->getElements() as $product) {
                    $deleteIds[] = ['id' => $product->getId()];
                }
                $this->productRepository->delete($deleteIds, $context);
                $productDeleted += $batch;
                $output->writeln("<info>$batch ürün silindi (toplam $productDeleted).</info>");
            }
        } while ($batch > 0);

        $this->logger->info("$productDeleted adet ürün silindi");

        // Tüm medya dosyalarını toplu sil (sayfalayarak)
        $mediaDeleted = 0;
        do {
            $mediaCriteria = (new Criteria())->setLimit(500);
            $mediaFiles = $this->mediaRepository->search($mediaCriteria, $context);
            $mediaBatch = $mediaFiles->count();

            if ($mediaBatch > 0) {
                $deleteMediaIds = [];
                foreach ($mediaFiles->getElements() as $media) {
                    $deleteMediaIds[] = ['id' => $media->getId()];
                }
                $this->mediaRepository->delete($deleteMediaIds, $context);
                $mediaDeleted += $mediaBatch;
                $output->writeln("<info>$mediaBatch adet media silindi (toplam $mediaDeleted).</info>");
            }
        } while ($mediaBatch > 0);

        $this->logger->info("$mediaDeleted adet media kaydı silindi");

        $output->writeln('<info>Temizlik tamamlandı!</info>');

        return Command::SUCCESS;
    }
}
