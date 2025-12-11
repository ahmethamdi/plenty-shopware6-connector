<?php

namespace PlentyConnector\Core\Content\TokenTransaction;

use PlentyConnector\Core\Content\Package\PackageDefinition;
use PlentyConnector\Core\Content\TokenProduct\TokenProductDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class TokenTransactionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plenty_token_transaction';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return TokenTransactionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return TokenTransactionCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            (new IntField('amount', 'amount'))->addFlags(new Required()),
            (new StringField('type', 'type'))->addFlags(new Required()), // earned, spent
            new FkField('package_id', 'packageId', PackageDefinition::class),
            new FkField('token_product_id', 'tokenProductId', TokenProductDefinition::class),
            new FkField('order_id', 'orderId', OrderDefinition::class),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id'),
            new ManyToOneAssociationField('package', 'package_id', PackageDefinition::class, 'id'),
            new ManyToOneAssociationField('tokenProduct', 'token_product_id', TokenProductDefinition::class, 'id'),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id'),
        ]);
    }
}
