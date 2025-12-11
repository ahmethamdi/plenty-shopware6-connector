<?php

namespace PlentyConnector;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class PlentyConnectorPlugin extends Plugin
{
    public function getLabel(): string
    {
        return 'Plentymarkets Integration';
    }

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->executeMigrations($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $this->executeMigrations($updateContext);
    }

    private function executeMigrations($context): void
    {
        $migrationPath = $this->getPath() . '/src/Migration';
        if (is_dir($migrationPath)) {
            $this->getMigrationCollection($migrationPath, 'PlentyConnector\\Migration')
                ->migrateInPlace();
        }
    }
}
