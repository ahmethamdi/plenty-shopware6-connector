<?php

namespace PlentyConnector\Core\Content\Package;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use PlentyConnector\Core\Content\PackageProgress\PackageProgressDefinition;

class PackageDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plenty_package';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PackageEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PackageCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new FloatField('target_amount', 'targetAmount'))->addFlags(new Required()),
            (new IntField('token_reward', 'tokenReward'))->addFlags(new Required()),
            (new BoolField('active', 'active')),
            (new JsonField('category_ids', 'categoryIds')),
            (new StringField('visibility_type', 'visibilityType')), // all, whitelist, blacklist
            (new JsonField('allowed_customer_ids', 'allowedCustomerIds')),
            (new JsonField('excluded_customer_ids', 'excludedCustomerIds')),
            new OneToManyAssociationField('progresses', PackageProgressDefinition::class, 'package_id'),
        ]);
    }
}
