<?php

namespace PlentyConnector\Core\Content\TokenOrder;

use PlentyConnector\Core\Content\TokenProduct\TokenProductDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class TokenOrderDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plenty_token_order';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return TokenOrderEntity::class;
    }

    public function getCollectionClass(): string
    {
        return TokenOrderCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            (new FkField('token_product_id', 'tokenProductId', TokenProductDefinition::class))->addFlags(new Required()),
            (new IntField('token_amount', 'tokenAmount'))->addFlags(new Required()),
            (new StringField('status', 'status'))->addFlags(new Required()), // pending, shipped, delivered
            new JsonField('customer_address', 'customerAddress'),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id'),
            new ManyToOneAssociationField('tokenProduct', 'token_product_id', TokenProductDefinition::class, 'id'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
