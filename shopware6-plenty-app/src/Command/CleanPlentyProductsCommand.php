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

        // Plenty ürünlerini bul (product_number "plenty-" ile başlayanlar)
        $criteria = new Criteria();
        $criteria->addFilter(new PrefixFilter('productNumber', 'plenty-'));
        $criteria->setLimit(500);

        $products = $this->productRepository->search($criteria, $context);
        $productCount = $products->count();

        if ($productCount > 0) {
            $output->writeln("<info>$productCount adet Plenty ürünü bulundu, siliniyor...</info>");

            $deleteIds = [];
            foreach ($products->getElements() as $product) {
                $deleteIds[] = ['id' => $product->getId()];
            }

            $this->productRepository->delete($deleteIds, $context);
            $output->writeln("<info>$productCount adet ürün silindi.</info>");
            $this->logger->info("$productCount adet Plenty ürünü silindi");
        } else {
            $output->writeln('<info>Silinecek Plenty ürünü bulunamadı.</info>');
        }

        // Plenty media dosyalarını bul (fileName "plenty_" ile başlayanlar)
        $mediaCriteria = new Criteria();
        $mediaCriteria->addFilter(new PrefixFilter('fileName', 'plenty_'));
        $mediaCriteria->setLimit(1000);

        $mediaFiles = $this->mediaRepository->search($mediaCriteria, $context);
        $mediaCount = $mediaFiles->count();

        if ($mediaCount > 0) {
            $output->writeln("<info>$mediaCount adet Plenty media dosyası bulundu, siliniyor...</info>");

            $deleteMediaIds = [];
            foreach ($mediaFiles->getElements() as $media) {
                $deleteMediaIds[] = ['id' => $media->getId()];
            }

            $this->mediaRepository->delete($deleteMediaIds, $context);
            $output->writeln("<info>$mediaCount adet media dosyası silindi.</info>");
            $this->logger->info("$mediaCount adet Plenty media dosyası silindi");
        } else {
            $output->writeln('<info>Silinecek Plenty media dosyası bulunamadı.</info>');
        }

        // Eski "product" media kayıtlarını da temizle
        $oldMediaCriteria = new Criteria();
        $oldMediaCriteria->addFilter(new ContainsFilter('fileName', 'product'));
        $oldMediaCriteria->setLimit(100);

        $oldMediaFiles = $this->mediaRepository->search($oldMediaCriteria, $context);
        $oldMediaCount = $oldMediaFiles->count();

        if ($oldMediaCount > 0) {
            $output->writeln("<info>$oldMediaCount adet eski 'product' media kaydı bulundu, siliniyor...</info>");

            $deleteOldMediaIds = [];
            foreach ($oldMediaFiles->getElements() as $media) {
                $deleteOldMediaIds[] = ['id' => $media->getId()];
            }

            $this->mediaRepository->delete($deleteOldMediaIds, $context);
            $output->writeln("<info>$oldMediaCount adet eski media kaydı silindi.</info>");
            $this->logger->info("$oldMediaCount adet eski 'product' media kaydı silindi");
        }

        $output->writeln('<info>Temizlik tamamlandı!</info>');

        return Command::SUCCESS;
    }
}
