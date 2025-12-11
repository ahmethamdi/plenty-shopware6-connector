<?php

namespace PlentyConnector\Core\Content\PackageProgress;

use PlentyConnector\Core\Content\Package\PackageDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PackageProgressDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plenty_package_progress';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PackageProgressEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PackageProgressCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            (new FkField('package_id', 'packageId', PackageDefinition::class))->addFlags(new Required()),
            (new FloatField('current_amount', 'currentAmount')),
            (new IntField('completed_cycles', 'completedCycles')),
            new DateTimeField('last_reset_at', 'lastResetAt'),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id'),
            new ManyToOneAssociationField('package', 'package_id', PackageDefinition::class, 'id'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
